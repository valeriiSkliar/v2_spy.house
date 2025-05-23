/**
 * Unified validation constants and methods for profile settings
 * This file centralizes all validation logic to eliminate duplication
 */

export const VALIDATION_PATTERNS = {
    telegram: /^@[a-zA-Z0-9_]{5,32}$/,
    viber: /^\d{10,15}$/,
    whatsapp: /^\d{10,15}$/,
    login: /^[a-zA-Z0-9_]+$/
};

export const MESSENGER_CONFIG = {
    telegram: {
        placeholder: '@username',
        minLength: 6, // including @
        maxLength: 33, // including @
        pattern: VALIDATION_PATTERNS.telegram,
        errorMessage: 'Please enter a valid Telegram username (e.g., @username)'
    },
    viber: {
        placeholder: '+1 (999) 999-99-99',
        minLength: 10,
        maxLength: 15,
        pattern: VALIDATION_PATTERNS.viber,
        errorMessage: 'Please enter a valid phone number (10-15 digits)'
    },
    whatsapp: {
        placeholder: '+1 (999) 999-99-99',
        minLength: 10,
        maxLength: 15,
        pattern: VALIDATION_PATTERNS.whatsapp,
        errorMessage: 'Please enter a valid phone number (10-15 digits)'
    }
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
    }
};