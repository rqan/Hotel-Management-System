<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'hotel_name'                 => 'Grand Hotel Management',
            'address'                    => 'Jl. Contoh No. 123, Magetan, Jawa Timur',
            'logo'                       => null,
            'email'                      => 'info@grandhotel.test',
            'phone'                      => '0351123456',
            'tax_percentage'             => 10.00,
            'service_charge_percentage'  => 5.00,
            'currency'                   => 'IDR',
            'timezone'                   => 'Asia/Jakarta',
            'created_at'                 => date('Y-m-d H:i:s'),
            'updated_at'                 => date('Y-m-d H:i:s'),
        ];

        $this->db->table('settings')->insert($data);
    }
}