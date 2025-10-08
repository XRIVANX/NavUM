<?php

define('BASE_PATH', __DIR__ . '/');

$page = $_GET['page'] ?? 'landing';
$view_file = '';

switch ($page) {
    case 'landing':
        // The main landing page view (renamed from landing_page.html)
        $view_file = BASE_PATH . 'landing_view.html'; 
        break;

    case 'user':
        // The user application page
        $view_file = BASE_PATH . 'starting_page.php';
        break;

    case 'admin_auth':
        // Admin login and registration page
        $view_file = BASE_PATH . 'admin_login_and_register.php';
        break;

    case 'admin_dashboard':
        // The main admin management page
        $view_file = BASE_PATH . 'admin_page.php';
        break;

    case 'edit_room':
        // Admin room editing page
        // Note: This route requires a room_id parameter (e.g., index.php?page=edit_room&room_id=123)
        $view_file = BASE_PATH . 'edit_room.php';
        break;
        
    case 'logout':
        // Handles session destruction and redirects
        $view_file = BASE_PATH . 'logout.php';
        break;

    default:
        // 404 Not Found handler
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>The requested page '$page' was not found.</p>";
        exit();
}
// --- End Route Definitions ---

// Include the corresponding file
if (file_exists($view_file)) {
    // If it's a static HTML file, handle it as HTML output
    if ($view_file === BASE_PATH . 'landing_view.html') {
        readfile($view_file);
    } else {
        // For PHP files, execute them
        include $view_file;
    }
} else {
    http_response_code(500);
    echo "<h1>Error</h1><p>Application file is missing for route: '$page'.</p>";
}
?>