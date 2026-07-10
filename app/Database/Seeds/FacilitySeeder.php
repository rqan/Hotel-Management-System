<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['name' => 'WiFi Gratis',       'icon' => 'bi-wifi',        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'AC',                'icon' => 'bi-snow',        'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'TV',                'icon' => 'bi-tv',          'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Air Panas',         'icon' => 'bi-droplet-half','created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Sarapan',           'icon' => 'bi-cup-hot',     'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Kolam Renang',      'icon' => 'bi-water',       'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Parkir',            'icon' => 'bi-p-square',    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ];

        $this->db->table('facilities')->insertBatch($data);
    }
}