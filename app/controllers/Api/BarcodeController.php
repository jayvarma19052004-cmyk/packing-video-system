<?php
/**
 * API Barcode Controller
 * Handles barcode scanning and verification
 */

class Api_BarcodeController {
    /**
     * Record barcode scan
     */
    public function scan() {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $data = Request::json();
            
            $validator = new Validator($data);
            $validator->required('barcode_value', 'Barcode Value');
            $validator->required('video_id', 'Video ID')->integer('video_id');
            $validator->required('order_id', 'Order ID')->integer('order_id');
            $validator->required('session_id', 'Session ID')->integer('session_id');

            if ($validator->fails()) {
                Response::error('Validation failed', $validator->errors(), 422);
            }

            // Check for duplicate scans (prevent duplicate within 5 seconds)
            $recentScan = Database::fetchOne(
                'SELECT * FROM barcode_scans WHERE video_id = ? AND barcode_value = ? AND scan_timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)',
                [$data['video_id'], $data['barcode_value'], DUPLICATE_SCAN_WINDOW]
            );

            if ($recentScan && PREVENT_DUPLICATE_SCANS) {
                Response::error('Duplicate scan detected', [], 409);
            }

            // Get session and video info
            $session = Database::fetchOne('SELECT * FROM packing_sessions WHERE id = ?', [$data['session_id']]);
            $video = Database::fetchOne('SELECT * FROM videos WHERE id = ?', [$data['video_id']]);
            $order = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$data['order_id']]);

            if (!$session || !$video || !$order) {
                Response::error('Session, video, or order not found', [], 404);
            }

            // Try to match with product
            $product = Database::fetchOne(
                'SELECT * FROM products WHERE barcode = ?',
                [$data['barcode_value']]
            );

            // Insert barcode scan
            $scanId = Database::insert('barcode_scans', [
                'video_id' => $data['video_id'],
                'packing_session_id' => $data['session_id'],
                'order_id' => $data['order_id'],
                'employee_id' => $session['employee_id'],
                'vendor_id' => $order['vendor_id'],
                'barcode_value' => $data['barcode_value'],
                'barcode_format' => $data['barcode_format'] ?? 'unknown',
                'product_id' => $product['id'] ?? null,
                'scan_timestamp' => $data['scan_timestamp'] ?? date('Y-m-d H:i:s'),
                'scan_duration_ms' => $data['scan_duration_ms'] ?? 0,
                'confidence_score' => $data['confidence_score'] ?? 100,
                'location_x' => $data['location_x'] ?? null,
                'location_y' => $data['location_y'] ?? null,
                'status' => $product ? 'valid' : 'unmatched',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update video scan count
            Database::query(
                'UPDATE videos SET total_barcode_scans = total_barcode_scans + 1 WHERE id = ?',
                [$data['video_id']]
            );

            Logger::info('Barcode scan recorded', ['scan_id' => $scanId, 'barcode' => $data['barcode_value']]);

            Response::success([
                'scan_id' => $scanId,
                'status' => $product ? 'matched' : 'unmatched',
                'product' => $product
            ], 'Barcode scanned successfully', 201);
        } catch (Exception $e) {
            Logger::error('Barcode scan failed: ' . $e->getMessage());
            Response::error('Scan failed', [], 500);
        }
    }

    /**
     * Get barcode scans for video
     */
    public function getScans(int $videoId) {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $scans = Database::fetchAll(
                'SELECT bs.*, p.product_name, p.sku FROM barcode_scans bs ' .
                'LEFT JOIN products p ON bs.product_id = p.id ' .
                'WHERE bs.video_id = ? ORDER BY bs.scan_timestamp ASC',
                [$videoId]
            );

            Response::success($scans);
        } catch (Exception $e) {
            Logger::error('Get barcode scans failed: ' . $e->getMessage());
            Response::error('Failed to get scans', [], 500);
        }
    }

    /**
     * Verify barcode scans
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
                'SELECT * FROM barcode_scans WHERE video_id = ?',
                [$videoId]
            );

            // Get order items
            $items = Database::fetchAll(
                'SELECT * FROM order_items WHERE order_id = ?',
                [$orderId]
            );

            $itemSkus = array_column($items, 'product_id');
            $scannedBarcodes = array_column($scans, 'barcode_value');

            // Check if all items scanned
            $allScanned = true;
            foreach ($items as $item) {
                $matchCount = count(array_filter($scans, fn($s) => $s['product_id'] === $item['product_id']));
                if ($matchCount < $item['quantity']) {
                    $allScanned = false;
                    break;
                }
            }

            Response::success([
                'verified' => $allScanned,
                'total_scans' => count($scans),
                'matched_scans' => count(array_filter($scans, fn($s) => $s['status'] === 'valid')),
                'unmatched_scans' => count(array_filter($scans, fn($s) => $s['status'] === 'unmatched')),
                'scans' => $scans
            ]);
        } catch (Exception $e) {
            Logger::error('Barcode verification failed: ' . $e->getMessage());
            Response::error('Verification failed', [], 500);
        }
    }
}
