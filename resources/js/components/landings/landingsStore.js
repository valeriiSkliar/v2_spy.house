/**
 * Simple module for tracking and managing landing status across components
 */
class LandingsStore {
    constructor() {
        // Map of landing IDs to their status elements
        this.pendingLandings = new Map();
    }

    /**
     * Register a landing for tracking
     * @param {string|number} landingId - ID of the landing
     * @param {Object} statusElement - jQuery element representing the status
     */
    registerLanding(landingId, statusElement) {
        // Ensure we're working with jQuery objects
        const $element = $(statusElement);
        if ($element.length) {
            this.pendingLandings.set(landingId, $element);
        } else {
            console.warn(`Attempted to register invalid element for landing ${landingId}`);
        }
    }

    /**
     * Unregister a landing from tracking
     * @param {string|number} landingId - ID of the landing
     */
    unregisterLanding(landingId) {
        this.pendingLandings.delete(landingId);
    }

    /**
     * Get all registered landing IDs
     * @returns {Array} - Array of landing IDs
     */
    getAllRegisteredLandingIds() {
        return Array.from(this.pendingLandings.keys());
    }

    /**
     * Get status element for a landing
     * @param {string|number} landingId - ID of the landing
     * @returns {Object|null} - jQuery element or null if not found
     */
    getStatusElement(landingId) {
        return this.pendingLandings.get(landingId) || null;
    }

    /**
     * Check if a registered landing element is still in the DOM
     * @param {string|number} landingId - ID of the landing
     * @returns {boolean} - True if element exists and is in the DOM
     */
    isElementInDOM(landingId) {
        const $element = this.getStatusElement(landingId);
        return $element && $element.length && $.contains(document, $element[0]);
    }

    /**
     * Clear all registered landings
     */
    clear() {
        this.pendingLandings.clear();
    }

    /**
     * Check if a landing is registered
     * @param {string|number} landingId - ID of the landing
     * @returns {boolean} - True if landing is registered
     */
    hasLanding(landingId) {
        return this.pendingLandings.has(landingId);
    }
}

export default new LandingsStore();