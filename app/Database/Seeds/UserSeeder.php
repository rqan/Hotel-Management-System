<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $roles = $this->db->table('roles')->get()->getResultArray();
        $roleMap = array_column($roles, 'id', 'name');

        $data = [
            [
                'role_id'    => $roleMap['super_admin'],
                'name'       => 'Super Administrator',
                'email'      => 'superadmin@hotel.test',
                'phone'      => '081200000001',
                'password'   => password_hash('password123', PASSWORD_DEFAULT),
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'role_id'    => $roleMap['admin'],
                'name'       => 'Admin Hotel',
                'email'      => 'admin@hotel.test',
                'phone'      => '081200000002',
                'password'   => password_hash('password123', PASSWORD_DEFAULT),
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'role_id'    => $roleMap['receptionist'],
                'name'       => 'Resepsionis',
                'email'      => 'receptionist@hotel.test',
                'phone'      => '081200000003',
                'password'   => password_hash('password123', PASSWORD_DEFAULT),
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'role_id'    => $roleMap['manager'],
                'name'       => 'Manager Hotel',
                'email'      => 'manager@hotel.test',
                'phone'      => '081200000004',
                'password'   => password_hash('password123', PASSWORD_DEFAULT),
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}