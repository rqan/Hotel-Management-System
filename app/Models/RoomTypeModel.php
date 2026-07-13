<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomTypeModel extends Model
{
    protected $table          = 'room_types';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'name', 'description', 'capacity', 'price', 'photo', 'is_active',
    ];

    protected $validationRules = [
        'name'        => 'required|min_length[2]|max_length[100]',
        'description' => 'permit_empty',
        'capacity'    => 'required|integer|greater_than[0]',
        'price'       => 'required|decimal|greater_than[0]',
    ];

    protected $validationMessages = [
        'capacity' => ['greater_than' => 'Kapasitas minimal 1 orang.'],
        'price'    => ['greater_than' => 'Harga harus lebih dari 0.'],
    ];

    public function getAllActive(): array
    {
        return $this->where('is_active', 1)->findAll();
    }

    /**
     * Cek apakah room type masih punya kamar aktif (mencegah delete
     * yang menyebabkan rooms.room_type_id yatim).
     */
    public function hasRooms(int $roomTypeId): bool
    {
        return $this->db->table('rooms')
            ->where('room_type_id', $roomTypeId)
            ->countAllResults() > 0;
    }

    public function getFacilityIds(int $roomTypeId): array
    {
        $rows = $this->db->table('room_type_facilities')
            ->select('facility_id')
            ->where('room_type_id', $roomTypeId)
            ->get()
            ->getResultArray();

        return array_column($rows, 'facility_id');
    }

    public function syncFacilities(int $roomTypeId, array $facilityIds): void
    {
        $this->db->table('room_type_facilities')->where('room_type_id', $roomTypeId)->delete();

        if (empty($facilityIds)) {
            return;
        }

        $rows = array_map(fn($fid) => [
            'room_type_id' => $roomTypeId,
            'facility_id'  => (int) $fid,
        ], $facilityIds);

        $this->db->table('room_type_facilities')->insertBatch($rows);
    }
}