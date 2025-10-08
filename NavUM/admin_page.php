<?php
include("connect.php");
include("log_action.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=admin_auth");
    exit();
}

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
    WHERE l.accountid = '$user_id'
    ORDER BY l.timestamp DESC 
    LIMIT 100;
";

$history_logs = [];
// Using @ to suppress errors if 'action_logs' table is missing during development
$log_result = @$conn->query($sql_logs); 

if ($log_result) {
    while ($log = $log_result->fetch_assoc()) {
        $history_logs[] = $log;
    }
}
$conn->close();


?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="style.css"/>
    <link rel="stylesheet" href="general.css"/>
    <title>Admin's Page</title>
  </head>
  <body class="admin-page">
    <section class = "nav">
        <h1 class = "placeholder-logo">NavUM</h1>
        <ul>
            <li class = "dashboard-button" id="dashboard-button">Dashboard</li>
            <li class = "manage-button" id="manage-button">Manage Rooms</li>
            <li class = "history-button" id="history-button">History Logs</li>
            <li class = "user-name-container"><span class="user-name"><?php echo $user_firstname . " " . $user_lastname; ?></span></li>
            <li class="log-out-button"><a href="index.php?page=logout">Log Out</a></li>
        </ul>
    </section>

    <section class = "dashboard-page" id="dashboard-page">
        <div class = "rooms-overview">
            <h2>Rooms Status Overview (Total Rooms: <?php echo htmlspecialchars($room_counts['total_count']); ?>)</h2>
            <div class="status-cards">
                <div class="status-card available">
                    <h3>Available</h3>
                    <p class="count"><?php echo htmlspecialchars($room_counts['available_count']); ?></p>
                </div>
                <div class="status-card maintenance">
                    <h3>In Maintenance</h3>
                    <p class="count"><?php echo htmlspecialchars($room_counts['maintenance_count']); ?></p>
                </div>
                <div class="status-card setting-up">
                    <h3>Setting Up</h3>
                    <p class="count"><?php echo htmlspecialchars($room_counts['setting_up_count']); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class = "history-page" id="history-page" style="display: none;">
        <div class = "history-logs">
            <h2>Your Recent Activity Logs (Last 100 Entries)</h2>
            <div class="log-container">
                <?php if (empty($history_logs)): ?>
                    <p class="no-logs">
                        No activity logs found for your user account.<br>
                    </p>
                <?php else: ?>
                    <ul class="log-list">
                        <?php foreach ($history_logs as $log): ?>
                            <li class="log-item">
                                <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?>]</span>
                                <span class="log-user"><?php echo htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></span>
                                <span class="log-details">- <?php echo htmlspecialchars($log['action_details']); ?></span>
                                <span class="log-action">(<?php echo htmlspecialchars($log['action_type']); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </section>
    <section class = "manage-page" id="manage-page" style="display: none;">
    <section class="adjacent-buildings-1">
      <section class="room-group-1">
        <ul class="rooms-1">
        <div class="unclickable-button">
            <button>1st Floor</button>
        </div>
        
        <?php 
        $group = 1; $floor = 1;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));

        ?>
        <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
      </section>

      <section class="room-group-2">
        <div class="first-floor" id="first-floor-2">
          <ul class="rooms-2">
            <div class="floor-buttons-2">
                <button id="first-floor-button-2_1">1st Floor</button>
                <button id="second-floor-button-2_1">2nd Floor</button>
                <button id="third-floor-button-2_1">3rd Floor</button>
            </div>
              <?php 
        $group = 2; $floor = 1;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
        <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>
        <div class="second-floor" id="second-floor-2" style="display: none">
          <ul class="rooms-2">
            <div class="floor-buttons-2">
                <button id="first-floor-button-2_2">1st Floor</button>
                <button id="second-floor-button-2_2">2nd Floor</button>
                <button id="third-floor-button-2_2">3rd Floor</button>
            </div>
            <?php 
        $group = 2; $floor = 2;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>

        <div class="third-floor" id="third-floor-2" style="display: none">
          <ul class="rooms-2">
            <div class="floor-buttons-2">
                <button id="first-floor-button-2_3">1st Floor</button>
                <button id="second-floor-button-2_3">2nd Floor</button>
                <button id="third-floor-button-2_3">3rd Floor</button>
            </div>
            <?php 
        $group = 2; $floor = 3;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>
      </section>
    </section>

    <section class="building-spacing">
        <div class = "adjacent-buildings-2">
      <section class="room-group-3">
        <div class="first-floor" id="first-floor-3">
          <ul class="rooms-3">  
            <div class="floor-buttons-3">
                <button id="first-floor-button-3_1">1st Floor</button>
                <button id="second-floor-button-3_1">2nd Floor</button>
                <button id="third-floor-button-3_1">3rd Floor</button>
            </div>
            <?php 
        $group = 3; $floor = 1;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>

        <div class="second-floor" id="second-floor-3" style="display: none">
          <ul class="rooms-3">
            <div class="floor-buttons-3">
                <button id="first-floor-button-3_2">1st Floor</button>
                <button id="second-floor-button-3_2">2nd Floor</button>
                <button id="third-floor-button-3_2">3rd Floor</button>
             </div>
            <?php 
        $group = 3; $floor = 2;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>

        <div class="third-floor" id="third-floor-3" style="display: none">
          <ul class="rooms-3">
            <div class="floor-buttons-3">
                <button id="first-floor-button-3_3">1st Floor</button>
                <button id="second-floor-button-3_3">2nd Floor</button>
                <button id="third-floor-button-3_3">3rd Floor</button>
             </div>
            <?php 
        $group = 3; $floor = 3;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>
      </section>

      <section class="room-group-4">
        <ul class="rooms-4">
          <div class="unclickable-button">
            <button>1st Floor</button>
          </div>
          <?php 
        $group = 4; $floor = 1;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
      </section>
      </div>

      <section class="open-area">
        <h1>Open Area</h1>
      </section>

      <section class="room-group-5">
        <div class="first-floor" id="first-floor-5">
          <ul class="rooms-5">
            <div class="floor-buttons-5">
                <button id="first-floor-button-5_1">1st Floor</button>
                <button id="second-floor-button-5_1">2nd Floor</button>
                <button id="third-floor-button-5_1">3rd Floor</button>
            </div>
            <?php 
        $group = 5; $floor = 1;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>
        <div class="second-floor" id="second-floor-5" style="display: none">
          <ul class="rooms-5">
            <div class="floor-buttons-5">
                <button id="first-floor-button-5_2">1st Floor</button>
                <button id="second-floor-button-5_2">2nd Floor</button>
                <button id="third-floor-button-5_2">3rd Floor</button>
            </div>
            <?php 
        $group = 5; $floor = 2;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>
        
        <div class="third-floor" id="third-floor-5" style="display: none">
          <ul class="rooms-5">
            <div class="floor-buttons-5">
                <button id="first-floor-button-5_3">1st Floor</button>
                <button id="second-floor-button-5_3">2nd Floor</button>
                <button id="third-floor-button-5_3">3rd Floor</button>
            </div>
            <?php 
        $group = 5; $floor = 3;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
        </div>
      </section>
    </section>

    <section class="room-group-6">
        <ul class="rooms-6">
          <div class="unclickable-button">
            <button>1st Floor</button>
          </div>
          <?php 
        $group = 6; $floor = 1;
        if (isset($room_groups_data[$group][$floor])): 
            foreach ($room_groups_data[$group][$floor] as $room):
                $status_class = strtolower(str_replace(' ', '-', $room['room_status']));
        ?>
            <a href="index.php?page=edit_room&room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
            <li data-room-id="<?php echo htmlspecialchars($room['room_id']); ?>" 
                class="room-item <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($room['room_name']); ?>
            </li>
            </a>
        <?php 
            endforeach;
        else: 
        ?>
            <li>No rooms found on this floor.</li>
        <?php endif; ?>
        </ul>
      </section>
      </section>
    <script src="admin_page.js"></script>
  </body>
</html>
