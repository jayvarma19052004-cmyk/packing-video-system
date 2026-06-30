<?php
/**
 * Web Routes
 * Define all web application routes
 */

$router = new Router();

// ============================================
// Public Routes
// ============================================

$router->get('/', function() {
    require VIEWS_PATH . 'welcome.php';
});

$router->get('/login', function() {
    if (Auth::check()) {
        Response::redirect(APP_URL . '/dashboard');
    }
    require VIEWS_PATH . 'auth/login.php';
});

$router->post('/login', 'AuthController@login');

$router->get('/register', function() {
    if (Auth::check()) {
        Response::redirect(APP_URL . '/dashboard');
    }
    require VIEWS_PATH . 'auth/register.php';
});

$router->post('/register', 'AuthController@register');

$router->get('/forgot-password', function() {
    if (Auth::check()) {
        Response::redirect(APP_URL . '/dashboard');
    }
    require VIEWS_PATH . 'auth/forgot-password.php';
});

$router->post('/forgot-password', 'AuthController@forgotPassword');

$router->get('/reset-password', function() {
    if (Auth::check()) {
        Response::redirect(APP_URL . '/dashboard');
    }
    require VIEWS_PATH . 'auth/reset-password.php';
});

$router->post('/reset-password', 'AuthController@resetPassword');

$router->get('/logout', function() {
    Auth::logout();
    Response::redirect(APP_URL . '/login');
});

// ============================================
// Super Admin Routes
// ============================================

$router->get('/admin/dashboard', 'Admin/DashboardController@index');
$router->get('/admin/vendors', 'Admin/VendorController@index');
$router->get('/admin/vendors/{id}', 'Admin/VendorController@show');
$router->post('/admin/vendors/{id}/approve', 'Admin/VendorController@approve');
$router->post('/admin/vendors/{id}/reject', 'Admin/VendorController@reject');
$router->post('/admin/vendors/{id}/suspend', 'Admin/VendorController@suspend');

$router->get('/admin/users', 'Admin/UserController@index');
$router->get('/admin/users/{id}', 'Admin/UserController@show');
$router->post('/admin/users', 'Admin/UserController@store');
$router->put('/admin/users/{id}', 'Admin/UserController@update');
$router->delete('/admin/users/{id}', 'Admin/UserController@destroy');

$router->get('/admin/analytics', 'Admin/AnalyticsController@index');
$router->get('/admin/reports', 'Admin/ReportController@index');
$router->get('/admin/reports/generate', 'Admin/ReportController@generate');
$router->get('/admin/reports/{id}/download', 'Admin/ReportController@download');

$router->get('/admin/wallet', 'Admin/WalletController@index');
$router->get('/admin/transactions', 'Admin/WalletController@transactions');
$router->post('/admin/transactions', 'Admin/WalletController@createTransaction');

$router->get('/admin/logs/login', 'Admin/LogController@loginLogs');
$router->get('/admin/logs/audit', 'Admin/LogController@auditLogs');
$router->get('/admin/logs/activity', 'Admin/LogController@activityLogs');

$router->get('/admin/settings', 'Admin/SettingsController@index');
$router->post('/admin/settings', 'Admin/SettingsController@update');
$router->get('/admin/settings/smtp', 'Admin/SettingsController@smtpSettings');
$router->post('/admin/settings/smtp', 'Admin/SettingsController@updateSmtpSettings');
$router->get('/admin/settings/storage', 'Admin/SettingsController@storageSettings');
$router->post('/admin/settings/storage', 'Admin/SettingsController@updateStorageSettings');

// ============================================
// Vendor Routes
// ============================================

$router->get('/vendor/dashboard', 'Vendor/DashboardController@index');

$router->get('/vendor/employees', 'Vendor/EmployeeController@index');
$router->get('/vendor/employees/create', 'Vendor/EmployeeController@create');
$router->post('/vendor/employees', 'Vendor/EmployeeController@store');
$router->get('/vendor/employees/{id}/edit', 'Vendor/EmployeeController@edit');
$router->put('/vendor/employees/{id}', 'Vendor/EmployeeController@update');
$router->delete('/vendor/employees/{id}', 'Vendor/EmployeeController@destroy');

$router->get('/vendor/orders', 'Vendor/OrderController@index');
$router->get('/vendor/orders/{id}', 'Vendor/OrderController@show');
$router->post('/vendor/orders/{id}/assign', 'Vendor/OrderController@assign');

$router->get('/vendor/inventory', 'Vendor/InventoryController@index');
$router->get('/vendor/products', 'Vendor/ProductController@index');
$router->post('/vendor/products', 'Vendor/ProductController@store');

$router->get('/vendor/barcodes', 'Vendor/BarcodeController@index');
$router->post('/vendor/barcodes/generate', 'Vendor/BarcodeController@generate');

$router->get('/vendor/videos', 'Vendor/VideoController@index');
$router->get('/vendor/videos/{id}', 'Vendor/VideoController@show');

$router->get('/vendor/wallet', 'Vendor/WalletController@index');
$router->get('/vendor/wallet/transactions', 'Vendor/WalletController@transactions');

$router->get('/vendor/subscription', 'Vendor/SubscriptionController@index');
$router->post('/vendor/subscription/upgrade', 'Vendor/SubscriptionController@upgrade');

$router->get('/vendor/reports', 'Vendor/ReportController@index');
$router->get('/vendor/reports/generate', 'Vendor/ReportController@generate');
$router->get('/vendor/reports/{id}/download', 'Vendor/ReportController@download');

// ============================================
// Employee Routes
// ============================================

$router->get('/employee/dashboard', 'Employee/DashboardController@index');

$router->get('/employee/orders', 'Employee/OrderController@index');
$router->get('/employee/orders/{id}', 'Employee/OrderController@show');

$router->get('/employee/packing/{orderId}', 'Employee/PackingController@start');
$router->get('/employee/packing/{orderId}/camera', 'Employee/PackingController@camera');
$router->post('/employee/packing/{orderId}/upload', 'Employee/PackingController@upload');

// ============================================
// Error Routes
// ============================================

$router->get('/404', function() {
    require VIEWS_PATH . 'errors/404.php';
});

$router->get('/500', function() {
    require VIEWS_PATH . 'errors/500.php';
});
