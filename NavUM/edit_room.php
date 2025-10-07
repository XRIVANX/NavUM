<?php
include("connect.php");
include("update_room.php");
$message = '';
$qr_id = null;

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

$directions_pictures = [];
$index_counter = 1;
if ($result_room->num_rows > 0) {
    $sql_directions = "SELECT end_point_pictures_id, qr_id, end_point_directions, end_point_picture_type FROM end_point_pictures WHERE end_point_id = ? ORDER BY end_point_pictures_id ASC";
    $stmt_directions = $conn->prepare($sql_directions);
    $stmt_directions->bind_param("i", $room_id);
    $stmt_directions->execute();
    $result_directions = $stmt_directions->get_result();

    if ($result_directions->num_rows > 0) {
        while ($row = $result_directions->fetch_assoc()) {
            $directions_pictures[] = [
                'id' => $row['end_point_pictures_id'],
                'index' => $index_counter,
                'data' => $row['end_point_directions'],
                'type' => $row['end_point_picture_type'],
                'qr_id' => $row['qr_id']
            ];
            if ($qr_id === null && $row['qr_id'] !== null) {
                $qr_id = htmlspecialchars($row['qr_id']);
            }
            $index_counter++;
        }
    }
    $stmt_directions->close();
}
$index_to_id_map = [];
foreach ($directions_pictures as $picture) {
    $index_to_id_map[$picture['index']] = $picture['id'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_directions'])) {
    $room_id_post = $_POST['room_id'];
    
    $new_qr_id = filter_var(trim($_POST['new_qr_id']), FILTER_VALIDATE_INT);
    $db_qr_id = ($new_qr_id === false || $_POST['new_qr_id'] === '') ? null : $new_qr_id;

    $files = $_FILES['new_end_point_picture'];
    $is_multiple = is_array($files['error']);
    $files_processed_count = 0;
    $all_updates_successful = true;
    
    if (isset($files) && (is_array($files['error']) ? $files['error'][0] : $files['error']) !== UPLOAD_ERR_NO_FILE) {
        
        $file_count = $is_multiple ? count($files['error']) : 1;
        for ($i = 0; $i < $file_count; $i++) {
            $error = $is_multiple ? $files['error'][$i] : $files['error'];
            
            if ($error === UPLOAD_ERR_OK) {
                $tmp_name = $is_multiple ? $files['tmp_name'][$i] : $files['tmp_name'];
                $file_type = $is_multiple ? $files['type'][$i] : $files['type'];
                if (strpos($file_type, 'image/') === 0) {
                    $file_data = file_get_contents($tmp_name);
                    $sql_insert = "
                        INSERT INTO end_point_pictures (end_point_id, qr_id, end_point_directions, end_point_picture_type)
                        VALUES (?, ?, ?, ?)";
                    
                    if ($stmt_insert = $conn->prepare($sql_insert)) {
                        $stmt_insert->bind_param("iiss", $room_id_post, $db_qr_id, $file_data, $file_type);
                        
                        if (!$stmt_insert->execute()) {
                            $all_updates_successful = false;
                            $message = "Error inserting picture: " . $conn->error;
                            break;
                        } else {
                            $files_processed_count++;
                        }
                        $stmt_insert->close();
                    } else {
                        $all_updates_successful = false;
                        $message = "Database error: Could not prepare picture insert statement: " . $conn->error;
                        break;
                    }
                }
            }
        }
        
    } elseif ($db_qr_id !== null) {
        $sql_update_qr_only = "UPDATE end_point_pictures SET qr_id = ? WHERE end_point_id = ?";
        
        if ($stmt_update_qr_only = $conn->prepare($sql_update_qr_only)) {
            $stmt_update_qr_only->bind_param("ii", $db_qr_id, $room_id_post); 
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
        if ($files_processed_count > 0) {
            $redirect_query .= "&files_count=" . $files_processed_count;
        }
        header("Location: edit_room.php" . $redirect_query);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_picture']) && isset($_POST['picture_id_to_delete'])) {
    $picture_id = filter_var($_POST['picture_id_to_delete'], FILTER_VALIDATE_INT);
    
    if ($picture_id) {
        $sql_delete = "DELETE FROM end_point_pictures WHERE end_point_pictures_id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $picture_id);
            if ($stmt_delete->execute()) {
                header("Location: edit_room.php?room_id=" . $room_id . "&delete_success=" . $picture_id);
                exit();
            } else {
                $message = "Error deleting picture: " . $conn->error;
            }
            $stmt_delete->close();
        } else {
            $message = "Database error: Could not prepare delete statement: " . $conn->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replace_picture']) && isset($_POST['picture_id_to_update'])) {
    $picture_id = filter_var($_POST['picture_id_to_update'], FILTER_VALIDATE_INT);
    $file = $_FILES['replacement_picture'];

    if ($picture_id && $file['error'] === UPLOAD_ERR_OK) {
        $file_type = $file['type'];

        if (strpos($file_type, 'image/') === 0) {
            $file_data = file_get_contents($file['tmp_name']);
            $sql_update = "UPDATE end_point_pictures SET end_point_directions = ?, end_point_picture_type = ? WHERE end_point_pictures_id = ?";
            
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("ssi", $file_data, $file_type, $picture_id);
                
                if ($stmt_update->execute()) {
                    header("Location: edit_room.php?room_id=" . $room_id . "&update_picture_success=" . $picture_id);
                    exit();
                } else {
                    $message = "Error updating picture: " . $conn->error;
                }
                $stmt_update->close();
            } else {
                $message = "Database error: Could not prepare picture update statement: " . $conn->error;
            }
        } else {
            $message = "Error: Invalid file type for replacement picture.";
        }
    } elseif ($picture_id) {
        $message = "Error: No replacement file uploaded or an upload error occurred.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['swap_picture']) && isset($_POST['picture_id_to_swap']) && isset($_POST['target_picture_id'])) {
    
    $picture_index_1 = filter_var($_POST['picture_id_to_swap'], FILTER_VALIDATE_INT);
    $picture_index_2 = filter_var($_POST['target_picture_id'], FILTER_VALIDATE_INT);

    if ($picture_index_1 && $picture_index_2 && $picture_index_1 != $picture_index_2) {
        if (!isset($index_to_id_map[$picture_index_1]) || !isset($index_to_id_map[$picture_index_2])) {
            $message = "Error: One or both picture indices are invalid or out of range.";
        } else {
            $picture_id_1 = $index_to_id_map[$picture_index_1];
            $picture_id_2 = $index_to_id_map[$picture_index_2];
            
            $conn->begin_transaction();
            $swap_successful = false;

            try {
                $sql_fetch = "SELECT end_point_pictures_id, end_point_directions, end_point_picture_type, qr_id FROM end_point_pictures WHERE end_point_pictures_id = ? OR end_point_pictures_id = ?";
                $stmt_fetch = $conn->prepare($sql_fetch);
                $stmt_fetch->bind_param("ii", $picture_id_1, $picture_id_2);
                $stmt_fetch->execute();
                $result_fetch = $stmt_fetch->get_result();
                $stmt_fetch->close();

                $data = [];
                while ($row = $result_fetch->fetch_assoc()) {
                    $data[(int)$row['end_point_pictures_id']] = $row;
                }

                if (count($data) === 2) {
                    $data1 = $data[$picture_id_1]; 
                    $data2 = $data[$picture_id_2]; 

                    $sql_update1 = "UPDATE end_point_pictures SET end_point_directions = ?, end_point_picture_type = ?, qr_id = ? WHERE end_point_pictures_id = ?";
                    $stmt_update1 = $conn->prepare($sql_update1);
                    $stmt_update1->bind_param("ssii", $data2['end_point_directions'], $data2['end_point_picture_type'], $data2['qr_id'], $picture_id_1);
                    $stmt_update1->execute();
                    $stmt_update1->close();

                    $sql_update2 = "UPDATE end_point_pictures SET end_point_directions = ?, end_point_picture_type = ?, qr_id = ? WHERE end_point_pictures_id = ?";
                    $stmt_update2 = $conn->prepare($sql_update2);
                    $stmt_update2->bind_param("ssii", $data1['end_point_directions'], $data1['end_point_picture_type'], $data1['qr_id'], $picture_id_2);
                    $stmt_update2->execute();
                    $stmt_update2->close();
                    
                    $conn->commit();
                    $swap_successful = true;
                } else {
                     throw new Exception("One or both database IDs were not found for swapping.");
                }

            } catch (Exception $e) {
                $conn->rollback();
                $message = "Swap failed: " . $e->getMessage();
            }
            
            if ($swap_successful) {
                header("Location: edit_room.php?room_id=" . $room_id . "&swap_success=" . $picture_index_1 . "&swapped_with=" . $picture_index_2 . "&index_swap=true");
                exit();
            }
        }
    } else {
        $message = "Error: Invalid indices provided for swap or indices are the same.";
    }
}

if (isset($_GET['directions_update_success']) && $_GET['directions_update_success'] === 'true') {
    $files_count = isset($_GET['files_count']) ? (int)$_GET['files_count'] : 0;
    
    if ($files_count > 0) {
        $message = "Successfully uploaded " . $files_count . " new directions picture(s) and assigned the QR ID!"; 
    } else {
        $message = "Successfully updated the QR ID for all existing directions pictures.";
    }
}

if (isset($_GET['delete_success'])) {
    $message = "Successfully deleted directions picture (DB ID: " . htmlspecialchars($_GET['delete_success']) . ").";
}

if (isset($_GET['update_picture_success'])) {
    $message = "Successfully replaced directions picture (DB ID: " . htmlspecialchars($_GET['update_picture_success']) . ").";
}

if (isset($_GET['swap_success']) && isset($_GET['swapped_with']) && isset($_GET['index_swap'])) {
    $message = "Successfully swapped content of directions picture index " . htmlspecialchars($_GET['swap_success']) . " with index " . htmlspecialchars($_GET['swapped_with']) . ".";
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
            <h1>Editing Room</h1>
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
                style="max-width: 300px; height: auto; border: 1px solid #ccc; border-radius: 8px;"
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
                
                <label for="new_end_point_picture" style="margin-top: 15px;">Upload New Directions Picture(s) (JPEG/PNG):</label>               

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

                <div class="existing-directions-photos" style="margin-top: 20px; text-align: center;">
                    <?php if (!empty($directions_pictures)): ?>
                        <p style="font-weight: bold; margin-bottom: 15px;">Click a picture below to Manage (Total: <?php echo count($directions_pictures); ?>):</p>
                        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;">
                        <?php foreach ($directions_pictures as $picture): ?>
                            <div 
                                class="direction-picture-item" 
                                data-picture-index="<?php echo htmlspecialchars($picture['index']); ?>" 
                                data-picture-db-id="<?php echo htmlspecialchars($picture['id']); ?>" 
                                style="cursor: pointer; flex: 0 0 calc(50% - 10px); max-width: 250px; border: 2px solid #3498db; padding: 5px; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; background-color: #f9f9f9;"
                                onmouseover="this.style.transform='scale(1.03)'"
                                onmouseout="this.style.transform='scale(1)'"
                                title="Click to manage Picture Index: <?php echo htmlspecialchars($picture['index']); ?>"
                            >
                                <img 
                                src="data:<?php echo $picture['type']; ?>;base64,<?php echo base64_encode($picture['data']); ?>" 
                                alt="Directions Picture <?php echo $picture['index']; ?>"
                                style="width: 100%; height: auto; border-radius: 4px;"
                                />
                                <span style="display: block; font-size: 0.8em; color: #555; margin-top: 5px;">Picture Index: <?php echo htmlspecialchars($picture['index']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <p style="margin-top: 15px; font-style: italic; font-size: 0.9em;">All pictures shown here are currently associated with this room.</p>
                    <?php else: ?>
                        <p>No directions picture currently set.</p>
                    <?php endif; ?>
                </div>

                
                <div class="to-edit-room-buttons" style="margin-top: 20px;">
                    <button class="edit-room-button" type="submit">Upload New Picture(s) and/or Update QR ID</button>
                    <button class="back-to-room-edit-button" type="button" id="back-to-room-edit-button">Back to Room Info</button>
                </div>
            </form>
        </div>
    </section>

    <section class="single-picture-management" id="single-picture-management" style="display: none;">
        <div class="edit-room-window">
            <h2>Manage Direction Picture Index: <span id="current-picture-id-display" style="color: #3498db;"></span></h2>
            
            <form method="post" action="" enctype="multipart/form-data" id="replace-picture-form" style="padding-bottom: 20px; border-bottom: 1px dashed #ccc;">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <input type="hidden" name="picture_id_to_update" id="picture-id-to-update" value="">
                <input type="hidden" name="replace_picture" value="1">
                
                <p style="font-weight: bold; margin-bottom: 10px;">Option 1: Replace Picture Content</p>
                <p style="font-size: 0.9em; color: white; margin-bottom: 10px;">Replaces the picture content while keeping the current Index/DB ID.</p>
                
                <label for="replacement_picture" style="margin-top: 5px;">Select Replacement Picture (JPEG/PNG):</label>
                <input 
                    type="file" 
                    id="replacement_picture" 
                    name="replacement_picture" 
                    accept="image/jpeg, image/png" 
                    required
                    class="editable-field"
                    style="width: 100%; box-sizing: border-box;"
                >

                <div class="to-edit-room-buttons" style="margin-top: 15px;">
                    <button class="edit-room-button" type="submit" style="background-color: #2ecc71;">Replace Picture</button>
                </div>
            </form>

            <form method="post" action="" id="swap-picture-form" style="padding-bottom: 20px; border-bottom: 1px dashed #ccc; margin-top: 20px;">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <input type="hidden" name="picture_id_to_swap" id="picture-index-to-swap" value="">
                <input type="hidden" name="swap_picture" value="1">
                
                <p style="font-weight: bold; margin-bottom: 10px;">Option 2: Swap Picture Content (Change Order)</p>
                <p style="font-size: 0.9em; color: #777; margin-bottom: 10px;">Swaps the visual content with the picture at the target **Index**.</p>
                
                <label for="target_picture_index" style="margin-top: 5px;">Target Picture Index to Swap With:</label>
                <input 
                    type="number" 
                    id="target_picture_index" 
                    name="target_picture_id" 
                    required
                    class="editable-field"
                    min="1"
                    max="<?php echo count($directions_pictures); ?>"
                    pattern="\d*"
                    placeholder="Enter target Picture Index (1 to <?php echo count($directions_pictures); ?>)"
                    style="width: 100%; box-sizing: border-box;"
                >
                <div style="font-size: 0.8em; color: #555; margin-top: 5px;">
                    Available Picture Indices: 
                    <?php 
                        $indices = array_column($directions_pictures, 'index');
                        echo implode(', ', array_map('htmlspecialchars', $indices)); 
                    ?>
                </div>

                <div class="to-edit-room-buttons" style="margin-top: 15px;">
                    <button class="edit-room-button" type="submit" style="background-color: #f39c12;">Swap Content</button>
                </div>
            </form>

            <form method="post" action="" onsubmit="return confirm('WARNING: Are you sure you want to delete this picture permanently? This action cannot be undone.')" style="margin-top: 20px;">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <input type="hidden" name="picture_id_to_delete" id="picture-id-to-delete" value="">
                <input type="hidden" name="delete_picture" value="1">
                
                <p style="font-weight: bold; margin-bottom: 10px;">Option 3: Delete Picture</p>

                <div class="to-edit-room-buttons">
                    <button class="edit-room-button" type="submit" style="background-color: #e74c3c;">Delete Picture</button>
                </div>
            </form>

            <button class="back-button" type="button" id="back-to-directions-list-button" style="margin-top: 20px;">Back to Directions List</button>
        </div>
    </section>


    <script>
      document.addEventListener('DOMContentLoaded', function() {
          const editRoomContainer = document.getElementById('edit-room-container');
          const updateDirectionsContainer = document.getElementById('update-directions-container');
          const singlePictureManagement = document.getElementById('single-picture-management');
          
          const updateDirectionsButton = document.getElementById('update-directions-button');
          const backToRoomEditButton = document.getElementById('back-to-room-edit-button'); 

          const pictureItems = document.querySelectorAll('.direction-picture-item');
          const pictureIdToUpdateInput = document.getElementById('picture-id-to-update');
          const pictureIdToDeleteInput = document.getElementById('picture-id-to-delete');
          const pictureIndexToSwapInput = document.getElementById('picture-index-to-swap');
          const currentPictureIdDisplay = document.getElementById('current-picture-id-display');
          const backToDirectionsListButton = document.getElementById('back-to-directions-list-button');
          const showView = (viewElement) => {
              editRoomContainer.style.display = 'none';
              updateDirectionsContainer.style.display = 'none';
              singlePictureManagement.style.display = 'none';
              if (viewElement) {
                  viewElement.style.display = 'block';
              }
          };
          if (updateDirectionsButton) {
              updateDirectionsButton.addEventListener('click', function(e) {
                  e.preventDefault(); 
                  showView(updateDirectionsContainer);
              });
          }
          if (backToRoomEditButton) {
              backToRoomEditButton.addEventListener('click', function() {
                  showView(editRoomContainer);
              });
          }
          pictureItems.forEach(item => {
              item.addEventListener('click', function() {
                  const pictureDbId = this.getAttribute('data-picture-db-id');
                  const pictureIndex = this.getAttribute('data-picture-index');
                  pictureIdToUpdateInput.value = pictureDbId; 
                  pictureIdToDeleteInput.value = pictureDbId;
                  pictureIndexToSwapInput.value = pictureIndex; 
                  currentPictureIdDisplay.textContent = pictureIndex + ' (DB ID: ' + pictureDbId + ')';
                  
                  showView(singlePictureManagement);
              });
          });
          if (backToDirectionsListButton) {
              backToDirectionsListButton.addEventListener('click', function() {
                  showView(updateDirectionsContainer);
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
