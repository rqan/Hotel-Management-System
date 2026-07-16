<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'module', 'action', 'reference_id', 'description',
        'ip_address', 'user_agent', 'created_at',
    ];

    /**
     * Ambil log dengan filter opsional (module, tanggal, user).
     * Semua parameter opsional — kalau null/kosong, filter itu diabaikan.
     */
    public function getFiltered(?string $module = null, ?string $startDate = null, ?string $endDate = null, ?int $userId = null): array
    {
        $builder = $this->select('activity_logs.*, users.name as user_name')
            ->join('users', 'users.id = activity_logs.user_id', 'left')
            ->orderBy('activity_logs.created_at', 'DESC');

        if (!empty($module)) {
            $builder->where('activity_logs.module', $module);
        }

        if (!empty($startDate)) {
            $builder->where('DATE(activity_logs.created_at) >=', $startDate);
        }

        if (!empty($endDate)) {
            $builder->where('DATE(activity_logs.created_at) <=', $endDate);
        }

        if (!empty($userId)) {
            $builder->where('activity_logs.user_id', $userId);
        }

        // Batasi 1000 baris terbaru untuk mencegah query terlalu berat
        // kalau log sudah menumpuk banyak — cukup untuk kebutuhan audit wajar.
        return $builder->limit(1000)->findAll();
    }

    public function getDistinctModules(): array
    {
        $rows = $this->select('module')->distinct()->orderBy('module', 'ASC')->findAll();
        return array_column($rows, 'module');
    }
}