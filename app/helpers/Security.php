<?php
/**
 * Security Helper
 * Handles security-related operations
 */

class Security {
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken(string $token): bool {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Generate random token
     */
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Escape output (XSS prevention)
     */
    public static function escape($value): string {
        if (is_array($value)) {
            return array_map([self::class, 'escape'], $value);
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize input
     */
    public static function sanitize(string $input, string $type = 'string'): string {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return (string) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return (string) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT);
            default:
                return filter_var($input, FILTER_SANITIZE_STRING);
        }
    }

    /**
     * Validate email
     */
    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Rate limiting check
     */
    public static function checkRateLimit(string $key, int $limit = API_RATE_LIMIT, int $window = 60): bool {
        $cacheFile = STORAGE_PATH . 'cache/' . md5($key) . '.cache';
        $directory = dirname($cacheFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $now = time();
        $requests = [];

        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));
            if (is_array($data) && $data['expires'] > $now) {
                $requests = $data['requests'];
            }
        }

        // Remove old requests
        $requests = array_filter($requests, fn($t) => $t > ($now - $window));

        if (count($requests) >= $limit) {
            return false;
        }

        // Add current request
        $requests[] = $now;

        // Save to cache
        $cacheData = [
            'requests' => $requests,
            'expires' => $now + $window
        ];
        file_put_contents($cacheFile, serialize($cacheData), LOCK_EX);

        return true;
    }

    /**
     * Generate JWT token
     */
    public static function generateJWT(array $payload): string {
        $header = [
            'alg' => JWT_ALGORITHM,
            'typ' => 'JWT'
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;

        $headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            JWT_SECRET,
            true
        );
        $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Verify JWT token
     */
    public static function verifyJWT(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            JWT_SECRET,
            true
        );
        $expectedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }

        // Decode payload
        $payload = json_decode(
            base64_decode(strtr($payloadEncoded, '-_', '+/')),
            true
        );

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array {
        $errors = [];

        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $errors[] = 'No file provided';
        }

        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadError($file['error']);
        }

        if (isset($file['size']) && $file['size'] > $maxSize) {
            $errors[] = 'File size exceeds limit';
        }

        if (isset($file['type']) && !in_array($file['type'], $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }

        return $errors;
    }

    /**
     * Get upload error message
     */
    private static function getUploadError(int $errorCode): string {
        $errors = [
            UPLOAD_ERR_OK => 'Upload successful',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload',
        ];
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
}
