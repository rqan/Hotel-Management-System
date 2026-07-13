<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CheckOutModel;
use App\Models\ReservationModel;
use App\Models\RoomModel;
use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\SettingModel;
use CodeIgniter\I18n\Time;

class CheckOutController extends BaseController
{
    protected CheckOutModel $checkOutModel;
    protected ReservationModel $reservationModel;
    protected RoomModel $roomModel;
    protected InvoiceModel $invoiceModel;
    protected InvoiceItemModel $invoiceItemModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->checkOutModel    = new CheckOutModel();
        $this->reservationModel = new ReservationModel();
        $this->roomModel        = new RoomModel();
        $this->invoiceModel     = new InvoiceModel();
        $this->invoiceItemModel = new InvoiceItemModel();
        $this->settingModel     = new SettingModel();
    }

    public function index()
    {
        return view('checkout/index', ['title' => 'Check Out']);
    }

    public function readyList()
    {
        return $this->response->setJSON(['data' => $this->checkOutModel->getReadyForCheckOut()]);
    }

    public function todayList()
    {
        return $this->response->setJSON(['data' => $this->checkOutModel->getTodayCheckOuts()]);
    }

    /**
     * Preview biaya sebelum konfirmasi checkout (dipanggil AJAX saat modal dibuka).
     */
    public function preview($reservationId)
    {
        $reservation = $this->reservationModel->getDetailById((int) $reservationId);

        if (!$reservation) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Reservasi tidak ditemukan.']);
        }

        $costs = $this->calculateCost($reservation);

        return $this->response->setJSON(['data' => $costs]);
    }

    public function process($reservationId)
    {
        $reservation = $this->reservationModel->getDetailById((int) $reservationId);

        if (!$reservation) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Reservasi tidak ditemukan.']);
        }

        // Guard: hanya reservasi checked_in yang boleh checkout
        if ($reservation['status'] !== 'checked_in') {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => "Reservasi ini berstatus '{$reservation['status']}', tidak bisa diproses check-out.",
            ]);
        }

        $alreadyCheckedOut = $this->checkOutModel->where('reservation_id', $reservationId)->countAllResults() > 0;
        if ($alreadyCheckedOut) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Reservasi ini sudah pernah di-check-out sebelumnya.',
            ]);
        }

        $costs = $this->calculateCost($reservation);
        $notes = $this->request->getPost('notes');

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Insert check_outs
        $this->checkOutModel->insert([
            'reservation_id' => $reservationId,
            'checked_out_at' => Time::now()->toDateTimeString(),
            'checked_out_by' => session()->get('userId'),
            'total_amount'   => $costs['total_amount'],
            'notes'          => $notes,
            'created_at'     => Time::now()->toDateTimeString(),
        ]);

        // 2. Update status reservasi
        $this->reservationModel->update($reservationId, ['status' => 'checked_out']);

        // 3. Kamar kembali available
        $this->roomModel->update($reservation['room_id'], ['status' => 'available']);

        // 4. Generate invoice (status unpaid — pelunasan di Tahap 9 Payment)
        $invoiceId = $this->invoiceModel->insert([
            'reservation_id'         => $reservationId,
            'invoice_number'         => $this->invoiceModel->generateInvoiceNumber(),
            'subtotal'               => $costs['subtotal'],
            'tax_amount'             => $costs['tax_amount'],
            'service_charge_amount'  => $costs['service_charge_amount'],
            'total_amount'           => $costs['total_amount'],
            'status'                 => 'unpaid',
        ]);

        // 5. Room charge dimasukkan sebagai item pertama invoice (bukan lagi
        // field terpisah), supaya konsisten dengan item tambahan lain
        // (extra pillow, late checkout, dll) yang bisa ditambah dari Tahap 9.
        $this->invoiceItemModel->insert([
            'invoice_id'  => $invoiceId,
            'description' => "{$reservation['room_type_name']} - Kamar {$reservation['room_number']} ({$reservation['nights']} malam)",
            'quantity'    => $reservation['nights'],
            'unit_price'  => $reservation['room_price'],
            'amount'      => $costs['subtotal'],
            'created_by'  => session()->get('userId'),
            'created_at'  => Time::now()->toDateTimeString(),
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Terjadi kesalahan saat memproses check-out. Silakan coba lagi.',
            ]);
        }

        $this->logActivity('check_out', 'check_out', (int) $reservationId, "Check-out untuk booking {$reservation['booking_number']}");

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Check-out berhasil diproses. Invoice telah dibuat.',
        ]);
    }

    /**
     * Hitung subtotal, pajak, service charge, dan total dari data reservasi + settings.
     */
    private function calculateCost(array $reservation): array
    {
        $settings = $this->settingModel->getSettings();

        $subtotal = $reservation['nights'] * $reservation['room_price'];
        $taxRate  = (float) ($settings['tax_percentage'] ?? 0);
        $scRate   = (float) ($settings['service_charge_percentage'] ?? 0);

        $taxAmount = round($subtotal * ($taxRate / 100));
        $scAmount  = round($subtotal * ($scRate / 100));
        $total     = $subtotal + $taxAmount + $scAmount;

        return [
            'nights'                     => $reservation['nights'],
            'room_price'                 => $reservation['room_price'],
            'subtotal'                   => $subtotal,
            'tax_percentage'             => $taxRate,
            'tax_amount'                 => $taxAmount,
            'service_charge_percentage'  => $scRate,
            'service_charge_amount'      => $scAmount,
            'total_amount'               => $total,
        ];
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->checkOutModel->db->table('activity_logs')->insert([
            'user_id'      => session()->get('userId'),
            'module'       => $module,
            'action'       => $action,
            'reference_id' => $referenceId,
            'description'  => $description,
            'ip_address'   => $this->request->getIPAddress(),
            'user_agent'   => (string) $this->request->getUserAgent(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}