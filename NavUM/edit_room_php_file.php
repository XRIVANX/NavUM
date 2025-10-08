<?php

if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    header("Location: index.php?page=admin_dashboard");
    exit();
}

$room_id = $_GET['room_id'];
$room_name = 'Setting Up';
$room_type = 'Setting Up';
$room_status = 'Setting Up';
$room_picture_data = null;
$room_picture_type = null;

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
    $room_type = htmlspecialchars($room['room_type']);
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
    $sql_endpoint = "SELECT end_point_id FROM end_points WHERE room_id = ?";
    $stmt_endpoint = $conn->prepare($sql_endpoint);
    $stmt_endpoint->bind_param("i", $room_id);
    $stmt_endpoint->execute();
    $result_endpoint = $stmt_endpoint->get_result();
    $end_point_id = null;
    if ($result_endpoint->num_rows > 0) {
        $end_point_id = $result_endpoint->fetch_assoc()['end_point_id'];
    }
    $stmt_endpoint->close();
    
    if ($end_point_id) {
        $sql_directions = "SELECT end_point_pictures_id, end_point_pictures, end_point_picture_type FROM end_point_pictures WHERE end_point_id = ? ORDER BY end_point_pictures_id ASC";
        $stmt_directions = $conn->prepare($sql_directions);
        $stmt_directions->bind_param("i", $end_point_id);
        $stmt_directions->execute();
        $result_directions = $stmt_directions->get_result();

        if ($result_directions->num_rows > 0) {
            $group_index = 1;
            $group_key = 'Directions'; 

            while ($row = $result_directions->fetch_assoc()) {
                
                $directions_pictures_flat[] = [
                    'id' => (int)$row['end_point_pictures_id'],
                    'data' => $row['end_point_pictures'],
                    'type' => $row['end_point_picture_type'],
                    'group_index' => $group_index 
                ];

                if (!isset($directions_groups_by_qr[$group_key])) {
                     $directions_groups_by_qr[$group_key] = [];
                }
                $directions_groups_by_qr[$group_key][] = [
                    'id' => (int)$row['end_point_pictures_id'],
                    'data' => $row['end_point_pictures'],
                    'type' => $row['end_point_picture_type'],
                    'group_index' => $group_index
                ];

                $group_index++;
            }
        }
    }
    $directions_groups_by_qr_sorted = $directions_groups_by_qr;
}


$room_id_post = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['room_id']) && 
    $room_id_post) {

    // --- Main Room Picture Upload/Update Logic ---
    if (isset($_POST['upload_room_picture']) && $_POST['upload_room_picture'] === '1' && 
        isset($_FILES['room_main_picture_upload']) && $_FILES['room_main_picture_upload']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp = $_FILES['room_main_picture_upload']['tmp_name'];
        $file_type = $_FILES['room_main_picture_upload']['type'];
        $file_data = file_get_contents($file_tmp);

        // SQL query updated to include room_picture and room_picture_type
        $sql_update_pic = "UPDATE rooms SET room_picture = ?, room_picture_type = ? WHERE room_id = ?";
        $stmt_update_pic = $conn->prepare($sql_update_pic);
        $stmt_update_pic->bind_param("ssi", $file_data, $file_type, $room_id_post);
        
        if ($stmt_update_pic->execute()) {
             log_user_action($conn, 'ROOM_PIC_UPLOAD', 'Uploaded main room picture for Room ID: ' . $room_id_post);
             header("Location: edit_room.php?room_id=" . $room_id_post . "&room_pic_update_success=true");
             exit();
        } else {
             $message = "Error uploading main room picture: " . $conn->error;
        }
        $stmt_update_pic->close();

    // --- Main Room Picture Remove Logic ---
    } else if (isset($_POST['remove_room_picture']) && $_POST['remove_room_picture'] === '1') {
        
        // SQL query updated to set room_picture and room_picture_type to NULL
        $sql_remove_pic = "UPDATE rooms SET room_picture = NULL, room_picture_type = NULL WHERE room_id = ?";
        $stmt_remove_pic = $conn->prepare($sql_remove_pic);
        $stmt_remove_pic->bind_param("i", $room_id_post);
        
        if ($stmt_remove_pic->execute()) {
             log_user_action($conn, 'ROOM_PIC_REMOVE', 'Removed main room picture for Room ID: ' . $room_id_post);
             header("Location: edit_room.php?room_id=" . $room_id_post . "&room_pic_remove_success=true");
             exit();
        } else {
             $message = "Error removing main room picture: " . $conn->error;
        }
        $stmt_remove_pic->close();

    // --- Directions Picture Upload Logic ---
    } else if (isset($_POST['update_directions']) && $_POST['update_directions'] === '1') {
        
        if (!$end_point_id) {
            $sql_insert_endpoint = "INSERT INTO end_points (room_id) VALUES (?)";
            $stmt_insert_endpoint = $conn->prepare($sql_insert_endpoint);
            $stmt_insert_endpoint->bind_param("i", $room_id_post);
            if ($stmt_insert_endpoint->execute()) {
                $end_point_id = $conn->insert_id;
            } else {
                $message = "Error creating end point: " . $conn->error;
                $stmt_insert_endpoint->close();
                goto end_post;
            }
            $stmt_insert_endpoint->close();
        }
        
        $files_uploaded_count = 0;
        $upload_successful = true; 
        
        if (isset($_FILES['new_end_point_picture']) && is_array($_FILES['new_end_point_picture']['error'])) {
            $file_count = count($_FILES['new_end_point_picture']['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['new_end_point_picture']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['new_end_point_picture']['tmp_name'][$i];
                    $file_type = $_FILES['new_end_point_picture']['type'][$i];
                    $file_data = file_get_contents($file_tmp);

                    // SQL query: Inserts into end_point_pictures using end_point_id
                    $sql_insert = "INSERT INTO end_point_pictures (end_point_id, end_point_pictures, end_point_picture_type) VALUES (?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("iss", $end_point_id, $file_data, $file_type);
                    
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
        

        if ($upload_successful) {
            if ($files_uploaded_count > 0) {
                log_user_action($conn, 'DIRECTIONS_UPLOAD', 
                    "Uploaded $files_uploaded_count new direction picture(s). Room ID: " . $room_id_post);

                header("Location: index.php?page=edit_room&room_id=" . $room_id_post . "&directions_update_success=true&files_count=" . $files_uploaded_count);
                exit();
            } else {
                $message = "No new pictures were uploaded.";
            }
        }
    }
}
end_post:

// --- Directions Picture Delete Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_picture']) && $room_id_post) {
    $picture_id_to_delete = filter_input(INPUT_POST, 'picture_id_to_delete', FILTER_VALIDATE_INT);
    if ($picture_id_to_delete) {
        $sql_delete = "DELETE FROM end_point_pictures WHERE end_point_pictures_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $picture_id_to_delete);
        if ($stmt_delete->execute()) {
            log_user_action($conn, 'DIRECTIONS_PIC_DELETE', 'Deleted directions picture (DB ID: ' . $picture_id_to_delete . ') from Room ID: ' . $room_id_post); 
            header("Location: index.php?page=edit_room&room_id=" . $room_id_post . "&delete_success=" . $picture_id_to_delete);
        exit();
        } else {
            $message = "Error deleting picture: " . $conn->error;
        }
        $stmt_delete->close();
    }
}

// --- Directions Picture Replace Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replace_picture']) && $room_id_post) {
    $picture_id_to_update = filter_input(INPUT_POST, 'picture_id_to_update', FILTER_VALIDATE_INT);
    if ($picture_id_to_update && isset($_FILES['replacement_picture']) && $_FILES['replacement_picture']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['replacement_picture']['tmp_name'];
        $file_type = $_FILES['replacement_picture']['type'];
        $file_data = file_get_contents($file_tmp);

        $sql_update = "UPDATE end_point_pictures SET end_point_pictures = ?, end_point_picture_type = ? WHERE end_point_pictures_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $file_data, $file_type, $picture_id_to_update);
        
        if ($stmt_update->execute()) {
            log_user_action($conn, 'DIRECTIONS_PIC_REPLACE', 'Replaced content of directions picture (DB ID: ' . $picture_id_to_update . ') in Room ID: ' . $room_id_post); 
            header("Location: index.php?page=edit_room&room_id=" . $room_id_post . "&update_picture_success=" . $picture_id_to_update);
        exit();
        } else {
            $message = "Error replacing picture: " . $conn->error;
        }
        $stmt_update->close();
    } else {
        $message = "Error: Invalid picture ID or replacement file not uploaded.";
    }
}

// --- Directions Picture Swap Logic ---
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
        $pictures_in_group = $directions_groups_by_qr[$current_group_key];
        
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
            $sql_fetch = "SELECT end_point_pictures_id, end_point_pictures, end_point_picture_type FROM end_point_pictures WHERE end_point_pictures_id = ? OR end_point_pictures_id = ?";
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

                // Update #1 with content of #2
                $sql_update1 = "UPDATE end_point_pictures SET end_point_pictures = ?, end_point_picture_type = ? WHERE end_point_pictures_id = ?";
                $stmt_update1 = $conn->prepare($sql_update1);
                $stmt_update1->bind_param("ssi", $data2['end_point_pictures'], $data2['end_point_picture_type'], $picture_id_1);
                $stmt_update1->execute();
                $stmt_update1->close();

                // Update #2 with content of #1
                $sql_update2 = "UPDATE end_point_pictures SET end_point_pictures = ?, end_point_picture_type = ? WHERE end_point_pictures_id = ?";
                $stmt_update2 = $conn->prepare($sql_update2);
                $stmt_update2->bind_param("ssi", $data1['end_point_pictures'], $data1['end_point_picture_type'], $picture_id_2);
                $stmt_update2->execute();
                $stmt_update2->close();
                
                $conn->commit();
                $swap_successful = true;
                log_user_action($conn, 'DIRECTIONS_PIC_SWAP', "Swapped picture content between Index " . $picture_group_index_1 . " and Index " . $picture_group_index_2 . " in Group: " . $current_group_key . " for Room ID: " . $room_id_post);
            } else {
                 throw new Exception("One or both database IDs were not found for swapping. (Debug: " . count($data) . " rows found)");
            }

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Swap failed: " . $e->getMessage();
        }
        
        if ($swap_successful) {
            header("Location: index.php?page=edit_room&room_id=" . $room_id_post . "&swap_success=" . $picture_group_index_1 . "&swapped_with=" . $picture_group_index_2 . "&group=" . urlencode($current_group_key));
            exit();
        }

    } else {
        $message = "Error: Invalid indices or picture IDs provided for swap. **You can only swap pictures within the same Group: " . $current_group_key . "**";
    }
}


if (isset($_GET['directions_update_success']) && $_GET['directions_update_success'] === 'true') {
    $files_count = isset($_GET['files_count']) ? (int)$_GET['files_count'] : 0;
    
    if ($files_count > 0) {
        $message = "Successfully uploaded " . $files_count . " new directions picture(s)!"; 
    } else {
        $message = "Directions pictures updated successfully.";
    }
}

if (isset($_GET['room_pic_update_success']) && $_GET['room_pic_update_success'] === 'true') {
    $message = "Main room picture uploaded successfully!";
}

if (isset($_GET['room_pic_remove_success']) && $_GET['room_pic_remove_success'] === 'true') {
    $message = "Main room picture removed successfully!";
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