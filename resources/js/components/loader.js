/**
 * Loader component for handling fullscreen loader.
 */
export class Loader {
    constructor() {
        this.loaderElement = document.querySelector(".loader-fullscreen");
    }

    /**
     * Shows the loader
     */
    show() {
        if (this.loaderElement) {
            this.loaderElement.classList.add("active");
        }
    }

    /**
     * Hides the loader
     */
    hide() {
        if (this.loaderElement) {
            this.loaderElement.classList.remove("active");
        }
    }
}

// Create a singleton instance
const loader = new Loader();

// Export the instance
export default loader;
