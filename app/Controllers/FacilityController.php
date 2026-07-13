<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FacilityModel;

class FacilityController extends BaseController
{
    protected FacilityModel $facilityModel;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
    }

    public function index()
    {
        return view('facility/index', ['title' => 'Fasilitas']);
    }

    /**
     * Endpoint JSON untuk DataTables (server-side sederhana: ambil semua,
     * karena data fasilitas biasanya sedikit — tidak perlu server-side paging).
     */
    public function list()
    {
        $data = $this->facilityModel->orderBy('name', 'ASC')->findAll();
        return $this->response->setJSON(['data' => $data]);
    }

    public function create()
    {
        $rules = $this->facilityModel->validationRules;

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $this->facilityModel->insert([
            'name' => $this->request->getPost('name'),
            'icon' => $this->request->getPost('icon'),
        ]);

        $this->logActivity('facility', 'create', $this->facilityModel->getInsertID(), 'Menambahkan fasilitas baru');

        return $this->response->setJSON(['success' => true, 'message' => 'Fasilitas berhasil ditambahkan.']);
    }

    public function update($id)
    {
        $facility = $this->facilityModel->find($id);
        if (!$facility) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Fasilitas tidak ditemukan.']);
        }

        $rules = $this->facilityModel->validationRules;

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $this->facilityModel->update($id, [
            'name' => $this->request->getPost('name'),
            'icon' => $this->request->getPost('icon'),
        ]);

        $this->logActivity('facility', 'update', $id, 'Mengubah fasilitas');

        return $this->response->setJSON(['success' => true, 'message' => 'Fasilitas berhasil diperbarui.']);
    }

    public function delete($id)
    {
        $facility = $this->facilityModel->find($id);
        if (!$facility) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Fasilitas tidak ditemukan.']);
        }

        if ($this->facilityModel->isUsedByRoomType((int) $id)) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Fasilitas tidak bisa dihapus karena masih digunakan oleh salah satu Tipe Kamar.',
            ]);
        }

        $this->facilityModel->delete($id);

        $this->logActivity('facility', 'delete', $id, 'Menghapus fasilitas');

        return $this->response->setJSON(['success' => true, 'message' => 'Fasilitas berhasil dihapus.']);
    }

    /**
     * Helper kecil untuk activity log — dipakai di semua controller Tahap 5.
     * Menulis langsung ke tabel (belum ada ActivityLogModel khusus,
     * cukup query builder karena strukturnya sederhana & dipakai lintas modul).
     */
    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->facilityModel->db->table('activity_logs')->insert([
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