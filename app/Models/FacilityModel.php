<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table         = 'facilities';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = ['name', 'icon'];

    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[100]',
        'icon' => 'permit_empty|max_length[100]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Nama fasilitas wajib diisi.',
            'min_length' => 'Nama fasilitas minimal 2 karakter.',
        ],
    ];

    /**
     * Cek apakah fasilitas masih dipakai oleh room_type (mencegah delete
     * yang menyebabkan pivot data yatim / orphan).
     */
    public function isUsedByRoomType(int $facilityId): bool
    {
        return $this->db->table('room_type_facilities')
            ->where('facility_id', $facilityId)
            ->countAllResults() > 0;
    }
}