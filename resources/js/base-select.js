// resources/js/base-select.js
document.addEventListener('DOMContentLoaded', function () {
  // Base select functionality
  const baseSelects = document.querySelectorAll('.base-select');

  baseSelects.forEach(select => {
    // Пропускаем элементы с кастомной обработкой
    if (select.classList.contains('js-custom-handling')) {
      return;
    }

    const trigger = select.querySelector('.base-select__trigger');
    const dropdown = select.querySelector('.base-select__dropdown');
    const options = select.querySelectorAll('.base-select__option');
    const valueElement = select.querySelector('.base-select__value');
    // Ищем hidden input по data-target атрибуту или в form-item
    const targetName = select.getAttribute('data-target');
    let hiddenInput = null;
    if (targetName) {
      hiddenInput = document.querySelector(`input[name="${targetName}"]`);
    } else {
      hiddenInput = select.closest('.form-item')?.querySelector('input[type="hidden"]');
    }

    if (trigger) {
      trigger.addEventListener('click', function (e) {
        e.stopPropagation();

        // Close all other dropdowns
        document.querySelectorAll('.base-select__trigger.is-open').forEach(openTrigger => {
          if (openTrigger !== trigger) {
            openTrigger.classList.remove('is-open');
            openTrigger
              .closest('.base-select')
              .querySelector('.base-select__dropdown').style.display = 'none';
          }
        });

        // Toggle current dropdown
        this.classList.toggle('is-open');
        dropdown.style.display = this.classList.contains('is-open') ? 'block' : 'none';
      });
    }

    options.forEach(option => {
      option.addEventListener('click', function () {
        const value = this.getAttribute('data-value');
        const text = this.textContent.trim();

        // Update value
        valueElement.textContent = text;

        // Update hidden input if exists
        if (hiddenInput) {
          hiddenInput.value = value;
        }

        // Update selected state
        options.forEach(opt => opt.classList.remove('is-selected'));
        this.classList.add('is-selected');

        // Close dropdown
        trigger.classList.remove('is-open');
        dropdown.style.display = 'none';

        // Trigger change event
        select.dispatchEvent(
          new CustomEvent('baseSelect:change', {
            detail: { value: value, text: text },
          })
        );
      });
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.base-select')) {
      document.querySelectorAll('.base-select__trigger.is-open').forEach(trigger => {
        const select = trigger.closest('.base-select');
        // Пропускаем элементы с кастомной обработкой
        if (select && !select.classList.contains('js-custom-handling')) {
          trigger.classList.remove('is-open');
          select.querySelector('.base-select__dropdown').style.display = 'none';
        }
      });
    }
  });
});
