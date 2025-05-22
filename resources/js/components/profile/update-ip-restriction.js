import { createAndShowToast } from '@/utils';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';

/**
 * Handle server validation errors
 * @param {Object} response - The error response from the server
 */
const handleServerValidationErrors = response => {
  if (response.errors) {
    // Clear previous errors
    $('input, textarea').removeClass('error');
    $('.error-message').remove();

    // Add errors for each field
    Object.keys(response.errors).forEach(field => {
      const input = $(`[name="${field}"]`);
      input.addClass('error');
      
      // Add error message after the input
      const errorMessage = response.errors[field][0];
      const errorDiv = $('<div>').addClass('error-message text-danger mt-1').text(errorMessage);
      errorDiv.insertAfter(input);
    });

    // Show toast with the main error message
    if (response.message) {
      createAndShowToast(response.message, 'error');
    }
  }
};

/**
 * Initialize jQuery validation for the IP restriction form
 * @param {jQuery} form - The form to validate
 */
const initFormValidation = form => {
  if (!form.length || !$.validator) return;

  // Add custom IP validation method
  $.validator.addMethod(
    'validIpAddresses',
    function(value, element) {
      if (!value || value.trim() === '') {
        return true; // Empty is valid (nullable)
      }
      
      // Split by newlines and check each line
      const ips = value.split('\n').map(ip => ip.trim()).filter(ip => ip !== '');
      
      for (const ip of ips) {
        // Check for simple IP address format
        const ipv4Regex = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
        const ipv4Match = ip.match(ipv4Regex);
        
        // Check for CIDR notation (e.g., 192.168.1.0/24)
        const cidrRegex = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/;
        const cidrMatch = ip.match(cidrRegex);
        
        // Check for IP range (e.g., 192.168.1.1-192.168.1.255)
        const rangeRegex = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})-(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
        const rangeMatch = ip.match(rangeRegex);
        
        // If it's not any valid format, return false
        if (!ipv4Match && !cidrMatch && !rangeMatch) {
          return false;
        }
        
        // For simple IP addresses, validate each octet
        if (ipv4Match) {
          for (let i = 1; i <= 4; i++) {
            const octet = parseInt(ipv4Match[i], 10);
            if (octet < 0 || octet > 255) {
              return false;
            }
          }
        }
        
        // For CIDR notation, validate the IP part and the mask
        if (cidrMatch) {
          for (let i = 1; i <= 4; i++) {
            const octet = parseInt(cidrMatch[i], 10);
            if (octet < 0 || octet > 255) {
              return false;
            }
          }
          
          const mask = parseInt(cidrMatch[5], 10);
          if (mask < 0 || mask > 32) {
            return false;
          }
        }
        
        // For IP ranges, validate both start and end IPs
        if (rangeMatch) {
          for (let i = 1; i <= 8; i++) {
            const octet = parseInt(rangeMatch[i], 10);
            if (octet < 0 || octet > 255) {
              return false;
            }
          }
        }
      }
      
      return true;
    },
    'Please enter valid IP addresses, IP ranges (e.g., 192.168.1.1-192.168.1.255), or CIDR notation (e.g., 192.168.1.0/24)'
  );

  form.validate({
    errorClass: 'error',
    errorElement: 'div',
    errorPlacement: function(error, element) {
      error.addClass('error-message text-danger mt-1');
      error.insertAfter(element);
    },
    highlight: function(element) {
      $(element).addClass('error');
    },
    unhighlight: function(element) {
      $(element).removeClass('error');
    },
    rules: {
      ip_restrictions: {
        validIpAddresses: true
      },
      password: {
        required: true
      }
    },
    messages: {
      ip_restrictions: {
        validIpAddresses: 'Please enter valid IP addresses, IP ranges, or CIDR notation'
      },
      password: {
        required: 'Password is required to update IP restrictions'
      }
    }
  });
};

/**
 * Handles the IP restriction form submission
 */
const updateIpRestriction = () => {
  if (typeof $ === 'undefined') {
    console.error('jQuery is not loaded');
    return;
  }

  const form = $('#ip-restriction-form');
  if (!form.length) {
    console.error('IP restriction form not found');
    return;
  }

  // Ensure textareas auto-resize
  const adjustHeight = element => {
    element.style.height = 'auto';
    element.style.height = element.scrollHeight + 'px';
  };

  const textareas = document.querySelectorAll('.auto-resize');
  textareas.forEach(textarea => {
    adjustHeight(textarea);
    textarea.addEventListener("input", function() {
      adjustHeight(this);
    });
  });

  // Initialize form validation
  initFormValidation(form);

  // Handle form submission
  form.on('submit', async function(e) {
    e.preventDefault();
    
    // Check if form is valid before proceeding
    if (!form.valid()) {
      return;
    }
    
    const formLoader = showInElement('#ip-restriction-form');
    const formData = new FormData(this);

    try {
      const response = await ajaxFetcher.form(
        config.apiProfileIpRestrictionUpdateEndpoint,
        formData
      );

      if (response.success) {
        createAndShowToast(response.message, 'success');

        if (response.successFormHtml) {
          $('#ip-restriction-form').replaceWith(response.successFormHtml);
          updateIpRestriction();
        }

        $('input[name="password"]').val('');
      } else {
        // Handle server validation errors
        handleServerValidationErrors(response);
        createAndShowToast(response.message || 'Error updating IP restrictions', 'error');
      }
    } catch (error) {
      console.error('Error updating IP restrictions:', error);
      
      // Handle validation errors (code 422)
      if (error.status === 422 && error.responseJSON) {
        handleServerValidationErrors(error.responseJSON);
        
        const errorData = error.responseJSON;
        if (errorData.message) {
          createAndShowToast(errorData.message, 'error');
        } else if (errorData.errors) {
          // If there are errors, form a message from the first error of each field
          const errorMessages = Object.values(errorData.errors)
            .map(fieldErrors => fieldErrors[0])
            .join(', ');

          createAndShowToast(errorMessages, 'error');
        }
      } else {
        createAndShowToast('Error updating IP restrictions. Please try again.', 'error');
      }
    } finally {
      hideInElement(formLoader);
    }
  });
};

const initUpdateIpRestriction = () => {
  if ($('#ip-restriction-form').length) {
    updateIpRestriction();
  }
};

export { initUpdateIpRestriction, updateIpRestriction };