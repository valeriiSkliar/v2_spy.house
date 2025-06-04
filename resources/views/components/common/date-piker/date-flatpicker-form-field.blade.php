{{-- <div class="form-item mb-20">
  <label class="d-block mb-10">{{ $label }}</label>
  <input type="{{ $type }}" name="{{ $name }}" class="input-h-57 datepicker" value="{{ old($name, $value ?? '') }}"
    autocomplete="off">
  <div class="datepicker-calendar _icon-calendar"></div>
  @error($name)
  <span class="text-danger">{{ $message }}</span>
  @enderror
</div> --}}

{{-- Date picker with quick selection --}}
<div class="date-picker-container">
  <input type="{{ $type }}" name="{{ $name }}" autocomplete="off" id="{{ $id ?? 'dateRangePicker' }}"
    class="date-range-input datepicker " placeholder="{{ $placeholder ?? '' }}" value="{{ old($name, $value ?? '') }}">

  {{-- Quick Selection Panel --}}
  <div class="date-quick-selection" id="quickSelection">
    <div class="quick-selection-item" data-preset="today">Today</div>
    <div class="quick-selection-item" data-preset="yesterday">Yesterday</div>
    <div class="quick-selection-item" data-preset="last7days">Last 7 days</div>
    <div class="quick-selection-item" data-preset="last30days">Last 30 days</div>
    <div class="quick-selection-item" data-preset="thismonth">This month</div>
    <div class="quick-selection-item" data-preset="lastmonth">Last month</div>
  </div>
</div>

<style>
  .date-picker-container {
    position: relative;
    display: inline-flex;
    /* Align icon and input */
    align-items: center;
    /* border: 1px solid #ccc; Default border */
    /* border-radius: 25px;   Highly rounded corners */
    /* padding: 8px 15px; */
    background-color: #fff;
    transition: border-color 0.3s ease;
    /* Smooth transition for active state */
    cursor: pointer;
    /* Indicate it's clickable */
    width: auto;
    /* Adjust as needed or make it fit content */
    min-width: 180px;
    /* Example minimum width */
  }

  .date-picker-container:focus-within,
  .date-picker-container.active {
    /* Class to add with JS when picker is open/active */
    border-color: #66cc99;
    /* Green border for active state */
    box-shadow: 0 0 0 2px rgba(102, 204, 153, 0.2);
    /* Optional: soft glow */
  }


  .date-range-input {
    border: none;
    outline: none;
    font-size: 16px;
    color: #333;
    background-color: transparent;
    /* Input background should be transparent */
    width: 100%;
    /* Make input fill the container */
  }

  .date-range-input::placeholder {
    color: #888;
    /* Placeholder text color */
    opacity: 1;
    /* Firefox fix */
  }

  /* Styling for the dropdown calendar (this will be library-specific) */
  /* For example, if you use Litepicker, you'd target .litepicker classes */

  /* Quick Selection Styles */
  .date-quick-selection {
    display: none;
    position: absolute;
    top: 100%;
    left: -80%;
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    min-width: 150px;
    margin-top: 4px;
  }

  .date-quick-selection.show {
    display: block;
  }

  .quick-selection-item {
    padding: 10px 15px;
    cursor: pointer;
    color: #333;
    font-size: 14px;
    border-bottom: 1px solid #f5f5f5;
    transition: background-color 0.2s ease;
  }

  .quick-selection-item:last-child {
    border-bottom: none;
    border-radius: 0 0 8px 8px;
  }

  .quick-selection-item:first-child {
    border-radius: 8px 8px 0 0;
  }

  .quick-selection-item:hover {
    background-color: #f8f9fa;
    color: #66cc99;
  }

  .quick-selection-item.active {
    background-color: #66cc99;
    color: white;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('{{ $id ?? "dateRangePicker" }}');
    const quickSelection = document.getElementById('quickSelection');
    
    // Show/hide quick selection on input focus/blur
    dateInput.addEventListener('focus', function() {
        quickSelection.classList.add('show');
    });
    
    // Hide when clicking outside
    document.addEventListener('click', function(e) {
        if (!dateInput.contains(e.target) && !quickSelection.contains(e.target)) {
            quickSelection.classList.remove('show');
        }
    });
    
    // Handle quick selection clicks
    quickSelection.addEventListener('click', function(e) {
        if (e.target.classList.contains('quick-selection-item')) {
            const preset = e.target.getAttribute('data-preset');
            const dateRange = getDateRange(preset);
            
            // Update input value
            dateInput.value = dateRange;
            
            // Remove active class from all items
            quickSelection.querySelectorAll('.quick-selection-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to clicked item
            e.target.classList.add('active');
            
            // Hide quick selection
            quickSelection.classList.remove('show');
            
            // Trigger change event
            dateInput.dispatchEvent(new Event('change'));
        }
    });
    
    function getDateRange(preset) {
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        const formatDate = (date) => {
            return date.toISOString().split('T')[0];
        };
        
        switch(preset) {
            case 'today':
                return formatDate(today) + ' to ' + formatDate(today);
                
            case 'yesterday':
                return formatDate(yesterday) + ' to ' + formatDate(yesterday);
                
            case 'last7days':
                const week = new Date(today);
                week.setDate(week.getDate() - 6);
                return formatDate(week) + ' to ' + formatDate(today);
                
            case 'last30days':
                const month = new Date(today);
                month.setDate(month.getDate() - 29);
                return formatDate(month) + ' to ' + formatDate(today);
                
            case 'thismonth':
                const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                return formatDate(thisMonthStart) + ' to ' + formatDate(today);
                
            case 'lastmonth':
                const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                return formatDate(lastMonthStart) + ' to ' + formatDate(lastMonthEnd);
                
            default:
                return '';
        }
    }
});
</script>