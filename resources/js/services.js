// resources/js/services.js
document.addEventListener('DOMContentLoaded', function() {
    // Filter toggle on mobile
    const filterTrigger = document.querySelector('.filter__trigger-mobile');
    
    if (filterTrigger) {
        filterTrigger.addEventListener('click', function() {
            const filterContent = document.querySelector('.filter__content');
            
            this.classList.toggle('is-active');
            
            if (filterContent) {
                if (filterContent.style.display === 'block') {
                    filterContent.style.display = 'none';
                } else {
                    filterContent.style.display = 'block';
                }
            }
        });
    }
    
    // Base select with icon
    const baseSelectIcons = document.querySelectorAll('.base-select-icon .base-select__trigger');
    
    if (baseSelectIcons.length > 0) {
        baseSelectIcons.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close all other dropdowns
                document.querySelectorAll('.base-select__trigger.is-open').forEach(openTrigger => {
                    if (openTrigger !== this) {
                        openTrigger.classList.remove('is-open');
                        openTrigger.closest('.base-select').querySelector('.base-select__dropdown').style.display = 'none';
                    }
                });
                
                // Toggle current dropdown
                this.classList.toggle('is-open');
                
                const dropdown = this.closest('.base-select').querySelector('.base-select__dropdown');
                dropdown.style.display = this.classList.contains('is-open') ? 'block' : 'none';
            });
        });
    }
    
    // Handle base select option click
    const baseSelectOptions = document.querySelectorAll('.base-select-icon .base-select__option');
    
    if (baseSelectOptions.length > 0) {
        baseSelectOptions.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.textContent.trim();
                const baseSelect = this.closest('.base-select');
                const valueElement = baseSelect.querySelector('.base-select__value');
                
                // Update selected value
                if (valueElement) {
                    valueElement.textContent = value;
                }
                
                // Update selected state
                baseSelect.querySelectorAll('.base-select__option').forEach(opt => {
                    opt.classList.remove('is-selected');
                });
                this.classList.add('is-selected');
                
                // Close dropdown
                const trigger = baseSelect.querySelector('.base-select__trigger');
                if (trigger) {
                    trigger.classList.remove('is-open');
                    baseSelect.querySelector('.base-select__dropdown').style.display = 'none';
                }
                
                // If it's a sorting option, update the sort parameter and reload
                if (value.includes('High to Low') || value.includes('Low to High')) {
                    let sortParam = '';
                    
                    if (value.includes('Transitions')) {
                        sortParam = value.includes('High to Low') ? 'transitions-high' : 'transitions-low';
                    } else if (value.includes('Rating')) {
                        sortParam = value.includes('High to Low') ? 'rating-high' : 'rating-low';
                    } else if (value.includes('Views')) {
                        sortParam = value.includes('High to Low') ? 'views-high' : 'views-low';
                    }
                    
                    if (sortParam) {
                        // Get current URL and update or add sort parameter
                        const url = new URL(window.location.href);
                        url.searchParams.set('sort', sortParam);
                        window.location.href = url.toString();
                    }
                }
                
                // If it's a per page option, update the per_page parameter and reload
                if (['12', '24', '48', '96'].includes(value)) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('per_page', value);
                    window.location.href = url.toString();
                }
            });
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.base-select')) {
            document.querySelectorAll('.base-select__trigger.is-open').forEach(trigger => {
                trigger.classList.remove('is-open');
                trigger.closest('.base-select').querySelector('.base-select__dropdown').style.display = 'none';
            });
        }
    });
});