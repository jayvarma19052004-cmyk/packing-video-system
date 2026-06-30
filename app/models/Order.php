<?php
/**
 * Order Model
 */

class Order extends Model {
    protected string $table = 'orders';
    protected array $fillable = [
        'vendor_id', 'order_number', 'customer_name', 'customer_email',
        'customer_phone', 'shipping_address', 'order_date', 'due_date',
        'status', 'verification_status', 'total_items', 'total_amount', 'notes'
    ];

    /**
     * Get order vendor
     */
    public function vendor(): ?array {
        return Database::fetchOne(
            'SELECT * FROM vendors WHERE id = ?',
            [$this->getAttribute('vendor_id')]
        );
    }

    /**
     * Get order items
     */
    public function items(): array {
        return Database::fetchAll(
            'SELECT oi.*, p.product_name, p.sku FROM order_items oi ' .
            'LEFT JOIN products p ON oi.product_id = p.id ' .
            'WHERE oi.order_id = ?',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get assigned employees
     */
    public function assignedEmployees(): array {
        return Database::fetchAll(
            'SELECT e.*, oa.assigned_at, oa.status FROM employees e ' .
            'JOIN order_assignments oa ON e.id = oa.employee_id ' .
            'WHERE oa.order_id = ?',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get order videos
     */
    public function videos(): array {
        return Database::fetchAll(
            'SELECT * FROM videos WHERE order_id = ? ORDER BY created_at DESC',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get order packing sessions
     */
    public function packingSessions(): array {
        return Database::fetchAll(
            'SELECT * FROM packing_sessions WHERE order_id = ? ORDER BY session_start DESC',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get order barcode scans
     */
    public function barcodeScans(): array {
        return Database::fetchAll(
            'SELECT * FROM barcode_scans WHERE order_id = ? ORDER BY scan_timestamp DESC',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get order QR scans
     */
    public function qrScans(): array {
        return Database::fetchAll(
            'SELECT * FROM qr_scans WHERE order_id = ? ORDER BY scan_timestamp DESC',
            [$this->getAttribute('id')]
        );
    }
}
