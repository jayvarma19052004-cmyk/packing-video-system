<?php
/**
 * User Model
 */

class User extends Model {
    protected string $table = 'users';
    protected array $fillable = ['username', 'email', 'phone', 'password_hash', 'first_name', 'last_name', 'role_id', 'status'];

    /**
     * Get user role
     */
    public function role(): ?array {
        return Database::fetchOne(
            'SELECT * FROM roles WHERE id = ?',
            [$this->getAttribute('role_id')]
        );
    }

    /**
     * Get user permissions
     */
    public function permissions(): array {
        return Database::fetchAll(
            'SELECT p.* FROM permissions p ' .
            'JOIN role_permissions rp ON p.id = rp.permission_id ' .
            'WHERE rp.role_id = ?',
            [$this->getAttribute('role_id')]
        );
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool {
        $role = $this->role();
        return $role && $role['name'] === 'super_admin';
    }

    /**
     * Check if user is vendor
     */
    public function isVendor(): bool {
        $role = $this->role();
        return $role && $role['name'] === 'vendor';
    }
}
