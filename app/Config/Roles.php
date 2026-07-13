<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Daftar nama role sebagai konstanta.
 * Mencegah "magic string" tersebar di controller/view yang rawan typo.
 */
class Roles extends BaseConfig
{
    public const SUPER_ADMIN  = 'super_admin';
    public const ADMIN        = 'admin';
    public const RECEPTIONIST = 'receptionist';
    public const MANAGER      = 'manager';
    public const CUSTOMER     = 'customer';

    /**
     * Role yang dianggap "staff hotel" (bukan customer).
     * Berguna untuk pengecekan cepat mis. akses ke back-office.
     */
    public static function staffRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::RECEPTIONIST,
            self::MANAGER,
        ];
    }

    /**
     * Role yang boleh mengelola data master (room, room type, dsb).
     */
    public static function managementRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
        ];
    }
}