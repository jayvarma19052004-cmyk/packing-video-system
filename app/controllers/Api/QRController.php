<?php
/**
 * API QR Code Controller
 * Handles QR code scanning and verification
 */

class Api_QRController {
    /**
     * Record QR scan
     */
    public function scan() {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $data = Request::json();
            
            $validator = new Validator($data);
            $validator->required('qr_value', 'QR Code Value');
            $validator->required('video_id', 'Video ID')->integer('video_id');
            $validator->required('order_id', 'Order ID')->integer('order_id');
            $validator->required('session_id', 'Session ID')->integer('session_id');

            if ($validator->fails()) {
                Response::error('Validation failed', $validator->errors(), 422);
            }

            // Check for duplicate scans
            $recentScan = Database::fetchOne(
                'SELECT * FROM qr_scans WHERE video_id = ? AND qr_value = ? AND scan_timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)',
                [$data['video_id'], $data['qr_value'], DUPLICATE_SCAN_WINDOW]
            );

            if ($recentScan && PREVENT_DUPLICATE_SCANS) {
                Response::error('Duplicate QR scan detected', [], 409);
            }

            // Get session and video info
            $session = Database::fetchOne('SELECT * FROM packing_sessions WHERE id = ?', [$data['session_id']]);
            $video = Database::fetchOne('SELECT * FROM videos WHERE id = ?', [$data['video_id']]);
            $order = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$data['order_id']]);

            if (!$session || !$video || !$order) {
                Response::error('Session, video, or order not found', [], 404);
            }

            // Parse QR data
            $qrType = 'custom';
            $parsedData = null;
            
            if ($data['qr_value'] === (string)$order['id']) {
                $qrType = 'order_id';
                $parsedData = ['order_id' => $order['id']];
            } elseif (strpos($data['qr_value'], 'tracking_') === 0) {
                $qrType = 'tracking_code';
                $parsedData = ['tracking_code' => $data['qr_value']];
            }

            // Insert QR scan
            $scanId = Database::insert('qr_scans', [
                'video_id' => $data['video_id'],
                'packing_session_id' => $data['session_id'],
                'order_id' => $data['order_id'],
                'employee_id' => $session['employee_id'],
                'vendor_id' => $order['vendor_id'],
                'qr_value' => $data['qr_value'],
                'qr_type' => $qrType,
                'scan_timestamp' => $data['scan_timestamp'] ?? date('Y-m-d H:i:s'),
                'scan_duration_ms' => $data['scan_duration_ms'] ?? 0,
                'confidence_score' => $data['confidence_score'] ?? 100,
                'location_x' => $data['location_x'] ?? null,
                'location_y' => $data['location_y'] ?? null,
                'parsed_data' => $parsedData ? json_encode($parsedData) : null,
                'status' => 'valid',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update video scan count
            Database::query(
                'UPDATE videos SET total_qr_scans = total_qr_scans + 1 WHERE id = ?',
                [$data['video_id']]
            );

            Logger::info('QR scan recorded', ['scan_id' => $scanId, 'qr_type' => $qrType]);

            Response::success([
                'scan_id' => $scanId,
                'qr_type' => $qrType,
                'parsed_data' => $parsedData
            ], 'QR code scanned successfully', 201);
        } catch (Exception $e) {
            Logger::error('QR scan failed: ' . $e->getMessage());
            Response::error('Scan failed', [], 500);
        }
    }

    /**
     * Get QR scans for video
     */
    public function getScans(int $videoId) {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $scans = Database::fetchAll(
                'SELECT * FROM qr_scans WHERE video_id = ? ORDER BY scan_timestamp ASC',
                [$videoId]
            );

            Response::success($scans);
        } catch (Exception $e) {
            Logger::error('Get QR scans failed: ' . $e->getMessage());
            Response::error('Failed to get scans', [], 500);
        }
    }

    /**
     * Verify QR scans
     */
    public function verify() {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $data = Request::json();
            $videoId = $data['video_id'] ?? null;
            $orderId = $data['order_id'] ?? null;

            $validator = new Validator(['video_id' => $videoId, 'order_id' => $orderId]);
            $validator->required('video_id')->integer('video_id');
            $validator->required('order_id')->integer('order_id');

            if ($validator->fails()) {
                Response::error('Validation failed', $validator->errors(), 422);
            }

            // Get all scans for video
            $scans = Database::fetchAll(
                'SELECT * FROM qr_scans WHERE video_id = ? ORDER BY scan_timestamp ASC',
                [$videoId]
            );

            // Check if order ID was scanned
            $orderIdScanned = false;
            foreach ($scans as $scan) {
                if ($scan['qr_type'] === 'order_id') {
                    $orderIdScanned = true;
                    break;
                }
            }

            Response::success([
                'verified' => $orderIdScanned,
                'total_scans' => count($scans),
                'order_id_scanned' => $orderIdScanned,
                'scans' => $scans
            ]);
        } catch (Exception $e) {
            Logger::error('QR verification failed: ' . $e->getMessage());
            Response::error('Verification failed', [], 500);
        }
    }
}
