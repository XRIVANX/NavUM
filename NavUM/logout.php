<?php
session_start();
if (isset($_SESSION['user_id'])) {
    require_once 'connect.php'; 
    require_once 'log_action.php';
    log_user_action($conn, 'LOGOUT', 'User logged out.');
    $conn->close();
}
unset($_SESSION['user_id']); 
session_destroy();
header("Location: admin_login_and_register.php");
exit();
?>