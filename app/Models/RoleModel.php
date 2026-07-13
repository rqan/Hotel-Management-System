<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = ['name', 'description'];

    protected $validationRules = [
        'name'        => 'required|min_length[3]|max_length[50]|is_unique[roles.name,id,{id}]',
        'description' => 'permit_empty|max_length[255]',
    ];

    /**
     * Ambil semua role dalam format [id => name], berguna untuk dropdown.
     */
    public function getRoleOptions(): array
    {
        $roles = $this->select('id, name')->findAll();
        return array_column($roles, 'name', 'id');
    }
}