<?php
/**
 * HTTP Response Handler
 */

class Response {
    private static int $statusCode = 200;
    private static array $headers = [];

    /**
     * Set HTTP status code
     */
    public static function status(int $code): void {
        self::$statusCode = $code;
        http_response_code($code);
    }

    /**
     * Set response header
     */
    public static function header(string $key, string $value): void {
        self::$headers[$key] = $value;
        header($key . ': ' . $value);
    }

    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void {
        self::status($statusCode);
        self::header('Content-Type', 'application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send success response
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => []
        ], $statusCode);
    }

    /**
     * Send error response
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): void {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Send HTML response
     */
    public static function html(string $content, int $statusCode = 200): void {
        self::status($statusCode);
        self::header('Content-Type', 'text/html; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Send file download
     */
    public static function download(string $filePath, string $fileName = null): void {
        if (!file_exists($filePath)) {
            self::error('File not found', [], 404);
        }

        $fileName = $fileName ?? basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        self::header('Content-Type', $mimeType);
        self::header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        self::header('Content-Length', (string) $fileSize);
        self::header('Cache-Control', 'no-cache, must-revalidate');
        self::header('Pragma', 'public');

        readfile($filePath);
        exit;
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $statusCode = 302): void {
        self::header('Location', $url);
        self::status($statusCode);
        exit;
    }

    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Not Found'): void {
        self::error($message, [], 404);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void {
        self::error($message, [], 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): void {
        self::error($message, [], 403);
    }
}
