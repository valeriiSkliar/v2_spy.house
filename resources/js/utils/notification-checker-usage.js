/**
 * Notification Checker Usage Example
 * 
 * This file demonstrates how to use the notification checker helper
 * after async requests to check for new notifications.
 */

import { checkNotifications } from '../helpers/notification-checker';

/**
 * Example usage after an AJAX request
 */
const exampleAjaxRequest = async () => {
    try {
        // Perform your AJAX request
        const response = await fetch('/api/some-endpoint', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ /* your data */ }),
        });
        
        // Process the response
        const data = await response.json();
        
        // Check for new notifications after the request completes
        await checkNotifications();
        
        return data;
    } catch (error) {
        console.error('Error in AJAX request:', error);
        throw error;
    }
};

/**
 * Example usage with form submission
 */
const setupFormWithNotificationCheck = () => {
    const form = document.querySelector('#your-form');
    
    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            try {
                // Your form submission logic here
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                
                const data = await response.json();
                
                // Process form response
                // ...
                
                // Check for new notifications after form submission
                await checkNotifications();
                
            } catch (error) {
                console.error('Error submitting form:', error);
            }
        });
    }
};

/**
 * Initialize notification checking at regular intervals
 * @param {number} intervalMs - Interval in milliseconds (default: 60000 = 1 minute)
 */
export const initializeNotificationChecking = (intervalMs = 60000) => {
    // Check notifications immediately when page loads
    checkNotifications();
    
    // Check notifications periodically
    setInterval(checkNotifications, intervalMs);
};

export {
    exampleAjaxRequest,
    setupFormWithNotificationCheck,
};