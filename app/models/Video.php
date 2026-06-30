<?php
/**
 * Video Model
 */

class Video extends Model {
    protected string $table = 'videos';
    protected array $fillable = [
        'packing_session_id', 'order_id', 'vendor_id', 'employee_id',
        'video_filename', 'video_path', 'video_url', 'thumbnail_path',
        'thumbnail_url', 'duration_seconds', 'file_size_bytes', 'mime_type',
        'resolution', 'bitrate', 'codec', 'status', 'upload_progress',
        'verification_result', 'verified_by', 'verified_at'
    ];

    /**
     * Get video order
     */
    public function order(): ?array {
        return Database::fetchOne(
            'SELECT * FROM orders WHERE id = ?',
            [$this->getAttribute('order_id')]
        );
    }

    /**
     * Get video employee
     */
    public function employee(): ?array {
        return Database::fetchOne(
            'SELECT * FROM employees WHERE id = ?',
            [$this->getAttribute('employee_id')]
        );
    }

    /**
     * Get video packing session
     */
    public function packingSession(): ?array {
        return Database::fetchOne(
            'SELECT * FROM packing_sessions WHERE id = ?',
            [$this->getAttribute('packing_session_id')]
        );
    }

    /**
     * Get video barcode scans
     */
    public function barcodeScans(): array {
        return Database::fetchAll(
            'SELECT * FROM barcode_scans WHERE video_id = ? ORDER BY scan_timestamp ASC',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get video QR scans
     */
    public function qrScans(): array {
        return Database::fetchAll(
            'SELECT * FROM qr_scans WHERE video_id = ? ORDER BY scan_timestamp ASC',
            [$this->getAttribute('id')]
        );
    }

    /**
     * Get video logs
     */
    public function logs(): array {
        return Database::fetchAll(
            'SELECT * FROM video_logs WHERE video_id = ? ORDER BY created_at ASC',
            [$this->getAttribute('id')]
        );
    }
}
