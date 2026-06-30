<?php
/**
 * Vendor Model
 */

class Vendor extends Model {
    protected string $table = 'vendors';
    protected array $fillable = [
        'user_id', 'company_name', 'business_type', 'registration_number',
        'tax_id', 'description', 'address_line1', 'address_line2',
        'city', 'state', 'country', 'postal_code', 'phone', 'website',
        'contact_person_name', 'contact_person_email', 'contact_person_phone',
        'bank_account_holder', 'bank_account_number', 'bank_name',
        'status'
    ];

    /**
     * Get vendor user
     */
    public function user(): ?array {
        return Database::fetchOne(
            'SELECT * FROM users WHERE id = ?',
            [$this->getAttribute('user_id')]
        );
    }

    /**
     * Get vendor subscription
     */
    public function subscription(): ?array {
        return Database::fetchOne(
            'SELECT * FROM subscriptions WHERE vendor_id = ? AND status = "active"',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get vendor wallet
     */
    public function wallet(): ?array {
        return Database::fetchOne(
            'SELECT * FROM wallets WHERE vendor_id = ?',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get vendor employees
     */
    public function employees(): array {
        return Database::fetchAll(
            'SELECT * FROM employees WHERE vendor_id = ? ORDER BY created_at DESC',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get vendor orders
     */
    public function orders(string $status = null): array {
        $sql = 'SELECT * FROM orders WHERE vendor_id = ?';
        $params = [$this->getAttribute('id')];

        if ($status) {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY created_at DESC';

        return Database::fetchAll($sql, $params);
    }

    /**
     * Get vendor videos
     */
    public function videos(): array {
        return Database::fetchAll(
            'SELECT * FROM videos WHERE vendor_id = ? ORDER BY created_at DESC LIMIT 100',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get vendor statistics
     */
    public function statistics(): array {
        $vendorId = $this->getAttribute('id');

        return [
            'total_orders' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM orders WHERE vendor_id = ?',
                [$vendorId]
            )['count'] ?? 0,
            'completed_orders' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM orders WHERE vendor_id = ? AND status = "completed"',
                [$vendorId]
            )['count'] ?? 0,
            'total_videos' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM videos WHERE vendor_id = ?',
                [$vendorId]
            )['count'] ?? 0,
            'verified_videos' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM videos WHERE vendor_id = ? AND verification_result = "passed"',
                [$vendorId]
            )['count'] ?? 0,
            'total_employees' => Database::fetchOne(
                'SELECT COUNT(*) as count FROM employees WHERE vendor_id = ? AND status = "active"',
                [$vendorId]
            )['count'] ?? 0,
            'storage_used' => $this->getAttribute('storage_used_gb') ?? 0
        ];
    }
}
