document.addEventListener("DOMContentLoaded", function () {
  const editRoomContainer = document.getElementById("edit-room-container");
  const updateDirectionsContainer = document.getElementById(
    "update-directions-container"
  );
  const singlePictureManagement = document.getElementById(
    "single-picture-management"
  );

  const updateDirectionsButton = document.getElementById(
    "update-directions-button"
  );
  const backToRoomEditButton = document.getElementById(
    "back-to-room-edit-button"
  );

  const pictureItems = document.querySelectorAll(".direction-picture-item");
  const pictureIdToUpdateInput = document.getElementById(
    "picture-id-to-update"
  );
  const pictureIdToDeleteForm = document.getElementById(
    "picture-id-to-delete-form"
  );

  const currentGroupKeyForSwap = document.getElementById(
    "current-group-key-for-swap"
  );
  const pictureGroupIndexToSwapInput = document.getElementById(
    "picture-group-index-to-swap"
  );
  const targetPictureGroupIndexInput = document.getElementById(
    "target-picture-group-index"
  );

  const currentGroupKeyDisplay = document.getElementById(
    "current-group-key-display"
  );
  const currentPictureIndexManaged = document.getElementById(
    "current-picture-index-managed"
  );
  const currentPictureDbIdManaged = document.getElementById(
    "current-picture-db-id-managed"
  );
  const currentGroupSizeDisplay = document.getElementById(
    "current-group-size-display"
  );
  const currentGroupIndicesDisplay = document.getElementById(
    "current-group-indices-display"
  );

  const backToDirectionsListButton = document.getElementById(
    "back-to-directions-list-button"
  );

  const mainEditForm = document.getElementById("main-edit-form");

  const photoClickable = document.getElementById(
    "selected-room-photo-clickable"
  );
  const fileUploadInput = document.getElementById("room-main-picture-upload");
  const uploadFlagInput = document.getElementById("upload-room-picture-flag");
  const removeFlagInput = document.getElementById("remove-room-picture-flag");
  const removePicButton = document.getElementById("remove-room-pic-button");

  const showView = (viewElement) => {
    editRoomContainer.style.display = "none";
    updateDirectionsContainer.style.display = "none";
    singlePictureManagement.style.display = "none";
    if (viewElement) {
      viewElement.style.display = "block";
    }
  };

  // --- Room Picture Upload/Remove JS Logic ---
  if (photoClickable && fileUploadInput) {
    photoClickable.addEventListener("click", function (e) {
      if (e.target.closest("#remove-room-pic-button")) {
        return;
      }
      fileUploadInput.click();
    });

    fileUploadInput.addEventListener("change", function () {
      if (this.files.length > 0) {
        uploadFlagInput.value = "1";
        removeFlagInput.value = "0";
        mainEditForm.submit();
      }
    });
  }

  if (removePicButton) {
    removePicButton.addEventListener("click", function (e) {
      e.preventDefault();
      uploadFlagInput.value = "0";
      removeFlagInput.value = "1";
      mainEditForm.submit();
    });
  }

  // --- Directions Management JS Logic ---
  if (updateDirectionsButton) {
    updateDirectionsButton.addEventListener("click", function (e) {
      e.preventDefault();
      showView(updateDirectionsContainer);
    });
  }
  if (backToRoomEditButton) {
    backToRoomEditButton.addEventListener("click", function () {
      showView(editRoomContainer);
    });
  }

  pictureItems.forEach((item) => {
    item.addEventListener("click", function () {
      const pictureDbId = this.getAttribute("data-picture-db-id");
      const groupKey = this.getAttribute("data-group-key");
      const groupIndex = this.getAttribute("data-group-index");
      const groupSize = parseInt(this.getAttribute("data-group-size"));

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
      const indices = Array.from({ length: groupSize }, (_, i) => i + 1).join(
        ", "
      );
      currentGroupIndicesDisplay.textContent = indices;

      showView(singlePictureManagement);
    });
  });

  if (backToDirectionsListButton) {
    backToDirectionsListButton.addEventListener("click", function () {
      showView(updateDirectionsContainer);
    });
  }
});
