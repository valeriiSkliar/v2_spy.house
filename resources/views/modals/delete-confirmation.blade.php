<div class="modal-head">
    <h5 class="modal-title">Confirm Deletion</h5>
</div>

<div class="modal-body">
    <p>Are you sure you want to delete this {{ $itemType ?? 'item' }}? This action cannot be undone.</p>
</div>

<div class="modal-footer">
    <form id="delete-form-{{ $itemId }}" action="{{ $deleteUrl }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="button" class="btn _flex _gray _medium" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn _flex _red _medium">Delete</button>
    </form>
</div>