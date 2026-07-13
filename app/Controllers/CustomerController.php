<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;

class CustomerController extends BaseController
{
    protected CustomerModel $customerModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
    }

    public function index()
    {
        return view('customer/index', ['title' => 'Customer']);
    }

    public function list()
    {
        $data = $this->customerModel->orderBy('name', 'ASC')->findAll();
        return $this->response->setJSON(['data' => $data]);
    }

    public function create()
    {
        if (!$this->validate($this->customerModel->validationRules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $this->customerModel->insert([
            'name'            => $this->request->getPost('name'),
            'email'           => $this->request->getPost('email'),
            'phone'           => $this->request->getPost('phone'),
            'address'         => $this->request->getPost('address'),
            'identity_type'   => $this->request->getPost('identity_type'),
            'identity_number' => $this->request->getPost('identity_number'),
        ]);

        $this->logActivity('customer', 'create', $this->customerModel->getInsertID(), 'Menambahkan customer baru');

        return $this->response->setJSON(['success' => true, 'message' => 'Customer berhasil ditambahkan.']);
    }

    public function update($id)
    {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Customer tidak ditemukan.']);
        }

        if (!$this->validate($this->customerModel->validationRules)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $this->customerModel->update($id, [
            'name'            => $this->request->getPost('name'),
            'email'           => $this->request->getPost('email'),
            'phone'           => $this->request->getPost('phone'),
            'address'         => $this->request->getPost('address'),
            'identity_type'   => $this->request->getPost('identity_type'),
            'identity_number' => $this->request->getPost('identity_number'),
        ]);

        $this->logActivity('customer', 'update', (int) $id, 'Mengubah data customer');

        return $this->response->setJSON(['success' => true, 'message' => 'Customer berhasil diperbarui.']);
    }

    public function delete($id)
    {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Customer tidak ditemukan.']);
        }

        if ($this->customerModel->hasReservations((int) $id)) {
            return $this->response->setStatusCode(409)->setJSON([
                'message' => 'Customer tidak bisa dihapus karena memiliki riwayat reservasi.',
            ]);
        }

        $this->customerModel->delete($id);
        $this->logActivity('customer', 'delete', (int) $id, 'Menghapus customer');

        return $this->response->setJSON(['success' => true, 'message' => 'Customer berhasil dihapus.']);
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->customerModel->db->table('activity_logs')->insert([
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