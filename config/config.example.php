<?php
/**
 * Application Configuration
 * Copy this file to config.php and update values for your environment
 */

// Environment
define('APP_ENV', 'production'); // development, staging, production
define('DEBUG', false);
define('APP_URL', 'http://localhost');
define('APP_NAME', 'Packing Video Verification System');
define('APP_VERSION', '1.0.0');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'packing_video_system');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');
define('DB_POOL_SIZE', 5);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_COOKIE_SECURE', true);
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Lax');
define('SESSION_NAME', 'PACKING_SESSION');

// File Upload Configuration
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024); // 500MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('VIDEO_UPLOAD_DIR', __DIR__ . '/../public/uploads/videos/');
define('THUMBNAIL_UPLOAD_DIR', __DIR__ . '/../public/uploads/thumbnails/');
define('TEMP_UPLOAD_DIR', __DIR__ . '/../storage/temp/');
define('MAX_VIDEO_DURATION', 3600); // 1 hour

// Video Processing
define('VIDEO_QUALITY', 'medium'); // low, medium, high
define('VIDEO_BITRATE', '2500k');
define('THUMBNAIL_QUALITY', 85);
define('ENABLE_VIDEO_COMPRESSION', true);

// Security
define('CSRF_TOKEN_NAME', '_csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_HASH_ALGO', 'bcrypt');
define('PASSWORD_HASH_COST', 12);
define('API_RATE_LIMIT', 100); // requests per minute
define('FAILED_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 1800); // 30 minutes
define('ENABLE_2FA', false);

// JWT Configuration
define('JWT_SECRET', 'your-super-secret-key-change-in-production');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY', 86400); // 24 hours

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@packingvideo.com');
define('SMTP_FROM_NAME', 'Packing Video System');
define('SMTP_ENCRYPTION', 'tls');

// Storage Configuration
define('STORAGE_DRIVER', 'local'); // local, s3, gcs
define('STORAGE_PATH', __DIR__ . '/../storage/');
define('STORAGE_RETENTION_DAYS', 365);
define('STORAGE_AUTO_CLEANUP', true);
define('STORAGE_QUOTA_GB', 1000);

// AWS S3 (if using S3 storage)
define('AWS_ACCESS_KEY', '');
define('AWS_SECRET_KEY', '');
define('AWS_REGION', 'us-east-1');
define('AWS_BUCKET', 'packing-videos');

// Barcode & QR Configuration
define('BARCODE_DETECTION_API', true);
define('BARCODE_ZXING_FALLBACK', true);
define('PREVENT_DUPLICATE_SCANS', true);
define('DUPLICATE_SCAN_WINDOW', 5); // seconds
define('SUPPORTED_BARCODE_FORMATS', ['qr', 'ean', 'upc', 'code128', 'code39', 'datamatrix']);

// Payment Configuration
define('PAYMENT_GATEWAY', 'stripe'); // stripe, razorpay, paypal
define('STRIPE_PUBLIC_KEY', '');
define('STRIPE_SECRET_KEY', '');
define('CURRENCY', 'USD');

// Subscription Plans
define('SUBSCRIPTION_PLANS', [
    'basic' => [
        'name' => 'Basic',
        'monthly' => 99,
        'yearly' => 990,
        'max_employees' => 5,
        'max_orders_monthly' => 1000,
        'storage_gb' => 100
    ],
    'professional' => [
        'name' => 'Professional',
        'monthly' => 299,
        'yearly' => 2990,
        'max_employees' => 50,
        'max_orders_monthly' => 10000,
        'storage_gb' => 500
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'monthly' => 999,
        'yearly' => 9990,
        'max_employees' => 500,
        'max_orders_monthly' => 100000,
        'storage_gb' => 2000
    ]
]);

// WhatsApp Configuration
define('WHATSAPP_ENABLED', false);
define('WHATSAPP_API_URL', 'https://api.whatsapp.com');
define('WHATSAPP_API_KEY', '');
define('WHATSAPP_PHONE', '');
define('WHATSAPP_VERIFY_TOKEN', '');

// Notification Settings
define('NOTIFICATION_EMAIL', true);
define('NOTIFICATION_SMS', false);
define('NOTIFICATION_WHATSAPP', false);
define('NOTIFICATION_IN_APP', true);

// Logging
define('LOG_FILE', __DIR__ . '/../storage/logs/app.log');
define('LOG_LEVEL', 'info'); // debug, info, warning, error
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_BACKUP_FILES', 5);
define('AUDIT_LOG_FILE', __DIR__ . '/../storage/logs/audit.log');

// API Configuration
define('API_VERSION', 'v1');
define('API_RESPONSE_FORMAT', 'json');
define('API_TIMEOUT', 30);
define('API_MAX_PAGE_SIZE', 100);

// Pagination
define('ITEMS_PER_PAGE', 20);
define('MAX_ITEMS_PER_PAGE', 100);
define('DEFAULT_SORT_ORDER', 'DESC');

// Time Zone
define('TIMEZONE', 'UTC');

// Maintenance Mode
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'System is under maintenance. Please try again later.');

// Feature Flags
define('FEATURE_VIDEO_COMPRESSION', true);
define('FEATURE_BARCODE_DETECTION', true);
define('FEATURE_QR_DETECTION', true);
define('FEATURE_GPS_TRACKING', false);
define('FEATURE_REPORTS', true);
define('FEATURE_ANALYTICS', true);
define('FEATURE_DARK_MODE', true);
define('FEATURE_EXPORT_PDF', true);
define('FEATURE_EXPORT_EXCEL', true);
define('FEATURE_EXPORT_CSV', true);

// Path Configuration
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app/');
define('PUBLIC_PATH', ROOT_PATH . '/public/');
define('CONFIG_PATH', ROOT_PATH . '/config/');
define('ROUTES_PATH', ROOT_PATH . '/routes/');
define('API_PATH', ROOT_PATH . '/api/');
define('DATABASE_PATH', ROOT_PATH . '/database/');
define('VIEWS_PATH', APP_PATH . 'views/');
define('MODELS_PATH', APP_PATH . 'models/');
define('CONTROLLERS_PATH', APP_PATH . 'controllers/');
define('HELPERS_PATH', APP_PATH . 'helpers/');

// Set Default Timezone
date_default_timezone_set(TIMEZONE);

// Set Error Reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
}

// Enable output buffering
ob_start();

return [];