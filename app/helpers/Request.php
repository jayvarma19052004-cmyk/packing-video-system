<?php
/**
 * HTTP Request Handler
 */

class Request {
    /**
     * Get request method
     */
    public static function method(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request path
     */
    public static function path(): string {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return trim(str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $path), '/');
    }

    /**
     * Get query parameter
     */
    public static function query(string $key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get POST parameter
     */
    public static function post(string $key = null, $default = null) {
        // Handle JSON request bodies
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true) ?? [];
            if ($key === null) {
                return $json;
            }
            return $json[$key] ?? $default;
        }

        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get input (GET or POST)
     */
    public static function input(string $key = null, $default = null) {
        $data = array_merge(self::query(), self::post());
        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    /**
     * Get JSON body
     */
    public static function json(): array {
        $json = json_decode(file_get_contents('php://input'), true);
        return is_array($json) ? $json : [];
    }

    /**
     * Get uploaded file
     */
    public static function file(string $key): ?array {
        return $_FILES[$key] ?? null;
    }

    /**
     * Check if request has file
     */
    public static function hasFile(string $key): bool {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get all files
     */
    public static function files(): array {
        return $_FILES;
    }

    /**
     * Get HTTP header
     */
    public static function header(string $key): ?string {
        $key = strtoupper(str_replace('-', '_', $key));
        return $_SERVER['HTTP_' . $key] ?? null;
    }

    /**
     * Check if AJAX request
     */
    public static function isAjax(): bool {
        return self::header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get client IP
     */
    public static function ip(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public static function userAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get referrer
     */
    public static function referrer(): ?string {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }
}
