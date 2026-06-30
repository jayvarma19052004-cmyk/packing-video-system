<?php
/**
 * File Upload Handler
 */

class FileUploader {
    private array $errors = [];

    /**
     * Upload video file
     */
    public function uploadVideo(array $file, string $destination): ?string {
        // Validate upload
        $validation = Security::validateFileUpload($file, ALLOWED_VIDEO_TYPES, MAX_UPLOAD_SIZE);
        if (!empty($validation)) {
            $this->errors = $validation;
            return null;
        }

        // Create destination directory
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Generate unique filename
        $filename = 'video_' . time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filepath = $destination . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->errors[] = 'Failed to move uploaded file';
            return null;
        }

        // Set permissions
        chmod($filepath, 0644);

        return $filename;
    }

    /**
     * Generate video thumbnail
     */
    public function generateThumbnail(string $videoPath, string $thumbnailPath, int $second = 1): ?string {
        if (!file_exists($videoPath)) {
            $this->errors[] = 'Video file not found';
            return null;
        }

        // Create thumbnail directory
        if (!is_dir($thumbnailPath)) {
            mkdir($thumbnailPath, 0755, true);
        }

        // Generate thumbnail filename
        $filename = 'thumb_' . time() . '_' . uniqid() . '.jpg';
        $filepath = $thumbnailPath . $filename;

        // Use FFmpeg to generate thumbnail
        $command = "ffmpeg -i {$videoPath} -ss {$second} -vframes 1 -vf \"scale=320:180\" {$filepath} 2>&1";
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            // Fallback: try ImageMagick
            $command = "convert {$videoPath}[0] -resize 320x180 {$filepath}";
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->errors[] = 'Failed to generate thumbnail';
                return null;
            }
        }

        chmod($filepath, 0644);
        return $filename;
    }

    /**
     * Get upload errors
     */
    public function getErrors(): array {
        return $this->errors;
    }
}
