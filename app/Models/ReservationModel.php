<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservationModel extends Model
{
    protected $table          = 'reservations';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'booking_number', 'customer_id', 'room_id', 'booking_date',
        'check_in_date', 'check_out_date', 'nights', 'guests',
        'status', 'notes', 'created_by',
    ];

    protected $validationRules = [
        'customer_id'    => 'required|integer|is_not_unique[customers.id]',
        'room_id'        => 'required|integer|is_not_unique[rooms.id]',
        'check_in_date'  => 'required|valid_date',
        'check_out_date' => 'required|valid_date',
        'guests'         => 'required|integer|greater_than[0]',
    ];

    protected $validationMessages = [
        'guests' => ['greater_than' => 'Jumlah tamu minimal 1 orang.'],
    ];

    /**
     * Generate booking number unik format: BKG-YYYYMMDD-XXXX
     */
    public function generateBookingNumber(): string
    {
        $prefix = 'BKG-' . date('Ymd') . '-';
        $countToday = $this->like('booking_number', $prefix, 'after')->countAllResults(false);
        $sequence = str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);
        return $prefix . $sequence;
    }

    /**
     * Cek apakah kamar tertentu sudah punya reservasi yang overlap
     * dengan rentang tanggal baru (mencegah double-booking).
     * Status yang dianggap "menghalangi" adalah pending, confirmed, checked_in.
     */
    public function isRoomBooked(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool
    {
        $builder = $this->where('room_id', $roomId)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->groupStart()
                ->where('check_in_date <', $checkOut)
                ->where('check_out_date >', $checkIn)
            ->groupEnd();

        if ($excludeReservationId) {
            $builder->where('id !=', $excludeReservationId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Cari 1 kamar available dari tipe tertentu untuk rentang tanggal yang diminta.
     * Dipakai untuk alur self-booking customer (customer hanya pilih tipe, bukan nomor kamar).
     */
    public function findAvailableRoomByType(int $roomTypeId, string $checkIn, string $checkOut): ?array
    {
        $rooms = $this->db->table('rooms')
            ->where('room_type_id', $roomTypeId)
            ->where('status !=', 'maintenance')
            ->get()
            ->getResultArray();

        foreach ($rooms as $room) {
            if (!$this->isRoomBooked((int) $room['id'], $checkIn, $checkOut)) {
                return $room;
            }
        }

        return null;
    }

    public function getAllWithDetail(): array
    {
        return $this->select('reservations.*, customers.name as customer_name, rooms.room_number, room_types.name as room_type_name')
            ->join('customers', 'customers.id = reservations.customer_id')
            ->join('rooms', 'rooms.id = reservations.room_id')
            ->join('room_types', 'room_types.id = rooms.room_type_id')
            ->orderBy('reservations.created_at', 'DESC')
            ->findAll();
    }

    public function getDetailById(int $id): ?array
    {
        return $this->select('reservations.*, customers.name as customer_name, customers.phone as customer_phone, rooms.room_number, room_types.name as room_type_name, room_types.price as room_price')
            ->join('customers', 'customers.id = reservations.customer_id')
            ->join('rooms', 'rooms.id = reservations.room_id')
            ->join('room_types', 'room_types.id = rooms.room_type_id')
            ->where('reservations.id', $id)
            ->first();
    }
}