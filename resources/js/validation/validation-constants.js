/**
 * Unified validation constants and methods for profile settings
 * This file centralizes all validation logic to eliminate duplication
 */

export const VALIDATION_PATTERNS = {
  telegram: /^@[a-zA-Z0-9_]{5,32}$/,
  viber: /^\d{10,15}$/,
  whatsapp: /^\d{10,15}$/,
  login: /^[a-zA-Z0-9_]+$/,
  email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
  verificationCode: /^\d{6}$/,
};

export const MESSENGER_CONFIG = {
  telegram: {
    placeholder: '@username',
    minLength: 6, // including @
    maxLength: 33, // including @
    pattern: VALIDATION_PATTERNS.telegram,
    errorMessage: 'Please enter a valid Telegram username (e.g., @username)',
  },
  viber: {
    placeholder: '+1 (999) 999-99-99',
    minLength: 10,
    maxLength: 15,
    pattern: VALIDATION_PATTERNS.viber,
    errorMessage: 'Please enter a valid phone number (10-15 digits)',
  },
  whatsapp: {
    placeholder: '+1 (999) 999-99-99',
    minLength: 10,
    maxLength: 15,
    pattern: VALIDATION_PATTERNS.whatsapp,
    errorMessage: 'Please enter a valid phone number (10-15 digits)',
  },
};

export const EMAIL_CONFIG = {
  minLength: 3,
  maxLength: 254,
  pattern: VALIDATION_PATTERNS.email,
  errorMessage: 'Please enter a valid email address',
};

export const VERIFICATION_CODE_CONFIG = {
  length: 6,
  pattern: VALIDATION_PATTERNS.verificationCode,
  errorMessage: 'Please enter a valid 6-digit verification code',
};

export const PERSONAL_GREETING_CONFIG = {
  minLength: 3,
  maxLength: 100,
  errorMessage: 'Personal greeting must be between 3 and 100 characters',
};

export const PASSWORD_CONFIG = {
  minLength: 8,
  errorMessage: 'Password must be at least 8 characters long',
};

/**
 * Unified validation methods
 */
export const ValidationMethods = {
  /**
   * Validate Telegram username
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validateTelegram(value) {
    if (!value || !value.trim()) return true; // Optional field
    return VALIDATION_PATTERNS.telegram.test(value.trim());
  },

  /**
   * Validate Viber phone number
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validateViber(value) {
    if (!value || !value.trim()) return true; // Optional field
    const cleanValue = value.replace(/[^0-9]/g, '');
    return VALIDATION_PATTERNS.viber.test(cleanValue);
  },

  /**
   * Validate WhatsApp phone number
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validateWhatsapp(value) {
    if (!value || !value.trim()) return true; // Optional field
    const cleanValue = value.replace(/[^0-9]/g, '');
    return VALIDATION_PATTERNS.whatsapp.test(cleanValue);
  },

  /**
   * Validate messenger contact based on type
   * @param {string} type - The messenger type
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validateMessengerContact(type, value) {
    if (!value || !value.trim()) return true; // Optional field

    switch (type) {
      case 'telegram':
        return this.validateTelegram(value);
      case 'viber':
        return this.validateViber(value);
      case 'whatsapp':
        return this.validateWhatsapp(value);
      default:
        return true;
    }
  },

  /**
   * Validate login format
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validateLogin(value) {
    if (!value || !value.trim()) return false; // Required field
    return VALIDATION_PATTERNS.login.test(value.trim());
  },

  /**
   * Get error message for messenger type
   * @param {string} type - The messenger type
   * @returns {string} - Error message
   */
  getMessengerErrorMessage(type) {
    return MESSENGER_CONFIG[type]?.errorMessage || 'Please enter a valid contact';
  },

  /**
   * Get placeholder for messenger type
   * @param {string} type - The messenger type
   * @returns {string} - Placeholder text
   */
  getMessengerPlaceholder(type) {
    return MESSENGER_CONFIG[type]?.placeholder || '@username';
  },

  /**
   * Validate email address
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validateEmail(value) {
    if (!value || !value.trim()) return false; // Required field
    return VALIDATION_PATTERNS.email.test(value.trim());
  },

  /**
   * Validate verification code
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validateVerificationCode(value) {
    if (!value || !value.trim()) return false; // Required field
    return VALIDATION_PATTERNS.verificationCode.test(value.trim());
  },

  /**
   * Check if email is different from current email
   * @param {string} newEmail - The new email to validate
   * @param {string} currentEmail - The current email to compare against
   * @returns {boolean} - True if different
   */
  validateEmailNotEqual(newEmail, currentEmail) {
    if (!newEmail || !newEmail.trim()) return false;
    return newEmail.trim().toLowerCase() !== currentEmail.trim().toLowerCase();
  },

  /**
   * Validate password minimum length
   * @param {string} value - The value to validate
   * @param {number} minLength - Minimum length (default 6)
   * @returns {boolean} - True if valid
   */
  validatePassword(value, minLength = 6) {
    if (!value || !value.trim()) return false; // Required field
    return value.length >= minLength;
  },

  /**
   * Validate personal greeting
   * @param {string} value - The value to validate
   * @returns {boolean} - True if valid
   */
  validatePersonalGreeting(value) {
    if (!value || !value.trim()) return false; // Required field
    const trimmedValue = value.trim();
    return (
      trimmedValue.length >= PERSONAL_GREETING_CONFIG.minLength &&
      trimmedValue.length <= PERSONAL_GREETING_CONFIG.maxLength
    );
  },

  /**
   * Validate password strength
   * @param {string} value - The value to validate
   * @param {number} minLength - Minimum length (default from config)
   * @returns {boolean} - True if valid
   */
  validatePasswordStrength(value, minLength = PASSWORD_CONFIG.minLength) {
    if (!value || !value.trim()) return false; // Required field
    return value.length >= minLength;
  },

  /**
   * Validate password confirmation
   * @param {string} password - The password value
   * @param {string} confirmation - The confirmation value
   * @returns {boolean} - True if they match
   */
  validatePasswordConfirmation(password, confirmation) {
    if (!password || !confirmation) return false;
    return password === confirmation;
  },
};
