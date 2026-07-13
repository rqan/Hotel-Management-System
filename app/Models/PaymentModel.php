<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'invoice_id', 'payment_number', 'method', 'amount', 'status',
        'reference_number', 'paid_at', 'notes', 'created_by',
    ];

    protected $validationRules = [
        'invoice_id' => 'required|integer|is_not_unique[invoices.id]',
        'method'     => 'required|in_list[cash,transfer,qris,credit_card]',
        'amount'     => 'required|decimal|greater_than[0]',
    ];

    protected $validationMessages = [
        'amount' => ['greater_than' => 'Jumlah pembayaran harus lebih dari 0.'],
    ];

    /**
     * Generate nomor pembayaran unik format: PAY-YYYYMMDD-XXXX
     */
    public function generatePaymentNumber(): string
    {
        $prefix = 'PAY-' . date('Ymd') . '-';
        $countToday = $this->like('payment_number', $prefix, 'after')->countAllResults(false);
        $sequence = str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . $sequence;
    }

    /**
     * Total yang sudah dibayar (status paid saja) untuk satu invoice.
     */
    public function getTotalPaidByInvoice(int $invoiceId): float
    {
        $result = $this->selectSum('amount')
            ->where('invoice_id', $invoiceId)
            ->where('status', 'paid')
            ->first();

        return (float) ($result['amount'] ?? 0);
    }

    public function getByInvoiceId(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}