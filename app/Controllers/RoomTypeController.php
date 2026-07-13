<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RoomTypeModel;
use App\Models\FacilityModel;

class RoomTypeController extends BaseController
{
    protected RoomTypeModel $roomTypeModel;
    protected FacilityModel $facilityModel;

    public function __construct()
    {
        $this->roomTypeModel = new RoomTypeModel();
        $this->facilityModel = new FacilityModel();
    }

    public function index()
    {
        return view('roomtype/index', [
            'title'      => 'Tipe Kamar',
            'facilities' => $this->facilityModel->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function list()
    {
        $data = $this->roomTypeModel->orderBy('name', 'ASC')->findAll();
        return $this->response->setJSON(['data' => $data]);
    }

    public function getFacilities($id)
    {
        return $this->response->setJSON([
            'facility_ids' => $this->roomTypeModel->getFacilityIds((int) $id),
        ]);
    }

    public function create()
    {
        if (!$this->validate($this->roomTypeModel->validationRules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $photoPath = $this->handlePhotoUpload();

        $this->roomTypeModel->insert([
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'capacity'    => $this->request->getPost('capacity'),
            'price'       => $this->request->getPost('price'),
            'photo'       => $photoPath,
            'is_active'   => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        $roomTypeId = $this->roomTypeModel->getInsertID();
        $this->roomTypeModel->syncFacilities($roomTypeId, $this->request->getPost('facility_ids') ?? []);

        $this->logActivity('room_type', 'create', $roomTypeId, 'Menambahkan tipe kamar baru');

        return $this->response->setJSON(['success' => true, 'message' => 'Tipe kamar berhasil ditambahkan.']);
    }

    public function update($id)
    {
        $roomType = $this->roomTypeModel->find($id);
        if (!$roomType) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Tipe kamar tidak ditemukan.']);
        }

        if (!$this->validate($this->roomTypeModel->validationRules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $updateData = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'capacity'    => $this->request->getPost('capacity'),
            'price'       => $this->request->getPost('price'),
            'is_active'   => $this->request->getPost('is_active') ? 1 : 0,
        ];

        $newPhoto = $this->handlePhotoUpload();
        if ($newPhoto) {
            $updateData['photo'] = $newPhoto;
            // Hapus foto lama agar tidak menumpuk file yatim di storage
            if (!empty($roomType['photo']) && file_exists(FCPATH . 'uploads/roomtypes/' . $roomType['photo'])) {
                unlink(FCPATH . 'uploads/roomtypes/' . $roomType['photo']);
            }
        }

        $this->roomTypeModel->update($id, $updateData);
        $this->roomTypeModel->syncFacilities((int) $id, $this->request->getPost('facility_ids') ?? []);

        $this->logActivity('room_type', 'update', (int) $id, 'Mengubah tipe kamar');

        return $this->response->setJSON(['success' => true, 'message' => 'Tipe kamar berhasil diperbarui.']);
    }

    public function delete($id)
    {
        $roomType = $this->roomTypeModel->find($id);
        if (!$roomType) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Tipe kamar tidak ditemukan.']);
        }

        if ($this->roomTypeModel->hasRooms((int) $id)) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Tipe kamar tidak bisa dihapus karena masih memiliki data Kamar terkait.',
            ]);
        }

        $this->roomTypeModel->delete($id);
        $this->logActivity('room_type', 'delete', (int) $id, 'Menghapus tipe kamar');

        return $this->response->setJSON(['success' => true, 'message' => 'Tipe kamar berhasil dihapus.']);
    }

    /**
     * Upload foto tipe kamar (opsional). Return nama file baru, atau null jika tidak ada upload.
     */
    private function handlePhotoUpload(): ?string
    {
        $file = $this->request->getFile('photo');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        // Validasi manual: tipe & ukuran file (max 2MB, hanya gambar)
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true) || $file->getSizeByUnit('mb') > 2) {
            return null;
        }

        $newName = $file->getRandomName();
        $uploadPath = FCPATH . 'uploads/roomtypes';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $newName);

        return $newName;
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->roomTypeModel->db->table('activity_logs')->insert([
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