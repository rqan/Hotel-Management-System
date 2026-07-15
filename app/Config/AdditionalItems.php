<?php

namespace Config;

/**
 * Daftar item tambahan invoice yang sering dipakai (extra pillow, dsb).
 * Dikelola sebagai konstanta karena jarang berubah — kalau butuh CRUD
 * penuh nanti, upgrade jadi tabel master seperti RoomType/Facility.
 */
class AdditionalItems
{
    public static array $items = [
        'Extra Pillow'              => 50000,
        'Extra Blanket'             => 30000,
        'Extra Bed'                 => 150000,
        'Late Check Out (per jam)'  => 50000,
        'Laundry Service'           => 75000,
        'Airport Transfer'          => 200000,
        'Breakfast Tambahan'        => 60000,
        'Minibar'                   => 25000,
    ];
}