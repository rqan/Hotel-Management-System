<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'hotel_name', 'address', 'logo', 'email', 'phone',
        'tax_percentage', 'service_charge_percentage', 'currency', 'timezone',
    ];

    protected $validationRules = [
        'hotel_name'                 => 'required|min_length[2]|max_length[150]',
        'address'                    => 'permit_empty',
        'email'                      => 'permit_empty|valid_email|max_length[150]',
        'phone'                      => 'permit_empty|max_length[20]',
        'tax_percentage'             => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'service_charge_percentage'  => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'currency'                   => 'required|max_length[10]',
        'timezone'                   => 'required|max_length[50]',
    ];

    protected $validationMessages = [
        'tax_percentage' => [
            'less_than_equal_to' => 'Pajak tidak boleh lebih dari 100%.',
        ],
        'service_charge_percentage' => [
            'less_than_equal_to' => 'Service charge tidak boleh lebih dari 100%.',
        ],
    ];

    /**
     * Ambil baris settings (selalu row pertama, karena tabel ini single-row).
     */
    public function getSettings(): array
    {
        return $this->first() ?? [
            'id'                         => null,
            'hotel_name'                 => '',
            'tax_percentage'             => 0,
            'service_charge_percentage'  => 0,
            'currency'                   => 'IDR',
            'timezone'                   => 'Asia/Jakarta',
        ];
    }

    /**
     * Update satu-satunya baris settings. Karena tabel ini didesain single-row
     * (diisi sekali via seeder di Tahap 1), operasi di sini SELALU update,
     * tidak pernah insert baris baru — mencegah munculnya baris settings ganda
     * yang bisa membuat perhitungan pajak/service charge ambigu.
     */
    public function updateSettings(array $data): bool
    {
        $existing = $this->first();

        if (!$existing) {
            // Fallback: kalau karena suatu alasan baris settings belum ada sama sekali
            // (mis. SettingSeeder belum pernah dijalankan), buat baru sekali ini saja.
            return (bool) $this->insert($data);
        }

        return $this->update($existing['id'], $data);
    }
}