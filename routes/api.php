<?php
/**
 * API Routes
 * Define all REST API endpoints
 */

$router = new Router();

// ============================================
// Authentication APIs
// ============================================

$router->post('/api/v1/auth/login', 'Api/AuthController@login');
$router->post('/api/v1/auth/register', 'Api/AuthController@register');
$router->post('/api/v1/auth/logout', 'Api/AuthController@logout');
$router->post('/api/v1/auth/refresh', 'Api/AuthController@refresh');
$router->post('/api/v1/auth/forgot-password', 'Api/AuthController@forgotPassword');
$router->post('/api/v1/auth/reset-password', 'Api/AuthController@resetPassword');

// ============================================
// Order APIs
// ============================================

$router->get('/api/v1/orders', 'Api/OrderController@index');
$router->get('/api/v1/orders/{id}', 'Api/OrderController@show');
$router->post('/api/v1/orders', 'Api/OrderController@store');
$router->put('/api/v1/orders/{id}', 'Api/OrderController@update');
$router->delete('/api/v1/orders/{id}', 'Api/OrderController@destroy');
$router->post('/api/v1/orders/{id}/assign', 'Api/OrderController@assign');
$router->get('/api/v1/orders/{id}/verify', 'Api/OrderController@verifyStatus');

// ============================================
// Video APIs
// ============================================

$router->post('/api/v1/videos/upload', 'Api/VideoController@upload');
$router->get('/api/v1/videos/{id}', 'Api/VideoController@show');
$router->get('/api/v1/videos/{id}/download', 'Api/VideoController@download');
$router->post('/api/v1/videos/{id}/verify', 'Api/VideoController@verify');
$router->delete('/api/v1/videos/{id}', 'Api/VideoController@destroy');
$router->post('/api/v1/videos/{id}/thumbnail', 'Api/VideoController@generateThumbnail');

// ============================================
// Barcode APIs
// ============================================

$router->post('/api/v1/barcodes/scan', 'Api/BarcodeController@scan');
$router->get('/api/v1/barcodes/{videoId}', 'Api/BarcodeController@getScans');
$router->post('/api/v1/barcodes/verify', 'Api/BarcodeController@verify');

// ============================================
// QR Code APIs
// ============================================

$router->post('/api/v1/qrcodes/scan', 'Api/QRController@scan');
$router->get('/api/v1/qrcodes/{videoId}', 'Api/QRController@getScans');
$router->post('/api/v1/qrcodes/verify', 'Api/QRController@verify');

// ============================================
// Employee APIs
// ============================================

$router->get('/api/v1/employees', 'Api/EmployeeController@index');
$router->get('/api/v1/employees/{id}', 'Api/EmployeeController@show');
$router->post('/api/v1/employees', 'Api/EmployeeController@store');
$router->put('/api/v1/employees/{id}', 'Api/EmployeeController@update');
$router->delete('/api/v1/employees/{id}', 'Api/EmployeeController@destroy');

// ============================================
// Wallet APIs
// ============================================

$router->get('/api/v1/wallet', 'Api/WalletController@getBalance');
$router->get('/api/v1/wallet/transactions', 'Api/WalletController@getTransactions');
$router->post('/api/v1/wallet/transaction', 'Api/WalletController@createTransaction');

// ============================================
// Report APIs
// ============================================

$router->get('/api/v1/reports', 'Api/ReportController@index');
$router->post('/api/v1/reports/generate', 'Api/ReportController@generate');
$router->get('/api/v1/reports/{id}/download', 'Api/ReportController@download');
$router->get('/api/v1/reports/daily-packing', 'Api/ReportController@dailyPacking');
$router->get('/api/v1/reports/employee', 'Api/ReportController@employee');
$router->get('/api/v1/reports/vendor', 'Api/ReportController@vendor');
$router->get('/api/v1/reports/revenue', 'Api/ReportController@revenue');
$router->get('/api/v1/reports/storage', 'Api/ReportController@storage');

// ============================================
// Dashboard APIs
// ============================================

$router->get('/api/v1/dashboard/stats', 'Api/DashboardController@stats');
$router->get('/api/v1/dashboard/recent-videos', 'Api/DashboardController@recentVideos');
$router->get('/api/v1/dashboard/pending-orders', 'Api/DashboardController@pendingOrders');
$router->get('/api/v1/dashboard/employees-activity', 'Api/DashboardController@employeesActivity');

// ============================================
// Inventory APIs
// ============================================

$router->get('/api/v1/inventory', 'Api/InventoryController@index');
$router->get('/api/v1/products', 'Api/ProductController@index');
$router->post('/api/v1/products', 'Api/ProductController@store');
$router->put('/api/v1/products/{id}', 'Api/ProductController@update');

// ============================================
// Notification APIs
// ============================================

$router->get('/api/v1/notifications', 'Api/NotificationController@index');
$router->post('/api/v1/notifications/{id}/read', 'Api/NotificationController@markAsRead');
$router->post('/api/v1/notifications/read-all', 'Api/NotificationController@markAllAsRead');
$router->delete('/api/v1/notifications/{id}', 'Api/NotificationController@destroy');

// ============================================
// Settings APIs
// ============================================

$router->get('/api/v1/settings', 'Api/SettingsController@index');
$router->post('/api/v1/settings', 'Api/SettingsController@update');
$router->get('/api/v1/settings/storage', 'Api/SettingsController@storageUsage');
