<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomModel extends Model
{
    protected $table          = 'rooms';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'room_type_id', 'room_number', 'floor', 'status', 'notes',
    ];

    protected $validationRules = [
        'room_type_id' => 'required|integer|is_not_unique[room_types.id]',
        'room_number'  => 'required|max_length[20]',
        'floor'        => 'permit_empty|max_length[10]',
        'status'       => 'required|in_list[available,occupied,reserved,cleaning,maintenance]',
    ];

    /**
     * Cek keunikan room_number, dikecualikan dari ID sendiri (untuk edit).
     */
    public function isRoomNumberTaken(string $roomNumber, ?int $excludeId = null): bool
    {
        $builder = $this->where('room_number', $roomNumber);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    public function getWithType(): array
    {
        return $this->select('rooms.*, room_types.name as room_type_name')
            ->join('room_types', 'room_types.id = rooms.room_type_id')
            ->orderBy('rooms.room_number', 'ASC')
            ->findAll();
    }

    public function hasActiveReservations(int $roomId): bool
    {
        return $this->db->table('reservations')
            ->where('room_id', $roomId)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->countAllResults() > 0;
    }
}