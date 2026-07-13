<?php

namespace App\Models;

use CodeIgniter\Model;

class CheckOutModel extends Model
{
    protected $table         = 'check_outs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'reservation_id', 'checked_out_at', 'checked_out_by',
        'total_amount', 'notes', 'created_at',
    ];

    /**
     * Daftar reservasi yang sedang checked_in (siap untuk checkout).
     */
    public function getReadyForCheckOut(): array
    {
        return $this->db->table('reservations r')
            ->select('r.id, r.booking_number, r.check_in_date, r.check_out_date, r.nights, c.name as customer_name, c.phone as customer_phone, rm.id as room_id, rm.room_number, rt.name as room_type_name, rt.price as room_price')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->join('room_types rt', 'rt.id = rm.room_type_id')
            ->where('r.status', 'checked_in')
            ->orderBy('r.check_in_date', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTodayCheckOuts(): array
    {
        return $this->select('check_outs.*, r.booking_number, c.name as customer_name, rm.room_number')
            ->join('reservations r', 'r.id = check_outs.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->where('DATE(check_outs.checked_out_at)', date('Y-m-d'))
            ->orderBy('check_outs.checked_out_at', 'DESC')
            ->findAll();
    }
}