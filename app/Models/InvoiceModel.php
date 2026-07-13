<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table         = 'invoices';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'reservation_id', 'invoice_number', 'subtotal', 'tax_amount',
        'service_charge_amount', 'total_amount', 'status',
    ];

    /**
     * Generate nomor invoice unik format: INV-YYYYMMDD-XXXX
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $countToday = $this->like('invoice_number', $prefix, 'after')->countAllResults(false);
        $sequence = str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . $sequence;
    }

    public function getByReservationId(int $reservationId): ?array
    {
        return $this->where('reservation_id', $reservationId)->first();
    }

    /**
     * Hitung ulang subtotal, pajak, service charge, dan total invoice
     * berdasarkan SUM seluruh invoice_items terkini.
     * Dipanggil setiap kali item ditambah/dihapus, agar total selalu sinkron
     * dengan rincian item — mencegah invoice yang "total"-nya tidak cocok
     * dengan penjumlahan barisnya.
     */
    public function recalculateTotals(int $invoiceId): array
    {
        $itemModel    = new InvoiceItemModel();
        $settingModel = new SettingModel();

        $subtotal = $itemModel->sumByInvoiceId($invoiceId);
        $settings = $settingModel->getSettings();

        $taxRate = (float) ($settings['tax_percentage'] ?? 0);
        $scRate  = (float) ($settings['service_charge_percentage'] ?? 0);

        $taxAmount = round($subtotal * ($taxRate / 100));
        $scAmount  = round($subtotal * ($scRate / 100));
        $total     = $subtotal + $taxAmount + $scAmount;

        $this->update($invoiceId, [
            'subtotal'              => $subtotal,
            'tax_amount'            => $taxAmount,
            'service_charge_amount' => $scAmount,
            'total_amount'          => $total,
        ]);

        return [
            'subtotal'              => $subtotal,
            'tax_amount'            => $taxAmount,
            'service_charge_amount' => $scAmount,
            'total_amount'          => $total,
        ];
    }
}