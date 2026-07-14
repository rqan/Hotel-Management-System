<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ReservationModel;
use App\Models\RoomModel;
use App\Models\RoomTypeModel;
use App\Models\CustomerModel;
use CodeIgniter\I18n\Time;

class ReservationController extends BaseController
{
    protected ReservationModel $reservationModel;
    protected RoomModel $roomModel;
    protected RoomTypeModel $roomTypeModel;
    protected CustomerModel $customerModel;

    public function __construct()
    {
        $this->reservationModel = new ReservationModel();
        $this->roomModel        = new RoomModel();
        $this->roomTypeModel    = new RoomTypeModel();
        $this->customerModel    = new CustomerModel();
    }

    // ==========================================================
    // STAFF: List semua reservasi
    // ==========================================================

    public function index()
    {
        return view('reservation/index', [
            'title'      => 'Reservasi',
            'customers'  => $this->customerModel->orderBy('name', 'ASC')->findAll(),
            'roomTypes'  => $this->roomTypeModel->getAllActive(),
        ]);
    }

    public function list()
    {
        return $this->response->setJSON(['data' => $this->reservationModel->getAllWithDetail()]);
    }

    /**
     * Ambil kamar available untuk tipe & tanggal tertentu (dipanggil AJAX
     * saat staff memilih tipe kamar di form, untuk menampilkan pilihan kamar).
     */
    public function availableRooms()
    {
        $roomTypeId = $this->request->getGet('room_type_id');
        $checkIn    = $this->request->getGet('check_in_date');
        $checkOut   = $this->request->getGet('check_out_date');

        if (!$roomTypeId || !$checkIn || !$checkOut) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Parameter tidak lengkap.']);
        }

        $rooms = $this->roomModel->where('room_type_id', $roomTypeId)
            ->where('status !=', 'maintenance')
            ->findAll();

        $available = array_filter($rooms, function ($room) use ($checkIn, $checkOut) {
            return !$this->reservationModel->isRoomBooked((int) $room['id'], $checkIn, $checkOut);
        });

        return $this->response->setJSON(['data' => array_values($available)]);
    }

    // ==========================================================
    // STAFF: Create (bebas pilih customer & kamar)
    // ==========================================================

    public function create()
    {
        $rules = [
            'customer_id'    => 'required|integer|is_not_unique[customers.id]',
            'room_id'        => 'required|integer|is_not_unique[rooms.id]',
            'check_in_date'  => 'required|valid_date',
            'check_out_date' => 'required|valid_date',
            'guests'         => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $checkIn  = $this->request->getPost('check_in_date');
        $checkOut = $this->request->getPost('check_out_date');
        $roomId   = (int) $this->request->getPost('room_id');

        if (strtotime($checkOut) <= strtotime($checkIn)) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['check_out_date' => 'Tanggal check-out harus setelah check-in.'],
            ]);
        }

        // Bungkus transaksi + row lock, konsisten dengan pola selfBooking() di
        // bawah — mencegah race condition kalau 2 staff input reservasi untuk
        // kamar yang sama secara nyaris bersamaan.
        $db = \Config\Database::connect();
        $db->transStart();

        $db->query('SELECT id FROM rooms WHERE id = ? FOR UPDATE', [$roomId]);

        if ($this->reservationModel->isRoomBooked($roomId, $checkIn, $checkOut)) {
            $db->transComplete();
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Kamar sudah dibooking pada rentang tanggal tersebut.',
            ]);
        }

        $nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
        $status = $this->request->getPost('status') ?: 'pending';

        $reservationId = $this->reservationModel->insert([
            'booking_number' => $this->reservationModel->generateBookingNumber(),
            'customer_id'    => $this->request->getPost('customer_id'),
            'room_id'        => $roomId,
            'booking_date'   => date('Y-m-d'),
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'nights'         => $nights,
            'guests'         => $this->request->getPost('guests'),
            'status'         => $status,
            'notes'          => $this->request->getPost('notes'),
            'created_by'     => session()->get('userId'),
        ]);

        // Set status kamar jadi 'reserved' jika reservasi langsung confirmed
        if ($status === 'confirmed') {
            $this->roomModel->update($roomId, ['status' => 'reserved']);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Terjadi kesalahan saat memproses reservasi. Silakan coba lagi.',
            ]);
        }

        $this->logActivity('reservation', 'create', $reservationId, 'Membuat reservasi baru (staff)');

        return $this->response->setJSON(['success' => true, 'message' => 'Reservasi berhasil dibuat.']);
    }

    // ==========================================================
    // CUSTOMER: Self-booking (hanya pilih tipe kamar, bukan nomor kamar)
    // ==========================================================

    public function selfBookingForm()
    {
        return view('reservation/create', [
            'title'     => 'Buat Reservasi',
            'roomTypes' => $this->roomTypeModel->getAllActive(),
        ]);
    }

    public function selfBooking()
    {
        $userId = session()->get('userId');
        $customer = $this->customerModel->findByUserId($userId);

        if (!$customer) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Data profil customer Anda belum lengkap. Silakan hubungi resepsionis.',
            ]);
        }
        if (!$customer) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Data profil customer Anda belum lengkap. Silakan hubungi resepsionis.',
            ]);
        }

        // Guard anti-spam: batasi maksimal 3 reservasi pending aktif per
        // customer. Mencegah "booking troll" yang spam banyak reservasi
        // tanpa niat serius (menyandera ketersediaan kamar untuk tamu lain).
        $pendingCount = $this->reservationModel->countPendingByCustomer($customer['id']);
        if ($pendingCount >= 3) {
            return $this->response->setStatusCode(429)->setJSON([
                'message' => 'Anda memiliki terlalu banyak reservasi yang belum dikonfirmasi (maks. 3). Selesaikan atau batalkan salah satunya terlebih dahulu.',
            ]);
        }

        $rules = [
            'room_type_id'   => 'required|integer|is_not_unique[room_types.id]',
            'check_in_date'  => 'required|valid_date',
            'check_out_date' => 'required|valid_date',
            'guests'         => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $checkIn  = $this->request->getPost('check_in_date');
        $checkOut = $this->request->getPost('check_out_date');

        if (strtotime($checkIn) < strtotime(date('Y-m-d'))) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['check_in_date' => 'Tanggal check-in tidak boleh sebelum hari ini.'],
            ]);
        }

        if (strtotime($checkOut) <= strtotime($checkIn)) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['check_out_date' => 'Tanggal check-out harus setelah check-in.'],
            ]);
        }

        // Validasi kode referal dilakukan SEBELUM transaksi dimulai (murni
        // validasi string, tidak butuh row locking).
        $referralInput  = trim((string) $this->request->getPost('referral_code'));
        $referralCode   = null;
        $discountAmount = 0;

        if ($referralInput !== '') {
            if (strtoupper($referralInput) === 'DEWA') {
                $referralCode   = 'DEWA';
                $discountAmount = 50000;
            } else {
                return $this->response->setStatusCode(422)->setJSON([
                    'errors' => ['referral_code' => 'Kode referal tidak valid.'],
                ]);
            }
        }

        $roomTypeId = (int) $this->request->getPost('room_type_id');
        $nights     = (strtotime($checkOut) - strtotime($checkIn)) / 86400;

        // Seluruh proses cari-kamar + insert dibungkus transaksi agar row lock
        // dari FOR UPDATE di findAvailableRoomByType() efektif mencegah race
        // condition — lock baru lepas setelah insert selesai (commit/rollback).
        $db = \Config\Database::connect();
        $db->transStart();

        $room = $this->reservationModel->findAvailableRoomByType($roomTypeId, $checkIn, $checkOut);

        if (!$room) {
            $db->transComplete();
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Maaf, tidak ada kamar tersedia untuk tipe dan tanggal yang dipilih.',
            ]);
        }

        $reservationId = $this->reservationModel->insert([
            'booking_number'   => $this->reservationModel->generateBookingNumber(),
            'customer_id'      => $customer['id'],
            'room_id'          => $room['id'],
            'booking_date'     => date('Y-m-d'),
            'check_in_date'    => $checkIn,
            'check_out_date'   => $checkOut,
            'nights'           => $nights,
            'guests'           => $this->request->getPost('guests'),
            'status'           => 'pending',
            'notes'            => $this->request->getPost('notes'),
            'referral_code'    => $referralCode,
            'discount_amount'  => $discountAmount,
            'created_by'       => $userId,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Terjadi kesalahan saat memproses reservasi. Silakan coba lagi.',
            ]);
        }

        $this->logActivity('reservation', 'create', $reservationId, 'Customer melakukan self-booking' . ($referralCode ? " (referal: {$referralCode})" : ''));

        $message = 'Reservasi berhasil dibuat! Menunggu konfirmasi dari staff.';
        if ($discountAmount > 0) {
            $message .= ' Diskon referal Rp ' . number_format($discountAmount, 0, ',', '.') . ' akan diterapkan saat check-out.';
        }

        return $this->response->setJSON(['success' => true, 'message' => $message]);
    }

    // ==========================================================
    // Update status (staff only — confirm/cancel dari list)
    // ==========================================================

    public function updateStatus($id)
    {
        $reservation = $this->reservationModel->find($id);
        if (!$reservation) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Reservasi tidak ditemukan.']);
        }

        $newStatus = $this->request->getPost('status');
        $allowedStatuses = ['pending', 'confirmed', 'cancelled', 'no_show'];

        // Sengaja TIDAK mengizinkan set manual ke 'checked_in'/'checked_out' di sini —
        // dua status itu hanya boleh diset lewat proses Check In/Out (Tahap 7 & 8),
        // karena harus disertai efek samping (ubah status kamar, hitung biaya, dsb).
        if (!in_array($newStatus, $allowedStatuses, true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Status tidak valid untuk diubah manual dari sini.',
            ]);
        }

        $this->reservationModel->update($id, ['status' => $newStatus]);

        // Sinkronkan status kamar mengikuti status reservasi
        if ($newStatus === 'confirmed') {
            $this->roomModel->update($reservation['room_id'], ['status' => 'reserved']);
        } elseif (in_array($newStatus, ['cancelled', 'no_show'], true)) {
            $this->roomModel->update($reservation['room_id'], ['status' => 'available']);
        }

        $this->logActivity('reservation', 'update_status', (int) $id, "Mengubah status reservasi menjadi {$newStatus}");

        return $this->response->setJSON(['success' => true, 'message' => 'Status reservasi berhasil diperbarui.']);
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->reservationModel->db->table('activity_logs')->insert([
            'user_id'      => session()->get('userId'),
            'module'       => $module,
            'action'       => $action,
            'reference_id' => $referenceId,
            'description'  => $description,
            'ip_address'   => $this->request->getIPAddress(),
            'user_agent'   => (string) $this->request->getUserAgent(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}