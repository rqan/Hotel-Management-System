<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\UserEntity;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = UserEntity::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'is_active',
        'remember_token',
        'reset_token',
        'reset_expires_at',
        'last_login_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'role_id'  => 'required|integer|is_not_unique[roles.id]',
        'name'     => 'required|min_length[3]|max_length[100]',
        'email'    => 'required|valid_email|max_length[150]',
        'phone'    => 'permit_empty|max_length[20]',
        'password' => 'permit_empty|min_length[8]',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Email sudah terdaftar, gunakan email lain.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Cari user aktif berdasarkan email (dipakai saat login).
     */
    public function findActiveByEmail(string $email): ?UserEntity
    {
        return $this->where('email', $email)
                    ->where('is_active', 1)
                    ->first();
    }

    /**
     * Cari user berdasarkan remember token yang sudah di-hash.
     */
    public function findByRememberToken(string $hashedToken): ?UserEntity
    {
        return $this->where('remember_token', $hashedToken)
                    ->where('is_active', 1)
                    ->first();
    }

    /**
     * Cari user berdasarkan reset token yang valid (belum expired).
     */
    public function findByValidResetToken(string $hashedToken): ?UserEntity
    {
        return $this->where('reset_token', $hashedToken)
                    ->where('reset_expires_at >=', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * Ambil user beserta nama role-nya (join sederhana).
     * Dipaksa return array (bukan UserEntity) karena hasil join punya
     * kolom tambahan (role_name) yang tidak ada di UserEntity.
     */
    public function getWithRole(int $userId): ?array
    {
        return $this->asArray()
                    ->select('users.*, roles.name as role_name')
                    ->join('roles', 'roles.id = users.role_id')
                    ->where('users.id', $userId)
                    ->first();
    }
}