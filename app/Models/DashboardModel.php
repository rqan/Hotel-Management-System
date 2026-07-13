<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $table = 'rooms'; // default table, sebagian besar method override tabel sendiri

    // ==========================================================
    // ROOM STATS
    // ==========================================================

    /**
     * Hitung jumlah kamar per status. Hasil: ['available' => 10, 'occupied' => 5, ...]
     */
    public function countRoomsByStatus(): array
    {
        $rows = $this->db->table('rooms')
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $result = [
            'available'   => 0,
            'occupied'    => 0,
            'reserved'    => 0,
            'cleaning'    => 0,
            'maintenance' => 0,
        ];

        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['total'];
        }

        return $result;
    }

    public function countTotalRooms(): int
    {
        return $this->db->table('rooms')->countAllResults();
    }

    // ==========================================================
    // RESERVATION STATS
    // ==========================================================

    public function countReservationsToday(): int
    {
        return $this->db->table('reservations')
            ->where('booking_date', date('Y-m-d'))
            ->countAllResults();
    }

    public function countCheckInsToday(): int
    {
        return $this->db->table('check_ins')
            ->where('DATE(checked_in_at)', date('Y-m-d'))
            ->countAllResults();
    }

    public function countCheckOutsToday(): int
    {
        return $this->db->table('check_outs')
            ->where('DATE(checked_out_at)', date('Y-m-d'))
            ->countAllResults();
    }

    // ==========================================================
    // REVENUE STATS (hanya dipakai untuk role dengan akses finansial)
    // ==========================================================

    public function revenueToday(): float
    {
        $result = $this->db->table('payments')
            ->selectSum('amount')
            ->where('status', 'paid')
            ->where('DATE(paid_at)', date('Y-m-d'))
            ->get()
            ->getRowArray();

        return (float) ($result['amount'] ?? 0);
    }

    public function revenueThisMonth(): float
    {
        $result = $this->db->table('payments')
            ->selectSum('amount')
            ->where('status', 'paid')
            ->where('YEAR(paid_at)', date('Y'))
            ->where('MONTH(paid_at)', date('m'))
            ->get()
            ->getRowArray();

        return (float) ($result['amount'] ?? 0);
    }

    /**
     * Data grafik pendapatan 6 bulan terakhir. Hasil: [['month' => 'Feb 2026', 'total' => 1200000], ...]
     */
    public function revenueChartData(int $months = 6): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date  = strtotime("-{$i} months");
            $year  = date('Y', $date);
            $month = date('m', $date);
            $label = date('M Y', $date);

            $result = $this->db->table('payments')
                ->selectSum('amount')
                ->where('status', 'paid')
                ->where('YEAR(paid_at)', $year)
                ->where('MONTH(paid_at)', $month)
                ->get()
                ->getRowArray();

            $data[] = [
                'month' => $label,
                'total' => (float) ($result['amount'] ?? 0),
            ];
        }

        return $data;
    }

    /**
     * Data grafik jumlah reservasi 7 hari terakhir.
     */
    public function reservationChartData(int $days = 7): array
    {
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date  = date('Y-m-d', strtotime("-{$i} days"));
            $label = date('d M', strtotime($date));

            $total = $this->db->table('reservations')
                ->where('booking_date', $date)
                ->countAllResults();

            $data[] = [
                'date'  => $label,
                'total' => $total,
            ];
        }

        return $data;
    }

    // ==========================================================
    // RECENT BOOKINGS (untuk staff dashboard)
    // ==========================================================

    public function recentBookings(int $limit = 5): array
    {
        return $this->db->table('reservations r')
            ->select('r.booking_number, r.check_in_date, r.check_out_date, r.status, c.name as customer_name, rm.room_number')
            ->join('customers c', 'c.id = r.customer_id')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->orderBy('r.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    // ==========================================================
    // CUSTOMER-SPECIFIC (untuk dashboard customer)
    // ==========================================================

    /**
     * Riwayat reservasi milik satu customer, terbaru dulu.
     */
    public function customerReservations(int $customerId, int $limit = 10): array
    {
        return $this->db->table('reservations r')
            ->select('r.booking_number, r.check_in_date, r.check_out_date, r.nights, r.status, rt.name as room_type_name, rm.room_number')
            ->join('rooms rm', 'rm.id = r.room_id')
            ->join('room_types rt', 'rt.id = rm.room_type_id')
            ->where('r.customer_id', $customerId)
            ->orderBy('r.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function countCustomerActiveReservations(int $customerId): int
    {
        return $this->db->table('reservations')
            ->where('customer_id', $customerId)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->countAllResults();
    }
}