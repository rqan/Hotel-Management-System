<?php

use Config\Roles;

if (!function_exists('current_role')) {
    /**
     * Ambil nama role user yang sedang login dari session.
     */
    function current_role(): ?string
    {
        return session()->get('roleName');
    }
}

if (!function_exists('has_role')) {
    /**
     * Cek apakah user yang login memiliki salah satu role yang diberikan.
     * Contoh: has_role(['admin', 'manager'])
     */
    function has_role(array $roles): bool
    {
        return in_array(current_role(), $roles, true);
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        return current_role() === Roles::SUPER_ADMIN;
    }
}

if (!function_exists('is_staff')) {
    /**
     * Cek apakah user adalah bagian dari staff hotel (bukan customer).
     */
    function is_staff(): bool
    {
        return in_array(current_role(), Roles::staffRoles(), true);
    }
}

if (!function_exists('can_manage_master_data')) {
    function can_manage_master_data(): bool
    {
        return in_array(current_role(), Roles::managementRoles(), true);
    }
}