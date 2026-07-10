<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run()
    {
        $roomTypes = $this->db->table('room_types')->get()->getResultArray();
        $rtMap = array_column($roomTypes, 'id', 'name');

        $data = [];

        // Lantai 1: Standard (101-105)
        for ($i = 1; $i <= 5; $i++) {
            $data[] = [
                'room_type_id' => $rtMap['Standard'],
                'room_number'  => '10' . $i,
                'floor'        => '1',
                'status'       => 'available',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }

        // Lantai 2: Deluxe (201-204)
        for ($i = 1; $i <= 4; $i++) {
            $data[] = [
                'room_type_id' => $rtMap['Deluxe'],
                'room_number'  => '20' . $i,
                'floor'        => '2',
                'status'       => 'available',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }

        // Lantai 3: Suite (301-302)
        for ($i = 1; $i <= 2; $i++) {
            $data[] = [
                'room_type_id' => $rtMap['Suite'],
                'room_number'  => '30' . $i,
                'floor'        => '3',
                'status'       => 'available',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->table('rooms')->insertBatch($data);
    }
}