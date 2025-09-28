<?php

if (isset($_POST['update_room'])) {

    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_SANITIZE_NUMBER_INT);
    $new_name = filter_input(INPUT_POST, 'new_room_name', FILTER_SANITIZE_STRING);
    $new_status = filter_input(INPUT_POST, 'new_room_status', FILTER_SANITIZE_STRING);

    if ($room_id && $new_name && $new_status) {
        $sql_update = "UPDATE rooms SET room_name = ?, room_status = ? WHERE room_id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update === false) {
            $message = "SQL Prepare Error: " . $conn->error;
        } else {
            $stmt_update->bind_param("ssi", $new_name, $new_status, $room_id);
            
            if ($stmt_update->execute()) {
                // *** FIX: POST/REDIRECT/GET (PRG) PATTERN ***
                // This redirect ensures the page reloads as a GET request, preventing form resubmission
                // and, most importantly, preserving the mandatory 'room_id' GET parameter.
                header("Location: edit_room.php?room_id=" . $room_id . "&update_success=true");
                exit(); // Crucial to stop script execution after redirect
            } else {
                $message = "Error updating room: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    } else {
        $message = "Error: Invalid input data for update.";
    }
}

?>