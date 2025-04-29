{{-- We don't need @props here because they are defined in the PHP class --}}
<div class="inline-block {{ $class }}"> {{-- Wrap for consistent styling/alignment --}}

    @if ($status === 'completed')
    {{-- Completed Icon (Simple Checkmark) --}}
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="$width"
        :height="$height"
        viewBox="0 0 24 24"
        fill="none"
        :stroke="$finalColor" {{-- Use finalColor --}}
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="status-icon status-icon-completed">
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>

    @elseif ($status === 'failed')
    {{-- Failed Icon (Using your first SVG example) --}}
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="$width"
        :height="$height"
        viewBox="0 0 24 24"
        fill="none" {{-- Changed from your example to use stroke consistently --}}
        :stroke="$finalColor" {{-- Use finalColor --}}
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="status-icon status-icon-failed">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
        {{-- Alternative: Cross Mark --}}
        {{-- <line x1="18" y1="6" x2="6" y2="18"></line> --}}
        {{-- <line x1="6" y1="6" x2="18" y2="18"></line> --}}
    </svg>

    @elseif ($status === 'pending')
    {{-- Pending Icon (Using your second SVG example with animation) --}}
    <svg
        :width="$width"
        :height="$height"
        version="1.1"
        id="_x32_"
        xmlns="http://www.w3.org/2000/svg"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        viewBox="0 0 512 512"
        xml:space="preserve"
        fill="none" {{-- Parent fill is none --}}
        class="status-icon status-icon-pending svg-animate" {{-- Added animation class --}}>
        <g>
            <path
                :fill="$finalColor" {{-- Use finalColor --}}
                d="M403.925,108.102c-27.595-27.595-62.899-47.558-102.459-56.29L304.182,0L201.946,53.867l-27.306,14.454 l-5.066,2.654l8.076,4.331l38.16,20.542l81.029,43.602l2.277-42.859c28.265,7.546,53.438,22.53,73.623,42.638 c29.94,29.939,48.358,71.119,48.358,116.776c0,23.407-4.843,45.58-13.575,65.687l40.37,17.532 c11.076-25.463,17.242-53.637,17.242-83.219C465.212,198.306,441.727,145.904,403.925,108.102z"></path>
            <path
                :fill="$finalColor" {{-- Use finalColor --}}
                d="M296.256,416.151l-81.101-43.612l-2.272,42.869c-28.26-7.555-53.51-22.53-73.618-42.636 c-29.945-29.95-48.364-71.12-48.364-116.767c0-23.427,4.844-45.522,13.576-65.697l-40.37-17.531 c-11.076,25.53-17.242,53.723-17.242,83.228c0,57.679,23.407,110.157,61.21,147.893c27.595,27.594,62.899,47.548,102.453,56.202 l-2.716,51.9l102.169-53.878l27.455-14.454l4.988-2.643l-7.999-4.332L296.256,416.151z"></path>
        </g>
    </svg>
    @else
    {{-- Optional: Fallback for unknown status --}}
    <svg
        xmlns="http://www.w3.org/2000/svg"
        :width="$width"
        :height="$height"
        viewBox="0 0 24 24"
        fill="none"
        :stroke="$finalColor" {{-- Use finalColor --}}
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="status-icon status-icon-unknown">
        <circle cx="12" cy="12" r="10"></circle>
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
        <line x1="12" y1="17" x2="12.01" y2="17"></line>
    </svg>
    @endif

</div>

@once
<style>
    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .svg-animate {
        animation: rotate 2s linear infinite;
    }

    /* Optional: Add some basic alignment */
    .status-icon {
        display: inline-block;
        /* Or block */
        vertical-align: middle;
        /* Align with text if needed */
    }

    .status-icon-completed {
        color: #3DC98A;
        stroke: #3DC98A;
        width: 15px;
        height: 15px;
    }

    .status-icon-failed {
        color: #FF0000;
        stroke: #FF0000;
        width: 15px;
        height: 15px;
    }

    .status-icon-pending {
        color: #677d87;
        stroke: #677d87;
        fill: #677d87;
        width: 15px;
        height: 15px;
        z-index: 100;
    }
</style>
@endonce