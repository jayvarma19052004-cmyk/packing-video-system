<?php
/**
 * Admin Dashboard Controller
 */

class Admin_DashboardController {
    /**
     * Show dashboard
     */
    public function index() {
        if (!Auth::check() || !Auth::hasRole('super_admin')) {
            Response::redirect(APP_URL . '/login');
        }

        // Get statistics
        $stats = [
            'total_vendors' => Database::fetchOne('SELECT COUNT(*) as count FROM vendors')['count'],
            'pending_vendors' => Database::fetchOne('SELECT COUNT(*) as count FROM vendors WHERE status = "pending"')['count'],
            'total_employees' => Database::fetchOne('SELECT COUNT(*) as count FROM employees')['count'],
            'total_orders' => Database::fetchOne('SELECT COUNT(*) as count FROM orders')['count'],
            'completed_orders' => Database::fetchOne('SELECT COUNT(*) as count FROM orders WHERE status = "completed"')['count'],
            'total_videos' => Database::fetchOne('SELECT COUNT(*) as count FROM videos')['count'],
            'verified_videos' => Database::fetchOne('SELECT COUNT(*) as count FROM videos WHERE verification_result = "passed"')['count'],
            'total_users' => Database::fetchOne('SELECT COUNT(*) as count FROM users')['count'],
            'active_subscriptions' => Database::fetchOne('SELECT COUNT(*) as count FROM subscriptions WHERE status = "active"')['count'],
        ];

        // Get recent vendors
        $recentVendors = Database::fetchAll(
            'SELECT v.*, u.email FROM vendors v JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC LIMIT 10'
        );

        // Get pending approvals
        $pendingVendors = Database::fetchAll(
            'SELECT v.*, u.email FROM vendors v JOIN users u ON v.user_id = u.id WHERE v.status = "pending" ORDER BY v.created_at ASC LIMIT 10'
        );

        // Get recent videos
        $recentVideos = Database::fetchAll(
            'SELECT v.*, e.first_name, e.last_name, o.order_number FROM videos v ' .
            'JOIN employees e ON v.employee_id = e.id ' .
            'JOIN orders o ON v.order_id = o.id ' .
            'ORDER BY v.created_at DESC LIMIT 10'
        );

        require VIEWS_PATH . 'admin/dashboard.php';
    }
}
