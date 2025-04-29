# Global Modal System Documentation

This documentation outlines the global modal system implementation for your Laravel application. The system provides a consistent, reusable approach to display modal windows throughout your application.

## Features

- Declarative modals with Blade components
- Dynamic modals created via JavaScript
- Confirmation dialogs
- Alert modals
- AJAX content loading
- Support for different modal sizes and configurations
- Easy integration with existing code

## Installation

1. Add the `modal.js` file to your `resources/js` directory
2. Update your `app.js` to import the modal script
3. Add the modal container div to your main layout
4. Register the `ModalController` in your application

```php
// In routes/web.php
Route::get('/modal/{type}', [App\Http\Controllers\ModalController::class, 'loadModal'])->name('modal.load');
```

## Using the Modal System

### 1. Blade Component

The simplest way to create a modal is using the Blade component:

```blade
<x-modal id="my-modal" title="My Modal Title" size="lg">
    <p>Modal content goes here</p>
    
    <x-slot name="footer">
        <button type="button" class="btn _flex _gray _medium" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn _flex _green _medium">Save changes</button>
    </x-slot>
</x-modal>
```

Available props:
- `id` (required): Unique identifier for the modal
- `title` (optional): Modal title
- `size` (optional): Modal size ('sm', 'lg', 'xl')
- `closeButton` (optional): Show close button (default: true)
- `staticBackdrop` (optional): Prevent closing when clicking outside (default: false)
- `centered` (optional): Vertically center the modal (default: false)

### 2. Show a Modal with Data Attributes

You can trigger a modal using data attributes:

```blade
<button data-toggle="modal" data-target="#my-modal">Open Modal</button>
```

### 3. JavaScript API

The modal system exposes a global `Modal` object with the following methods:

#### Show an Existing Modal

```javascript
window.Modal.show('my-modal');
```

#### Hide a Modal

```javascript
window.Modal.hide('my-modal');
```

#### Create a Dynamic Modal

```javascript
window.Modal.showWithContent(
    'Dynamic Title', 
    '<p>This is dynamic content</p>',
    'lg', // size
    function() {
        // Confirmation callback
        console.log('Confirmed!');
    }
);
```

#### Confirmation Dialog

```javascript
window.Modal.confirm(
    'Confirm Action',
    'Are you sure you want to proceed?',
    function() {
        // User confirmed
        console.log('User confirmed');
    },
    'Yes, Proceed', // confirm button text
    'Cancel'        // cancel button text
);
```

#### Alert Dialog

```javascript
window.Modal.alert(
    'Success',
    'Your changes have been saved.',
    'OK' // button text
);
```

### 4. AJAX Loading

You can load modal content via AJAX:

```javascript
// First show a loading indicator
const modal = window.Modal.showWithContent(
    'Loading...', 
    '<div class="text-center"><div class="spinner-border" role="status"></div></div>'
);

// Fetch the modal content
fetch('/modal/contact')
    .then(response => response.text())
    .then(html => {
        // Update modal content
        const modalBody = document.querySelector(`#${modal.modalId} .modal-body`);
        modalBody.innerHTML = html;
    });
```

### 5. Confirmation with Data Attributes

For delete confirmations or other actions requiring confirmation:

```blade
<button 
    data-confirm="Are you sure you want to delete this item?"
    data-confirm-title="Confirm Deletion"
    data-confirm-btn="Delete"
    href="{{ route('items.delete', $item->id) }}">
    Delete
</button>
```

## Advanced Usage

### Custom Modal Types

You can create custom modal types by extending the `ModalController`:

```php
// In ModalController.php
public function loadModal(Request $request)
{
    $modalType = $request->input('type');
    
    switch ($modalType) {
        case 'contact':
            return view('modals.contact', ['managers' => $this->getManagers()]);
            
        case 'delete-confirmation':
            return view('modals.delete-confirmation', [
                'itemId' => $request->input('item_id'),
                'itemType' => $request->input('item_type'),
                'deleteUrl' => $request->input('delete_url')
            ]);
            
        // Add your custom modal types here
            
        default:
            return response()->json(['error' => 'Invalid modal type'], 400);
    }
}
```

### Styling Modal Components

The modal system uses your existing CSS classes. To customize the appearance of modals, you can update your CSS or create specific styles for different modal types.

## Troubleshooting

### Modal not showing

- Check that you've added the modal container div to your layout
- Ensure modal IDs are unique
- Check console for JavaScript errors

### Modal content not loading

- Verify that your AJAX routes are correct
- Check network tab for failed requests
- Ensure you're properly handling the response

### Bootstrap JS not initialized

If you see errors about Bootstrap components not being initialized, make sure you're properly importing Bootstrap JS in your application.

## Best Practices

1. **Keep Modal Content Light**: Modals should contain focused content. Avoid stuffing too much information.

2. **Consistent UI**: Use the same button placement and styles across all modals.

3. **Accessible Design**: Ensure your modals are keyboard navigable and work with screen readers.

4. **Close Buttons**: Always provide a visible way to close the modal (close button, cancel button, etc.).

5. **Clear Titles**: Use descriptive titles that explain the modal's purpose.

6. **Error Handling**: Always handle errors when loading modal content via AJAX.

7. **Form Validation**: Implement client-side validation before submitting forms in modals.

8. **Destructive Actions**: Use confirmation dialogs for destructive actions like deletion.

## Examples

Check the `usage-examples.blade.php` file for comprehensive examples of how to use the modal system in various scenarios.