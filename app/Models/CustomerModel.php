<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table          = 'customers';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'user_id', 'name', 'email', 'phone', 'address',
        'identity_type', 'identity_number',
    ];

    protected $validationRules = [
        'name'             => 'required|min_length[2]|max_length[100]',
        'email'            => 'permit_empty|valid_email|max_length[150]',
        'phone'            => 'required|max_length[20]',
        'address'          => 'permit_empty',
        'identity_type'    => 'permit_empty|in_list[ktp,sim,passport]',
        'identity_number'  => 'permit_empty|max_length[50]',
    ];

    /**
     * Cari data customer berdasarkan user_id yang login.
     * Dipakai untuk dashboard customer (Tahap 4).
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    public function hasReservations(int $customerId): bool
    {
        return $this->db->table('reservations')
            ->where('customer_id', $customerId)
            ->countAllResults() > 0;
    }
}