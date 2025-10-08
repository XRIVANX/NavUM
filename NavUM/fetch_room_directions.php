<?php
include("connect.php");
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request.', 'directions' => []];

if (isset($_GET['room_id']) && is_numeric($_GET['room_id'])) {
    $room_id = (int)$_GET['room_id'];
    
    // 1. Get the end_point_id associated with the room_id
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
    
    // 2. Fetch all pictures linked to that end_point_id, ordered by their ID (natural order)
    if ($end_point_id) {
        $sql_directions = "SELECT end_point_pictures, end_point_picture_type FROM end_point_pictures WHERE end_point_id = ? ORDER BY end_point_pictures_id ASC";
        $stmt_directions = $conn->prepare($sql_directions);
        $stmt_directions->bind_param("i", $end_point_id);
        $stmt_directions->execute();
        $result_directions = $stmt_directions->get_result();

        $pictures = [];
        while ($row = $result_directions->fetch_assoc()) {
            $pictures[] = [
                'type' => $row['end_point_picture_type'],
                // Base64 encode the binary data for JSON transfer
                'data' => base64_encode($row['end_point_pictures']) 
            ];
        }
        $stmt_directions->close();
        
        $response['status'] = 'success';
        $response['message'] = 'Directions fetched successfully.';
        $response['directions'] = $pictures;
    } else {
        $response['message'] = 'No end point defined for this room, or no pictures found.';
        $response['status'] = 'success'; // Treat as success with empty data
    }

}

$conn->close();
echo json_encode($response);
?>