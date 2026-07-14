<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        // Reservations: sering di-filter by status, booking_date, check_in_date
        $this->db->query('CREATE INDEX idx_reservations_status ON reservations (status)');
        $this->db->query('CREATE INDEX idx_reservations_booking_date ON reservations (booking_date)');
        $this->db->query('CREATE INDEX idx_reservations_check_in_date ON reservations (check_in_date)');

        // Payments: sering di-filter by status + paid_at (untuk laporan pendapatan)
        $this->db->query('CREATE INDEX idx_payments_status ON payments (status)');
        $this->db->query('CREATE INDEX idx_payments_paid_at ON payments (paid_at)');

        // Rooms: sering di-filter by status (dashboard, availability check)
        $this->db->query('CREATE INDEX idx_rooms_status ON rooms (status)');

        // Invoices: sering di-filter by status (halaman Payment)
        $this->db->query('CREATE INDEX idx_invoices_status ON invoices (status)');

        // Activity logs: sering di-filter by module + created_at (kalau nanti ada halaman log)
        $this->db->query('CREATE INDEX idx_activity_logs_module ON activity_logs (module)');
        $this->db->query('CREATE INDEX idx_activity_logs_created_at ON activity_logs (created_at)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_reservations_status ON reservations');
        $this->db->query('DROP INDEX idx_reservations_booking_date ON reservations');
        $this->db->query('DROP INDEX idx_reservations_check_in_date ON reservations');
        $this->db->query('DROP INDEX idx_payments_status ON payments');
        $this->db->query('DROP INDEX idx_payments_paid_at ON payments');
        $this->db->query('DROP INDEX idx_rooms_status ON rooms');
        $this->db->query('DROP INDEX idx_invoices_status ON invoices');
        $this->db->query('DROP INDEX idx_activity_logs_module ON activity_logs');
        $this->db->query('DROP INDEX idx_activity_logs_created_at ON activity_logs');
    }
}