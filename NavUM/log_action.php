<?php
/**
 * Logs a user action to the action_logs table.
 * * @param mysqli $conn The database connection object.
 * @param string $action_type A general category for the action (e.g., 'ROOM_UPDATE').
 * @param string $action_details Specific details about the action.
 * @return bool True on successful log insertion, false otherwise.
 */
function log_user_action($conn, $action_type, $action_details) {
    if (!isset($_SESSION['user_id'])) {
        // Cannot log action without a user ID
        return false;
    }
    
    $account_id = $_SESSION['user_id'];
    
    $sql_log = "INSERT INTO action_logs (accountid, action_type, action_details) VALUES (?, ?, ?)";
    $stmt_log = $conn->prepare($sql_log);
    
    if ($stmt_log) {
        $stmt_log->bind_param("iss", $account_id, $action_type, $action_details);
        $success = $stmt_log->execute();
        $stmt_log->close();
        return $success;
    } else {
        return false;
    }
}
?>