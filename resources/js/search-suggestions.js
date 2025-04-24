
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-form input[type="search"]');
    const suggestionContainer = document.querySelector('.search-suggestions');
    const suggestionContent = document.querySelector('.search-suggestions__content');
    
    if (searchInput && suggestionContainer && suggestionContent) {
        let debounceTimer;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(debounceTimer);
            
            if (query.length >= 3) {
                debounceTimer = setTimeout(() => {
                    fetchSuggestions(query);
                }, 300);
            } else {
                suggestionContainer.style.display = 'none';
            }
        });
        
        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-form')) {
                suggestionContainer.style.display = 'none';
            }
        });
        
        function fetchSuggestions(query) {
            fetch(`/api/blog/search?q=${encodeURIComponent(query)}&limit=5`)
                .then(response => response.json())
                .then(data => {
                    if (data.articles.length > 0) {
                        suggestionContent.innerHTML = '';
                        
                        data.articles.forEach(article => {
                            const item = document.createElement('div');
                            item.classList.add('search-suggestion-item');
                            
                            item.innerHTML = `
                                <a href="/blog/${article.slug}" class="search-suggestion-link">
                                    <div class="search-suggestion-thumb">
                                        <img src="${article.image}" alt="${article.title}">
                                    </div>
                                    <div class="search-suggestion-info">
                                        <div class="search-suggestion-title">${highlightText(article.title, query)}</div>
                                        <div class="search-suggestion-category" style="color:${article.category.color};">
                                            ${article.category.name}
                                        </div>
                                    </div>
                                </a>
                            `;
                            
                            suggestionContent.appendChild(item);
                        });
                        
                        // Add view all link
                        const viewAll = document.createElement('div');
                        viewAll.classList.add('search-suggestion-view-all');
                        viewAll.innerHTML = `
                            <a href="/blog/search?q=${encodeURIComponent(query)}">
                                View all ${data.total} results
                            </a>
                        `;
                        suggestionContent.appendChild(viewAll);
                        
                        suggestionContainer.style.display = 'block';
                    } else {
                        suggestionContainer.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    suggestionContainer.style.display = 'none';
                });
        }
        
        function highlightText(text, query) {
            // Create a regex with the query word boundaries and case insensitive
            const regex = new RegExp(`(${query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }
    }
});