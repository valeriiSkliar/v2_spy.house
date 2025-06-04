/**
 * Localization utility for frontend messages
 */
class Localization {
  constructor() {
    this.translations = {};
    this.currentLocale = document.documentElement.lang || 'en';
    this.fallbackLocale = 'en';
  }

  /**
   * Set translations data
   * @param {Object} translations
   */
  setTranslations(translations) {
    this.translations = translations;
  }

  /**
   * Get translation by key
   * @param {string} key - Translation key in dot notation (e.g., 'frontend.errors.comment_save_failed')
   * @param {Object} params - Parameters for replacement
   * @returns {string}
   */
  trans(key, params = {}) {
    let translation = this.getNestedValue(this.translations, key);

    if (!translation && this.currentLocale !== this.fallbackLocale) {
      // Try fallback locale
      translation = this.getNestedValue(this.translations, key, this.fallbackLocale);
    }

    if (!translation) {
      console.warn(`Translation not found for key: ${key}`);
      return key;
    }

    // Replace parameters
    return this.replacePlaceholders(translation, params);
  }

  /**
   * Get nested value from object by dot notation
   * @param {Object} obj
   * @param {string} path
   * @returns {string|null}
   */
  getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => {
      return current && current[key] !== undefined ? current[key] : null;
    }, obj);
  }

  /**
   * Replace placeholders in translation string
   * @param {string} text
   * @param {Object} params
   * @returns {string}
   */
  replacePlaceholders(text, params) {
    return text.replace(/:(\w+)/g, (match, key) => {
      return params[key] !== undefined ? params[key] : match;
    });
  }

  /**
   * Get current locale
   * @returns {string}
   */
  getLocale() {
    return this.currentLocale;
  }

  /**
   * Set current locale
   * @param {string} locale
   */
  setLocale(locale) {
    this.currentLocale = locale;
  }
}

// Create global instance
window.__ = window.__ || new Localization();

// Alias for trans method
window.trans = function (key, params = {}) {
  return window.__.trans(key, params);
};

// Initialize translations if they are already available
document.addEventListener('DOMContentLoaded', function () {
  if (typeof window.laravelTranslations !== 'undefined') {
    window.__.setTranslations({
      frontend: window.laravelTranslations.frontend,
    });
    window.__.setLocale(window.laravelTranslations.locale);
    console.log('Translations initialized from laravelTranslations');
  }
});

export default Localization;
