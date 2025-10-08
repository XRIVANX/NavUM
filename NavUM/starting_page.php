<?php
include("connect.php");

// Removed $scanned_qr_id as QR logic is now unused

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
    <style>
       /* Simple styling to make the image viewer look like a carousel */
       
    </style>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // UI Elements
            const roomsList = document.getElementById('roomsList');
            const campusMapImg = document.querySelector('.campus-map-img');
            const imageViewer = document.getElementById('imageViewer');
            const currentImage = document.getElementById('currentImage');
            const backBtn = document.getElementById('backBtn');
            const nextBtn = document.getElementById('nextBtn');

            let directionsData = []; // Array of {type, data} objects (pictures)
            let currentImageIndex = 0;

            // Function to display the current image and manage button visibility
            function updateImageViewer() {
                if (directionsData.length === 0) {
                    // Revert to default map view
                    imageViewer.style.display = 'none';
                    campusMapImg.style.display = 'block';
                    return;
                }

                // Show the viewer and hide the default map
                campusMapImg.style.display = 'none';
                imageViewer.style.display = 'flex';
                
                const currentDir = directionsData[currentImageIndex];
                currentImage.src = `data:${currentDir.type};base64,${currentDir.data}`;
                currentImage.alt = `Direction Step ${currentImageIndex + 1} of ${directionsData.length}`;
                
                // Update navigation buttons
                backBtn.style.display = currentImageIndex > 0 ? 'block' : 'none';
                nextBtn.style.display = currentImageIndex < directionsData.length - 1 ? 'block' : 'none';
            }

            // Button Handlers
            nextBtn.addEventListener('click', () => {
                if (currentImageIndex < directionsData.length - 1) {
                    currentImageIndex++;
                    updateImageViewer();
                }
            });

            backBtn.addEventListener('click', () => {
                if (currentImageIndex > 0) {
                    currentImageIndex--;
                    updateImageViewer();
                }
            });

            // Handle room click event
            roomsList.addEventListener('click', function(event) {
                const clickedElement = event.target.closest('.room-item-clickable');
                if (clickedElement) {
                    const roomId = clickedElement.getAttribute('data-room-id');
                    
                    // Reset to loading state
                    directionsData = [];
                    currentImageIndex = 0;
                    imageViewer.style.display = 'none';
                    campusMapImg.style.display = 'block'; // Show map while loading
                    currentImage.alt = 'Loading directions...';
                    
                    // Fetch directions using AJAX from the new file
                    fetch(`fetch_room_directions.php?room_id=${roomId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success' && data.directions.length > 0) {
                                directionsData = data.directions;
                                currentImageIndex = 0; // Start at the first picture
                                updateImageViewer();
                            } else {
                                directionsData = [];
                                alert('No directions found for this room.');
                                updateImageViewer(); // Will revert to map
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            directionsData = [];
                            alert('Error fetching directions. Please try again.');
                            updateImageViewer(); // Will revert to map
                        });
                }
            });
            
            // Existing roomsBtn functionality for toggle
            const roomsBtn = document.getElementById('roomsBtn');
            const arrowUp = document.getElementById('arrowUp');
            roomsBtn.addEventListener('click', function() {
                roomsList.style.display = roomsList.style.display === 'block' ? 'none' : 'block';
                arrowUp.textContent = roomsList.style.display === 'block' ? '▲' : '▼';
            });
            
            // Initial view setup: ensure map is shown
            updateImageViewer(); 
        });
    </script>
  </body>
</html>