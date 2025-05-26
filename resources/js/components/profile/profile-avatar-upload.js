/**
 * Profile avatar upload handler
 * Manages asynchronous avatar uploads with loading state and feedback
 */
import { logger, loggerError } from '@/helpers/logger';
import { createAndShowToast } from '@/utils/uiHelpers';
import { hideInButton, showInButton } from '../loader';
document.addEventListener('DOMContentLoaded', function () {
  const avatarUploader = {
    // Configuration
    fileInputId: 'photo',
    uploadButtonId: 'upload-photo-button',
    avatarContainerId: 'user-avatar-container',
    fileNameId: 'selected-file-name',
    loadingClass: 'is-loading',
    apiEndpoint: '/api/profile/avatar',
    originalLabel: null,

    // DOM elements
    fileInput: null,
    uploadButton: null,
    avatarContainer: null,
    fileNameElement: null,

    // Initialize
    init: function () {
      // Get DOM elements
      this.fileInput = document.getElementById(this.fileInputId);
      const uploadButtonLabel = document.querySelector(`label[for="${this.fileInputId}"]`);
      this.uploadButton = uploadButtonLabel;
      this.avatarContainer = document.getElementById(this.avatarContainerId);
      // this.fileNameElement = document.getElementById(this.fileNameId);

      if (!this.fileInput) {
        loggerError('Avatar file input not found');
        return;
      }

      if (uploadButtonLabel) {
        this.originalLabel = uploadButtonLabel.innerHTML;
      }

      // Initialize event listeners
      this.initEventListeners();

      logger('Avatar uploader initialized');
    },

    // Set up event listeners
    initEventListeners: function () {
      // File selection event
      this.fileInput.addEventListener('change', event => {
        const file = event.target.files[0];
        if (!file) return;

        // Display file info
        // this.showFileInfo(file);

        // Upload the file automatically on selection
        this.uploadFile(file);
      });
    },

    // Display selected file information
    // showFileInfo: function (file) {
    //   // if (!this.fileNameElement) return;

    //   const img = new Image();
    //   img.onload = () => {
    //     const fileType = file.name.split('.').pop().toUpperCase();
    //     const fileSizeInKB = Math.round(file.size / 1024);
    //     this.fileNameElement.textContent = `${fileType} (${img.width}x${img.height}) ${fileSizeInKB}kb`;
    //   };
    //   img.src = URL.createObjectURL(file);
    // },

    // Upload the file to the server
    uploadFile: async function (file) {
      if (!this.uploadButton) return;
      this.uploadButton.disabled = true;
      try {
        // Show loading state
        this.showLoadingState();

        // Create FormData
        const formData = new FormData();
        formData.append('avatar', file);

        // Add CSRF token
        const csrfToken = document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute('content');

        // Get API token if available
        const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content');

        // Set headers
        const headers = {
          'X-CSRF-TOKEN': csrfToken || '',
        };

        if (apiToken) {
          headers['Authorization'] = `Bearer ${apiToken}`;
        }

        // Make API request
        const response = await fetch(this.apiEndpoint, {
          method: 'POST',
          body: formData,
          headers: headers,
          credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || 'Failed to upload avatar');
        }

        // Handle success
        this.handleUploadSuccess(data);
      } catch (error) {
        // Handle error
        this.handleUploadError(error);
      } finally {
        // Reset loading state
        this.resetLoadingState();
        this.uploadButton.disabled = false;
      }
    },

    // Show loading state on the upload button
    showLoadingState: function () {
      if (!this.uploadButton) return;
      showInButton(this.uploadButton, '_dark');
      // this.uploadButton.classList.add(this.loadingClass);
      // this.uploadButton.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Uploading...`;
    },

    // Reset loading state on the upload button
    resetLoadingState: function () {
      if (!this.uploadButton || !this.originalLabel) return;
      hideInButton(this.uploadButton);
      // this.uploadButton.classList.remove(this.loadingClass);
      // this.uploadButton.innerHTML = this.originalLabel;
    },

    // Handle successful upload
    handleUploadSuccess: function (data) {
      // Show success toast
      createAndShowToast(data.message, 'success');

      const userPreviewAvatarHeader = $('#user-preview-avatar-header');
      if (userPreviewAvatarHeader.length) {
        const img = userPreviewAvatarHeader.find('#user-preview-avatar-header-img');
        if (img.length) {
          img.attr('src', data.avatar.url);
        } else {
          // Create new image element for the user preview header
          const newImg = document.createElement('img');
          newImg.id = 'user-preview-avatar-header-img';
          newImg.src = data.avatar.url;
          newImg.alt = 'User Avatar';

          // Clear any existing content (like initials)
          userPreviewAvatarHeader.empty();

          // Append the new image to the container
          userPreviewAvatarHeader.append(newImg);
          userPreviewAvatarHeader.attr('src', data.avatar.url);
        }
      }

      // Update avatar image if container exists
      if (this.avatarContainer) {
        const avatar = data.avatar;

        // Create image if it doesn't exist
        let imgElement = this.avatarContainer.querySelector('img');
        if (!imgElement) {
          imgElement = document.createElement('img');
          this.avatarContainer.textContent = ''; // Clear any text (like initials)
          this.avatarContainer.appendChild(imgElement);
        }

        // Update image source with cache buster to force refresh
        imgElement.src = `${avatar.url}?t=${Date.now()}`;
        imgElement.alt = 'User Avatar';
      }
    },

    // Handle upload error
    handleUploadError: function (error) {
      console.error('Avatar upload failed:', error);

      // Show error toast
      createAndShowToast(
        error.message || 'Failed to upload profile photo. Please try again.',
        'error'
      );

      // Reset file input to allow re-selection
      if (this.fileInput) {
        this.fileInput.value = '';
      }
    },
  };

  // Initialize the uploader
  if (document.getElementById('upload-photo-button')) {
    avatarUploader.init();
  }
});
