(function () {
  "use strict";
  var currentAnnotator = null;
  var annotatorModal = null;

  document.addEventListener("DOMContentLoaded", function () {
    var modalEl = document.getElementById("annotatorModal");
    if (modalEl) annotatorModal = new bootstrap.Modal(modalEl);

    var uploadForm = document.getElementById("upload-form");
    if (uploadForm) {
      uploadForm.addEventListener("submit", function (e) {
        e.preventDefault();
        uploadPhoto();
      });
    }

    var dropzone = document.getElementById("dropzone");
    var fileInput = document.getElementById("photo-file");
    if (dropzone && fileInput) {
      dropzone.addEventListener("click", function () {
        fileInput.click();
      });
      dropzone.addEventListener("dragover", function (e) {
        e.preventDefault();
        dropzone.classList.add("is-dragover");
      });
      dropzone.addEventListener("dragleave", function () {
        dropzone.classList.remove("is-dragover");
      });
      dropzone.addEventListener("drop", function (e) {
        e.preventDefault();
        dropzone.classList.remove("is-dragover");
        if (e.dataTransfer.files.length) fileInput.files = e.dataTransfer.files;
      });
    }

    document.addEventListener("click", function (e) {
      var target = e.target.closest("[data-action]");
      if (!target) return;
      var action = target.dataset.action;
      var photoId = target.dataset.photoId;
      var imageSrc = target.dataset.imageSrc;
      if (action === "annotate") openAnnotator(photoId, imageSrc);
      if (action === "delete") deletePhoto(photoId);
      if (action === "save-annotations") saveAnnotations();
    });
  });

  function uploadPhoto() {
    var form = document.getElementById("upload-form");
    var fileInput = document.getElementById("photo-file");
    if (!form || !fileInput) return;

    var formData = new FormData(form);
    if (!fileInput.files.length) {
      alert("Please select a photo");
      return;
    }
    formData.append("photo", fileInput.files[0]);

    var checkId = (window.AHG_CONDITION && window.AHG_CONDITION.checkId) || null;
    if (!checkId) {
      alert("Missing check ID");
      return;
    }

    fetch("/condition/check/" + checkId + "/upload", { method: "POST", body: formData })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) location.reload();
        else alert("Upload failed: " + (data.error || "Unknown error"));
      })
      .catch(function (err) { alert("Upload failed: " + err.message); });
  }

  function openAnnotator(photoId, imageSrc) {
    if (!photoId || !imageSrc) return;
    if (currentAnnotator) {
      currentAnnotator.destroy();
      currentAnnotator = null;
    }

    var modalEl = document.getElementById("annotatorModal");
    if (!modalEl || !annotatorModal) return;

    var initOnce = function () {
      modalEl.removeEventListener("shown.bs.modal", initOnce);
      currentAnnotator = new ConditionAnnotator("annotator-container", {
        photoId: photoId,
        imageUrl: imageSrc,
        readonly: false,
        showToolbar: true,
        saveUrl: "/condition/annotation/save",
        getUrl: "/condition/annotation/get"
      });
    };
    modalEl.addEventListener("shown.bs.modal", initOnce);
    annotatorModal.show();
  }

  function saveAnnotations() {
    if (!currentAnnotator) return;
    currentAnnotator.save().then(function () {
      annotatorModal.hide();
    });
  }

  function deletePhoto(photoId) {
    var msg = (window.AHG_CONDITION && window.AHG_CONDITION.confirmDelete) || "Delete?";
    if (!confirm(msg)) return;

    fetch("/condition/photo/" + photoId + "/delete", { method: "POST" })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) location.reload();
        else alert("Delete failed: " + (data.error || "Unknown error"));
      });
  }
})();
