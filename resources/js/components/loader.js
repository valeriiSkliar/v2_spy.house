/**
 * Loader component for handling fullscreen loader.
 */
export class Loader {
  constructor() {
    this.loaderElement = document.querySelector('.loader-fullscreen');
  }

  /**
   * Shows the loader
   */
  show() {
    if (this.loaderElement) {
      this.loaderElement.classList.add('active');
    }
  }

  /**
   * Hides the loader
   */
  hide() {
    if (this.loaderElement) {
      this.loaderElement.classList.remove('active');
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
  if (typeof targetElement === 'string') {
    let id = targetElement;
    if (id.startsWith('#')) {
      id = id.substring(1);
    }
    targetElement = document.getElementById(id);
  }
  if (!targetElement) {
    console.error('Target element not provided for inline loader.');
    return null;
  }

  // Ensure the target element has a relative or absolute position
  const currentPosition = window.getComputedStyle(targetElement).position;
  if (currentPosition === 'static') {
    targetElement.style.position = 'relative';
  }

  const loaderDiv = document.createElement('div');
  loaderDiv.className = 'loader-inline'; // Use the new class for inline loaders
  loaderDiv.innerHTML = `
        <div class="loader__logo">
            <div class="loader__animation"></div>
        </div>
    `;

  targetElement.appendChild(loaderDiv);

  // Force a reflow to ensure the transition is applied
  void loaderDiv.offsetWidth;

  loaderDiv.classList.add('active');

  return loaderDiv;
}

/**
 * Hides an inline loader element.
 * @param {HTMLElement} loaderElement The loader element to hide (returned by showInElement).
 */
export function hideInElement(loaderElement) {
  if (typeof loaderElement === 'string') {
    let id = loaderElement;
    if (id.startsWith('#')) {
      id = id.substring(1);
    }
    loaderElement = document.getElementById(id);
  }
  if (!loaderElement) {
    console.error('[hideInElement] element not provided for hiding.');
    return;
  }

  if (loaderElement.classList.contains('active')) {
    loaderElement.classList.remove('active');
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

/**
 * Shows a loader animation within a button element.
 * @param {HTMLElement|string} buttonElement The button element or its ID.
 * @param {string} [type='default'] The type of loader ('default', '_green', '_dark').
 */
export function showInButton(buttonElement, type = 'default') {
  let buttonEl = buttonElement;
  if (typeof buttonElement === 'string') {
    let id = buttonElement;
    if (id.startsWith('#')) {
      id = id.substring(1);
    }
    buttonEl = document.getElementById(id);
  } else if (buttonElement && typeof buttonElement.jquery !== 'undefined') {
    buttonEl = buttonElement[0];
  }

  if (!buttonEl) {
    console.error('[showInButton] Button element not provided for loader.');
    return;
  }

  // Check for icon class directly on the button
  const iconClassRegex = /^(icon-|fa-)[a-zA-Z0-9_-]+$/;
  for (const cls of Array.from(buttonEl.classList)) {
    if (iconClassRegex.test(cls)) {
      buttonEl.classList.remove(cls);
      buttonEl.dataset.originalButtonIconClass = cls;
      break;
    }
  }

  let loaderSpan = buttonEl.querySelector('span.loader-btn');

  if (!loaderSpan) {
    loaderSpan = document.createElement('span');
    loaderSpan.className = `loader-btn`;
    loaderSpan.setAttribute('data-dynamically-added', 'true');
    buttonEl.prepend(loaderSpan);
  }

  if (!loaderSpan.hasAttribute('data-original-display')) {
    const originalDisplay = loaderSpan.style.display || 'none';
    loaderSpan.setAttribute('data-original-display', originalDisplay);
  }
  loaderSpan.style.display = 'inline-block';

  loaderSpan.classList.remove('_green', '_dark');
  if (type === '_green' || type === '_dark') {
    loaderSpan.classList.add(type);
  }

  const otherIcons = buttonEl.querySelectorAll(
    'span[class*="icon-"]:not(.loader-btn), span[class*="fa-"]:not(.loader-btn)'
  );

  otherIcons.forEach(icon => {
    if (!icon.hasAttribute('data-original-display')) {
      const originalDisplay = icon.style.display || 'inline-block';
      icon.setAttribute('data-original-display', originalDisplay);
    }
    icon.style.display = 'none';
  });

  buttonEl.disabled = true;
}

/**
 * Hides the loader animation from a button element and restores its original content/state.
 * @param {HTMLElement|string} buttonElement The button element or its ID.
 */
export function hideInButton(buttonElement) {
  let buttonEl = buttonElement;
  if (typeof buttonElement === 'string') {
    let id = buttonElement;
    if (id.startsWith('#')) {
      id = id.substring(1);
    }
    buttonEl = document.getElementById(id);
  } else if (buttonElement && typeof buttonElement.jquery !== 'undefined') {
    buttonEl = buttonElement[0];
  }

  if (!buttonEl) {
    console.error('Button element not provided for hiding loader.');
    return;
  }

  const loaderSpan = buttonEl.querySelector('span.loader-btn');
  if (loaderSpan) {
    if (loaderSpan.getAttribute('data-dynamically-added') === 'true') {
      loaderSpan.remove();
    } else {
      loaderSpan.style.display = loaderSpan.getAttribute('data-original-display') || 'none';
      loaderSpan.removeAttribute('data-original-display');
      loaderSpan.classList.remove('_green', '_dark');
    }
  }

  // Restore other SPAN icons
  const otherIcons = buttonEl.querySelectorAll(
    'span[class*="icon-"]:not(.loader-btn), span[class*="fa-"]:not(.loader-btn)'
  );
  otherIcons.forEach(icon => {
    if (icon.hasAttribute('data-original-display')) {
      icon.style.display = icon.getAttribute('data-original-display') || 'inline-block';
      icon.removeAttribute('data-original-display');
    }
  });

  // Restore button's own icon class if it was removed
  if (buttonEl.dataset.originalButtonIconClass) {
    buttonEl.classList.add(buttonEl.dataset.originalButtonIconClass);
    delete buttonEl.dataset.originalButtonIconClass; // Clean up
  }

  buttonEl.disabled = false;
}
