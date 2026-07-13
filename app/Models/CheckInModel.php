<?php

namespace App\Models;

use CodeIgniter\Model;

class CheckInModel extends Model
{
    protected $table         = 'check_ins';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // tabel ini hanya punya created_at, diisi manual

    protected $allowedFields = [
        'reservation_id', 'checked_in_at', 'checked_in_by', 'notes', 'created_at',
    ];

    protected $validationRules = [
        'reservation_id' => 'required|integer|is_not_unique[reservations.id]|is_unique[check_ins.reservation_id]',
    ];

    protected $validationMessages = [
        'reservation_id' => [
            'is_unique' => 'Reservasi ini sudah pernah di-check-in sebelumnya.',
        ],
    ];

    /**
     * Daftar reservasi yang siap untuk check-in hari ini
     * (status confirmed, tanggal check_in_date <= hari ini, belum ada check_ins).
     */
    public function getReadyForCheckIn(): array
    {
        return $this->db->table('reservations r')
            ->select('r.id, r.booking_number, r.check_in_date, r.check_out_date, r.guests, c.name as customer_name, c.phone as customer_phone, rm.room_number, rt.name as room_type_name')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->join('room_types rt', 'rt.id = rm.room_type_id')
            ->where('r.status', 'confirmed')
            ->orderBy('r.check_in_date', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Riwayat check-in hari ini (untuk ditampilkan di tab kedua).
     */
    public function getTodayCheckIns(): array
    {
        return $this->select('check_ins.*, r.booking_number, c.name as customer_name, rm.room_number')
            ->join('reservations r', 'r.id = check_ins.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->where('DATE(check_ins.checked_in_at)', date('Y-m-d'))
            ->orderBy('check_ins.checked_in_at', 'DESC')
            ->findAll();
    }
}