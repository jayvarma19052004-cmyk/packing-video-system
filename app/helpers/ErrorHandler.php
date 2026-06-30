<?php
/**
 * Global Error Handler
 */

class ErrorHandler {
    /**
     * Handle errors
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        $errorType = match($errno) {
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            default => 'Unknown Error'
        };

        Logger::error("$errorType: $errstr in $errfile:$errline");

        if (DEBUG) {
            echo "<h1>$errorType</h1>";
            echo "<p>$errstr</p>";
            echo "<p>File: $errfile</p>";
            echo "<p>Line: $errline</p>";
        }

        return true;
    }

    /**
     * Handle exceptions
     */
    public static function handleException(Throwable $e): void {
        Logger::error('Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if (Request::isAjax()) {
            Response::error($e->getMessage(), [], 500);
        }

        if (DEBUG) {
            echo "<h1>Exception</h1>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<p>File: " . $e->getFile() . "</p>";
            echo "<p>Line: " . $e->getLine() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            Response::html(self::getErrorPage(), 500);
        }
    }

    /**
     * Handle shutdown
     */
    public static function handleShutdown(): void {
        $error = error_get_last();
        if ($error) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * Get error page
     */
    private static function getErrorPage(): string {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; }
                h1 { color: #d32f2f; }
            </style>
        </head>
        <body>
            <h1>An error occurred</h1>
            <p>Please contact support if the problem persists.</p>
        </body>
        </html>
        HTML;
    }
}
