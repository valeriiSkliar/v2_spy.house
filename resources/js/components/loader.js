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

/**
 * Shows an inline loader within the specified target element.
 * @param {HTMLElement} targetElement The element to show the loader in.
 * @returns {HTMLElement} The created loader element.
 */
export function showInElement(targetElement) {
    if (!targetElement) {
        console.error("Target element not provided for inline loader.");
        return null;
    }

    // Ensure the target element has a relative or absolute position
    const currentPosition = window.getComputedStyle(targetElement).position;
    if (currentPosition === "static") {
        targetElement.style.position = "relative";
    }

    const loaderDiv = document.createElement("div");
    loaderDiv.className = "loader-inline"; // Use the new class for inline loaders
    loaderDiv.innerHTML = `
        <div class="loader__logo">
            <div class="loader__animation"></div>
        </div>
    `;

    targetElement.appendChild(loaderDiv);

    // Force a reflow to ensure the transition is applied
    void loaderDiv.offsetWidth;

    loaderDiv.classList.add("active");

    return loaderDiv;
}

/**
 * Hides an inline loader element.
 * @param {HTMLElement} loaderElement The loader element to hide (returned by showInElement).
 */
export function hideInElement(loaderElement) {
    if (!loaderElement) {
        console.error("Loader element not provided for hiding.");
        return;
    }

    if (loaderElement.classList.contains("active")) {
        loaderElement.classList.remove("active");
        // Remove the element after the transition (0.1s = 100ms)
        setTimeout(() => {
            if (loaderElement.parentNode) {
                loaderElement.parentNode.removeChild(loaderElement);
            }
        }, 100);
    } else {
        // If not active, remove immediately (though this case should be rare)
        if (loaderElement.parentNode) {
            loaderElement.parentNode.removeChild(loaderElement);
        }
    }
}
