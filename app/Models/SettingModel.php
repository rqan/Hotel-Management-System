<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table      = 'settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'hotel_name', 'address', 'logo', 'email', 'phone',
        'tax_percentage', 'service_charge_percentage', 'currency', 'timezone',
    ];

    /**
     * Ambil baris settings (selalu row pertama, karena tabel ini single-row).
     * Method read-only untuk sekarang — form edit lengkap menyusul di Tahap 12.
     */
    public function getSettings(): array
    {
        return $this->first() ?? [
            'tax_percentage'            => 0,
            'service_charge_percentage' => 0,
            'currency'                  => 'IDR',
        ];
    }
}