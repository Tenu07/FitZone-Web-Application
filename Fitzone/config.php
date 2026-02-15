<?php
// Only process this file if constants aren't already defined
if (!defined('DB_HOST')) {
    // Only start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session cookie parameters *before* session_start()
        // Requires HTTPS to be effective
        /* Uncomment and configure if using HTTPS:
        session_set_cookie_params([
            'lifetime' => 0, // 0 = until browser closes
            'path' => '/',
            'domain' => '', // Set your domain if needed
            'secure' => true, // Requires HTTPS
            'httponly' => true, // Prevents JS access
            'samesite' => 'Lax' // Lax or Strict
        ]);
        */
        session_start();
    }

    // Error reporting - turn on during development
    error_reporting(E_ALL);
    ini_set('display_errors', 1); // Set to 0 in production
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/php_errors.log'); // Ensure this path is writable by the web server

    // --- Database Configuration ---
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'fitzone_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');

    // Establish PDO Connection
    try {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        // Use a more user-friendly error page in production
        die("Database connection error. Please try again later.");
    }

    // --- Security Headers ---
    if (!headers_sent()) {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        // Consider adding Content-Security-Policy header for better security
        // header("Content-Security-Policy: default-src 'self'; script-src 'self'; ...");
    }

    // --- Access Control Helper Functions ---

    /**
     * Checks if the logged-in user has the required role.
     * Redirects if not logged in or role doesn't match.
     *
     * @param string|array $required_role The role name (e.g., 'admin') or an array of allowed roles.
     * @param string $redirect_url Optional URL to redirect to if access is denied.
     * Defaults to 'access_denied.php'.
     */
    function require_role($required_role, $redirect_url = 'access_denied.php') {
        // If no user ID in session, they are not logged in
        if (!isset($_SESSION['user_id'])) {
             // Redirect to general login page if not logged in
            header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }

        $user_role = $_SESSION['role'] ?? 'guest'; // Default to guest if role not set

        $allowed = false;
        if (is_array($required_role)) {
            if (in_array($user_role, $required_role)) {
                $allowed = true;
            }
        } else {
            if ($user_role === $required_role) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            // Log attempt? (optional)
            error_log("Access Denied: User ID {$_SESSION['user_id']} (Role: {$user_role}) tried to access page requiring role(s): " . (is_array($required_role) ? implode(', ', $required_role) : $required_role));
            header("Location: " . $redirect_url);
            exit;
        }
    }

    /**
     * Checks if the logged-in user has a specific role without redirecting.
     *
     * @param string $role The role name to check.
     * @return bool True if the user has the role, false otherwise.
     */
    function has_role(string $role): bool {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    /**
     * Checks if the logged-in user has at least one of the specified roles.
     *
     * @param array $roles An array of role names to check.
     * @return bool True if the user has at least one of the roles, false otherwise.
     */
    function has_any_role(array $roles): bool {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        return in_array($_SESSION['role'], $roles);
    }

} // End of the initial check if( !defined('DB_HOST') )
?>