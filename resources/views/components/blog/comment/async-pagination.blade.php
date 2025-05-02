<nav class="pagination-nav" role="navigation" aria-label="pagination">
        @if ($paginator->hasPages())
        <ul class="pagination-list">
            @if ($paginator->onFirstPage())
                <li><a class="pagination-link prev disabled" data-page="{{ $paginator->previousPageUrl() }}" aria-disabled="true" href="javascript:void(0)"><span class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
            @else
                <li><a class="pagination-link prev" data-page="{{ $paginator->previousPageUrl() }}" aria-disabled="false" href="javascript:void(0)"><span class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
            @endif
            @if(isset($elements[0]) && is_array($elements[0]) && count($elements[0]) > 0)
                @foreach ($elements[0] as $page => $value)
                    @if ($page == $paginator->currentPage())
                        <li><a class="pagination-link active" data-page="{{ $page }}" href="javascript:void(0)">{{ $page }}</a></li>
                    @else
                        <li><a class="pagination-link" data-page="{{ $page }}" href="javascript:void(0)">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
            @if ($paginator->hasMorePages())
                <li><a class="pagination-link next" data-page="{{ $paginator->nextPageUrl() }}" aria-disabled="false" href="javascript:void(0)"><span class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
            @else
                <li><a class="pagination-link next disabled" data-page="" aria-disabled="true" href="javascript:void(0)"><span class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
            @endif
        </ul>
        @endif
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initCommentPagination();
        });
        
        function initCommentPagination() {
            const commentsList = $('.comment-list');
            const paginationLinks = $('.pagination-link');
            const commentsContainer = $('#comments');
            
            if (!commentsList || !commentsContainer) return;
            
            paginationLinks.each(function() {
                if ($(this).hasClass('disabled')) return;
                
                $(this).on('click', function(e) {
                    e.preventDefault();
                    
                    const slug = window.location.pathname.split('/').pop();
                    let page = this.dataset.page;
                    
                    // For number pagination links
                    if (!isNaN(parseInt(page))) {
                        loadComments(slug, parseInt(page));
                        return;
                    }
                    
                    // Handle prev/next links that have URLs
                    if (page && page.includes('?')) {
                        const urlParams = new URLSearchParams(page.split('?')[1]);
                        const pageParam = urlParams.get('page');
                        if (pageParam) {
                            loadComments(slug, parseInt(pageParam));
                            return;
                        }
                    }
                    
                    // Default to page 1 if we couldn't extract a page number
                    loadComments(slug, 1);
                });
            });
        }
        
        function loadComments(slug, page) {
            const commentsList = $('.comment-list');
            const paginationContainer = $('.pagination-nav');
            
            // Show loading indicator that matches site styling
            commentsList.html('<div class="text-center py-4"><div class="message _bg _with-border">Loading comments...</div></div>');
            
            fetch(`/api/blog/${slug}/comments?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update comments
                        commentsList.html(data.commentsHtml);
                        
                        // Update pagination
                        paginationContainer.html(data.paginationHtml);
                        
                        // Reinitialize pagination after DOM update
                        initCommentPagination();
                        
                        // Scroll to comments section
                        document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
                    } else {
                        commentsList.html('<div class="message _bg _with-border _red">Error loading comments. Please try again.</div>');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    commentsList.html('<div class="message _bg _with-border _red">Error loading comments. Please try again.</div>');
                });
        }
    </script>
    
