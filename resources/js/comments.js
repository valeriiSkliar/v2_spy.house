document.addEventListener('DOMContentLoaded', function () {
  const commentForm = document.querySelector('.comment-form form');
  const replyForm = document.querySelector('.comment-form form[action*="reply"]');

  if (commentForm) {
    commentForm.addEventListener('submit', function (e) {
      e.preventDefault();
      submitForm(this, false);
    });
  }

  if (replyForm) {
    replyForm.addEventListener('submit', function (e) {
      e.preventDefault();
      submitForm(this, true);
    });
  }

  function submitForm(form, isReply) {
    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: formData,
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Clear the form
          form.reset();

          // Add the new comment to the list
          if (isReply) {
            const commentId = formData.get('parent_id');
            const commentContainer = document.querySelector(
              `.comment[data-id="${commentId}"] .replies`
            );
            if (commentContainer) {
              commentContainer.innerHTML += data.html;
            } else {
              const comment = document.querySelector(`.comment[data-id="${commentId}"]`);
              const repliesDiv = document.createElement('div');
              repliesDiv.classList.add('replies');
              repliesDiv.innerHTML = data.html;
              comment.appendChild(repliesDiv);
            }

            // Close reply form
            document.querySelector('.comment-form[data-reply]').remove();
          } else {
            document.querySelector('.comment-list').innerHTML += data.html;
          }

          // Update comment count
          const commentCount = document.querySelector('.comment-count');
          commentCount.textContent = parseInt(commentCount.textContent) + 1;
        } else {
          // Show error message
          const errorDiv = document.createElement('div');
          errorDiv.classList.add('text-danger', 'mt-2');
          errorDiv.textContent = data.message;
          form.appendChild(errorDiv);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert(trans('frontend.errors.comment_save_failed'));
      });
  }

  // Add event delegation for reply links
  document.addEventListener('click', function (e) {
    if (e.target.matches('.reply-btn') || e.target.closest('.reply-btn')) {
      e.preventDefault();
      const link = e.target.matches('.reply-btn') ? e.target : e.target.closest('.reply-btn');
      const url = link.href;

      fetch(url)
        .then(response => response.json())
        .then(data => {
          // Remove any existing reply forms
          const existingForm = document.querySelector('.comment-form[data-reply]');
          if (existingForm) {
            existingForm.remove();
          }

          // Insert the reply form after the comment
          const comment = link.closest('.comment');
          comment.insertAdjacentHTML('afterend', data.html);
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }
  });
});
