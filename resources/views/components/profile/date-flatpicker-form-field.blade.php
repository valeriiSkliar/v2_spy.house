    {{-- <div class="form-item mb-20">
        <label class="d-block mb-10">{{ $label }}</label>
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            class="input-h-57 datepicker" 
            value="{{ old($name, $value ?? '') }}" 
            autocomplete="off"
        >
        <div class="datepicker-calendar _icon-calendar"></div>
        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div> --}}


    <div class="form-item mb-20 data-control-date-picker filter-date-select">
      <label class="d-block mb-10">{{ $label }}</label>
      <div class="datepicker-input-wrapper">
        <input 
          type="{{ $type }}" 
          name="{{ $name }}"
          autocomplete="off"
          id="dateRangePicker" 
          class="date-range-input datepicker input-h-57" 
          placeholder="{{ $placeholder ?? '' }}"
          value="{{ old($name, $value ?? '') }}"
        >
        <div class="datepicker-calendar icon-date _icon-calendar"></div>
      </div>
    </div>

      <style>
        .date-picker-container {
  display: inline-flex; /* Align icon and input */
  align-items: center;
  /* border: 1px solid #ccc; Default border */
  /* border-radius: 25px;   Highly rounded corners */
  /* padding: 8px 15px; */
  background-color: #fff;
  transition: border-color 0.3s ease; /* Smooth transition for active state */
  cursor: pointer; /* Indicate it's clickable */
  width: auto; /* Adjust as needed or make it fit content */
  min-width: 180px; /* Example minimum width */
}

.date-picker-container:focus-within,
.date-picker-container.active { /* Class to add with JS when picker is open/active */
  border-color: #66cc99; /* Green border for active state */
  box-shadow: 0 0 0 2px rgba(102, 204, 153, 0.2); /* Optional: soft glow */
}


.date-range-input {
  border: none;
  outline: none;
  font-size: 16px;
  color: #333;
  background-color: transparent; /* Input background should be transparent */
  width: 100%; /* Make input fill the container */
}

.date-range-input::placeholder {
  color: #888; /* Placeholder text color */
  opacity: 1; /* Firefox fix */
}

/* Styling for the dropdown calendar (this will be library-specific) */
/* For example, if you use Litepicker, you'd target .litepicker classes */
</style>
