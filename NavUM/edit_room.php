<?php
include("connect.php");
include("update_room.php");
include("log_action.php"); 
include("edit_room_php_file.php");
$message = '';

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
          <input type="hidden" name="upload_room_picture" id="upload-room-picture-flag" value="0">
          <input type="hidden" name="remove_room_picture" id="remove-room-picture-flag" value="0">
          
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
            
            <div class="selected-room-photo-container">
            <p style="font-weight: bold; margin-top: 15px; margin-bottom: 5px; color: white;">Main Room Picture (Click to change):</p>
                <div class="selected-room-photo-clickable" id="selected-room-photo-clickable">
                <?php if (!empty($room_picture_data)): ?>
                        <img 
                            src="data:<?php echo $room_picture_type; ?>;base64,<?php echo base64_encode($room_picture_data); ?>" 
                            alt="Room Photo"
                            style="max-width: 100%; height: auto; display: block; margin: 0 auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);"
                        />
                        </div>
                        <button class="remove-room-pic-button" id="remove-room-pic-button" style=" background-color: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Remove Picture</button>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; border: 2px dashed #ccc; color: #ccc;">
                            No Main Room Picture Set
                        </div>
                    <?php endif; ?>
                </div>
                <input type="file" id="room-main-picture-upload" name="room_main_picture_upload" accept="image/jpeg, image/png" style="display: none;">
            
            
            <div class = "types">
            
            <div>
            <label for="new_room_type" style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">Room Type:</label>
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
                Save Changes (Name/Type/Status)
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

                <div class="existing-directions-photos" style="margin-top: 20px; text-align: center;">
                    <?php if (!empty($directions_pictures_flat)): ?>
                        <p style="font-weight: bold; margin-bottom: 15px; color: white;">Click a picture below to Manage (Total: <?php echo count($directions_pictures_flat); ?>):</p>
                        
                        <?php foreach ($directions_groups_by_qr_sorted as $group_key => $pictures_in_group): ?>
                            <div style="border: 2px solid #ccc; border-radius: 8px; margin-bottom: 20px; padding: 10px;">
                                <p style="font-weight: bold; margin-bottom: 10px; color: #f39c12;">Group: <?php echo htmlspecialchars($group_key); ?></p>
                                <?php $group_size = count($pictures_in_group); ?>

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
                                            title="Click to manage Picture Index: <?php echo htmlspecialchars($picture['group_index']); ?> (Group: <?php echo $group_key; ?>)"
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
                    <button class="edit-room-button" type="submit">Upload New Picture(s)</button>
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
    <script src="edit_room_script.js"></script>
  </body>
</html>
<?php 
if (isset($conn)) {
    $conn->close();
}
?>