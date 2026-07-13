<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'reservations'; // default, sebagian besar method override tabel sendiri

    // ==========================================================
    // LAPORAN PENDAPATAN
    // ==========================================================

    public function revenueReport(string $startDate, string $endDate): array
    {
        return $this->db->table('payments p')
            ->select('p.payment_number, p.method, p.amount, p.paid_at, i.invoice_number, r.booking_number, c.name as customer_name')
            ->join('invoices i', 'i.id = p.invoice_id')
            ->join('reservations r', 'r.id = i.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->where('p.status', 'paid')
            ->where('DATE(p.paid_at) >=', $startDate)
            ->where('DATE(p.paid_at) <=', $endDate)
            ->orderBy('p.paid_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function revenueSummary(string $startDate, string $endDate): array
    {
        $result = $this->db->table('payments')
            ->selectSum('amount')
            ->where('status', 'paid')
            ->where('DATE(paid_at) >=', $startDate)
            ->where('DATE(paid_at) <=', $endDate)
            ->get()
            ->getRowArray();

        $byMethod = $this->db->table('payments')
            ->select('method, SUM(amount) as total')
            ->where('status', 'paid')
            ->where('DATE(paid_at) >=', $startDate)
            ->where('DATE(paid_at) <=', $endDate)
            ->groupBy('method')
            ->get()
            ->getResultArray();

        return [
            'total'     => (float) ($result['amount'] ?? 0),
            'by_method' => $byMethod,
        ];
    }

    // ==========================================================
    // LAPORAN RESERVASI
    // ==========================================================

    public function reservationReport(string $startDate, string $endDate): array
    {
        return $this->db->table('reservations r')
            ->select('r.booking_number, r.booking_date, r.check_in_date, r.check_out_date, r.nights, r.status, c.name as customer_name, rm.room_number, rt.name as room_type_name')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->join('room_types rt', 'rt.id = rm.room_type_id')
            ->where('r.booking_date >=', $startDate)
            ->where('r.booking_date <=', $endDate)
            ->orderBy('r.booking_date', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function reservationSummaryByStatus(string $startDate, string $endDate): array
    {
        return $this->db->table('reservations')
            ->select('status, COUNT(*) as total')
            ->where('booking_date >=', $startDate)
            ->where('booking_date <=', $endDate)
            ->groupBy('status')
            ->get()
            ->getResultArray();
    }

    // ==========================================================
    // LAPORAN CHECK IN
    // ==========================================================

    public function checkInReport(string $startDate, string $endDate): array
    {
        return $this->db->table('check_ins ci')
            ->select('ci.checked_in_at, r.booking_number, c.name as customer_name, rm.room_number, u.name as processed_by')
            ->join('reservations r', 'r.id = ci.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->join('users u', 'u.id = ci.checked_in_by')
            ->where('DATE(ci.checked_in_at) >=', $startDate)
            ->where('DATE(ci.checked_in_at) <=', $endDate)
            ->orderBy('ci.checked_in_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    // ==========================================================
    // LAPORAN CHECK OUT
    // ==========================================================

    public function checkOutReport(string $startDate, string $endDate): array
    {
        return $this->db->table('check_outs co')
            ->select('co.checked_out_at, co.total_amount, r.booking_number, c.name as customer_name, rm.room_number, u.name as processed_by')
            ->join('reservations r', 'r.id = co.reservation_id')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->join('users u', 'u.id = co.checked_out_by')
            ->where('DATE(co.checked_out_at) >=', $startDate)
            ->where('DATE(co.checked_out_at) <=', $endDate)
            ->orderBy('co.checked_out_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    // ==========================================================
    // LAPORAN KAMAR (okupansi)
    // ==========================================================

    public function roomOccupancyReport(string $startDate, string $endDate): array
    {
        // Untuk setiap kamar, hitung berapa malam terisi dalam rentang tanggal
        // (berdasarkan reservasi yang statusnya checked_in atau checked_out).
        return $this->db->table('rooms rm')
            ->select("rm.room_number, rt.name as room_type_name,
                      COUNT(DISTINCT r.id) as total_reservations,
                      COALESCE(SUM(r.nights), 0) as total_nights_booked")
            ->join('room_types rt', 'rt.id = rm.room_type_id')
            ->join('reservations r', "r.room_id = rm.id AND r.status IN ('checked_in','checked_out') AND r.check_in_date >= '{$startDate}' AND r.check_in_date <= '{$endDate}'", 'left')
            ->groupBy('rm.id')
            ->orderBy('rm.room_number', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function currentRoomStatusSummary(): array
    {
        return $this->db->table('rooms')
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->getResultArray();
    }

    // ==========================================================
    // LAPORAN CUSTOMER
    // ==========================================================

    public function customerReport(string $startDate, string $endDate): array
    {
        return $this->db->table('customers c')
            ->select("c.name, c.phone, c.email,
                      COUNT(DISTINCT r.id) as total_reservations,
                      COALESCE(SUM(CASE WHEN r.status = 'checked_out' THEN i.total_amount ELSE 0 END), 0) as total_spent")
            ->join('reservations r', "r.customer_id = c.id AND r.booking_date >= '{$startDate}' AND r.booking_date <= '{$endDate}'", 'left')
            ->join('invoices i', 'i.reservation_id = r.id', 'left')
            ->groupBy('c.id')
            ->having('total_reservations >', 0)
            ->orderBy('total_spent', 'DESC')
            ->get()
            ->getResultArray();
    }
}