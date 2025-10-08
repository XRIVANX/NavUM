document.addEventListener("DOMContentLoaded", function () {
  const roomsList = document.getElementById("roomsList");
  const campusMapImg = document.querySelector(".campus-map-img");
  const imageViewer = document.getElementById("imageViewer");
  const currentImage = document.getElementById("currentImage");
  const backBtn = document.getElementById("backBtn");
  const nextBtn = document.getElementById("nextBtn");

  let directionsData = [];
  let currentImageIndex = 0;

  function updateImageViewer() {
    if (directionsData.length === 0) {
      imageViewer.style.display = "none";
      campusMapImg.style.display = "block";
      return;
    }

    campusMapImg.style.display = "none";
    imageViewer.style.display = "flex";

    const currentDir = directionsData[currentImageIndex];
    currentImage.src = `data:${currentDir.type};base64,${currentDir.data}`;
    currentImage.alt = `Direction Step ${currentImageIndex + 1} of ${
      directionsData.length
    }`;

    backBtn.style.display = currentImageIndex > 0 ? "block" : "none";
    nextBtn.style.display =
      currentImageIndex < directionsData.length - 1 ? "block" : "none";
  }

  nextBtn.addEventListener("click", () => {
    if (currentImageIndex < directionsData.length - 1) {
      currentImageIndex++;
      updateImageViewer();
    }
  });

  backBtn.addEventListener("click", () => {
    if (currentImageIndex > 0) {
      currentImageIndex--;
      updateImageViewer();
    }
  });

  roomsList.addEventListener("click", function (event) {
    const clickedElement = event.target.closest(".room-item-clickable");
    if (clickedElement) {
      const roomId = clickedElement.getAttribute("data-room-id");

      directionsData = [];
      currentImageIndex = 0;
      imageViewer.style.display = "none";
      campusMapImg.style.display = "block";
      currentImage.alt = "Loading directions...";

      fetch(`fetch_room_directions.php?room_id=${roomId}`)
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.json();
        })
        .then((data) => {
          if (data.status === "success" && data.directions.length > 0) {
            directionsData = data.directions;
            currentImageIndex = 0;
            updateImageViewer();
          } else {
            directionsData = [];
            alert("No directions found for this room.");
            updateImageViewer();
          }
        })
        .catch((error) => {
          console.error("Fetch error:", error);
          directionsData = [];
          alert("Error fetching directions. Please try again.");
          updateImageViewer();
        });
    }
  });

  const roomsBtn = document.getElementById("roomsBtn");
  const arrowUp = document.getElementById("arrowUp");
  roomsBtn.addEventListener("click", function () {
    roomsList.style.display =
      roomsList.style.display === "block" ? "none" : "block";
    arrowUp.textContent = roomsList.style.display === "block" ? "▲" : "▼";
  });

  updateImageViewer();
});
