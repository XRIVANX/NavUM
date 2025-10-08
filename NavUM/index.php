<?php

define('BASE_PATH', __DIR__ . '/');

$page = $_GET['page'] ?? 'landing';
$view_file = '';

switch ($page) {
    case 'landing':
        $view_file = BASE_PATH . 'landing_view.html'; 
        break;

    case 'user':
        $view_file = BASE_PATH . 'starting_page.php';
        break;

    case 'admin_auth':
        $view_file = BASE_PATH . 'admin_login_and_register.php';
        break;

    case 'admin_dashboard':
        $view_file = BASE_PATH . 'admin_page.php';
        break;

    case 'edit_room':
        $view_file = BASE_PATH . 'edit_room.php';
        break;
        
    case 'logout':
        $view_file = BASE_PATH . 'logout.php';
        break;

    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>The requested page '$page' was not found.</p>";
        exit();
}

if (file_exists($view_file)) {
    if ($view_file === BASE_PATH . 'landing_view.html') {
        readfile($view_file);
    } else {
        include $view_file;
    }
} else {
    http_response_code(500);
    echo "<h1>Error</h1><p>Application file is missing for route: '$page'.</p>";
}
?>