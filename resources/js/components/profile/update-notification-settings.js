import { createAndShowToast } from '@/utils';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';
/**
 * Handles notification settings toggling and async updates
 */
const updateNotificationSettings = () => {
  if (typeof $ === 'undefined') {
    console.error('jQuery is not loaded');
    return;
  }

  const form = $('#notification-settings-form');
  if (!form.length) {
    console.error('Notification settings form not found');
    return;
  }

  // Try to get API endpoint from form data attribute or use the config value
  let apiEndpoint = form.data('api-endpoint');
  if (!apiEndpoint) {
    if (config.apiProfileNotificationsUpdateEndpoint) {
      apiEndpoint = config.apiProfileNotificationsUpdateEndpoint;
    } else {
      console.error('API endpoint not found in form or config');
      return;
    }
  }

  // Track pending requests to prevent multiple simultaneous calls
  let isPending = false;

  // Handle checkbox toggle
  form.on('change', '.notification-setting-toggle', async function (e) {
    // Prevent multiple requests
    if (isPending) {
      e.preventDefault();
      return;
    }
    const notificationTub = $(this).closest('[data-tub="notifications"]');
    isPending = true;
    // loader.show();
    const loader = showInElement(notificationTub[0]);

    // Get checkbox that was changed
    const checkbox = $(this);
    const isChecked = checkbox.prop('checked');
    const settingName = checkbox.attr('name');

    // Disable the checkbox during request
    checkbox.prop('disabled', true);

    // Prepare form data with proper structure for notification settings
    const formData = new FormData();
    formData.append('_token', form.find('input[name="_token"]').val());

    // Since we're only handling the 'system' key, we can simplify this
    console.log('Toggling system notifications to:', isChecked);

    // Create proper structure for the notification settings - always 'system' key
    formData.append('notification_settings[system]', isChecked ? '1' : '0');

    try {
      const response = await ajaxFetcher.form(apiEndpoint, formData);

      if (response.success) {
        createAndShowToast(response.message, 'success');

        // Update checkbox state based on response from server
        if (typeof response.system_enabled === 'boolean') {
          checkbox.prop('checked', response.system_enabled);
        }
      } else {
        // Revert the checkbox if request failed
        checkbox.prop('checked', !isChecked);

        if (response.errors) {
          Object.keys(response.errors).forEach(field => {
            const errorMessage = response.errors[field].join(', ');
            createAndShowToast(errorMessage, 'error');
          });
        } else {
          createAndShowToast(response.message || 'Error updating notification settings', 'error');
        }
      }
    } catch (error) {
      // Revert the checkbox on error
      checkbox.prop('checked', !isChecked);

      console.error('Error updating notification settings:', error);
      createAndShowToast('Error updating notification settings. Please try again.', 'error');
    } finally {
      // Re-enable the checkbox
      checkbox.prop('disabled', false);
      isPending = false;
      hideInElement(loader);
    }
  });
};

const initUpdateNotificationSettings = () => {
  if ($('#notification-settings-form').length) {
    updateNotificationSettings();
  }
};

export { initUpdateNotificationSettings, updateNotificationSettings };
