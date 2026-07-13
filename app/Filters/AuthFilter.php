<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;

class AuthFilter implements FilterInterface
{
    /**
     * Cek session login. Jika belum login, cek remember-me cookie.
     * Jika ada arguments (role yang diizinkan), cek juga role user.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Jika belum login, coba autentikasi via remember-me cookie
        if (!$session->get('isLoggedIn')) {
            $this->tryRememberMeLogin($request);
        }

        if (!$session->get('isLoggedIn')) {
            $session->setFlashdata('error', 'Silakan login terlebih dahulu.');
            return redirect()->to('/login');
        }

        // Jika filter diberi argumen role (mis. filter:auth:admin,manager)
        if ($arguments !== null && !empty($arguments)) {
            $userRole = $session->get('roleName');
            if (!in_array($userRole, $arguments, true)) {
                return redirect()->to('/dashboard')
                    ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah request selesai.
    }

    /**
     * Coba login otomatis dari cookie remember_token jika session sudah habis.
     */
    private function tryRememberMeLogin(RequestInterface $request): void
    {
        $cookieToken = $request->getCookie('remember_token');

        if (!$cookieToken) {
            return;
        }

        $hashedToken = hash('sha256', $cookieToken);

        $userModel = new UserModel();
        $user = $userModel->findByRememberToken($hashedToken);

        if ($user) {
            $userWithRole = $userModel->getWithRole($user->id);

            $session = session();
            $session->set([
                'userId'      => $user->id,
                'userName'    => $user->name,
                'userEmail'   => $user->email,
                'roleId'      => $user->role_id,
                'roleName'    => $userWithRole['role_name'],
                'isLoggedIn'  => true,
            ]);
        }
    }
}