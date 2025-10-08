<?php

$user_id = $_SESSION['user_id'];
$sql = "SELECT firstname, lastname FROM accounts WHERE accountid = '$user_id'";
$result = $conn->query($sql);

$user_firstname = '';
$user_lastname = '';

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_firstname = htmlspecialchars($row['firstname']);
    $user_lastname = htmlspecialchars($row['lastname']);
}


$room_groups_data = [];

$sql_rooms = "SELECT room_id, room_name, room_type, room_status, room_group_id, floor_id FROM rooms ORDER BY room_group_id, floor_id, room_id";
$result_rooms = $conn->query($sql_rooms);

if ($result_rooms) {
    while ($room = $result_rooms->fetch_assoc()) {
        $group = $room['room_group_id'];
        $floor = $room['floor_id'];
        $room_groups_data[$group][$floor][] = $room;
    }
}

$sql_counts = "
    SELECT 
        SUM(CASE WHEN room_status = 'Available' THEN 1 ELSE 0 END) AS available_count,
        SUM(CASE WHEN room_status = 'Maintenance' THEN 1 ELSE 0 END) AS maintenance_count,
        SUM(CASE WHEN room_status = 'Setting Up' THEN 1 ELSE 0 END) AS setting_up_count,
        COUNT(room_id) AS total_count
    FROM rooms;
";

$room_counts = [
    'available_count' => 0, 
    'maintenance_count' => 0, 
    'setting_up_count' => 0, 
    'total_count' => 0
];
$result_counts = $conn->query($sql_counts);

if ($result_counts && $result_counts->num_rows > 0) {
    $room_counts = $result_counts->fetch_assoc();
}

$sql_logs = "
    SELECT 
        a.firstname, 
        a.lastname, 
        l.action_type, 
        l.action_details, 
        l.timestamp 
    FROM action_logs l
    JOIN accounts a ON l.accountid = a.accountid
    WHERE l.accountid != 0
    ORDER BY l.timestamp DESC 
    LIMIT 100;
";

$history_logs = [];
$log_result = @$conn->query($sql_logs); 

if ($log_result) {
    while ($log = $log_result->fetch_assoc()) {
        $history_logs[] = $log;
    }
}

?>