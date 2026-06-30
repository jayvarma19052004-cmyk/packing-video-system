<?php
/**
 * Employee Model
 */

class Employee extends Model {
    protected string $table = 'employees';
    protected array $fillable = [
        'vendor_id', 'user_id', 'employee_id_number', 'first_name', 'last_name',
        'email', 'phone', 'role', 'department', 'hire_date', 'status'
    ];

    /**
     * Get employee vendor
     */
    public function vendor(): ?array {
        return Database::fetchOne(
            'SELECT * FROM vendors WHERE id = ?',
            [$this->getAttribute('vendor_id')]
        );
    }

    /**
     * Get employee user
     */
    public function user(): ?array {
        return Database::fetchOne(
            'SELECT * FROM users WHERE id = ?',
            [$this->getAttribute('user_id')]
        );
    }

    /**
     * Get employee assigned orders
     */
    public function assignedOrders(): array {
        return Database::fetchAll(
            'SELECT o.* FROM orders o ' .
            'JOIN order_assignments oa ON o.id = oa.order_id ' .
            'WHERE oa.employee_id = ? AND oa.status IN ("assigned", "started") ' .
            'ORDER BY o.created_at DESC',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get employee videos
     */
    public function videos(): array {
        return Database::fetchAll(
            'SELECT * FROM videos WHERE employee_id = ? ORDER BY created_at DESC LIMIT 100',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get employee statistics
     */
    public function statistics(): array {
        $employeeId = $this->getAttribute('id');

        return [
            'total_videos' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM videos WHERE employee_id = ?',
                [$employeeId]
            )['count'] ?? 0,
            'verified_videos' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM videos WHERE employee_id = ? AND verification_result = "passed"',
                [$employeeId]
            )['count'] ?? 0,
            'total_scans' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM barcode_scans WHERE employee_id = ?',
                [$employeeId]
            )['count'] ?? 0,
            'total_orders_packed' => $this->getAttribute('total_orders_packed') ?? 0,
            'average_video_duration' => Database::fetchOne(
                'SELECT AVG(ps.duration_seconds) as avg_duration FROM packing_sessions ps ' .
                'WHERE ps.employee_id = ?',
                [$employeeId]
            )['avg_duration'] ?? 0
        ];
    }
}
