<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;
use App\Models\UserModel;

class ActivityLogController extends BaseController
{
    protected ActivityLogModel $activityLogModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->activityLogModel = new ActivityLogModel();
        $this->userModel        = new UserModel();
    }

    public function index()
    {
        return view('activity_log/index', [
            'title'   => 'Activity Log',
            'modules' => $this->activityLogModel->getDistinctModules(),
            'users'   => $this->userModel->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function list()
    {
        $module    = $this->request->getGet('module');
        $startDate = $this->request->getGet('start_date');
        $endDate   = $this->request->getGet('end_date');
        $userId    = $this->request->getGet('user_id');

        $data = $this->activityLogModel->getFiltered(
            $module ?: null,
            $startDate ?: null,
            $endDate ?: null,
            $userId ? (int) $userId : null
        );

        return $this->response->setJSON(['data' => $data]);
    }
}