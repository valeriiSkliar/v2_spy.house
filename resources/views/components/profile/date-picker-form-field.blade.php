    <div class="form-item mb-20">
        <label class="d-block mb-10">{{ $label }}</label>
        <div class="form-item__field">
            <input type="{{ $type }}" name="{{ $name }}" class="input-h-57 datepicker" value="{{ old($name, $value ?? '') }}" autocomplete="off">
            <div class="datepicker-calendar _icon-calendar"></div>
        </div>
        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <script type="module">
        $(document).ready(function() {
            $('.datepicker').datepicker({
                format: 'dd.mm.yyyy',
                autoclose: true,
                todayHighlight: true
            });
        });
    </script>