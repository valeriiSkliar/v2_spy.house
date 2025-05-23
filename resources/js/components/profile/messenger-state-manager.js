/**
 * Unified messenger state management for profile settings
 * Eliminates duplication across components
 */

import { MESSENGER_CONFIG, ValidationMethods } from '../../validation/validation-constants.js';

export class MessengerStateManager {
    constructor(elements) {
        this.elements = elements;
        this.savedValues = {
            telegram: '',
            viber: '',
            whatsapp: ''
        };
        this.currentType = '';
        this.init();
    }

    init() {
        // Initialize saved values from current form state
        const initialType = this.elements.messengerType?.val();
        const initialValue = this.elements.messengerContact?.val();
        
        if (initialType && initialValue) {
            this.savedValues[initialType] = initialValue;
            this.currentType = initialType;
        }
    }

    /**
     * Update the selected messenger type and related UI elements
     * @param {string} type - The messenger type (telegram, viber, whatsapp)
     * @param {HTMLElement} selectedOption - The selected option element
     */
    updateSelectedMessenger(type, selectedOption) {
        // Save current value before switching
        if (this.currentType && this.currentType !== type) {
            const currentValue = this.elements.messengerContact?.val();
            if (currentValue) {
                this.savedValues[this.currentType] = currentValue;
            }
        }

        // Update current type
        this.currentType = type;

        // Update selected state in dropdown
        this.elements.profileMessangerSelectOptions?.removeClass('is-selected');
        $(selectedOption).addClass('is-selected');

        // Update trigger display
        this.updateTriggerDisplay(type, selectedOption);

        // Update form inputs
        this.updateFormInputs(type);

        // Dispatch custom event for other components
        this.dispatchChangeEvent(type);
    }

    /**
     * Update the trigger display with the selected messenger
     * @param {string} type - The messenger type
     * @param {HTMLElement} selectedOption - The selected option element
     */
    updateTriggerDisplay(type, selectedOption) {
        if (!this.elements.profileMessangerSelectTrigger) return;

        const imgSrc = $(selectedOption).find('img').attr('src');
        const altText = $(selectedOption).find('img').attr('alt') || type;

        this.elements.profileMessangerSelectTrigger.html(`
            <span class="base-select__value">
                <span class="base-select__img">
                    <img src="${imgSrc}" alt="${altText}">
                </span>
            </span>
            <span class="base-select__arrow"></span>
        `);
    }

    /**
     * Update form inputs with new type and restored value
     * @param {string} type - The messenger type
     */
    updateFormInputs(type) {
        // Update hidden messenger type input
        if (this.elements.messengerType) {
            this.elements.messengerType.val(type);
        }

        // Update placeholder
        if (this.elements.messengerContact) {
            const config = MESSENGER_CONFIG[type];
            this.elements.messengerContact.attr('placeholder', config?.placeholder || '@username');

            // Restore saved value or clear field
            const savedValue = this.savedValues[type] || '';
            this.elements.messengerContact.val(savedValue);

            // Trigger validation on the restored value
            this.elements.messengerContact.trigger('input');
        }
    }

    /**
     * Validate current messenger contact value
     * @returns {boolean} - True if valid
     */
    validateCurrentContact() {
        if (!this.currentType || !this.elements.messengerContact) {
            return true;
        }

        const value = this.elements.messengerContact.val();
        return ValidationMethods.validateMessengerContact(this.currentType, value);
    }

    /**
     * Get current validation error message
     * @returns {string|null} - Error message or null if valid
     */
    getCurrentErrorMessage() {
        if (this.validateCurrentContact()) {
            return null;
        }
        return ValidationMethods.getMessengerErrorMessage(this.currentType);
    }

    /**
     * Dispatch custom change event
     * @param {string} type - The messenger type
     */
    dispatchChangeEvent(type) {
        if (this.elements.profileMessangerSelect && this.elements.profileMessangerSelect[0]) {
            const event = new CustomEvent('baseSelect:change', {
                detail: { value: type }
            });
            this.elements.profileMessangerSelect[0].dispatchEvent(event);
        }
    }

    /**
     * Close dropdown
     */
    closeDropdown() {
        if (this.elements.profileMessangerSelect) {
            this.elements.profileMessangerSelect.find('.base-select__dropdown').slideUp(200);
        }
    }

    /**
     * Get current messenger configuration
     * @returns {Object|null} - Messenger configuration
     */
    getCurrentConfig() {
        return this.currentType ? MESSENGER_CONFIG[this.currentType] : null;
    }

    /**
     * Reset to initial state
     */
    reset() {
        this.savedValues = {
            telegram: '',
            viber: '',
            whatsapp: ''
        };
        this.currentType = '';
        
        if (this.elements.messengerContact) {
            this.elements.messengerContact.val('');
        }
        if (this.elements.messengerType) {
            this.elements.messengerType.val('');
        }
    }

    /**
     * Update values from server response
     * @param {string} type - The messenger type
     * @param {string} contact - The messenger contact
     */
    updateFromServer(type, contact) {
        if (type && contact) {
            this.currentType = type;
            this.savedValues[type] = contact;
            
            // Update form fields
            if (this.elements.messengerType) {
                this.elements.messengerType.val(type);
            }
            if (this.elements.messengerContact) {
                this.elements.messengerContact.val(contact);
            }

            // Update dropdown selection
            if (this.elements.profileMessangerSelectOptions) {
                this.elements.profileMessangerSelectOptions.removeClass('is-selected');
                const selectedOption = this.elements.profileMessangerSelectOptions.filter(`[data-value="${type}"]`);
                
                if (selectedOption.length) {
                    selectedOption.addClass('is-selected');
                    this.updateTriggerDisplay(type, selectedOption[0]);
                }
            }
        }
    }
}