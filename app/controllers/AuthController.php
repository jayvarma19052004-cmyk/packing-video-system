<?php
/**
 * Authentication Controller
 */

class AuthController {
    /**
     * Show login form
     */
    public function loginForm() {
        if (Auth::check()) {
            Response::redirect(APP_URL . '/dashboard');
        }
        require VIEWS_PATH . 'auth/login.php';
    }

    /**
     * Handle login
     */
    public function login() {
        $email = Security::sanitize(Request::post('email'), 'email');
        $password = Request::post('password');
        $remember = Request::post('remember') === '1';

        $validator = new Validator(['email' => $email, 'password' => $password]);
        $validator->required('email')->email('email');
        $validator->required('password')->minLength('password', PASSWORD_MIN_LENGTH);

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            Response::redirect(APP_URL . '/login');
        }

        if (Auth::authenticate($email, $password, $remember)) {
            $user = Auth::user();
            $_SESSION['success'] = 'Login successful';
            
            // Redirect based on role
            if ($user['role'] === 'super_admin') {
                Response::redirect(APP_URL . '/admin/dashboard');
            } elseif ($user['role'] === 'vendor') {
                Response::redirect(APP_URL . '/vendor/dashboard');
            } elseif (in_array($user['role'], ['vendor_employee', 'packing_user'])) {
                Response::redirect(APP_URL . '/employee/dashboard');
            } else {
                Response::redirect(APP_URL . '/dashboard');
            }
        }

        $_SESSION['errors'] = ['email' => 'Invalid email or password'];
        Response::redirect(APP_URL . '/login');
    }

    /**
     * Show register form
     */
    public function registerForm() {
        if (Auth::check()) {
            Response::redirect(APP_URL . '/dashboard');
        }
        require VIEWS_PATH . 'auth/register.php';
    }

    /**
     * Handle registration
     */
    public function register() {
        $data = [
            'username' => Security::sanitize(Request::post('username')),
            'email' => Security::sanitize(Request::post('email'), 'email'),
            'password' => Request::post('password'),
            'password_confirm' => Request::post('password_confirm'),
            'first_name' => Security::sanitize(Request::post('first_name')),
            'last_name' => Security::sanitize(Request::post('last_name')),
        ];

        $validator = new Validator($data);
        $validator->required('username')->minLength('username', 3);
        $validator->required('email')->email('email')->unique('email', 'users');
        $validator->required('password')->minLength('password', PASSWORD_MIN_LENGTH);
        $validator->match('password', 'password_confirm', 'password');
        $validator->required('first_name')->minLength('first_name', 2);
        $validator->required('last_name')->minLength('last_name', 2);

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            Response::redirect(APP_URL . '/register');
        }

        try {
            // Get vendor role ID (default for registration)
            $role = Database::fetchOne('SELECT id FROM roles WHERE name = ?', ['vendor']);
            if (!$role) {
                throw new Exception('Default role not found');
            }

            $userId = Database::insert('users', [
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => Security::hashPassword($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'role_id' => $role['id'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Create wallet
            Database::insert('wallets', [
                'vendor_id' => $userId,
                'balance' => 0,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            Logger::audit('register', 'user', $userId);
            $_SESSION['success'] = 'Registration successful. Please login.';
            Response::redirect(APP_URL . '/login');
        } catch (Exception $e) {
            Logger::error('Registration failed: ' . $e->getMessage());
            $_SESSION['errors'] = ['general' => 'Registration failed. Please try again.'];
            Response::redirect(APP_URL . '/register');
        }
    }

    /**
     * Logout
     */
    public function logout() {
        Auth::logout();
        $_SESSION['success'] = 'Logged out successfully';
        Response::redirect(APP_URL . '/login');
    }

    /**
     * Show forgot password form
     */
    public function forgotPasswordForm() {
        if (Auth::check()) {
            Response::redirect(APP_URL . '/dashboard');
        }
        require VIEWS_PATH . 'auth/forgot-password.php';
    }

    /**
     * Handle forgot password
     */
    public function forgotPassword() {
        $email = Security::sanitize(Request::post('email'), 'email');

        $validator = new Validator(['email' => $email]);
        $validator->required('email')->email('email');

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            Response::redirect(APP_URL . '/forgot-password');
        }

        $user = Database::fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user) {
            $_SESSION['success'] = 'If email exists, you will receive reset link';
            Response::redirect(APP_URL . '/forgot-password');
        }

        try {
            $token = Security::generateToken();
            $expiresAt = date('Y-m-d H:i:s', time() + 24 * 3600);

            Database::insert('password_resets', [
                'email' => $email,
                'token' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            Mailer::sendPasswordResetEmail($email, $token);
            Logger::audit('forgot_password', 'user', $user['id']);

            $_SESSION['success'] = 'Password reset link sent to your email';
        } catch (Exception $e) {
            Logger::error('Forgot password failed: ' . $e->getMessage());
        }

        Response::redirect(APP_URL . '/forgot-password');
    }

    /**
     * Show reset password form
     */
    public function resetPasswordForm() {
        if (Auth::check()) {
            Response::redirect(APP_URL . '/dashboard');
        }
        require VIEWS_PATH . 'auth/reset-password.php';
    }

    /**
     * Handle reset password
     */
    public function resetPassword() {
        $token = Request::post('token');
        $password = Request::post('password');
        $passwordConfirm = Request::post('password_confirm');

        $validator = new Validator([
            'password' => $password,
            'password_confirm' => $passwordConfirm
        ]);
        $validator->required('password')->minLength('password', PASSWORD_MIN_LENGTH);
        $validator->match('password', 'password_confirm', 'password');

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->errors();
            Response::redirect(APP_URL . '/reset-password?token=' . $token);
        }

        $reset = Database::fetchOne(
            'SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()',
            [hash('sha256', $token)]
        );

        if (!$reset) {
            $_SESSION['errors'] = ['token' => 'Invalid or expired token'];
            Response::redirect(APP_URL . '/forgot-password');
        }

        try {
            Database::update('users', 
                ['password_hash' => Security::hashPassword($password)],
                ['email' => $reset['email']]
            );

            Database::delete('password_resets', ['email' => $reset['email']]);

            $_SESSION['success'] = 'Password reset successfully. Please login.';
            Logger::audit('reset_password', 'user', Database::fetchOne('SELECT id FROM users WHERE email = ?', [$reset['email']])['id']);
            Response::redirect(APP_URL . '/login');
        } catch (Exception $e) {
            Logger::error('Password reset failed: ' . $e->getMessage());
            $_SESSION['errors'] = ['general' => 'Password reset failed'];
            Response::redirect(APP_URL . '/reset-password?token=' . $token);
        }
    }
}
