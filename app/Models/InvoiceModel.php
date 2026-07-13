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
}