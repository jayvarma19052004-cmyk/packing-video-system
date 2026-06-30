<?php
/**
 * API Video Controller
 * Handles video upload, verification, and management
 */

class Api_VideoController {
    /**
     * Upload video
     */
    public function upload() {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        if (!Request::hasFile('video')) {
            Response::error('No video file provided', [], 400);
        }

        $file = Request::file('video');
        $orderId = Request::post('order_id');
        $sessionId = Request::post('session_id');

        $validator = new Validator(['order_id' => $orderId, 'session_id' => $sessionId]);
        $validator->required('order_id')->integer('order_id');
        $validator->required('session_id')->integer('session_id');

        if ($validator->fails()) {
            Response::error('Validation failed', $validator->errors(), 422);
        }

        try {
            // Verify order and session exist
            $order = Database::fetchOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
            if (!$order) {
                Response::error('Order not found', [], 404);
            }

            $session = Database::fetchOne('SELECT * FROM packing_sessions WHERE id = ?', [$sessionId]);
            if (!$session) {
                Response::error('Session not found', [], 404);
            }

            // Upload video
            $uploader = new FileUploader();
            $filename = $uploader->uploadVideo($file, VIDEO_UPLOAD_DIR);

            if (!$filename) {
                Response::error('Video upload failed', $uploader->getErrors(), 400);
            }

            // Generate thumbnail
            $thumbnailFilename = $uploader->generateThumbnail(
                VIDEO_UPLOAD_DIR . $filename,
                THUMBNAIL_UPLOAD_DIR
            );

            // Insert video record
            $videoId = Database::insert('videos', [
                'packing_session_id' => $sessionId,
                'order_id' => $orderId,
                'vendor_id' => $order['vendor_id'],
                'employee_id' => $session['employee_id'],
                'video_filename' => $filename,
                'video_path' => VIDEO_UPLOAD_DIR . $filename,
                'video_url' => APP_URL . '/uploads/videos/' . $filename,
                'thumbnail_path' => THUMBNAIL_UPLOAD_DIR . $thumbnailFilename,
                'thumbnail_url' => APP_URL . '/uploads/thumbnails/' . $thumbnailFilename,
                'file_size_bytes' => filesize(VIDEO_UPLOAD_DIR . $filename),
                'mime_type' => $file['type'],
                'status' => 'ready',
                'verification_result' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Log video event
            Database::insert('video_logs', [
                'video_id' => $videoId,
                'event_type' => 'upload_complete',
                'event_data' => json_encode(['file_size' => filesize(VIDEO_UPLOAD_DIR . $filename)]),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            Logger::audit('upload_video', 'video', $videoId);

            Response::success([
                'video_id' => $videoId,
                'video_url' => APP_URL . '/uploads/videos/' . $filename,
                'thumbnail_url' => APP_URL . '/uploads/thumbnails/' . $thumbnailFilename
            ], 'Video uploaded successfully', 201);
        } catch (Exception $e) {
            Logger::error('Video upload failed: ' . $e->getMessage());
            Response::error('Video upload failed', [], 500);
        }
    }

    /**
     * Get video details
     */
    public function show(int $id) {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $video = Database::fetchOne('SELECT * FROM videos WHERE id = ?', [$id]);
            if (!$video) {
                Response::notFound('Video not found');
            }

            // Check access permission
            $user = Auth::user();
            if ($user['role'] !== 'super_admin' && $video['vendor_id'] != Request::post('vendor_id')) {
                Response::forbidden();
            }

            $video['barcode_scans'] = Database::fetchAll(
                'SELECT * FROM barcode_scans WHERE video_id = ? ORDER BY scan_timestamp ASC',
                [$id]
            );
            $video['qr_scans'] = Database::fetchAll(
                'SELECT * FROM qr_scans WHERE video_id = ? ORDER BY scan_timestamp ASC',
                [$id]
            );
            $video['logs'] = Database::fetchAll(
                'SELECT * FROM video_logs WHERE video_id = ? ORDER BY created_at ASC',
                [$id]
            );

            Response::success($video);
        } catch (Exception $e) {
            Logger::error('Get video failed: ' . $e->getMessage());
            Response::error('Failed to get video', [], 500);
        }
    }

    /**
     * Verify video
     */
    public function verify(int $id) {
        if (!Auth::check() || !Auth::hasPermission('videos.verify')) {
            Response::forbidden();
        }

        try {
            $data = Request::json();
            $result = $data['result'] ?? 'pending'; // passed, failed
            $comments = $data['comments'] ?? '';

            $validator = new Validator(['result' => $result]);
            $validator->in('result', ['passed', 'failed'], 'Verification result');

            if ($validator->fails()) {
                Response::error('Validation failed', $validator->errors(), 422);
            }

            Database::update('videos', [
                'verification_result' => $result,
                'verification_comments' => $comments,
                'verified_by' => Auth::id(),
                'verified_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            // Update order verification status if all videos verified
            $video = Database::fetchOne('SELECT order_id FROM videos WHERE id = ?', [$id]);
            $unverifiedCount = Database::fetchOne(
                'SELECT COUNT(*) as count FROM videos WHERE order_id = ? AND verification_result = "pending"',
                [$video['order_id']]
            )['count'];

            if ($unverifiedCount === 0) {
                Database::update('orders', ['verification_status' => 'verified'], ['id' => $video['order_id']]);
            }

            Logger::audit('verify_video', 'video', $id);
            Response::success(['verified' => true], 'Video verified successfully');
        } catch (Exception $e) {
            Logger::error('Video verification failed: ' . $e->getMessage());
            Response::error('Verification failed', [], 500);
        }
    }

    /**
     * Delete video
     */
    public function destroy(int $id) {
        if (!Auth::check() || !Auth::hasPermission('videos.delete')) {
            Response::forbidden();
        }

        try {
            $video = Database::fetchOne('SELECT * FROM videos WHERE id = ?', [$id]);
            if (!$video) {
                Response::notFound('Video not found');
            }

            // Delete files
            if (file_exists($video['video_path'])) {
                unlink($video['video_path']);
            }
            if (file_exists($video['thumbnail_path'])) {
                unlink($video['thumbnail_path']);
            }

            // Delete database records
            Database::delete('barcode_scans', ['video_id' => $id]);
            Database::delete('qr_scans', ['video_id' => $id]);
            Database::delete('video_logs', ['video_id' => $id]);
            Database::delete('videos', ['id' => $id]);

            Logger::audit('delete_video', 'video', $id);
            Response::success([], 'Video deleted successfully');
        } catch (Exception $e) {
            Logger::error('Video deletion failed: ' . $e->getMessage());
            Response::error('Deletion failed', [], 500);
        }
    }

    /**
     * Download video
     */
    public function download(int $id) {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $video = Database::fetchOne('SELECT * FROM videos WHERE id = ?', [$id]);
            if (!$video || !file_exists($video['video_path'])) {
                Response::notFound('Video not found');
            }

            Logger::audit('download_video', 'video', $id);
            Response::download($video['video_path'], $video['video_filename']);
        } catch (Exception $e) {
            Logger::error('Video download failed: ' . $e->getMessage());
            Response::error('Download failed', [], 500);
        }
    }

    /**
     * Generate thumbnail
     */
    public function generateThumbnail(int $id) {
        if (!Auth::check()) {
            Response::unauthorized();
        }

        try {
            $video = Database::fetchOne('SELECT * FROM videos WHERE id = ?', [$id]);
            if (!$video) {
                Response::notFound('Video not found');
            }

            $uploader = new FileUploader();
            $second = Request::post('second') ?? 1;
            $thumbnailFilename = $uploader->generateThumbnail($video['video_path'], THUMBNAIL_UPLOAD_DIR, $second);

            if (!$thumbnailFilename) {
                Response::error('Failed to generate thumbnail', [], 400);
            }

            Database::update('videos', [
                'thumbnail_path' => THUMBNAIL_UPLOAD_DIR . $thumbnailFilename,
                'thumbnail_url' => APP_URL . '/uploads/thumbnails/' . $thumbnailFilename
            ], ['id' => $id]);

            Response::success(['thumbnail_url' => APP_URL . '/uploads/thumbnails/' . $thumbnailFilename]);
        } catch (Exception $e) {
            Logger::error('Thumbnail generation failed: ' . $e->getMessage());
            Response::error('Generation failed', [], 500);
        }
    }
}
