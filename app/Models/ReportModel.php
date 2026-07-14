<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'reservations';

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
    // LAPORAN KAMAR (okupansi) — REFACTORED, tanpa string interpolation
    // ==========================================================

    /**
     * Sebelumnya method ini menyisipkan $startDate/$endDate langsung ke
     * string kondisi JOIN (berpotensi SQL Injection kalau validasi controller
     * lolos). Sekarang direfactor: reservasi dalam rentang tanggal diambil
     * dulu lewat query builder biasa (fully parameterized), lalu diagregasi
     * di PHP. Query tambahan, tapi 100% bebas dari string interpolation.
     */
    public function roomOccupancyReport(string $startDate, string $endDate): array
    {
        // Ambil semua kamar sebagai baseline (termasuk yang tidak ada reservasi)
        $rooms = $this->db->table('rooms rm')
            ->select('rm.id, rm.room_number, rt.name as room_type_name')
            ->join('room_types rt', 'rt.id = rm.room_type_id')
            ->orderBy('rm.room_number', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil agregat reservasi per kamar dalam rentang tanggal — fully parameterized
        $reservationStats = $this->db->table('reservations')
            ->select('room_id, COUNT(id) as total_reservations, SUM(nights) as total_nights_booked')
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->where('check_in_date >=', $startDate)
            ->where('check_in_date <=', $endDate)
            ->groupBy('room_id')
            ->get()
            ->getResultArray();

        // Index stats by room_id untuk lookup cepat
        $statsByRoom = [];
        foreach ($reservationStats as $stat) {
            $statsByRoom[$stat['room_id']] = $stat;
        }

        // Gabungkan: kamar tanpa reservasi tetap muncul dengan nilai 0
        foreach ($rooms as &$room) {
            $stat = $statsByRoom[$room['id']] ?? null;
            $room['total_reservations']   = $stat['total_reservations'] ?? 0;
            $room['total_nights_booked']  = $stat['total_nights_booked'] ?? 0;
        }

        return $rooms;
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
    // LAPORAN CUSTOMER — REFACTORED, tanpa string interpolation
    // ==========================================================

    /**
     * Sama seperti roomOccupancyReport() — direfactor dari JOIN kondisional
     * dengan string interpolation menjadi 2 query terpisah yang digabung di PHP.
     */
    public function customerReport(string $startDate, string $endDate): array
    {
        // Reservasi dalam rentang tanggal, per customer
        $reservationStats = $this->db->table('reservations')
            ->select('customer_id, id as reservation_id, status')
            ->where('booking_date >=', $startDate)
            ->where('booking_date <=', $endDate)
            ->get()
            ->getResultArray();

        if (empty($reservationStats)) {
            return [];
        }

        $customerIds    = array_unique(array_column($reservationStats, 'customer_id'));
        $reservationIds = array_column($reservationStats, 'reservation_id');

        // Total spending dari invoice yang reservation_id-nya ada di daftar di atas
        $invoiceTotals = $this->db->table('invoices')
            ->select('reservation_id, total_amount')
            ->whereIn('reservation_id', $reservationIds)
            ->get()
            ->getResultArray();

        $invoiceByReservation = array_column($invoiceTotals, 'total_amount', 'reservation_id');

        // Data dasar customer
        $customers = $this->db->table('customers')
            ->select('id, name, phone, email')
            ->whereIn('id', $customerIds)
            ->get()
            ->getResultArray();

        // Agregasi manual per customer
        $result = [];
        foreach ($customers as $customer) {
            $customerReservations = array_filter($reservationStats, fn($r) => $r['customer_id'] == $customer['id']);

            $totalSpent = 0;
            foreach ($customerReservations as $res) {
                if ($res['status'] === 'checked_out' && isset($invoiceByReservation[$res['reservation_id']])) {
                    $totalSpent += (float) $invoiceByReservation[$res['reservation_id']];
                }
            }

            $result[] = [
                'name'               => $customer['name'],
                'phone'              => $customer['phone'],
                'email'              => $customer['email'],
                'total_reservations' => count($customerReservations),
                'total_spent'        => $totalSpent,
            ];
        }

        // Urutkan berdasarkan total_spent tertinggi, konsisten dengan versi sebelumnya
        usort($result, fn($a, $b) => $b['total_spent'] <=> $a['total_spent']);

        return $result;
    }
}