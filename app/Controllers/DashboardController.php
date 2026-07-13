<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\CustomerModel;
use Config\Roles;

class DashboardController extends BaseController
{
    protected DashboardModel $dashboardModel;
    protected CustomerModel $customerModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
        $this->customerModel  = new CustomerModel();
    }

    public function index()
    {
        $session = session();

        if (!$session->get('isLoggedIn') || !$session->get('userId')) {
            $session->destroy();
            return redirect()->to('/login')->with('error', 'Sesi tidak valid, silakan login kembali.');
        }

        $role = $session->get('roleName') ?? 'unknown';

        if ($role === Roles::CUSTOMER) {
            return $this->customerDashboard();
        }

        if (in_array($role, Roles::staffRoles(), true)) {
            return $this->staffDashboard($role);
        }

        // Role tidak dikenali (data korup / role dihapus tapi session lama masih ada)
        $session->destroy();
        return redirect()->to('/login')->with('error', 'Role tidak valid, silakan login kembali.');
    }

    // ==========================================================
    // STAFF DASHBOARD (super_admin, admin, manager, receptionist)
    // ==========================================================

    private function staffDashboard(string $role)
    {
        $canSeeRevenue = in_array($role, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::MANAGER], true);

        $data = [
            'role'               => $role,
            'name'               => session()->get('userName'),
            'canSeeRevenue'      => $canSeeRevenue,
            'roomStats'          => $this->dashboardModel->countRoomsByStatus(),
            'totalRooms'         => $this->dashboardModel->countTotalRooms(),
            'reservationsToday'  => $this->dashboardModel->countReservationsToday(),
            'checkInsToday'      => $this->dashboardModel->countCheckInsToday(),
            'checkOutsToday'     => $this->dashboardModel->countCheckOutsToday(),
            'recentBookings'     => $this->dashboardModel->recentBookings(5),
            'reservationChart'   => $this->dashboardModel->reservationChartData(7),
        ];

        if ($canSeeRevenue) {
            $data['revenueToday']     = $this->dashboardModel->revenueToday();
            $data['revenueThisMonth'] = $this->dashboardModel->revenueThisMonth();
            $data['revenueChart']     = $this->dashboardModel->revenueChartData(6);
        }

        return view('dashboard/staff', $data);
    }

    // ==========================================================
    // CUSTOMER DASHBOARD
    // ==========================================================

    private function customerDashboard()
    {
        $userId   = session()->get('userId');
        $customer = $this->customerModel->findByUserId($userId);

        // Guard: user dengan role customer tapi belum punya data customers
        // (mis. baru register, belum pernah booking sama sekali).
        if (!$customer) {
            return view('dashboard/customer', [
                'name'               => session()->get('userName'),
                'hasCustomerProfile' => false,
                'reservations'       => [],
                'activeCount'        => 0,
            ]);
        }

        return view('dashboard/customer', [
            'name'               => session()->get('userName'),
            'hasCustomerProfile' => true,
            'reservations'       => $this->dashboardModel->customerReservations($customer['id']),
            'activeCount'        => $this->dashboardModel->countCustomerActiveReservations($customer['id']),
        ]);
    }
}