<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name'        => 'Standard',
                'description' => 'Kamar nyaman dengan fasilitas dasar',
                'capacity'    => 2,
                'price'       => 350000,
                'photo'       => null,
                'is_active'   => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Deluxe',
                'description' => 'Kamar lebih luas dengan pemandangan kota',
                'capacity'    => 2,
                'price'       => 550000,
                'photo'       => null,
                'is_active'   => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Suite',
                'description' => 'Kamar mewah dengan ruang tamu terpisah',
                'capacity'    => 4,
                'price'       => 950000,
                'photo'       => null,
                'is_active'   => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('room_types')->insertBatch($data);

        // Relasi fasilitas per tipe kamar
        $roomTypes = $this->db->table('room_types')->get()->getResultArray();
        $facilities = $this->db->table('facilities')->get()->getResultArray();

        $rtMap = array_column($roomTypes, 'id', 'name');
        $fMap  = array_column($facilities, 'id', 'name');

        $pivot = [
            // Standard: WiFi, AC, TV
            ['room_type_id' => $rtMap['Standard'], 'facility_id' => $fMap['WiFi Gratis']],
            ['room_type_id' => $rtMap['Standard'], 'facility_id' => $fMap['AC']],
            ['room_type_id' => $rtMap['Standard'], 'facility_id' => $fMap['TV']],
            // Deluxe: + Air Panas, Sarapan
            ['room_type_id' => $rtMap['Deluxe'], 'facility_id' => $fMap['WiFi Gratis']],
            ['room_type_id' => $rtMap['Deluxe'], 'facility_id' => $fMap['AC']],
            ['room_type_id' => $rtMap['Deluxe'], 'facility_id' => $fMap['TV']],
            ['room_type_id' => $rtMap['Deluxe'], 'facility_id' => $fMap['Air Panas']],
            ['room_type_id' => $rtMap['Deluxe'], 'facility_id' => $fMap['Sarapan']],
            // Suite: semua fasilitas
            ['room_type_id' => $rtMap['Suite'], 'facility_id' => $fMap['WiFi Gratis']],
            ['room_type_id' => $rtMap['Suite'], 'facility_id' => $fMap['AC']],
            ['room_type_id' => $rtMap['Suite'], 'facility_id' => $fMap['TV']],
            ['room_type_id' => $rtMap['Suite'], 'facility_id' => $fMap['Air Panas']],
            ['room_type_id' => $rtMap['Suite'], 'facility_id' => $fMap['Sarapan']],
            ['room_type_id' => $rtMap['Suite'], 'facility_id' => $fMap['Kolam Renang']],
            ['room_type_id' => $rtMap['Suite'], 'facility_id' => $fMap['Parkir']],
        ];

        $this->db->table('room_type_facilities')->insertBatch($pivot);
    }
}