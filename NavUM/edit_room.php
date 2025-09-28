<?php
include("connect.php");
include("update_room.php");
$message = '';
$directions_picture_data = '';
$directions_picture_type = '';
$qr_id = ''; 

if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    header("Location: admin_page.php");
    exit();
}

$room_id = $_GET['room_id'];
$room_name = 'Setting Up';
$room_status = 'Setting Up';

if (isset($_GET['update_success']) && $_GET['update_success'] === 'true') {
    $message = "Room updated successfully!";
}

$sql_room = "SELECT room_name, room_status, room_picture, room_picture_type FROM rooms WHERE room_id = ?";
$stmt_room = $conn->prepare($sql_room);
$stmt_room->bind_param("i", $room_id); 
$stmt_room->execute();
$result_room = $stmt_room->get_result();

if ($result_room->num_rows > 0) {
    $room = $result_room->fetch_assoc();
    $room_name = htmlspecialchars($room['room_name']);
    $room_status = htmlspecialchars($room['room_status']);
    
    $room_picture_data = $room['room_picture'];
    $room_picture_type = $room['room_picture_type'];
} else {
    $message = "Error: Room ID " . htmlspecialchars($room_id) . " not found in the database.";
}
$stmt_room->close();


if ($result_room->num_rows > 0) {
    $sql_directions = "SELECT end_point_directions, end_point_picture_type FROM end_point_pictures WHERE end_point_id = ?";
    $stmt_directions = $conn->prepare($sql_directions);
    $stmt_directions->bind_param("i", $room_id);
    $stmt_directions->execute();
    $result_directions = $stmt_directions->get_result();

    if ($result_directions->num_rows > 0) {
        $directions_row = $result_directions->fetch_assoc();

        $directions_picture_data = $directions_row['end_point_picture'];
        $directions_picture_type = $directions_row['end_point_picture_type'];
    }
    $stmt_directions->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_directions'])) {
    $room_id_post = $_POST['room_id'];
    
    $new_qr_id = filter_var(trim($_POST['new_qr_id']), FILTER_VALIDATE_INT); 

    if ($new_qr_id === false || $_POST['new_qr_id'] === '') {
        $new_qr_id = null;
    }
    
    $file_input_name = 'new_end_point_picture';
    $file_to_process = null;
    $files_count = 0;
    
    if (isset($_FILES[$file_input_name])) {
        $files = $_FILES[$file_input_name];
        $is_multiple = is_array($files['error']);

        if ($is_multiple) {
            for ($i = 0; $i < count($files['error']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $files_count++;
                    if ($file_to_process === null) {
                        $file_to_process = [
                            'tmp_name' => $files['tmp_name'][$i],
                            'type' => $files['type'][$i],
                        ];
                    }
                }
            }
        } elseif ($files['error'] === UPLOAD_ERR_OK) {
            $files_count = 1;
            $file_to_process = [
                'tmp_name' => $files['tmp_name'],
                'type' => $files['type'],
            ];
        }
    }
    
    $all_updates_successful = true;
    
    if ($file_to_process) {
        $file_tmp_name = $file_to_process['tmp_name'];
        $file_type = $file_to_process['type'];
        
        $file_data = file_get_contents($file_tmp_name);   
        
        $sql_update = "
            INSERT INTO end_point_pictures (end_point_id, end_point_picture, end_point_picture_type, qr_id)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                end_point_picture = VALUES(end_point_picture), 
                end_point_picture_type = VALUES(end_point_picture_type),
                qr_id = VALUES(qr_id)
        ";
        
        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("isii", $room_id_post, $file_data, $file_type, $new_qr_id);
            
            if (!$stmt_update->execute()) {
                $all_updates_successful = false;
                $message = "Error updating directions picture and QR ID: " . $conn->error;
            }
            $stmt_update->close();
        } else {
            $all_updates_successful = false;
            $message = "Database error: Could not prepare picture/QR statement: " . $conn->error;
        }
    } else { 
        $sql_update_qr_only = "UPDATE end_point_pictures SET qr_id = ? WHERE end_point_id = ?";
        
        if ($stmt_update_qr_only = $conn->prepare($sql_update_qr_only)) {
            // 'ii' for qr_id (int), room_id (int)
            $stmt_update_qr_only->bind_param("ii", $new_qr_id, $room_id_post); 
            if (!$stmt_update_qr_only->execute()) {
                 $message = "Error updating QR ID: " . $conn->error;
                 $all_updates_successful = false; 
            }
            $stmt_update_qr_only->close();
        } else {
             $message = "Database error: Could not prepare QR ID statement: " . $conn->error;
             $all_updates_successful = false;
        }
    }
    
    if ($all_updates_successful) {
        $redirect_query = "?room_id=" . $room_id_post . "&directions_update_success=true";
        if ($files_count > 1) {
            $redirect_query .= "&db_warning=true";
        }
        header("Location: edit_room.php" . $redirect_query);
        exit();
    }
}

if (isset($_GET['directions_update_success']) && $_GET['directions_update_success'] === 'true') {
    $message = "Directions picture and QR ID updated successfully! (Note: The QR ID field remains blank for the next edit.)"; 
    if (isset($_GET['db_warning']) && $_GET['db_warning'] === 'true') {
         $message .= " (Note: Only the first selected file was processed due to the database structure.)";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="general.css" />
    <title>Room Editing: <?php echo $room_name; ?></title>
  </head>
  <body class="edit-room-body">
    <section class="edit-room-container" id="edit-room-container">
      <?php if (!empty($message)): ?>
          <p style="text-align: center; color: green; font-weight: bold; margin-bottom: 10px;"><?php echo $message; ?></p>
      <?php endif; ?>

      <?php if ($result_room->num_rows > 0): ?>
        <form method="post" action="" enctype="multipart/form-data" class="edit-room-window">

          <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
          
          <div class="selected-room">
            <label for="new_room_name">Room Name:</label>
            <input
                type="text"
                id="new_room_name"
                name="new_room_name"
                value="<?php echo $room_name; ?>" 
                required 
                class="editable-field"
            />
            
            <div class="selected-room-photo">
              <?php if (!empty($room_picture_data) && !empty($room_picture_type)): ?>
                <img 
                src="data:<?php echo $room_picture_type; ?>;base64,<?php echo base64_encode($room_picture_data); ?>" 
                alt="<?php echo $room_name; ?> Picture"
                style="max-width: 300px; height: auto; border: 1px solid #ccc;"
                />
              <?php else: ?>
                <p>No picture available for this room.</p>
              <?php endif; ?>
              </div>

            <label for="new_room_status" style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">Room Status:</label>
            <select name="new_room_status" id="new_room_status" class="editable-field" required>
                <option value="Available" <?php if ($room_status == 'Available') echo 'selected'; ?>>Available</option>
                <option value="Maintenance" <?php if ($room_status == 'Maintenance') echo 'selected'; ?>>Maintenance</option>
                <option value="Setting Up" <?php if ($room_status == 'Setting Up') echo 'selected'; ?>>Setting Up</option>
            </select>


            <div class="to-edit-room-buttons" id="to-edit-room-buttons">

              <button class="edit-room-button" type="submit" name="update_room">
                Save Changes
              </button>
              <button class="update-directions-button" type="button" id="update-directions-button">Update Directions</button>
            </div>
              <button class="back-button" type="button" onclick="window.location.href='admin_page.php'">Back</button>
            
          </div>
        </form>


        <?php else: ?>
             <div class="edit-room-window">
                 <p style="color: red;"><?php echo $message; ?></p>
                 <button class="back-button" type="button" onclick="window.location.href='admin_page.php'">Go to Admin Page</button>
             </div>
        <?php endif; ?>
    </section>

    <section class="update-directions-container" id="update-directions-container" style="display: none;">
        <div class="edit-room-window">
            <h2>Update Directions Picture for <?php echo $room_name; ?></h2>
            
            <form method="post" action="" class="directions-form" enctype="multipart/form-data">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <input type="hidden" name="update_directions" value="1">
              <div class ="update-labels">
                <label for="new_qr_id">QR Code ID (Numbers Only):</label>
                <input 
                    type="number" 
                    id="new_qr_id" 
                    name="new_qr_id" 
                    value="<?php echo $qr_id; ?>" 
                    placeholder="Enter unique numerical QR ID"
                    class="editable-field"
                    min="0"
                    pattern="\d*"
                    style="width: 100%; box-sizing: border-box;"
                >
                
                <label for="new_end_point_picture">Select Directions Picture(s) (JPEG/PNG):</label>               

                <input 
                    type="file" 
                    id="new_end_point_picture" 
                    name="new_end_point_picture[]" 
                    accept="image/jpeg, image/png" 
                    multiple 
                    class="editable-field"
                    style="width: 100%; box-sizing: border-box;"
                >
              </div>
                <div class="existing-directions-photo" style="margin-bottom: 20px; text-align: center;">
                    <?php if (!empty($directions_picture_data) && !empty($directions_picture_type)): ?>
                        <p style="font-weight: bold;">Current Directions Picture:</p>
                        <img 
                        src="data:<?php echo $directions_picture_type; ?>;base64,<?php echo base64_encode($directions_picture_data); ?>" 
                        alt="Directions Picture"
                        style="max-width: 100%; height: auto; border: 1px solid #ccc; border-radius: 8px; margin-top: 10px;"
                        />
                    <?php else: ?>
                        <p>No directions picture currently set.</p>
                    <?php endif; ?>
                </div>

                
                <div class="to-edit-room-buttons" style="margin-top: 20px;">
                    <button class="edit-room-button" type="submit">Upload & Save Picture(s) and QR ID</button>
                    <button class="back-to-room-edit-button" type="button" id="back-to-room-edit-button">Back to Room Info</button>
                </div>
            </form>
        </div>
    </section>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
          const editRoomContainer = document.getElementById('edit-room-container');
          const updateDirectionsContainer = document.getElementById('update-directions-container');
          const updateDirectionsButton = document.getElementById('update-directions-button');
          const backToRoomEditButton = document.getElementById('back-to-room-edit-button');

          if (updateDirectionsButton) {
              updateDirectionsButton.addEventListener('click', function(e) {
                  e.preventDefault(); 
                  editRoomContainer.style.display = 'none';
                  updateDirectionsContainer.style.display = 'block';
              });
          }
          if (backToRoomEditButton) {
              backToRoomEditButton.addEventListener('click', function() {
                  updateDirectionsContainer.style.display = 'none';
                  editRoomContainer.style.display = 'block';
              });
          }
      });
    </script>
  </body>
</html>
<?php 
if (isset($conn)) {
    $conn->close();
}
?>
