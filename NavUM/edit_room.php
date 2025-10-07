<?php
include("connect.php");
include("update_room.php");
include("log_action.php");
$message = '';
$qr_id = null;


if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    header("Location: admin_page.php");
    exit();
}

$room_id = $_GET['room_id'];
$room_name = 'Setting Up';
$room_type = 'Setting Up';
$room_status = 'Setting Up';

if (isset($_GET['update_success']) && $_GET['update_success'] === 'true') {
    $message = "Room updated successfully!";
}

$sql_room = "SELECT room_name, room_type, room_status, room_picture, room_picture_type FROM rooms WHERE room_id = ?";
$stmt_room = $conn->prepare($sql_room);
$stmt_room->bind_param("i", $room_id); 
$stmt_room->execute();
$result_room = $stmt_room->get_result();

if ($result_room->num_rows > 0) {
    $room = $result_room->fetch_assoc();
    $room_name = htmlspecialchars($room['room_name']);
    $room_type = htmlspecialchars(string: $room['room_type']);
    $room_status = htmlspecialchars($room['room_status']);
    
    $room_picture_data = $room['room_picture'];
    $room_picture_type = $room['room_picture_type'];
} else {
    $message = "Error: Room ID " . htmlspecialchars($room_id) . " not found in the database.";
}
$stmt_room->close();

$directions_pictures_flat = []; 
$directions_groups_by_qr = []; 

if ($result_room->num_rows > 0) {
    $sql_directions = "SELECT end_point_pictures_id, qr_id, end_point_directions, end_point_picture_type FROM end_point_pictures WHERE end_point_id = ? ORDER BY end_point_pictures_id ASC";
    $stmt_directions = $conn->prepare($sql_directions);
    $stmt_directions->bind_param("i", $room_id);
    $stmt_directions->execute();
    $result_directions = $stmt_directions->get_result();

    if ($result_directions->num_rows > 0) {
        while ($row = $result_directions->fetch_assoc()) {
            
            $group_key = empty($row['qr_id']) ? 'No QR ID' : htmlspecialchars($row['qr_id']);
            
            if (!isset($directions_groups_by_qr[$group_key])) {
                $directions_groups_by_qr[$group_key] = [
                    'pictures' => [],
                    'group_index_counter' => 1 
                ];
            }

            $group_index = $directions_groups_by_qr[$group_key]['group_index_counter'];
            
            $picture_data = [
                'id' => $row['end_point_pictures_id'],
                'group_index' => $group_index,
                'data' => $row['end_point_directions'],
                'type' => $row['end_point_picture_type'],
                'qr_id' => $row['qr_id']
            ];
            
            $directions_pictures_flat[] = $picture_data; 
            $directions_groups_by_qr[$group_key]['pictures'][] = $picture_data; 
            $directions_groups_by_qr[$group_key]['group_index_counter']++; 

            if ($qr_id === null && !empty($row['qr_id'])) {
                $qr_id = htmlspecialchars($row['qr_id']);
            }
        }
    }
    $stmt_directions->close();
}

$no_qr_group_key = 'No QR ID';
$groups_to_sort = $directions_groups_by_qr;

$no_qr_group = [];
if (isset($groups_to_sort[$no_qr_group_key])) {
    $no_qr_group[$no_qr_group_key]['pictures'] = $groups_to_sort[$no_qr_group_key]['pictures'];
    unset($groups_to_sort[$no_qr_group_key]);
}

$numerical_groups_pictures = [];
foreach ($groups_to_sort as $key => $value) {
    $numerical_groups_pictures[$key] = $value['pictures'];
}

ksort($numerical_groups_pictures, SORT_NUMERIC);

$directions_groups_by_qr_sorted = $numerical_groups_pictures + $no_qr_group;

$room_id_post = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['room_id']) && 
    $room_id_post) {


    if (isset($_POST['upload_room_picture']) && $_POST['upload_room_picture'] === '1' && 
        isset($_FILES['room_main_picture_upload']) && $_FILES['room_main_picture_upload']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp = $_FILES['room_main_picture_upload']['tmp_name'];
        $file_type = $_FILES['room_main_picture_upload']['type'];
        $file_data = file_get_contents($file_tmp);

        $sql_update_room_pic = "UPDATE rooms SET room_picture = ?, room_picture_type = ? WHERE room_id = ?";
        $stmt_update_room_pic = $conn->prepare($sql_update_room_pic);

        $stmt_update_room_pic->bind_param("ssi", $file_data, $file_type, $room_id_post);
        
        if ($stmt_update_room_pic->execute()) {
            log_user_action($conn, 'ROOM_PIC_UPLOAD', 'Uploaded new main picture for Room ID: ' . $room_id_post);
            header("Location: edit_room.php?room_id=" . $room_id_post . "&room_pic_update_success=true");
        exit();
        } else {
            $message = "Error uploading room picture: " . $conn->error;
        }
        $stmt_update_room_pic->close();

    } else if (isset($_POST['remove_room_picture']) && $_POST['remove_room_picture'] === '1') {
        
        $sql_remove_room_pic = "UPDATE rooms SET room_picture = NULL, room_picture_type = NULL WHERE room_id = ?";
        $stmt_remove_room_pic = $conn->prepare($sql_remove_room_pic);
        $stmt_remove_room_pic->bind_param("i", $room_id_post);
        
        if ($stmt_remove_room_pic->execute()) {
            log_user_action($conn, 'ROOM_PIC_REMOVE', 'Removed main picture for Room ID: ' . $room_id_post); // Add this
            header("Location: edit_room.php?room_id=" . $room_id_post . "&room_pic_remove_success=true");
        exit();
        } else {
            $message = "Error removing room picture: " . $conn->error;
        }
        $stmt_remove_room_pic->close();

    }
    else if (isset($_POST['update_directions']) && $_POST['update_directions'] === '1') {
        
        $new_qr_id_input = filter_input(INPUT_POST, 'new_qr_id', FILTER_VALIDATE_INT);
        
        // Determine the QR ID to use for the new uploads and for updating existing ones
        $qr_id_for_db = ($new_qr_id_input !== false && $new_qr_id_input !== null) ? $new_qr_id_input : NULL;
        $files_uploaded_count = 0;
        $upload_successful = true; // Assume success initially
        
        // 1. Process New Picture Uploads (if any)
        if (isset($_FILES['new_end_point_picture']) && is_array($_FILES['new_end_point_picture']['error'])) {
            $file_count = count($_FILES['new_end_point_picture']['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['new_end_point_picture']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['new_end_point_picture']['tmp_name'][$i];
                    $file_type = $_FILES['new_end_point_picture']['type'][$i];
                    $file_data = file_get_contents($file_tmp);

                    $sql_insert = "INSERT INTO end_point_pictures (end_point_id, qr_id, end_point_directions, end_point_picture_type) VALUES (?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    // 'end_point_id' maps to 'room_id'
                    // 'qr_id_for_db' is NULL or INT
                    // 'file_data' is the BLOB (s)
                    // 'file_type' is the MIME type (s)
                    $stmt_insert->bind_param("iiss", $room_id_post, $qr_id_for_db, $file_data, $file_type);
                    
                    if ($stmt_insert->execute()) {
                        $files_uploaded_count++;
                    } else {
                        $upload_successful = false;
                        $message = "Error uploading picture " . ($i + 1) . ": " . $conn->error;
                        break; 
                    }
                    $stmt_insert->close();
                }
            }
        }
        
        // 2. Update QR ID for ALL existing pictures if a new QR ID was provided
        $existing_pictures_updated = false;
        if ($upload_successful && ($qr_id_for_db !== NULL)) {
            // Note: If qr_id_for_db is NULL, we only upload new files with a NULL qr_id, we don't change existing ones.
            $sql_update_existing_qr = "UPDATE end_point_pictures SET qr_id = ? WHERE end_point_id = ?";
            $stmt_update_existing_qr = $conn->prepare($sql_update_existing_qr);
            $stmt_update_existing_qr->bind_param("ii", $qr_id_for_db, $room_id_post);
            
            if ($stmt_update_existing_qr->execute()) {
                $existing_pictures_updated = true;
                if ($files_uploaded_count == 0) {
                    log_user_action($conn, 'DIRECTIONS_QR_UPDATE', 'Updated QR ID to ' . $qr_id_for_db . ' for all directions pictures in Room ID: ' . $room_id_post);
                }
            } else {
                $upload_successful = false;
                $message = "Error updating existing QR ID: " . $conn->error;
            }
            $stmt_update_existing_qr->close();
        }

        if ($upload_successful) {
            if ($files_uploaded_count > 0 || $existing_pictures_updated) {
                log_user_action($conn, 'DIRECTIONS_UPLOAD_AND_QR_UPDATE', 
                    "Uploaded $files_uploaded_count new picture(s). QR ID set to " . ($qr_id_for_db === NULL ? 'NULL' : $qr_id_for_db) . 
                    " for new uploads and " . ($existing_pictures_updated ? 'existing ones.' : 'not changed for existing ones.') . 
                    " Room ID: " . $room_id_post);

                header("Location: edit_room.php?room_id=" . $room_id_post . "&directions_update_success=true&files_count=" . $files_uploaded_count);
                exit();
            } else {
                $message = "No new pictures were uploaded and no QR ID was updated.";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_picture']) && $room_id_post) {
    $picture_id_to_delete = filter_input(INPUT_POST, 'picture_id_to_delete', FILTER_VALIDATE_INT);
    if ($picture_id_to_delete) {
        $sql_delete = "DELETE FROM end_point_pictures WHERE end_point_pictures_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $picture_id_to_delete);
        if ($stmt_delete->execute()) {
            log_user_action($conn, 'DIRECTIONS_PIC_DELETE', 'Deleted directions picture (DB ID: ' . $picture_id_to_delete . ') from Room ID: ' . $room_id_post); // Add this
            header("Location: edit_room.php?room_id=" . $room_id_post . "&delete_success=" . $picture_id_to_delete);
        exit();
        } else {
            $message = "Error deleting picture: " . $conn->error;
        }
        $stmt_delete->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replace_picture']) && $room_id_post) {
    $picture_id_to_update = filter_input(INPUT_POST, 'picture_id_to_update', FILTER_VALIDATE_INT);
    if ($picture_id_to_update && isset($_FILES['replacement_picture']) && $_FILES['replacement_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['replacement_picture']['tmp_name'];
        $file_type = $_FILES['replacement_picture']['type'];
        $file_data = file_get_contents($file_tmp);

        $sql_update = "UPDATE end_point_pictures SET end_point_directions = ?, end_point_picture_type = ? WHERE end_point_pictures_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $file_data, $file_type, $picture_id_to_update);
        
        if ($stmt_update->execute()) {
            log_user_action($conn, 'DIRECTIONS_PIC_REPLACE', 'Replaced content of directions picture (DB ID: ' . $picture_id_to_update . ') in Room ID: ' . $room_id_post); // Add this
            header("Location: edit_room.php?room_id=" . $room_id_post . "&update_picture_success=" . $picture_id_to_update);
        exit();
        } else {
            $message = "Error replacing picture: " . $conn->error;
        }
        $stmt_update->close();
    } else {
        $message = "Error: Invalid picture ID or replacement file not uploaded.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['swap_picture']) && 
    isset($_POST['current_group_key']) && 
    isset($_POST['picture_group_index']) && 
    isset($_POST['target_picture_group_index'])) {
    
    $current_group_key = $_POST['current_group_key'];
    $picture_group_index_1 = filter_input(INPUT_POST, 'picture_group_index', FILTER_VALIDATE_INT);
    $picture_group_index_2 = filter_input(INPUT_POST, 'target_picture_group_index', FILTER_VALIDATE_INT);

    $picture_id_1 = null;
    $picture_id_2 = null;
    
    if (isset($directions_groups_by_qr[$current_group_key])) {
        $pictures_in_group = $directions_groups_by_qr[$current_group_key]['pictures'];
        
        foreach ($pictures_in_group as $picture) {
            if ($picture['group_index'] == $picture_group_index_1) {
                $picture_id_1 = $picture['id'];
            }
            if ($picture['group_index'] == $picture_group_index_2) {
                $picture_id_2 = $picture['id'];
            }
        }
    }

    if ($picture_id_1 && $picture_id_2 && $picture_id_1 != $picture_id_2) {
        
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
                $qr_id_2 = $data2['qr_id'] === null ? NULL : (int)$data2['qr_id'];
                $stmt_update1->bind_param("ssii", $data2['end_point_directions'], $data2['end_point_picture_type'], $qr_id_2, $picture_id_1);
                $stmt_update1->execute();
                $stmt_update1->close();

                $sql_update2 = "UPDATE end_point_pictures SET end_point_directions = ?, end_point_picture_type = ?, qr_id = ? WHERE end_point_pictures_id = ?";
                $stmt_update2 = $conn->prepare($sql_update2);
                $qr_id_1 = $data1['qr_id'] === null ? NULL : (int)$data1['qr_id'];
                $stmt_update2->bind_param("ssii", $data1['end_point_directions'], $data1['end_point_picture_type'], $qr_id_1, $picture_id_2);
                $stmt_update2->execute();
                $stmt_update2->close();
                
                $conn->commit();
                $swap_successful = true;
                log_user_action($conn, 'DIRECTIONS_PIC_SWAP', "Swapped picture content between Index " . $picture_group_index_1 . " and Index " . $picture_group_index_2 . " in QR Group: " . $current_group_key . " for Room ID: " . $room_id_post);
            } else {
                 throw new Exception("One or both database IDs were not found for swapping. (Debug: " . count($data) . " rows found)");
            }

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Swap failed: " . $e->getMessage();
        }
        
        if ($swap_successful) {
            header("Location: edit_room.php?room_id=" . $room_id_post . "&swap_success=" . $picture_group_index_1 . "&swapped_with=" . $picture_group_index_2 . "&group=" . urlencode($current_group_key));
            exit();
        }

    } else {
        $message = "Error: Invalid indices or picture IDs provided for swap. **You can only swap pictures within the same QR Code Group.**";
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

if (isset($_GET['room_pic_update_success']) && $_GET['room_pic_update_success'] === 'true') {
    $message = "Room picture uploaded and saved successfully!";
}

if (isset($_GET['room_pic_remove_success']) && $_GET['room_pic_remove_success'] === 'true') {
    $message = "Room picture removed successfully!";
}

if (isset($_GET['delete_success'])) {
    $message = "Successfully deleted directions picture (DB ID: " . htmlspecialchars($_GET['delete_success']) . ").";
}

if (isset($_GET['update_picture_success'])) {
    $message = "Successfully replaced directions picture (DB ID: " . htmlspecialchars($_GET['update_picture_success']) . ").";
}

if (isset($_GET['swap_success']) && isset($_GET['swapped_with']) && isset($_GET['group'])) {
    $message = "Successfully swapped content of directions picture **Index " . htmlspecialchars($_GET['swap_success']) . "** with **Index " . htmlspecialchars($_GET['swapped_with']) . "** within Group: **" . htmlspecialchars(urldecode($_GET['group'])) . "**.";
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
        <form method="post" action="" enctype="multipart/form-data" class="edit-room-window" id="main-edit-form">
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
            <input 
                type="file" 
                id="room-main-picture-upload" 
                name="room_main_picture_upload" 
                accept="image/jpeg, image/png" 
                style="display: none;"
            />
            <input type="hidden" name="upload_room_picture" id="upload-room-picture-flag" value="0">
            <input type="hidden" name="remove_room_picture" id="remove-room-picture-flag" value="0">

            <div 
                class="selected-room-photo" 
                id="selected-room-photo-clickable"
                style="cursor: pointer; position: relative;"
                title="Click to upload or change room picture"
            >
              <?php if (!empty($room_picture_data) && !empty($room_picture_type)): ?>
                <img 
                id="current-room-image"
                src="data:<?php echo $room_picture_type; ?>;base64,<?php echo base64_encode($room_picture_data); ?>" 
                alt="<?php echo $room_name; ?> Picture"
                style="max-width: 300px; height: auto; border: 3px solid #3498db; border-radius: 8px; transition: border-color 0.3s;"
                />
                <div 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); border-radius: 8px; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s;"
                    onmouseover="this.style.opacity='1'"
                    onmouseout="this.style.opacity='0'">
                    <span style="color: white; font-weight: bold; text-shadow: 0 0 5px black; font-size: 1.2em;">Click to Change</span>
                </div>
              <?php else: ?>
                <p 
                    id="current-room-image-placeholder" 
                    style="border: 3px dashed #777; padding: 20px; border-radius: 8px; width: 300px; height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; color: #777; transition: border-color 0.3s; background-color: #f0f0f0;"
                    onmouseover="this.style.borderColor='#3498db'"
                    onmouseout="this.style.borderColor='#777'"
                >
                    No picture available. <br>Click here to upload one!
                </p>
              <?php endif; ?>
              </div>
                <div>
            <?php if (!empty($room_picture_data)): ?>
                <button class="remove-room-pic-button" type="button" id="remove-room-pic-button" style="background-color: #e74c3c; margin-top: 5px; margin-bottom: 10px; padding: 5px 10px; border-radius: 5px; font-size: 0.9em; cursor: pointer;">
                    Remove Current Picture
                </button>
            <?php endif; ?>
                </div>
                <div class = "types">
            <div>
            <label for="new_room_type" style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">Room Status:</label>
            <select name="new_room_type" id="new_room_type" class="editable-field" required>
                <option value="Faculty" <?php if ($room_type == 'Faculty') echo 'selected'; ?>>Faculty</option>
                <option value="Lecture" <?php if ($room_type == 'Lecture') echo 'selected'; ?>>Lecture</option>
                <option value="Laboratory" <?php if ($room_type == 'Laboratory') echo 'selected'; ?>>Laboratory</option>
                <option value="Miscellaneous" <?php if ($room_type == 'Miscellaneous') echo 'selected'; ?>>Miscellaneous</option>
                <option value="Setting Up" <?php if ($room_type == 'Setting Up') echo 'selected'; ?>>Setting Up</option>
            </select>
            </div>
            <div>
            <label for="new_room_status" style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">Room Status:</label>
            <select name="new_room_status" id="new_room_status" class="editable-field" required>
                <option value="Available" <?php if ($room_status == 'Available') echo 'selected'; ?>>Available</option>
                <option value="Maintenance" <?php if ($room_status == 'Maintenance') echo 'selected'; ?>>Maintenance</option>
                <option value="Setting Up" <?php if ($room_status == 'Setting Up') echo 'selected'; ?>>Setting Up</option>
            </select>
            </div>
            </div>

            <div class="to-edit-room-buttons" id="to-edit-room-buttons">

              <button class="edit-room-button" type="submit" name="update_room">
                Save Changes (Name/Status)
              </button>
              <button class="update-directions-button" type="button" id="update-directions-button">Manage Directions</button>
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
                    placeholder="Enter unique numerical QR ID (1 - 5)"
                    class="editable-field"
                    min="1"
                    max="5"
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
                    <?php if (!empty($directions_pictures_flat)): ?>
                        <p style="font-weight: bold; margin-bottom: 15px; color: white;">Click a picture below to Manage (Total: <?php echo count($directions_pictures_flat); ?>):</p>
                        
                        <?php foreach ($directions_groups_by_qr_sorted as $group_key => $pictures_in_group): ?>
                            <div style="border: 2px solid #ccc; border-radius: 8px; margin-bottom: 20px; padding: 10px;">
                                <?php $group_size = count($pictures_in_group); ?>
                                <h3 style="background-color: #f0f0f0; padding: 8px; border-radius: 6px; margin-top: 0; font-size: 1.1em; color: #333;">
                                    QR ID: <?php echo $group_key; ?> (Pictures: <?php echo $group_size; ?>)
                                </h3>

                                <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;">
                                    <?php foreach ($pictures_in_group as $picture): ?>                              
                                        <div 
                                            class="direction-picture-item" 
                                            data-picture-db-id="<?php echo htmlspecialchars($picture['id']); ?>" 
                                            data-group-key="<?php echo htmlspecialchars($group_key); ?>"
                                            data-group-index="<?php echo htmlspecialchars($picture['group_index']); ?>" 
                                            data-group-size="<?php echo htmlspecialchars($group_size); ?>"
                                            style="cursor: pointer; flex: 0 0 calc(50% - 10px); max-width: 250px; border: 2px solid #3498db; padding: 5px; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; background-color: #f9f9f9;"
                                            onmouseover="this.style.transform='scale(1.03)'"
                                            onmouseout="this.style.transform='scale(1)'"
                                            title="Click to manage Picture Index: <?php echo htmlspecialchars($picture['group_index']); ?> (QR: <?php echo $group_key; ?>)"
                                        >
                                            <img 
                                            src="data:<?php echo $picture['type']; ?>;base64,<?php echo base64_encode($picture['data']); ?>" 
                                            alt="Directions Picture <?php echo $picture['group_index']; ?>"
                                            style="width: 100%; height: auto; border-radius: 4px;"
                                            />
                                            <span style="display: block; font-size: 0.8em; color: #555; margin-top: 5px;">
                                                Index: <?php echo htmlspecialchars($picture['group_index']); ?> (DB ID: <?php echo htmlspecialchars($picture['id']); ?>)
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <p style="margin-top: 15px; font-style: italic; font-size: 0.9em; color: white;">All pictures shown here are currently associated with this room.</p>
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
            <h2>Manage Directions for Room: <span style="color: #3498db;"><?php echo $room_name; ?></span></h2>
            
            <p style=" font-weight: 500; color: white;">
                Group: <strong><span id="current-group-key-display" style="color: #555; font-size: 1.1em;"></span></strong> | 
                Index: <strong><span id="current-picture-index-managed" style="color: #f39c12; font-size: 1.1em;"></span></strong> 
                (DB ID: <span id="current-picture-db-id-managed" style="font-size: 0.9em;"></span>)
            </p>

            <form method="post" action="" enctype="multipart/form-data" id="replace-picture-form" style="padding-bottom: 20px; border-bottom: 1px dashed #ccc;">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <input type="hidden" name="picture_id_to_update" id="picture-id-to-update" value="">
                <input type="hidden" name="replace_picture" value="1">
                
                <p style="font-weight: bold; margin-bottom: 10px; color: white;">Option 1: Replace Picture Content</p>
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
                <input type="hidden" name="current_group_key" id="current-group-key-for-swap" value="">
                <input type="hidden" name="picture_group_index" id="picture-group-index-to-swap" value="">
                <input type="hidden" name="swap_picture" value="1">
                
                <p style="font-weight: bold; margin-bottom: 10px; color: white;">Option 2: Swap Picture Content (Change Order)</p>
                <p style="font-size: 0.9em; color: white; margin-bottom: 10px;">Swaps the visual content with the picture at the target **Index** *within the current group*.</p>
                
                <label for="target-picture-group-index" style="margin-top: 5px;">Target Picture Group Index to Swap With:</label>
                <input 
                    type="number" 
                    id="target-picture-group-index" 
                    name="target_picture_group_index" 
                    required
                    class="editable-field"
                    min="1"
                    pattern="\d*"
                    placeholder="Enter target Group Index"
                    style="width: 100%; box-sizing: border-box; color: #333;"
                >
                <div style="font-size: 0.8em; color: white; margin-top: 5px;">
                    Current Group Size: <strong id="current-group-size-display"></strong>. Available Indices: <span id="current-group-indices-display"></span>
                </div>

                <div class="to-edit-room-buttons" style="margin-top: 15px;">
                    <button class="edit-room-button" type="submit" style="background-color: #f39c12;">Swap Content</button>
                </div>
            </form>

            <form method="post" action="" id="delete-picture-form" style="margin-top: 20px;">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <input type="hidden" name="picture_id_to_delete" id="picture-id-to-delete-form" value="">
                <input type="hidden" name="delete_picture" value="1">
                
                <p style="font-weight: bold; margin-bottom: 10px; color: white;">Option 3: Delete Picture</p>
                <p style="font-size: 0.8em; color: #f9f9f9; background-color: #c0392b; padding: 5px; border-radius: 4px;">
                    WARNING: This action is permanent and cannot be undone.
                </p>

                <div class="to-edit-room-buttons">
                    <button class="edit-room-button" type="submit" style="background-color: #e74c3c;">Delete Picture Permanently</button>
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
          const pictureIdToDeleteForm = document.getElementById('picture-id-to-delete-form'); 
          
          const currentGroupKeyForSwap = document.getElementById('current-group-key-for-swap');
          const pictureGroupIndexToSwapInput = document.getElementById('picture-group-index-to-swap');
          const targetPictureGroupIndexInput = document.getElementById('target-picture-group-index');
          
          const currentGroupKeyDisplay = document.getElementById('current-group-key-display');
          const currentPictureIndexManaged = document.getElementById('current-picture-index-managed');
          const currentPictureDbIdManaged = document.getElementById('current-picture-db-id-managed');
          const currentGroupSizeDisplay = document.getElementById('current-group-size-display');
          const currentGroupIndicesDisplay = document.getElementById('current-group-indices-display');

          const backToDirectionsListButton = document.getElementById('back-to-directions-list-button');
          
          const mainEditForm = document.getElementById('main-edit-form');
          const photoClickable = document.getElementById('selected-room-photo-clickable');
          const fileUploadInput = document.getElementById('room-main-picture-upload');
          const uploadFlagInput = document.getElementById('upload-room-picture-flag');
          const removeFlagInput = document.getElementById('remove-room-picture-flag');
          const removePicButton = document.getElementById('remove-room-pic-button');

          
          const showView = (viewElement) => {
              editRoomContainer.style.display = 'none';
              updateDirectionsContainer.style.display = 'none';
              singlePictureManagement.style.display = 'none';
              if (viewElement) {
                  viewElement.style.display = 'block';
              }
          };

          if (photoClickable && fileUploadInput) {
              photoClickable.addEventListener('click', function(e) {
                  if (e.target.closest('#remove-room-pic-button')) {
                      return; 
                  }
                  fileUploadInput.click();
              });

              fileUploadInput.addEventListener('change', function() {
                  if (this.files.length > 0) {
                      uploadFlagInput.value = '1';
                      removeFlagInput.value = '0';
                      mainEditForm.submit();
                  }
              });
          }

          if (removePicButton) {
              removePicButton.addEventListener('click', function(e) {
                  e.preventDefault();
                  console.warn('Attempting to remove the main room picture...');
                  uploadFlagInput.value = '0'; 
                  removeFlagInput.value = '1'; 
                  mainEditForm.submit();
              });
          }


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
                  const groupKey = this.getAttribute('data-group-key');
                  const groupIndex = this.getAttribute('data-group-index');
                  const groupSize = parseInt(this.getAttribute('data-group-size'));
                  
                  // Set IDs for forms
                  pictureIdToUpdateInput.value = pictureDbId; 
                  pictureIdToDeleteForm.value = pictureDbId; 
                  
                  currentGroupKeyForSwap.value = groupKey;
                  pictureGroupIndexToSwapInput.value = groupIndex; 

                  currentGroupKeyDisplay.textContent = groupKey;
                  currentPictureIndexManaged.textContent = groupIndex;
                  currentPictureDbIdManaged.textContent = pictureDbId;

                  targetPictureGroupIndexInput.max = groupSize;
                  targetPictureGroupIndexInput.placeholder = `Enter target Group Index (1 to ${groupSize})`;
                  
                  currentGroupSizeDisplay.textContent = groupSize;
                  const indices = Array.from({length: groupSize}, (_, i) => i + 1).join(', ');
                  currentGroupIndicesDisplay.textContent = indices;
                  
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
