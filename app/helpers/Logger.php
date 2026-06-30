<?php
/**
 * Logging Service
 * Handles all application logging
 */

class Logger {
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';

    private static array $logLevels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4,
    ];

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log audit action
     */
    public static function audit(string $action, string $entityType, int $entityId, array $oldValues = [], array $newValues = []): void {
        $userId = Auth::user() ? Auth::user()['id'] : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $auditData = [
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
            'new_values' => !empty($newValues) ? json_encode($newValues) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => 'success'
        ];

        try {
            Database::insert('audit_logs', $auditData);
        } catch (Exception $e) {
            self::error('Failed to log audit: ' . $e->getMessage());
        }
    }

    /**
     * Main logging function
     */
    private static function log(string $level, string $message, array $context = []): void {
        if (self::$logLevels[$level] < self::$logLevels[LOG_LEVEL]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message";

        if (!empty($context)) {
            $logMessage .= ' | Context: ' . json_encode($context);
        }

        // Write to log file
        self::writeToFile($logMessage);

        // Write to stdout in debug mode
        if (DEBUG) {
            error_log($logMessage);
        }
    }

    /**
     * Write to log file
     */
    private static function writeToFile(string $message): void {
        $logFile = LOG_FILE;
        $directory = dirname($logFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check file size and rotate if needed
        if (file_exists($logFile) && filesize($logFile) > LOG_MAX_SIZE) {
            self::rotateLog();
        }

        $message .= PHP_EOL;
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotate log files
     */
    private static function rotateLog(): void {
        $logFile = LOG_FILE;
        $directory = dirname($logFile);
        $basename = basename($logFile);
        $ext = pathinfo($logFile, PATHINFO_EXTENSION);
        $name = pathinfo($logFile, PATHINFO_FILENAME);

        // Remove oldest backup if limit reached
        for ($i = LOG_BACKUP_FILES; $i >= 1; $i--) {
            $oldFile = $directory . '/' . $name . '.' . $i . '.' . $ext;
            if (file_exists($oldFile)) {
                if ($i >= LOG_BACKUP_FILES) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $directory . '/' . $name . '.' . ($i + 1) . '.' . $ext);
                }
            }
        }

        // Rename current log
        if (file_exists($logFile)) {
            rename($logFile, $directory . '/' . $name . '.1.' . $ext);
        }
    }
}
