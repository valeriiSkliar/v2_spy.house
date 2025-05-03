
/**
 * Copy text to clipboard using the modern Clipboard API with fallback
 * @param {string} text - The text to copy
 * @returns {Promise<boolean>} - Returns true if successful, false otherwise
 */
export const copyToClipboard = async (text) => {
    try {
      // Try to use the modern Clipboard API first
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
        return true;
      }
      
      // Fallback to the older execCommand method
      const textArea = document.createElement('textarea');
      textArea.value = text;
      
      // Make the textarea out of viewport
      textArea.style.position = 'fixed';
      textArea.style.left = '-999999px';
      textArea.style.top = '-999999px';
      document.body.appendChild(textArea);
      
      // Select and copy
      textArea.focus();
      textArea.select();
      const success = document.execCommand('copy');
      
      // Clean up
      document.body.removeChild(textArea);
      
      return success;
    } catch (err) {
      console.error('Failed to copy text: ', err);
      return false;
    }
  };