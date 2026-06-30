<?php
/**
 * Application Entry Point
 * Handles all requests and routes to appropriate controllers
 */

session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load core classes
require_once APP_PATH . 'helpers/ErrorHandler.php';
require_once APP_PATH . 'helpers/Database.php';
require_once APP_PATH . 'helpers/Logger.php';
require_once APP_PATH . 'helpers/Auth.php';
require_once APP_PATH . 'helpers/Router.php';
require_once APP_PATH . 'helpers/Request.php';
require_once APP_PATH . 'helpers/Response.php';
require_once APP_PATH . 'helpers/Validator.php';
require_once APP_PATH . 'helpers/Security.php';

// Initialize error handling
set_error_handler(['ErrorHandler', 'handleError']);
set_exception_handler(['ErrorHandler', 'handleException']);
register_shutdown_function(['ErrorHandler', 'handleShutdown']);

// Initialize database connection
Database::getInstance();

// Initialize router
$router = new Router();

// Include route definitions
require_once ROUTES_PATH . 'web.php';
require_once ROUTES_PATH . 'api.php';

// Handle request
try {
    $router->dispatch();
} catch (Exception $e) {
    ErrorHandler::handleException($e);
}

ob_end_flush();