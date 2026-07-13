<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class UserEntity extends Entity
{
    protected $attributes = [
        'id'               => null,
        'role_id'          => null,
        'name'             => null,
        'email'            => null,
        'phone'            => null,
        'password'         => null,
        'avatar'           => null,
        'is_active'        => null,
        'remember_token'   => null,
        'reset_token'      => null,
        'reset_expires_at' => null,
        'last_login_at'    => null,
        'created_at'       => null,
        'updated_at'       => null,
        'deleted_at'       => null,
    ];

    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'reset_expires_at', 'last_login_at'];
    protected $casts   = [
        'id'      => 'integer',
        'role_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Hash password otomatis saat di-set.
     * Mencegah password plain-text tersimpan jika developer lupa hash manual.
     */
    public function setPassword(string $password): self
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password']);
    }
}