<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\PaymentModel;
use App\Models\SettingModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceController extends BaseController
{
    protected InvoiceModel $invoiceModel;
    protected InvoiceItemModel $invoiceItemModel;
    protected PaymentModel $paymentModel;
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->invoiceModel     = new InvoiceModel();
        $this->invoiceItemModel = new InvoiceItemModel();
        $this->paymentModel     = new PaymentModel();
        $this->settingModel     = new SettingModel();
    }

    public function index()
    {
        return view('invoice/index', ['title' => 'Invoice']);
    }

    public function list()
    {
        $data = $this->invoiceModel->db->table('invoices i')
            ->select('i.*, r.booking_number, r.check_in_date, r.check_out_date, c.name as customer_name, rm.room_number')
            ->join('reservations r', 'r.id = i.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->orderBy('i.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['data' => $data]);
    }

    /**
     * Tampilkan invoice di browser (HTML), untuk preview sebelum print/download.
     */
    public function view($invoiceId)
    {
        $data = $this->buildInvoiceData((int) $invoiceId);

        if (!$data) {
            return redirect()->to('/invoice')->with('error', 'Invoice tidak ditemukan.');
        }

        return view('invoice/print', $data);
    }

    /**
     * Download invoice sebagai file PDF.
     */
    public function downloadPdf($invoiceId)
    {
        $data = $this->buildInvoiceData((int) $invoiceId);

        if (!$data) {
            return redirect()->to('/invoice')->with('error', 'Invoice tidak ditemukan.');
        }

        $html = view('invoice/print', $data);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Invoice-' . $data['invoice']['invoice_number'] . '.pdf';

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    /**
     * Daftar item pada satu invoice (dipanggil AJAX di modal pembayaran).
     */
    public function items($invoiceId)
    {
        return $this->response->setJSON(['data' => $this->invoiceItemModel->getByInvoiceId((int) $invoiceId)]);
    }

    /**
     * Tambah item biaya tambahan ke invoice (hanya jika belum lunas).
     */
    public function addItem()
    {
        $invoiceId = (int) $this->request->getPost('invoice_id');
        $invoice   = $this->invoiceModel->find($invoiceId);

        if (!$invoice) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Invoice tidak ditemukan.']);
        }

        // Guard: item tidak bisa ditambah kalau invoice sudah lunas —
        // mencegah total berubah setelah uang sudah diterima penuh.
        if ($invoice['status'] === 'paid') {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Invoice sudah lunas, tidak bisa menambah item baru.',
            ]);
        }

        if (!$this->validate($this->invoiceItemModel->validationRules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $quantity  = (int) $this->request->getPost('quantity');
        $unitPrice = (float) $this->request->getPost('unit_price');

        $this->invoiceItemModel->insert([
            'invoice_id'  => $invoiceId,
            'description' => $this->request->getPost('description'),
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'amount'      => $quantity * $unitPrice,
            'created_by'  => session()->get('userId'),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $newTotals = $this->invoiceModel->recalculateTotals($invoiceId);

        $this->logActivity('invoice', 'add_item', $invoiceId, 'Menambahkan item tambahan ke invoice');

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Item berhasil ditambahkan.',
            'totals'  => $newTotals,
        ]);
    }

    /**
     * Hapus item dari invoice (hanya jika belum lunas, dan minimal 1 item tersisa).
     */
    public function deleteItem($itemId)
    {
        $item = $this->invoiceItemModel->find($itemId);

        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Item tidak ditemukan.']);
        }

        $invoice = $this->invoiceModel->find($item['invoice_id']);

        if (!$invoice) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Invoice tidak ditemukan.']);
        }

        if ($invoice['status'] === 'paid') {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Invoice sudah lunas, tidak bisa menghapus item.',
            ]);
        }

        // Guard: minimal harus tersisa 1 item di invoice (tidak boleh kosong total)
        $remainingCount = $this->invoiceItemModel->where('invoice_id', $item['invoice_id'])->countAllResults();
        if ($remainingCount <= 1) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Invoice harus memiliki minimal 1 item.',
            ]);
        }

        $this->invoiceItemModel->delete($itemId);
        $newTotals = $this->invoiceModel->recalculateTotals($item['invoice_id']);

        $this->logActivity('invoice', 'delete_item', $item['invoice_id'], 'Menghapus item dari invoice');

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Item berhasil dihapus.',
            'totals'  => $newTotals,
        ]);
    }

    /**
     * Kumpulkan semua data yang dibutuhkan template invoice (dipakai bersama
     * oleh view() dan downloadPdf() agar tidak duplikasi query).
     */
    private function buildInvoiceData(int $invoiceId): ?array
    {
        $invoice = $this->invoiceModel->db->table('invoices i')
            ->select('i.*, r.booking_number, r.check_in_date, r.check_out_date, r.nights, r.guests,
                      c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
                      rm.room_number, rt.name as room_type_name, rt.price as room_price')
            ->join('reservations r', 'r.id = i.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->join('room_types rt', 'rt.id = rm.room_type_id')
            ->where('i.id', $invoiceId)
            ->get()
            ->getRowArray();

        if (!$invoice) {
            return null;
        }

        return [
            'invoice'  => $invoice,
            'items'    => $this->invoiceItemModel->getByInvoiceId($invoiceId),
            'payments' => $this->paymentModel->getByInvoiceId($invoiceId),
            'settings' => $this->settingModel->getSettings(),
        ];
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->invoiceModel->db->table('activity_logs')->insert([
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