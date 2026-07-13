<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PaymentModel;
use App\Models\InvoiceModel;
use CodeIgniter\I18n\Time;

class PaymentController extends BaseController
{
    protected PaymentModel $paymentModel;
    protected InvoiceModel $invoiceModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->invoiceModel = new InvoiceModel();
    }

    public function index()
    {
        return view('payment/index', ['title' => 'Pembayaran']);
    }

    /**
     * Daftar invoice yang belum lunas (unpaid/partial), untuk ditampilkan
     * sebagai daftar utama yang bisa diklik staff untuk input pembayaran.
     */
    public function unpaidList()
    {
        $data = $this->invoiceModel->db->table('invoices i')
            ->select('i.*, r.booking_number, c.name as customer_name, rm.room_number')
            ->join('reservations r', 'r.id = i.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->whereIn('i.status', ['unpaid', 'partial'])
            ->orderBy('i.created_at', 'DESC')
            ->get()
            ->getResultArray();

        // Tambahkan info sisa tagihan per invoice
        foreach ($data as &$invoice) {
            $totalPaid = $this->paymentModel->getTotalPaidByInvoice($invoice['id']);
            $invoice['total_paid']   = $totalPaid;
            $invoice['remaining']    = $invoice['total_amount'] - $totalPaid;
        }

        return $this->response->setJSON(['data' => $data]);
    }

    /**
     * Detail invoice + riwayat pembayaran (dipanggil saat modal dibuka).
     */
    public function detail($invoiceId)
    {
        $invoice = $this->invoiceModel->find($invoiceId);
        if (!$invoice) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Invoice tidak ditemukan.']);
        }

        $totalPaid = $this->paymentModel->getTotalPaidByInvoice((int) $invoiceId);

        return $this->response->setJSON([
            'invoice'  => $invoice,
            'payments' => $this->paymentModel->getByInvoiceId((int) $invoiceId),
            'total_paid' => $totalPaid,
            'remaining'  => $invoice['total_amount'] - $totalPaid,
        ]);
    }

    public function create()
    {
        $rules = $this->paymentModel->validationRules;

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $invoiceId = (int) $this->request->getPost('invoice_id');
        $amount    = (float) $this->request->getPost('amount');

        $invoice = $this->invoiceModel->find($invoiceId);
        if (!$invoice) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Invoice tidak ditemukan.']);
        }

        if ($invoice['status'] === 'paid') {
            return $this->response->setStatusCode(409)->setJSON(['message' => 'Invoice ini sudah lunas.']);
        }

        $totalPaid = $this->paymentModel->getTotalPaidByInvoice($invoiceId);
        $remaining = $invoice['total_amount'] - $totalPaid;

        // Validasi: tidak boleh bayar lebih dari sisa tagihan.
        // Ini mencegah overpayment yang tidak sengaja (typo nominal, dsb).
        if ($amount > $remaining) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['amount' => 'Jumlah pembayaran melebihi sisa tagihan (Rp ' . number_format($remaining, 0, ',', '.') . ').'],
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->paymentModel->insert([
            'invoice_id'        => $invoiceId,
            'payment_number'    => $this->paymentModel->generatePaymentNumber(),
            'method'            => $this->request->getPost('method'),
            'amount'            => $amount,
            'status'            => 'paid', // Pembayaran yang diinput staff langsung dianggap lunas saat itu juga
            'reference_number'  => $this->request->getPost('reference_number'),
            'paid_at'           => Time::now()->toDateTimeString(),
            'notes'             => $this->request->getPost('notes'),
            'created_by'        => session()->get('userId'),
        ]);

        // Hitung ulang status invoice berdasarkan total pembayaran terbaru
        $newTotalPaid = $totalPaid + $amount;
        $newStatus = $this->determineInvoiceStatus($newTotalPaid, $invoice['total_amount']);

        $this->invoiceModel->update($invoiceId, ['status' => $newStatus]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.',
            ]);
        }

        $this->logActivity('payment', 'create', $invoiceId, "Pembayaran sejumlah Rp " . number_format($amount, 0, ',', '.') . " untuk invoice #{$invoice['invoice_number']}");

        return $this->response->setJSON([
            'success' => true,
            'message' => $newStatus === 'paid' ? 'Pembayaran berhasil, invoice lunas.' : 'Pembayaran berhasil dicatat (parsial).',
        ]);
    }

    /**
     * Tentukan status invoice berdasarkan perbandingan total dibayar vs total tagihan.
     * Dipusatkan di satu method agar konsisten setiap kali dipanggil.
     */
    private function determineInvoiceStatus(float $totalPaid, float $totalAmount): string
    {
        if ($totalPaid <= 0) {
            return 'unpaid';
        }
        if ($totalPaid < $totalAmount) {
            return 'partial';
        }
        return 'paid';
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->paymentModel->db->table('activity_logs')->insert([
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