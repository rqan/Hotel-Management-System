<?php

namespace App\Controllers;

use App\Models\RoomTypeModel;
use App\Models\FacilityModel;
use App\Models\SettingModel;

class Home extends BaseController
{
    public function index()
    {
        // Kalau sudah login, tidak perlu lihat landing page — langsung ke dashboard.
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $roomTypeModel = new RoomTypeModel();
        $facilityModel = new FacilityModel();
        $settingModel  = new SettingModel();

        return view('home/index', [
            'title'     => 'Beranda',
            'roomTypes' => $roomTypeModel->getAllActive(),
            'facilities' => $facilityModel->orderBy('name', 'ASC')->findAll(),
            'settings'  => $settingModel->getSettings(),
        ]);
    }
}