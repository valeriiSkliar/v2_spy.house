document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('.search-form form');
    const searchInput = document.querySelector('.search-form input[type="search"]');
    const blogList = document.querySelector('.blog-list');
    const searchResults = document.querySelector('.search-results');
    
    if (searchForm && searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            
            if (query.length >= 3) {
                performSearch(query);
            } else if (searchResults) {
                searchResults.style.display = 'none';
                blogList.style.display = 'block';
            }
        }, 300));
        
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            
            if (query.length >= 3) {
                window.location.href = `/blog/search?q=${encodeURIComponent(query)}`;
            }
        });
    }
    
    function performSearch(query) {
        fetch(`/api/blog/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (!searchResults) {
                    // Create search results container if it doesn't exist
                    searchResults = document.createElement('div');
                    searchResults.classList.add('search-results');
                    blogList.parentNode.insertBefore(searchResults, blogList);
                }
                
                searchResults.innerHTML = '';
                
                if (data.articles.length > 0) {
                    // Show search results
                    blogList.style.display = 'none';
                    searchResults.style.display = 'block';
                    
                    // Add heading
                    const heading = document.createElement('h2');
                    heading.textContent = `Search Results for "${query}"`;
                    searchResults.appendChild(heading);
                    
                    // Add articles
                    data.articles.forEach(article => {
                        searchResults.innerHTML += `
                            <div class="article">
                                <a href="/blog/${article.slug}" class="article__thumb thumb">
                                    <img src="${article.image}" alt="${article.title}">
                                </a>
                                <div class="article-info">
                                    <div class="article-info__item icon-date">${article.date}</div>
                                    <a href="/blog/${article.slug}#comments" class="article-info__item icon-comment1">${article.comments_count}</a>
                                    <div class="article-info__item icon-view">${article.views}</div>
                                    <div class="article-info__item icon-rating">${article.rating}</div>
                                </div>
                                <a href="/blog/${article.slug}" class="article__title">${article.title}</a>
                                <div class="cat-links">
                                    <a href="/blog/category/${article.category.slug}" style="color:${article.category.color};">${article.category.name}</a>
                                </div>
                            </div>
                        `;
                    });
                    
                    // Add "View all results" link
                    const viewAll = document.createElement('div');
                    viewAll.classList.add('text-center', 'mt-4', 'mb-4');
                    viewAll.innerHTML = `<a href="/blog/search?q=${encodeURIComponent(query)}" class="btn _flex _green _medium">View all ${data.total} results</a>`;
                    searchResults.appendChild(viewAll);
                } else {
                    // No results
                    searchResults.innerHTML = `
                        <div class="text-center mt-4 mb-4">
                            <h2>No results found for "${query}"</h2>
                            <p>Try different keywords or check out our categories below.</p>
                        </div>
                    `;
                    
                    // Show blog list
                    blogList.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
});