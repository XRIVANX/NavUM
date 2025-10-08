<?php
include("connect.php");

$sql = "SELECT room_id, room_name FROM rooms WHERE room_status = 'Available' ORDER BY room_name ASC";
$result = $conn->query($sql);
      
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nav-UM</title>
    <link rel="stylesheet" href="user_style.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  </head>
  <body>
    <div class="logo-container">
      <img src="UM Logo.png" alt="School Logo" class="logo" id="logoBtn" />
    </div>

    <div class="map-placeholder" id="mapPlaceholder">
      <img src="Map.png" alt="Campus Map" class="campus-map-img" />

      <div class="image-viewer" id="imageViewer" style="display: none">
        <img id="currentImage" src="" alt="Direction Step" />
        <button id="backBtn" style="display: none;">Back</button>
        <button id="nextBtn" style="display: none;">Next</button>
      </div>

      </div>

    <div class="rooms-btn-container">
      <button class="rooms-btn" id="roomsBtn">
        Rooms <span class="arrow-up" id="arrowUp">▲</span>
      </button>

      <div class="rooms-list" id="roomsList">
        <ul>
            <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<li data-room-id=\"{$row['room_id']}\" class=\"room-item-clickable\">" . htmlspecialchars($row['room_name']) . "</li>";
                    }
                } else {
                    echo "<li>No available rooms found.</li>";
                }
                $conn->close();
            ?>
        </ul>
      </div>
    </div>

    <script src = "starting_page_script.js"></script>
  </body>
</html>