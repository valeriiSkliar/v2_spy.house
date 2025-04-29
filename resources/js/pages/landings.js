document.addEventListener("DOMContentLoaded", function () {
    console.log("Landings.js loaded");

    // const deleteModalElement = document.getElementById("deleteLandingModal");

    // if (deleteModalElement) {
    //     const deleteModalForm = document.getElementById("deleteModalForm");
    //     // Optional: Get modal title or body if you want to customize them
    //     // const modalTitle = deleteModalElement.querySelector('.modal-title');
    //     // const modalBody = deleteModalElement.querySelector('.modal-body');

    //     deleteModalElement.addEventListener("show.bs.modal", function (event) {
    //         // Button that triggered the modal
    //         const button = event.relatedTarget;

    //         // Extract info from data-* attributes
    //         const deleteUrl = button.getAttribute("data-delete-url");
    //         const landingId = button.getAttribute("data-landing-id");

    //         // Update the form's action attribute
    //         if (deleteModalForm && deleteUrl) {
    //             deleteModalForm.setAttribute("action", deleteUrl);
    //         }

    //         // Optional: Update the modal's content.
    //         // Example: modalBody.textContent = `Are you sure you want to delete landing #${landingId}?`;
    //     });
    // }

    // Removed old confirm logic
    /*
    const deleteForms = document.querySelectorAll('.delete-landing-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default form submission
            if (confirm('Are you sure you want to delete this landing?')) {
                form.submit(); // Submit the form if confirmed
            }
        });
    });
    */
});
