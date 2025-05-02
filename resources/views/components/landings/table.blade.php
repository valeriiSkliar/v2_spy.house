<div class="c-table">
    <div class="inner">
        @if($landings->count() > 0)
        <table class="table no-wrap-table">
            <x-landings.table.head
                :headers="[ 'landings.table.header.downloadLink', 'landings.table.header.dateAdded', '']" />
            <tbody>
                @foreach($landings as $landing)
                <x-landings.table.row :landing="$landing" />
                @endforeach
            </tbody>
        </table>
        @else
        <x-empty-landings />
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('liveToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    });
</script>
@endpush