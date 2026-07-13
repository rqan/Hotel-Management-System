<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CheckInModel;
use App\Models\ReservationModel;
use App\Models\RoomModel;
use CodeIgniter\I18n\Time;

class CheckInController extends BaseController
{
    protected CheckInModel $checkInModel;
    protected ReservationModel $reservationModel;
    protected RoomModel $roomModel;

    public function __construct()
    {
        $this->checkInModel     = new CheckInModel();
        $this->reservationModel = new ReservationModel();
        $this->roomModel        = new RoomModel();
    }

    public function index()
    {
        return view('checkin/index', ['title' => 'Check In']);
    }

    public function readyList()
    {
        return $this->response->setJSON(['data' => $this->checkInModel->getReadyForCheckIn()]);
    }

    public function todayList()
    {
        return $this->response->setJSON(['data' => $this->checkInModel->getTodayCheckIns()]);
    }

    public function process($reservationId)
{
    $reservation = $this->reservationModel->find($reservationId);

    if (!$reservation) {
        return $this->response->setStatusCode(404)->setJSON(['message' => 'Reservasi tidak ditemukan.']);
    }

    if ($reservation['status'] !== 'confirmed') {
        return $this->response->setStatusCode(409)->setJSON([
            'message' => "Reservasi ini berstatus '{$reservation['status']}', tidak bisa diproses check-in.",
        ]);
    }

    // Cek manual (lebih eksplisit daripada memanipulasi request global)
    $alreadyCheckedIn = $this->checkInModel->where('reservation_id', $reservationId)->countAllResults() > 0;
    if ($alreadyCheckedIn) {
        return $this->response->setStatusCode(409)->setJSON([
            'message' => 'Reservasi ini sudah pernah di-check-in sebelumnya.',
        ]);
    }

    $notes = $this->request->getPost('notes');

    $db = \Config\Database::connect();
    $db->transStart();

    $this->checkInModel->insert([
        'reservation_id' => $reservationId,
        'checked_in_at'  => Time::now()->toDateTimeString(),
        'checked_in_by'  => session()->get('userId'),
        'notes'          => $notes,
        'created_at'     => Time::now()->toDateTimeString(),
    ]);

    $this->reservationModel->update($reservationId, ['status' => 'checked_in']);
    $this->roomModel->update($reservation['room_id'], ['status' => 'occupied']);

    $db->transComplete();

    if ($db->transStatus() === false) {
        return $this->response->setStatusCode(500)->setJSON([
            'message' => 'Terjadi kesalahan saat memproses check-in. Silakan coba lagi.',
        ]);
    }

    $this->logActivity('check_in', 'check_in', (int) $reservationId, "Check-in untuk booking {$reservation['booking_number']}");

    return $this->response->setJSON(['success' => true, 'message' => 'Check-in berhasil diproses.']);
}

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->checkInModel->db->table('activity_logs')->insert([
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