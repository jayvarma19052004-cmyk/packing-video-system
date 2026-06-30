<?php
/**
 * API Authentication Controller
 */

class Api_AuthController {
    /**
     * Login API
     */
    public function login() {
        if (Request::method() !== 'POST') {
            Response::error('Method not allowed', [], 405);
        }

        $data = Request::json();
        $email = Security::sanitize($data['email'] ?? '', 'email');
        $password = $data['password'] ?? '';

        $validator = new Validator(['email' => $email, 'password' => $password]);
        $validator->required('email')->email('email');
        $validator->required('password');

        if ($validator->fails()) {
            Response::error('Validation failed', $validator->errors(), 422);
        }

        if (!Auth::authenticate($email, $password)) {
            Response::error('Invalid credentials', [], 401);
        }

        $user = Auth::user();
        $token = Security::generateJWT([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        Response::success([
            'user' => $user,
            'token' => $token
        ], 'Login successful', 200);
    }

    /**
     * Register API
     */
    public function register() {
        if (Request::method() !== 'POST') {
            Response::error('Method not allowed', [], 405);
        }

        $data = Request::json();

        $validator = new Validator($data);
        $validator->required('username', 'Username')->minLength('username', 3);
        $validator->required('email', 'Email')->email('email')->unique('email', 'users');
        $validator->required('password', 'Password')->minLength('password', PASSWORD_MIN_LENGTH);
        $validator->required('first_name', 'First Name');
        $validator->required('last_name', 'Last Name');

        if ($validator->fails()) {
            Response::error('Validation failed', $validator->errors(), 422);
        }

        try {
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

            Database::insert('wallets', [
                'vendor_id' => $userId,
                'balance' => 0,
                'status' => 'active'
            ]);

            Logger::audit('api_register', 'user', $userId);
            Response::success(['user_id' => $userId], 'Registration successful', 201);
        } catch (Exception $e) {
            Logger::error('API registration failed: ' . $e->getMessage());
            Response::error('Registration failed', [], 500);
        }
    }

    /**
     * Logout API
     */
    public function logout() {
        if (!Auth::check()) {
            Response::error('Not authenticated', [], 401);
        }

        Auth::logout();
        Response::success([], 'Logout successful');
    }

    /**
     * Refresh token API
     */
    public function refresh() {
        if (!Auth::check()) {
            Response::error('Not authenticated', [], 401);
        }

        $user = Auth::user();
        $token = Security::generateJWT([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        Response::success(['token' => $token], 'Token refreshed');
    }
}
