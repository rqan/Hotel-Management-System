<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RoomModel;
use App\Models\RoomTypeModel;

class RoomController extends BaseController
{
    protected RoomModel $roomModel;
    protected RoomTypeModel $roomTypeModel;

    public function __construct()
    {
        $this->roomModel     = new RoomModel();
        $this->roomTypeModel = new RoomTypeModel();
    }

    public function index()
    {
        return view('room/index', [
            'title'     => 'Kamar',
            'roomTypes' => $this->roomTypeModel->getAllActive(),
        ]);
    }

    public function list()
    {
        return $this->response->setJSON(['data' => $this->roomModel->getWithType()]);
    }

    public function create()
    {
        if (!$this->validate($this->roomModel->validationRules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $roomNumber = $this->request->getPost('room_number');

        if ($this->roomModel->isRoomNumberTaken($roomNumber)) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['room_number' => 'Nomor kamar sudah digunakan.'],
            ]);
        }

        $this->roomModel->insert([
            'room_type_id' => $this->request->getPost('room_type_id'),
            'room_number'  => $roomNumber,
            'floor'        => $this->request->getPost('floor'),
            'status'       => $this->request->getPost('status'),
            'notes'        => $this->request->getPost('notes'),
        ]);

        $this->logActivity('room', 'create', $this->roomModel->getInsertID(), 'Menambahkan kamar baru');

        return $this->response->setJSON(['success' => true, 'message' => 'Kamar berhasil ditambahkan.']);
    }

    public function update($id)
    {
        $room = $this->roomModel->find($id);
        if (!$room) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Kamar tidak ditemukan.']);
        }

        if (!$this->validate($this->roomModel->validationRules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $roomNumber = $this->request->getPost('room_number');

        if ($this->roomModel->isRoomNumberTaken($roomNumber, (int) $id)) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['room_number' => 'Nomor kamar sudah digunakan.'],
            ]);
        }

        // Guard: mencegah status diubah manual jadi 'occupied' lewat form master data.
        // Status 'occupied' seharusnya hanya diset otomatis oleh proses Check In (Tahap 7).
        $status = $this->request->getPost('status');
        if ($status === 'occupied' && $room['status'] !== 'occupied') {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => ['status' => 'Status "Occupied" hanya bisa diset otomatis melalui proses Check In.'],
            ]);
        }

        $this->roomModel->update($id, [
            'room_type_id' => $this->request->getPost('room_type_id'),
            'room_number'  => $roomNumber,
            'floor'        => $this->request->getPost('floor'),
            'status'       => $status,
            'notes'        => $this->request->getPost('notes'),
        ]);

        $this->logActivity('room', 'update', (int) $id, 'Mengubah data kamar');

        return $this->response->setJSON(['success' => true, 'message' => 'Kamar berhasil diperbarui.']);
    }

    public function delete($id)
    {
        $room = $this->roomModel->find($id);
        if (!$room) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Kamar tidak ditemukan.']);
        }

        if ($this->roomModel->hasActiveReservations((int) $id)) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Kamar tidak bisa dihapus karena memiliki reservasi aktif.',
            ]);
        }

        $this->roomModel->delete($id);
        $this->logActivity('room', 'delete', (int) $id, 'Menghapus kamar');

        return $this->response->setJSON(['success' => true, 'message' => 'Kamar berhasil dihapus.']);
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->roomModel->db->table('activity_logs')->insert([
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