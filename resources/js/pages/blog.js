// /**
//  * Blog Comments JavaScript
//  * Handles comment and reply submission via AJAX
//  */
// document.addEventListener("DOMContentLoaded", function () {
//     // Get the CSRF token for API requests
//     const csrfToken = document
//         .querySelector('meta[name="csrf-token"]')
//         ?.getAttribute("content");
//     if (!csrfToken) {
//         console.error(
//             "CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token"
//         );
//     }

//     // Initialize comment form submission
//     initCommentForm();

//     // Initialize reply links
//     initReplyLinks();
// });

// /**
//  * Initialize the main comment form
//  */
// function initCommentForm() {
//     const commentForms = document.querySelectorAll(".comment-form form");
//     if (!commentForms.length) return;

//     commentForms.forEach((form) => {
//         // Remove existing listeners to prevent duplicates
//         form.removeEventListener("submit", formSubmitHandler);
//         // Add the submit listener
//         form.addEventListener("submit", formSubmitHandler);
//     });
// }

// /**
//  * Handler for form submissions
//  * @param {Event} e - The submit event
//  */
// function formSubmitHandler(e) {
//     e.preventDefault();
//     submitForm(this);
// }

// /**
//  * Initialize reply links to load reply forms
//  */
// function initReplyLinks() {
//     const replyLinks = $(".reply-btn");
//     if (!replyLinks.length) return;

//     replyLinks.each((index, link) => {
//         // Remove any existing click listeners to prevent duplicate requests
//         link.off("click", replyClickHandler);
//         // Add the click listener
//         link.on("click", replyClickHandler);
//     });
// }

// /**
//  * Handler for reply link clicks
//  * @param {Event} e - The click event
//  */
// function replyClickHandler(e) {
//     e.preventDefault();
//     loadReplyForm(this);
// }

// /**
//  * Generic form submission handler
//  * @param {HTMLFormElement} form - The form element to submit
//  */
// function submitForm(form) {
//     const formData = new FormData(form);
//     const url = form.getAttribute("action");
//     const method = form.getAttribute("method") || "POST";
//     const submitBtn = form.querySelector('button[type="submit"]');

//     // Disable submit button
//     if (submitBtn) {
//         submitBtn.disabled = true;
//         submitBtn.textContent = "Sending...";
//     }

//     // Clear previous error messages
//     const errorMessages = form.querySelectorAll(".text-danger");
//     errorMessages.forEach((el) => el.remove());

//     fetch(url, {
//         method: method,
//         body: formData,
//         headers: {
//             "X-Requested-With": "XMLHttpRequest",
//             "X-CSRF-TOKEN": document
//                 .querySelector('meta[name="csrf-token"]')
//                 .getAttribute("content"),
//         },
//         credentials: "same-origin",
//     })
//         .then((response) => {
//             if (!response.ok) {
//                 return response.json().then((errorData) => {
//                     throw { status: response.status, data: errorData };
//                 });
//             }
//             return response.json();
//         })
//         .then((data) => {
//             if (data.success) {
//                 // Replace comments section with updated content
//                 if (data.commentsHtml) {
//                     const commentsList =
//                         document.querySelector(".comment-list");
//                     if (commentsList) {
//                         commentsList.innerHTML = data.commentsHtml;
//                     }

//                     // Update pagination if needed
//                     if (data.paginationHtml) {
//                         const paginationContainer =
//                             document.querySelector(".pagination-nav");
//                         if (paginationContainer) {
//                             paginationContainer.innerHTML = data.paginationHtml;
//                         }
//                     }

//                     // Reinitialize event listeners
//                     initCommentForm();
//                     initReplyLinks();

//                     // Check if initCommentPagination function exists (from the async-pagination component)
//                     if (typeof initCommentPagination === "function") {
//                         initCommentPagination();
//                     }

//                     // Update comment count if available
//                     if (data.total !== undefined) {
//                         const commentCount =
//                             document.querySelector(".comment-count");
//                         if (commentCount) {
//                             commentCount.textContent = data.total;
//                         }
//                     }

//                     // Scroll to comments section
//                     document
//                         .getElementById("comments")
//                         .scrollIntoView({ behavior: "smooth" });
//                 }
//             } else {
//                 // Handle validation errors
//                 if (data.errors) {
//                     Object.keys(data.errors).forEach((field) => {
//                         const input = form.querySelector(`[name="${field}"]`);
//                         if (input) {
//                             const errorDiv = document.createElement("div");
//                             errorDiv.className = "text-danger mt-2";
//                             errorDiv.textContent = data.errors[field][0];
//                             input.parentNode.appendChild(errorDiv);
//                         }
//                     });
//                 } else {
//                     // Generic error message if no specific errors provided
//                     const errorDiv = document.createElement("div");
//                     errorDiv.className = "message _bg _with-border _red mt-3";
//                     errorDiv.textContent =
//                         data.message || "An error occurred. Please try again.";
//                     form.appendChild(errorDiv);
//                 }
//             }
//         })
//         .catch((error) => {
//             console.error("Error:", error);

//             // Handle validation errors from Laravel (422 responses)
//             if (error.status === 422 && error.data && error.data.errors) {
//                 Object.keys(error.data.errors).forEach((field) => {
//                     const input = form.querySelector(`[name="${field}"]`);
//                     if (input) {
//                         const errorDiv = document.createElement("div");
//                         errorDiv.className = "text-danger mt-2";
//                         errorDiv.textContent = error.data.errors[field][0];
//                         input.parentNode.appendChild(errorDiv);
//                     }
//                 });
//             } else {
//                 // Generic error message
//                 const errorDiv = document.createElement("div");
//                 errorDiv.className = "message _bg _with-border _red mt-3";
//                 errorDiv.textContent = "An error occurred. Please try again.";
//                 form.appendChild(errorDiv);
//             }
//         })
//         .finally(() => {
//             // Re-enable submit button
//             if (submitBtn) {
//                 submitBtn.disabled = false;
//                 submitBtn.textContent = "Send";
//             }
//         });
// }

// /**
//  * Load reply form for a specific comment
//  * @param {HTMLElement} replyLink - The reply link element
//  */
// function loadReplyForm(replyLink) {
//     const commentElement = replyLink.closest(".comment");
//     const commentList = $(".comment-list");
//     if (!commentList) return;
//     if (!commentElement) return;

//     // Get the URL from the reply link
//     const url = replyLink.getAttribute("href");

//     // Check if a reply form already exists
//     const existingForm = $(".comment-form");
//     const previousForm = existingForm.html();
//     if (existingForm) {
//         // existingForm.remove();
//         return;
//     }

//     // Create container for the reply form
//     const replyFormContainer = document.createElement("div");
//     replyFormContainer.className = "reply-form-container mt-3";
//     replyFormContainer.innerHTML =
//         '<div class="message _bg _with-border">Loading reply form...</div>';

//     // Insert after the comment content
//     const commentContent = commentElement.querySelector(".comment-content");
//     if (commentContent) {
//         commentContent.parentNode.insertBefore(
//             replyFormContainer,
//             commentContent.nextSibling
//         );
//     } else {
//         commentElement.appendChild(replyFormContainer);
//     }

//     // Fetch the reply form
//     fetch(url, {
//         method: "GET",
//         headers: {
//             "X-Requested-With": "XMLHttpRequest",
//             "X-CSRF-TOKEN": document
//                 .querySelector('meta[name="csrf-token"]')
//                 .getAttribute("content"),
//         },
//         credentials: "same-origin",
//     })
//         .then((response) => {
//             if (!response.ok) {
//                 throw new Error(
//                     "Network response was not ok " + response.statusText
//                 );
//             }
//             return response.json();
//         })
//         .then((data) => {
//             if (data.success && data.html) {
//                 // replyFormContainer.innerHTML = data.html;
//                 existingForm.html(previousForm);
//                 // Initialize the reply form
//                 existingForm.off("submit", formSubmitHandler);
//                 existingForm.on("submit", formSubmitHandler);
//                 const replyForm = existingForm.find("form");
//                 if (replyForm) {
//                     replyForm.addEventListener("submit", function (e) {
//                         e.preventDefault();
//                         submitForm(this);
//                     });

//                     // Focus on textarea
//                     const textarea = replyForm.querySelector("textarea");
//                     if (textarea) {
//                         textarea.focus();
//                     }

//                     // Add cancel button functionality
//                     const cancelBtn =
//                         replyFormContainer.querySelector(".cancel-reply");
//                     if (cancelBtn) {
//                         cancelBtn.addEventListener("click", function (e) {
//                             e.preventDefault();
//                             replyFormContainer.remove();
//                         });
//                     }
//                 }
//             } else {
//                 replyFormContainer.innerHTML =
//                     '<div class="message _bg _with-border _red">Could not load reply form. Please try again.</div>';
//             }
//         })
//         .catch((error) => {
//             console.error("Error:", error);
//             replyFormContainer.innerHTML =
//                 '<div class="message _bg _with-border _red">Error loading reply form. Please try again.</div>';
//         });
// }
