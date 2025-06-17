document.addEventListener('DOMContentLoaded', function () {
  /**
   * Global Modal System
   *
   * Usage:
   * 1. To show a modal: window.Modal.show('modal-id')
   * 2. To hide a modal: window.Modal.hide('modal-id')
   * 3. To show a modal with content: window.Modal.showWithContent('title', 'content', 'size')
   * 4. To show a confirmation modal: window.Modal.confirm('title', 'message', confirmCallback)
   */
  window.Modal = {
    /**
     * Show an existing modal by ID
     *
     * @param {string} modalId - The ID of the modal to show
     */
    show: function (modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) {
        console.error(`Modal with id ${modalId} not found`);
        return;
      }

      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
    },

    /**
     * Hide an existing modal by ID
     *
     * @param {string} modalId - The ID of the modal to hide
     */
    hide: function (modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) {
        console.error(`Modal with id ${modalId} not found`);
        return;
      }

      const bsModal = bootstrap.Modal.getInstance(modal);
      if (bsModal) {
        bsModal.hide();
      }
    },

    /**
     * Show a dynamic modal with custom content
     *
     * @param {string} title - The title for the modal
     * @param {string} content - HTML content for the modal body
     * @param {string} size - Optional size (sm, lg, xl)
     * @param {Function} onConfirm - Optional callback when primary action is clicked
     * @param {boolean} showCloseButton - Whether to show the close button (default: true)
     * @param {Object} buttons - Custom buttons configuration
     */
    showWithContent: function (
      title,
      content,
      size = '',
      onConfirm = null,
      showCloseButton = true,
      buttons = null
    ) {
      const modalContainer = document.getElementById('global-modal-container');
      if (!modalContainer) {
        console.error(
          'Global modal container not found. Add <div id="global-modal-container"></div> to your layout.'
        );
        return;
      }

      // Generate a unique modal ID
      const modalId = 'dynamic-modal-' + Math.random().toString(36).substring(2, 11);

      // Create modal HTML
      let modalHTML = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}-label" aria-hidden="true">
                    <div class="modal-dialog ${size ? 'modal-' + size : ''}">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}-label">${title}</h5>
                                ${
                                  showCloseButton
                                    ? '<button type="button" class="btn-icon _gray btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true"><span class="icon-x"></span></span></button>'
                                    : ''
                                }
                            </div>
                            <div class="modal-body">
                                ${content}
                            </div>`;

      // Add footer if there are buttons or a confirmation callback
      if (buttons || onConfirm) {
        modalHTML += `<div class="modal-footer">`;

        if (buttons) {
          // Custom buttons
          for (const button of buttons) {
            modalHTML += `<button type="button" class="${button.class}" ${
              button.dismiss ? 'data-bs-dismiss="modal"' : ''
            } id="${modalId}-${button.id}">${button.text}</button>`;
          }
        } else if (onConfirm) {
          // Default confirm/cancel buttons
          modalHTML += `
                        <button type="button" class="btn _flex _gray _medium" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn _flex _green _medium" id="${modalId}-confirm">Confirm</button>
                    `;
        }

        modalHTML += `</div>`;
      }

      modalHTML += `
                        </div>
                    </div>
                </div>
            `;

      // Add modal to container
      modalContainer.innerHTML = modalHTML;

      // Show the modal
      const modal = document.getElementById(modalId);
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();

      // Add event listeners for custom buttons
      if (buttons) {
        for (const button of buttons) {
          if (button.callback) {
            document.getElementById(`${modalId}-${button.id}`).addEventListener('click', () => {
              button.callback(modal, bsModal);
            });
          }
        }
      } else if (onConfirm) {
        // Add event listener for the confirm button
        document.getElementById(`${modalId}-confirm`).addEventListener('click', () => {
          onConfirm();
          bsModal.hide();
        });
      }

      // Clean up when the modal is hidden
      modal.addEventListener('hidden.bs.modal', function () {
        modalContainer.innerHTML = '';
      });

      return {
        modalId,
        modal,
        bsModal,
        close: function () {
          bsModal.hide();
        },
      };
    },

    /**
     * Show a confirmation modal
     *
     * @param {string} title - The confirmation title
     * @param {string} message - The confirmation message
     * @param {Function} onConfirm - Callback function to execute on confirmation
     * @param {string} confirmBtnText - Text for the confirm button
     * @param {string} cancelBtnText - Text for the cancel button
     */
    confirm: function (
      title,
      message,
      onConfirm,
      confirmBtnText = 'Confirm',
      cancelBtnText = 'Cancel'
    ) {
      const content = `<p>${message}</p>`;
      const buttons = [
        {
          id: 'cancel',
          text: cancelBtnText,
          class: 'btn _flex _gray _medium',
          dismiss: true,
        },
        {
          id: 'confirm',
          text: confirmBtnText,
          class: 'btn _flex _green _medium',
          callback: (modal, bsModal) => {
            onConfirm();
            bsModal.hide();
          },
        },
      ];

      return this.showWithContent(title, content, '', null, true, buttons);
    },

    /**
     * Show an alert modal
     *
     * @param {string} title - The alert title
     * @param {string} message - The alert message
     * @param {string} btnText - Text for the OK button
     */
    alert: function (title, message, btnText = 'OK') {
      const content = `<p>${message}</p>`;
      const buttons = [
        {
          id: 'ok',
          text: btnText,
          class: 'btn _flex _green _medium',
          dismiss: true,
        },
      ];

      return this.showWithContent(title, content, '', null, true, buttons);
    },

    /**
     * Show current subscription modal
     * Unified method to display subscription modal
     */
    showCurrentSubscription: function () {
      // Очистка существующих backdrop'ов перед показом
      const existingBackdrops = document.querySelectorAll('.modal-backdrop');
      existingBackdrops.forEach(backdrop => backdrop.remove());

      this.show('modal-current-subscription');

      // Принудительная установка правильного z-index после показа
      setTimeout(() => {
        const modal = document.getElementById('modal-current-subscription');
        const backdrop = document.querySelector('.modal-backdrop');

        if (modal && backdrop) {
          // Backdrop должен быть ниже модального окна
          backdrop.style.zIndex = '1050';
          modal.style.zIndex = '1070';
        }
      }, 50);
    },
  };

  // Set up data attribute API
  document.body.addEventListener('click', function (e) {
    // Handle [data-toggle="modal"]
    const modalToggler = e.target.closest('[data-toggle="modal"]');
    if (modalToggler) {
      e.preventDefault();
      const targetModal =
        modalToggler.getAttribute('data-target') || modalToggler.getAttribute('href');
      if (targetModal) {
        const modalId = targetModal.replace('#', '');
        window.Modal.show(modalId);
      }
    }

    // Handle confirm dialogs with [data-confirm]
    // const confirmEl = e.target.closest("[data-confirm]");
    // if (confirmEl) {
    //     e.preventDefault();
    //     const message =
    //         confirmEl.getAttribute("data-confirm") || "Are you sure?";
    //     const title =
    //         confirmEl.getAttribute("data-confirm-title") || "Confirmation";
    //     const confirmBtn =
    //         confirmEl.getAttribute("data-confirm-btn") || "Confirm";
    //     const cancelBtn =
    //         confirmEl.getAttribute("data-confirm-cancel") || "Cancel";

    //     // Store original click action
    //     const href = confirmEl.getAttribute("href");
    //     const deleteUrl = confirmEl.getAttribute("data-delete-url");
    //     const isForm =
    //         confirmEl.tagName === "BUTTON" && confirmEl.closest("form");
    //     const form = isForm ? confirmEl.closest("form") : null;

    //     window.Modal.confirm(
    //         title,
    //         message,
    //         function () {
    //             if (deleteUrl) {
    //                 const deleteForm = document.createElement("form");
    //                 deleteForm.method = "POST";
    //                 deleteForm.action = deleteUrl;
    //                 deleteForm.style.display = "none";

    //                 const csrfToken = document
    //                     .querySelector('meta[name="csrf-token"]')
    //                     .getAttribute("content");
    //                 const csrfInput = document.createElement("input");
    //                 csrfInput.type = "hidden";
    //                 csrfInput.name = "_token";
    //                 csrfInput.value = csrfToken;
    //                 deleteForm.appendChild(csrfInput);

    //                 const methodInput = document.createElement("input");
    //                 methodInput.type = "hidden";
    //                 methodInput.name = "_method";
    //                 methodInput.value = "DELETE";
    //                 deleteForm.appendChild(methodInput);

    //                 document.body.appendChild(deleteForm);
    //                 deleteForm.submit();
    //             } else if (href) {
    //                 window.location.href = href;
    //             } else if (form) {
    //                 form.submit();
    //             }
    //         },
    //         confirmBtn,
    //         cancelBtn
    //     );
    // }
  });
});
