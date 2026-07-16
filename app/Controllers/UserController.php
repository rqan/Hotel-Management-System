<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;

class UserController extends BaseController
{
    protected UserModel $userModel;
    protected RoleModel $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    public function index()
    {
        return view('user/index', [
            'title' => 'Manajemen User',
            'roles' => $this->roleModel->findAll(),
        ]);
    }

    public function list()
    {
        $data = $this->userModel->select('users.id, users.name, users.email, users.phone, users.is_active, users.last_login_at, roles.name as role_name, roles.id as role_id')
            ->join('roles', 'roles.id = users.role_id')
            ->orderBy('users.name', 'ASC')
            ->findAll();

        return $this->response->setJSON(['data' => $data]);
    }

    public function create()
    {
        $rules = [
            'name'     => 'required|min_length[3]|max_length[100]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'phone'    => 'permit_empty|max_length[20]',
            'role_id'  => 'required|integer|is_not_unique[roles.id]',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $userId = $this->userModel->insert([
            'name'      => $this->request->getPost('name'),
            'email'     => $this->request->getPost('email'),
            'phone'     => $this->request->getPost('phone'),
            'role_id'   => $this->request->getPost('role_id'),
            'password'  => $this->request->getPost('password'), // auto-hash via UserEntity::setPassword()
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        // Kalau role yang dipilih adalah 'customer', otomatis buat baris
        // terkait di tabel customers — konsisten dengan alur registrasi
        // mandiri, supaya user ini juga bisa langsung self-booking.
        $role = $this->roleModel->find($this->request->getPost('role_id'));
        if ($role && $role['name'] === 'customer') {
            $customerModel = new \App\Models\CustomerModel();
            $customerModel->insert([
                'user_id' => $userId,
                'name'    => $this->request->getPost('name'),
                'phone'   => $this->request->getPost('phone') ?: '-',
                'email'   => $this->request->getPost('email'),
            ]);
        }

        $this->logActivity('user', 'create', $userId, 'Menambahkan user baru: ' . $this->request->getPost('name'));

        return $this->response->setJSON(['success' => true, 'message' => 'User berhasil ditambahkan.']);
    }

    public function update($id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'User tidak ditemukan.']);
        }

        $rules = [
            'name'    => 'required|min_length[3]|max_length[100]',
            'email'   => "required|valid_email|is_unique[users.email,id,{$id}]",
            'phone'   => 'permit_empty|max_length[20]',
            'role_id' => 'required|integer|is_not_unique[roles.id]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        // Guard: mencegah super_admin terakhir kehilangan role-nya sendiri
        // (mis. tidak sengaja ganti role diri sendiri jadi 'admin' biasa,
        // sampai tidak ada satupun super_admin tersisa di sistem).
        $newRoleId = (int) $this->request->getPost('role_id');
        if ((int) $user['role_id'] !== $newRoleId) {
            $currentRole = $this->roleModel->find($user['role_id']);
            if ($currentRole && $currentRole['name'] === 'super_admin') {
                $superAdminCount = $this->userModel->where('role_id', $user['role_id'])
                    ->where('is_active', 1)
                    ->countAllResults();

                if ($superAdminCount <= 1) {
                    return $this->response->setStatusCode(409)->setJSON([
                        'message' => 'Tidak bisa mengubah role user ini karena merupakan satu-satunya Super Admin aktif.',
                    ]);
                }
            }
        }

        $this->userModel->update($id, [
            'name'    => $this->request->getPost('name'),
            'email'   => $this->request->getPost('email'),
            'phone'   => $this->request->getPost('phone'),
            'role_id' => $newRoleId,
        ]);

        $this->logActivity('user', 'update', (int) $id, 'Mengubah data user: ' . $this->request->getPost('name'));

        return $this->response->setJSON(['success' => true, 'message' => 'User berhasil diperbarui.']);
    }

    /**
     * Toggle status aktif/nonaktif (bukan hapus permanen — lebih aman untuk
     * user yang punya riwayat transaksi/reservasi terkait).
     */
    public function toggleActive($id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'User tidak ditemukan.']);
        }

        // Guard: tidak boleh menonaktifkan diri sendiri
        if ((int) $id === (int) session()->get('userId')) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Anda tidak bisa menonaktifkan akun Anda sendiri.',
            ]);
        }

        $newStatus = $user['is_active'] ? 0 : 1;

        // Guard: mencegah nonaktifkan super_admin terakhir yang aktif
        if ($newStatus === 0) {
            $role = $this->roleModel->find($user['role_id']);
            if ($role && $role['name'] === 'super_admin') {
                $activeSuperAdmins = $this->userModel->where('role_id', $user['role_id'])
                    ->where('is_active', 1)
                    ->countAllResults();

                if ($activeSuperAdmins <= 1) {
                    return $this->response->setStatusCode(409)->setJSON([
                        'message' => 'Tidak bisa menonaktifkan satu-satunya Super Admin aktif di sistem.',
                    ]);
                }
            }
        }

        $this->userModel->update($id, ['is_active' => $newStatus]);

        $this->logActivity('user', $newStatus ? 'activate' : 'deactivate', (int) $id, ($newStatus ? 'Mengaktifkan' : 'Menonaktifkan') . " user: {$user['name']}");

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Status user berhasil diperbarui.',
        ]);
    }

    /**
     * Reset password oleh admin (tanpa perlu tahu password lama) —
     * berbeda dari change-password biasa yang butuh password lama.
     */
    public function resetPassword($id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'User tidak ditemukan.']);
        }

        $rules = ['new_password' => 'required|min_length[8]'];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $user['password'] = $this->request->getPost('new_password'); // auto-hash via entity
        $this->userModel->save($user);

        $this->logActivity('user', 'reset_password', (int) $id, "Reset password untuk user: {$user['name']}");

        return $this->response->setJSON(['success' => true, 'message' => 'Password berhasil direset.']);
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->userModel->db->table('activity_logs')->insert([
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