<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class SettingController extends BaseController
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function index()
    {
        return view('settings/index', [
            'title'    => 'Pengaturan Hotel',
            'settings' => $this->settingModel->getSettings(),
        ]);
    }

    public function update()
    {
        if (!$this->validate($this->settingModel->validationRules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $current = $this->settingModel->getSettings();

        $data = [
            'hotel_name'                 => $this->request->getPost('hotel_name'),
            'address'                    => $this->request->getPost('address'),
            'email'                      => $this->request->getPost('email'),
            'phone'                      => $this->request->getPost('phone'),
            'tax_percentage'             => $this->request->getPost('tax_percentage'),
            'service_charge_percentage'  => $this->request->getPost('service_charge_percentage'),
            'currency'                   => $this->request->getPost('currency'),
            'timezone'                   => $this->request->getPost('timezone'),
        ];

        $newLogo = $this->handleLogoUpload();
        if ($newLogo) {
            $data['logo'] = $newLogo;

            // Hapus logo lama agar tidak menumpuk file yatim di storage
            if (!empty($current['logo']) && file_exists(FCPATH . 'uploads/settings/' . $current['logo'])) {
                unlink(FCPATH . 'uploads/settings/' . $current['logo']);
            }
        }

        $this->settingModel->updateSettings($data);

        $this->logActivity('settings', 'update', $current['id'] ?? null, 'Mengubah pengaturan hotel');

        return redirect()->to('/settings')->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Upload logo hotel (opsional). Return nama file baru, atau null jika tidak ada upload.
     * Pola identik dengan handlePhotoUpload() di RoomTypeController (Tahap 5).
     */
    private function handleLogoUpload(): ?string
    {
        $file = $this->request->getFile('logo');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true) || $file->getSizeByUnit('mb') > 2) {
            return null;
        }

        $newName = $file->getRandomName();
        $uploadPath = FCPATH . 'uploads/settings';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $newName);

        return $newName;
    }

    private function logActivity(string $module, string $action, ?int $referenceId, string $description): void
    {
        $this->settingModel->db->table('activity_logs')->insert([
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