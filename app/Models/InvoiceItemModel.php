<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceItemModel extends Model
{
    protected $table         = 'invoice_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'invoice_id', 'description', 'quantity', 'unit_price', 'amount', 'created_by', 'created_at',
    ];

    protected $validationRules = [
        'description' => 'required|min_length[2]|max_length[150]',
        'quantity'    => 'required|integer|greater_than[0]',
        'unit_price'  => 'required|decimal|greater_than[0]',
    ];

    protected $validationMessages = [
        'description' => [
            'required' => 'Deskripsi item wajib diisi.',
        ],
        'quantity' => [
            'greater_than' => 'Qty minimal 1.',
        ],
        'unit_price' => [
            'greater_than' => 'Harga satuan harus lebih dari 0.',
        ],
    ];

    public function getByInvoiceId(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)->orderBy('id', 'ASC')->findAll();
    }

    public function sumByInvoiceId(int $invoiceId): float
    {
        $result = $this->selectSum('amount')->where('invoice_id', $invoiceId)->first();
        return (float) ($result['amount'] ?? 0);
    }
}