<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // ==========================================================
    // LOGIN
    // ==========================================================

    public function loginForm()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember');

        $user = $this->userModel->findActiveByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Email atau password salah.');
        }

        $userWithRole = $this->userModel->getWithRole($user->id);

        // Set session
        session()->set([
            'userId'     => $user->id,
            'userName'   => $user->name,
            'userEmail'  => $user->email,
            'roleId'     => $user->role_id,
            'roleName'   => $userWithRole['role_name'],
            'isLoggedIn' => true,
        ]);

        // Update last_login_at
        $this->userModel->update($user->id, [
            'last_login_at' => Time::now()->toDateTimeString(),
        ]);

        // Remember Me
        if ($remember) {
            $this->setRememberMeCookie($user->id);
        }

        return redirect()->to('/dashboard')->with('success', 'Selamat datang, ' . $user->name . '!');
    }

    /**
     * Generate token acak, simpan versi hash di DB, kirim versi mentah ke cookie.
     * Prinsip: DB tidak pernah menyimpan token yang bisa langsung dipakai jika bocor.
     */
    private function setRememberMeCookie(int $userId): void
    {
        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $this->userModel->update($userId, ['remember_token' => $hashedToken]);

        $response = service('response');
        $response->setCookie([
            'name'     => 'remember_token',
            'value'    => $rawToken,
            'expire'   => 60 * 60 * 24 * 30, // 30 hari
            'httponly' => true,
            'secure'   => (bool) request()->isSecure(),
            'samesite' => 'Lax',
        ]);
    }

    // ==========================================================
    // LOGOUT
    // ==========================================================

    public function logout()
    {
        $userId = session()->get('userId');

        if ($userId) {
            // Hapus remember token di DB agar cookie lama tidak bisa dipakai lagi
            $this->userModel->update($userId, ['remember_token' => null]);
        }

        $response = service('response');
        $response->deleteCookie('remember_token');

        session()->destroy();

        return redirect()->to('/login')->with('success', 'Anda berhasil logout.');
    }

    // ==========================================================
    // FORGOT PASSWORD
    // ==========================================================

    public function forgotPasswordForm()
    {
        return view('auth/forgot_password');
    }

    public function forgotPassword()
    {
        $rules = ['email' => 'required|valid_email'];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $user  = $this->userModel->findActiveByEmail($email);

        // Catatan keamanan: pesan sukses ditampilkan sama baik email ditemukan
        // atau tidak, untuk mencegah "email enumeration" (menebak email terdaftar).
        if (!$user) {
            return redirect()->back()
                ->with('success', 'Jika email terdaftar, link reset password akan ditampilkan.');
        }

        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expiresAt   = Time::now()->addMinutes(60)->toDateTimeString();

        $this->userModel->update($user->id, [
            'reset_token'      => $hashedToken,
            'reset_expires_at' => $expiresAt,
        ]);

        $resetLink = base_url('reset-password?token=' . $rawToken);

        // TODO (production): ganti blok ini dengan pengiriman email SMTP.
        // Struktur sengaja dipisah agar penggantian tidak menyentuh logic di atas.
        return redirect()->to('/login')
            ->with('success', 'Link reset password berhasil dibuat (mode development, belum ada SMTP):')
            ->with('reset_link', $resetLink);
    }
    // ==========================================================
    // REGISTER (Customer self-registration)
    // ==========================================================

    public function registerForm()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/register');
    }

    public function register()
    {
        $rules = [
            'name'             => 'required|min_length[3]|max_length[100]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'phone'            => 'required|max_length[20]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'email' => [
                'is_unique' => 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.',
            ],
            'password_confirm' => [
                'matches' => 'Konfirmasi password tidak sama.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Ambil role_id untuk 'customer' — hardcode nama role, bukan hardcode
        // angka id, supaya tidak rapuh kalau urutan seeder role berubah.
        $roleModel = new \App\Models\RoleModel();
        $customerRole = $roleModel->where('name', \Config\Roles::CUSTOMER)->first();

        if (!$customerRole) {
            // Kondisi ini seharusnya tidak pernah terjadi kecuali RoleSeeder
            // belum pernah dijalankan — dijaga agar tidak fatal error, tapi
            // beri pesan jelas untuk debugging.
            return redirect()->back()
                ->withInput()
                ->with('error', 'Registrasi gagal: konfigurasi role belum lengkap. Hubungi admin.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $userId = $this->userModel->insert([
            'role_id'   => $customerRole['id'],
            'name'      => $this->request->getPost('name'),
            'email'     => $this->request->getPost('email'),
            'phone'     => $this->request->getPost('phone'),
            'password'  => $this->request->getPost('password'), // otomatis di-hash oleh UserEntity::setPassword()
            'is_active' => 1,
        ]);

        $customerModel = new \App\Models\CustomerModel();
        $customerModel->insert([
            'user_id' => $userId,
            'name'    => $this->request->getPost('name'),
            'phone'   => $this->request->getPost('phone'),
            'email'   => $this->request->getPost('email'),
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.');
        }

        // Auto-login setelah registrasi sukses, supaya customer tidak perlu
        // mengetik ulang kredensial yang baru saja mereka buat.
        $userWithRole = $this->userModel->getWithRole($userId);

        session()->set([
            'userId'     => $userId,
            'userName'   => $this->request->getPost('name'),
            'userEmail'  => $this->request->getPost('email'),
            'roleId'     => $customerRole['id'],
            'roleName'   => $userWithRole['role_name'],
            'isLoggedIn' => true,
        ]);

        $this->userModel->update($userId, ['last_login_at' => \CodeIgniter\I18n\Time::now()->toDateTimeString()]);

        return redirect()->to('/dashboard')->with('success', 'Registrasi berhasil! Selamat datang, ' . $this->request->getPost('name') . '.');
    }

    // ==========================================================
    // RESET PASSWORD
    // ==========================================================

    public function resetPasswordForm()
    {
        $token = $this->request->getGet('token');

        if (!$token) {
            return redirect()->to('/login')->with('error', 'Token reset tidak valid.');
        }

        $hashedToken = hash('sha256', $token);
        $user = $this->userModel->findByValidResetToken($hashedToken);

        if (!$user) {
            return redirect()->to('/login')->with('error', 'Token reset tidak valid atau sudah kedaluwarsa.');
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    public function resetPassword()
    {
        $rules = [
            'token'                 => 'required',
            'password'              => 'required|min_length[8]',
            'password_confirm'      => 'required|matches[password]',
        ];

        $messages = [
            'password_confirm' => [
                'matches' => 'Konfirmasi password tidak sama.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $token       = $this->request->getPost('token');
        $hashedToken = hash('sha256', $token);
        $user        = $this->userModel->findByValidResetToken($hashedToken);

        if (!$user) {
            return redirect()->to('/login')->with('error', 'Token reset tidak valid atau sudah kedaluwarsa.');
        }

        $user->password = $this->request->getPost('password'); // otomatis di-hash via setPassword() entity

        $this->userModel->save($user);

        // Hapus token agar tidak bisa dipakai ulang
        $this->userModel->update($user->id, [
            'reset_token'      => null,
            'reset_expires_at' => null,
        ]);

        return redirect()->to('/login')->with('success', 'Password berhasil direset, silakan login.');
    }

    // ==========================================================
    // CHANGE PASSWORD (user sudah login)
    // ==========================================================

    public function changePasswordForm()
    {
        return view('auth/change_password');
    }

    public function changePassword()
    {
        $rules = [
            'current_password' => 'required',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        $messages = [
            'password_confirm' => ['matches' => 'Konfirmasi password baru tidak sama.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $userId = session()->get('userId');
        $user   = $this->userModel->find($userId);

        if (!$user || !$user->verifyPassword($this->request->getPost('current_password'))) {
            return redirect()->back()->with('error', 'Password lama tidak sesuai.');
        }

        $user->password = $this->request->getPost('password');
        $this->userModel->save($user);

        return redirect()->to('/change-password')->with('success', 'Password berhasil diubah.');
    }

    // ==========================================================
    // PROFILE
    // ==========================================================

    public function profile()
    {
        $userId = session()->get('userId');
        $user   = $this->userModel->find($userId);

        return view('auth/profile', ['user' => $user]);
    }

    public function updateProfile()
    {
        $userId = session()->get('userId');

        $rules = [
            'name'  => 'required|min_length[3]|max_length[100]',
            'phone' => 'permit_empty|max_length[20]',
            'email' => "required|valid_email|is_unique[users.email,id,{$userId}]",
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->userModel->update($userId, [
            'name'  => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
        ]);

        // Sinkronkan session jika nama/email berubah
        session()->set([
            'userName'  => $this->request->getPost('name'),
            'userEmail' => $this->request->getPost('email'),
        ]);

        return redirect()->to('/profile')->with('success', 'Profil berhasil diperbarui.');
    }
}