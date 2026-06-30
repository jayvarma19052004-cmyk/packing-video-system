<?php
/**
 * Authentication Handler
 * Manages user authentication and sessions
 */

class Auth {
    const SESSION_KEY = 'auth_user';
    const REMEMBER_COOKIE = 'remember_token';

    /**
     * Get current user
     */
    public static function user(): ?array {
        if (isset($_SESSION[self::SESSION_KEY])) {
            return $_SESSION[self::SESSION_KEY];
        }
        return null;
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool {
        return self::user() !== null;
    }

    /**
     * Check if user is guest
     */
    public static function guest(): bool {
        return !self::check();
    }

    /**
     * Get user ID
     */
    public static function id(): ?int {
        $user = self::user();
        return $user['id'] ?? null;
    }

    /**
     * Check if user has role
     */
    public static function hasRole(string $role): bool {
        $user = self::user();
        return isset($user['role']) && $user['role'] === $role;
    }

    /**
     * Check if user has permission
     */
    public static function hasPermission(string $permission): bool {
        $user = self::user();
        if (!isset($user['permissions']) || !is_array($user['permissions'])) {
            return false;
        }
        return in_array($permission, $user['permissions']);
    }

    /**
     * Check if user has any permission
     */
    public static function hasAnyPermission(array $permissions): bool {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Login user
     */
    public static function login(int $userId, bool $remember = false): bool {
        try {
            $user = Database::fetchOne(
                'SELECT u.*, r.name as role FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?',
                [$userId]
            );

            if (!$user) {
                return false;
            }

            // Get user permissions
            $permissions = self::getUserPermissions($userId);

            $userData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role_id' => $user['role_id'],
                'role' => $user['role'],
                'permissions' => $permissions,
                'avatar_url' => $user['avatar_url']
            ];

            $_SESSION[self::SESSION_KEY] = $userData;

            // Update last login
            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $userId]);

            if ($remember) {
                self::rememberUser($userId);
            }

            Logger::info('User logged in', ['user_id' => $userId]);
            Logger::audit('login', 'user', $userId);

            return true;
        } catch (Exception $e) {
            Logger::error('Login failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout user
     */
    public static function logout(): void {
        Logger::audit('logout', 'user', self::id() ?? 0);
        
        unset($_SESSION[self::SESSION_KEY]);
        setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/');
        session_destroy();
    }

    /**
     * Authenticate user with email and password
     */
    public static function authenticate(string $email, string $password, bool $remember = false): bool {
        try {
            $user = Database::fetchOne(
                'SELECT id, password_hash, status FROM users WHERE email = ?',
                [$email]
            );

            if (!$user || $user['status'] !== 'active') {
                self::logFailedLogin($email, 'Invalid credentials or inactive account');
                return false;
            }

            if (!Security::verifyPassword($password, $user['password_hash'])) {
                self::logFailedLogin($email, 'Invalid password');
                return false;
            }

            return self::login($user['id'], $remember);
        } catch (Exception $e) {
            Logger::error('Authentication failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user permissions
     */
    private static function getUserPermissions(int $userId): array {
        try {
            $result = Database::fetchAll(
                'SELECT p.name FROM permissions p ' .
                'JOIN role_permissions rp ON p.id = rp.permission_id ' .
                'JOIN users u ON rp.role_id = u.role_id ' .
                'WHERE u.id = ?',
                [$userId]
            );
            return array_column($result, 'name');
        } catch (Exception $e) {
            Logger::error('Failed to get user permissions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Remember user
     */
    private static function rememberUser(int $userId): void {
        $token = Security::generateToken(64);
        $expiresAt = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 days

        Database::insert('user_sessions', [
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'expires_at' => $expiresAt
        ]);

        setcookie(
            self::REMEMBER_COOKIE,
            $token,
            time() + 30 * 24 * 60 * 60,
            '/',
            '',
            true,
            true
        );
    }

    /**
     * Log failed login attempt
     */
    private static function logFailedLogin(string $email, string $reason): void {
        Database::insert('login_logs', [
            'email' => $email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'status' => 'failed',
            'failure_reason' => $reason,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
