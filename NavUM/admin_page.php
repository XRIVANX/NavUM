<?php
include("connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
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

$sql_rooms = "SELECT room_id, room_name, room_status, room_group_id, floor_id FROM rooms ORDER BY room_group_id, floor_id, room_id";
$result_rooms = $conn->query($sql_rooms);

if ($result_rooms) {
    while ($room = $result_rooms->fetch_assoc()) {
        $group = $room['room_group_id'];
        $floor = $room['floor_id'];
        $room_groups_data[$group][$floor][] = $room;
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
  <body>
    <section class = "nav">
        <h1 class = "placeholder-logo">NavUM</h1>
        <ul>
            <li class = "user-name-container"><span class="user-name"><?php echo $user_firstname . " " . $user_lastname; ?></span></li>
            <li class="log-out-button"><a href="logout.php">Log Out</a></li>
        </ul>
    </section>
    <section class = "hero-admin">
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
        <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
        <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['room_id']); ?>" style="text-decoration: none;">
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
