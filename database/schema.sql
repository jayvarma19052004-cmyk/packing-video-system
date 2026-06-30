-- Packing Video Verification Management System
-- MySQL Database Schema v1.0
-- Run this file to create the complete database structure

CREATE DATABASE IF NOT EXISTS packing_video_system;
USE packing_video_system;

-- ============================================
-- USERS AND AUTHENTICATION
-- ============================================

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    avatar_url VARCHAR(255),
    role_id INT NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    last_activity TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role_id (role_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    email VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'failed') DEFAULT 'success',
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VENDORS AND SUBSCRIPTION
-- ============================================

CREATE TABLE vendors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    business_type VARCHAR(100),
    registration_number VARCHAR(50),
    tax_id VARCHAR(50),
    logo_url VARCHAR(255),
    banner_url VARCHAR(255),
    description TEXT,
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    phone VARCHAR(20),
    website VARCHAR(255),
    contact_person_name VARCHAR(100),
    contact_person_email VARCHAR(100),
    contact_person_phone VARCHAR(20),
    bank_account_holder VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_name VARCHAR(100),
    bank_routing_number VARCHAR(50),
    status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    approval_date TIMESTAMP NULL,
    approved_by INT,
    rejection_reason TEXT,
    total_orders INT DEFAULT 0,
    total_videos INT DEFAULT 0,
    storage_used_gb DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_company_name (company_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    plan_type ENUM('basic', 'professional', 'enterprise') DEFAULT 'basic',
    billing_cycle ENUM('monthly', 'yearly') DEFAULT 'monthly',
    price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'cancelled', 'expired', 'suspended') DEFAULT 'active',
    started_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    max_employees INT,
    max_orders_monthly INT,
    storage_limit_gb INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- WALLETS AND PAYMENTS
-- ============================================

CREATE TABLE wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL UNIQUE,
    balance DECIMAL(12,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('active', 'frozen', 'closed') DEFAULT 'active',
    total_credits DECIMAL(12,2) DEFAULT 0,
    total_debits DECIMAL(12,2) DEFAULT 0,
    last_transaction_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallet_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wallet_id INT NOT NULL,
    transaction_type ENUM('credit', 'debit', 'refund') DEFAULT 'credit',
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255),
    reference_type VARCHAR(50), -- subscription, order, refund, adjustment
    reference_id INT,
    payment_method VARCHAR(50), -- stripe, paypal, bank_transfer, manual
    transaction_id VARCHAR(100) UNIQUE,
    status ENUM('pending', 'completed', 'failed', 'reversed') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EMPLOYEES
-- ============================================

CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    user_id INT,
    employee_id_number VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    role VARCHAR(50), -- packing_user, qc_user, supervisor
    department VARCHAR(100),
    hire_date DATE,
    avatar_url VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    total_videos INT DEFAULT 0,
    total_orders_packed INT DEFAULT 0,
    last_activity TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_user_id (user_id),
    INDEX idx_employee_id (employee_id_number),
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PRODUCTS AND INVENTORY
-- ============================================

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL,
    barcode VARCHAR(100),
    qr_code VARCHAR(255),
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    unit_price DECIMAL(10,2),
    quantity_in_stock INT DEFAULT 0,
    reorder_level INT,
    image_url VARCHAR(255),
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_sku (vendor_id, sku),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_barcode (barcode),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    product_id INT NOT NULL,
    warehouse_location VARCHAR(100),
    quantity INT DEFAULT 0,
    reserved_quantity INT DEFAULT 0,
    available_quantity INT DEFAULT 0,
    last_counted TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ORDERS
-- ============================================

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    shipping_address TEXT,
    order_date DATE NOT NULL,
    due_date DATE,
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'verified', 'failed', 'cancelled') DEFAULT 'pending',
    verification_status ENUM('not_started', 'in_progress', 'verified', 'failed') DEFAULT 'not_started',
    total_items INT DEFAULT 0,
    total_amount DECIMAL(12,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_verification_status (verification_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2),
    line_total DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    employee_id INT NOT NULL,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    status ENUM('assigned', 'started', 'completed') DEFAULT 'assigned',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PACKING SESSIONS AND VIDEOS
-- ============================================

CREATE TABLE packing_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    vendor_id INT NOT NULL,
    employee_id INT NOT NULL,
    session_start TIMESTAMP NOT NULL,
    session_end TIMESTAMP NULL,
    status ENUM('started', 'paused', 'resumed', 'completed', 'cancelled') DEFAULT 'started',
    duration_seconds INT,
    gps_latitude DECIMAL(10,8),
    gps_longitude DECIMAL(11,8),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    packing_session_id INT NOT NULL,
    order_id INT NOT NULL,
    vendor_id INT NOT NULL,
    employee_id INT NOT NULL,
    video_filename VARCHAR(255) NOT NULL,
    video_path VARCHAR(500) NOT NULL,
    video_url VARCHAR(500),
    thumbnail_path VARCHAR(500),
    thumbnail_url VARCHAR(500),
    duration_seconds INT,
    file_size_bytes BIGINT,
    mime_type VARCHAR(50),
    resolution VARCHAR(20), -- 1080p, 720p, 480p, etc
    bitrate VARCHAR(20),
    codec VARCHAR(50),
    status ENUM('uploading', 'processing', 'ready', 'failed', 'archived') DEFAULT 'uploading',
    upload_progress INT DEFAULT 0,
    total_barcode_scans INT DEFAULT 0,
    total_qr_scans INT DEFAULT 0,
    verification_result ENUM('passed', 'failed', 'pending') DEFAULT 'pending',
    verification_comments TEXT,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    storage_location VARCHAR(50), -- local, s3, gcs
    expiry_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (packing_session_id) REFERENCES packing_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_packing_session_id (packing_session_id),
    INDEX idx_order_id (order_id),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_verification_result (verification_result),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE video_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    event_type VARCHAR(50), -- upload_start, upload_progress, upload_complete, compression_start, compression_complete, thumbnail_generated, etc
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    INDEX idx_video_id (video_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BARCODE AND QR SCANNING
-- ============================================

CREATE TABLE barcode_scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    packing_session_id INT NOT NULL,
    order_id INT NOT NULL,
    employee_id INT NOT NULL,
    vendor_id INT NOT NULL,
    barcode_value VARCHAR(100) NOT NULL,
    barcode_format VARCHAR(50), -- ean, upc, code128, code39, etc
    product_id INT,
    scan_timestamp TIMESTAMP NOT NULL,
    scan_duration_ms INT,
    confidence_score DECIMAL(5,2), -- 0-100
    location_x INT,
    location_y INT,
    raw_data TEXT,
    status ENUM('valid', 'invalid', 'duplicate', 'unmatched') DEFAULT 'valid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (packing_session_id) REFERENCES packing_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_video_id (video_id),
    INDEX idx_barcode_value (barcode_value),
    INDEX idx_product_id (product_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qr_scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    packing_session_id INT NOT NULL,
    order_id INT NOT NULL,
    employee_id INT NOT NULL,
    vendor_id INT NOT NULL,
    qr_value VARCHAR(500) NOT NULL,
    qr_type VARCHAR(50), -- order_id, tracking_code, product_info, custom
    scan_timestamp TIMESTAMP NOT NULL,
    scan_duration_ms INT,
    confidence_score DECIMAL(5,2), -- 0-100
    location_x INT,
    location_y INT,
    raw_data TEXT,
    parsed_data JSON,
    status ENUM('valid', 'invalid', 'duplicate', 'unmatched') DEFAULT 'valid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (packing_session_id) REFERENCES packing_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_video_id (video_id),
    INDEX idx_qr_value (qr_value),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTIFICATIONS
-- ============================================

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50), -- order_assigned, video_uploaded, verification_complete, payment_received, etc
    title VARCHAR(255),
    message TEXT,
    reference_type VARCHAR(50), -- order, video, vendor, employee
    reference_id INT,
    action_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT AND ACTIVITY LOGS
-- ============================================

CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    entity_type VARCHAR(50), -- user, order, video, vendor, employee, etc
    entity_id INT,
    action VARCHAR(50), -- create, update, delete, view, download
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'failed') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SYSTEM SETTINGS
-- ============================================

CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    data_type VARCHAR(20), -- string, integer, boolean, json
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE smtp_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    host VARCHAR(100) NOT NULL,
    port INT NOT NULL,
    username VARCHAR(100),
    password VARCHAR(255),
    from_email VARCHAR(100) NOT NULL,
    from_name VARCHAR(100),
    encryption VARCHAR(10), -- tls, ssl, none
    is_active BOOLEAN DEFAULT TRUE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE storage_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver VARCHAR(50), -- local, s3, gcs
    config JSON,
    max_file_size_mb INT,
    retention_days INT,
    auto_cleanup BOOLEAN DEFAULT TRUE,
    compression_enabled BOOLEAN DEFAULT TRUE,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT,
    key_name VARCHAR(100),
    api_key VARCHAR(255) NOT NULL UNIQUE,
    api_secret VARCHAR(255),
    permissions JSON,
    rate_limit INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_vendor_id (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REPORTS AND ANALYTICS
-- ============================================

CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type VARCHAR(50), -- daily_packing, employee, vendor, video, revenue, storage
    generated_by INT,
    vendor_id INT,
    period_start DATE,
    period_end DATE,
    title VARCHAR(255),
    description TEXT,
    report_data JSON,
    file_path VARCHAR(500),
    format VARCHAR(20), -- pdf, excel, csv
    file_size_bytes INT,
    status ENUM('generating', 'completed', 'failed') DEFAULT 'generating',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    downloaded_at TIMESTAMP NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_report_type (report_type),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT,
    date DATE NOT NULL,
    total_orders INT DEFAULT 0,
    completed_orders INT DEFAULT 0,
    verified_videos INT DEFAULT 0,
    failed_videos INT DEFAULT 0,
    total_barcode_scans INT DEFAULT 0,
    total_qr_scans INT DEFAULT 0,
    total_storage_gb DECIMAL(10,2) DEFAULT 0,
    avg_video_duration_seconds INT,
    revenue DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_date (vendor_id, date),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT INITIAL ROLES
-- ============================================

INSERT INTO roles (name, description) VALUES
('super_admin', 'Super Administrator with full system access'),
('vendor', 'Vendor/Company Administrator'),
('vendor_employee', 'Vendor Employee - Can pack orders and record videos'),
('qc_user', 'Quality Control User - Can verify videos and quality'),
('packing_user', 'Packing User - Can record packing videos'),
('customer', 'Customer - Can view their orders');

-- ============================================
-- INSERT INITIAL PERMISSIONS
-- ============================================

INSERT INTO permissions (name, description, category) VALUES
-- Super Admin Permissions
('system.settings.view', 'View system settings', 'system'),
('system.settings.edit', 'Edit system settings', 'system'),
('system.backup', 'Create system backups', 'system'),
('system.logs.view', 'View system logs', 'system'),

-- User Management
('users.view', 'View users', 'users'),
('users.create', 'Create users', 'users'),
('users.edit', 'Edit users', 'users'),
('users.delete', 'Delete users', 'users'),

-- Vendor Management
('vendors.view', 'View vendors', 'vendors'),
('vendors.create', 'Create vendors', 'vendors'),
('vendors.edit', 'Edit vendors', 'vendors'),
('vendors.approve', 'Approve vendor registration', 'vendors'),
('vendors.suspend', 'Suspend vendors', 'vendors'),

-- Employee Management
('employees.view', 'View employees', 'employees'),
('employees.create', 'Create employees', 'employees'),
('employees.edit', 'Edit employees', 'employees'),
('employees.delete', 'Delete employees', 'employees'),

-- Order Management
('orders.view', 'View orders', 'orders'),
('orders.create', 'Create orders', 'orders'),
('orders.edit', 'Edit orders', 'orders'),
('orders.assign', 'Assign orders to employees', 'orders'),
('orders.delete', 'Delete orders', 'orders'),

-- Video Management
('videos.view', 'View videos', 'videos'),
('videos.upload', 'Upload videos', 'videos'),
('videos.verify', 'Verify videos', 'videos'),
('videos.delete', 'Delete videos', 'videos'),
('videos.download', 'Download videos', 'videos'),

-- Reporting
('reports.view', 'View reports', 'reports'),
('reports.generate', 'Generate reports', 'reports'),
('reports.export', 'Export reports', 'reports'),

-- Wallet Management
('wallet.view', 'View wallet', 'wallet'),
('wallet.transaction.view', 'View transactions', 'wallet'),
('wallet.transaction.create', 'Create transactions', 'wallet');

-- ============================================
-- INSERT SUPER ADMIN PERMISSIONS
-- ============================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- ============================================
-- CREATE INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_videos_created_at ON videos(created_at DESC);
CREATE INDEX idx_videos_status_created ON videos(status, created_at DESC);
CREATE INDEX idx_orders_vendor_created ON orders(vendor_id, created_at DESC);
CREATE INDEX idx_barcode_scans_created ON barcode_scans(created_at DESC);
CREATE INDEX idx_qr_scans_created ON qr_scans(created_at DESC);
CREATE INDEX idx_audit_logs_user_date ON audit_logs(user_id, created_at DESC);

-- ============================================
-- CREATE VIEWS FOR COMMON QUERIES
-- ============================================

CREATE VIEW vw_vendor_statistics AS
SELECT 
    v.id,
    v.company_name,
    COUNT(DISTINCT o.id) as total_orders,
    COUNT(DISTINCT vid.id) as total_videos,
    COUNT(DISTINCT e.id) as total_employees,
    SUM(vid.file_size_bytes) as total_storage_bytes,
    w.balance as wallet_balance
FROM vendors v
LEFT JOIN orders o ON v.id = o.vendor_id
LEFT JOIN videos vid ON v.id = vid.vendor_id
LEFT JOIN employees e ON v.id = e.vendor_id
LEFT JOIN wallets w ON v.id = w.vendor_id
GROUP BY v.id;

CREATE VIEW vw_employee_statistics AS
SELECT 
    e.id,
    e.first_name,
    e.last_name,
    e.vendor_id,
    COUNT(DISTINCT ps.id) as total_sessions,
    COUNT(DISTINCT vid.id) as total_videos,
    COUNT(DISTINCT bs.id) as total_barcode_scans,
    COUNT(DISTINCT qs.id) as total_qr_scans,
    SUM(ps.duration_seconds) as total_duration_seconds
FROM employees e
LEFT JOIN packing_sessions ps ON e.id = ps.employee_id
LEFT JOIN videos vid ON e.id = vid.employee_id
LEFT JOIN barcode_scans bs ON e.id = bs.employee_id
LEFT JOIN qr_scans qs ON e.id = qs.employee_id
GROUP BY e.id;

CREATE VIEW vw_order_verification_status AS
SELECT 
    o.id,
    o.order_number,
    o.status as order_status,
    o.verification_status,
    COUNT(DISTINCT vid.id) as video_count,
    SUM(CASE WHEN vid.verification_result = 'passed' THEN 1 ELSE 0 END) as verified_count,
    COUNT(DISTINCT bs.id) as barcode_scans,
    COUNT(DISTINCT qs.id) as qr_scans
FROM orders o
LEFT JOIN videos vid ON o.id = vid.order_id
LEFT JOIN barcode_scans bs ON o.id = bs.order_id
LEFT JOIN qr_scans qs ON o.id = qs.order_id
GROUP BY o.id;

-- ============================================
-- DATABASE TRIGGERS FOR AUTOMATION
-- ============================================

DELIMITER $$

CREATE TRIGGER update_video_count_on_insert
AFTER INSERT ON videos
FOR EACH ROW
BEGIN
    UPDATE employees SET total_videos = total_videos + 1 WHERE id = NEW.employee_id;
    UPDATE vendors SET total_videos = total_videos + 1 WHERE id = NEW.vendor_id;
END$$

CREATE TRIGGER update_video_count_on_delete
AFTER DELETE ON videos
FOR EACH ROW
BEGIN
    UPDATE employees SET total_videos = GREATEST(total_videos - 1, 0) WHERE id = OLD.employee_id;
    UPDATE vendors SET total_videos = GREATEST(total_videos - 1, 0) WHERE id = OLD.vendor_id;
END$$

CREATE TRIGGER update_order_count_on_complete
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        UPDATE employees SET total_orders_packed = total_orders_packed + 1 
        WHERE id IN (SELECT employee_id FROM order_assignments WHERE order_id = NEW.id);
    END IF;
END$$

CREATE TRIGGER update_wallet_on_transaction
AFTER INSERT ON wallet_transactions
FOR EACH ROW
BEGIN
    IF NEW.transaction_type = 'credit' THEN
        UPDATE wallets SET balance = balance + NEW.amount WHERE id = NEW.wallet_id;
    ELSEIF NEW.transaction_type = 'debit' THEN
        UPDATE wallets SET balance = balance - NEW.amount WHERE id = NEW.wallet_id;
    END IF;
END$$

CREATE TRIGGER update_inventory_on_product_change
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    UPDATE inventory 
    SET available_quantity = quantity - reserved_quantity 
    WHERE product_id = NEW.id;
END$$

DELIMITER ;

-- ============================================
-- COMPLETION
-- ============================================

ALTER DATABASE packing_video_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SHOW TABLES;
SHOW VIEWS;