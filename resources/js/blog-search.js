document.addEventListener("DOMContentLoaded", function () {
    const searchForm = document.querySelector(".search-form form");
    const searchInput = document.querySelector(
        '.search-form input[type="search"]'
    );
    const blogList = document.querySelector(".blog-list");
    let searchResults = document.querySelector(".search-results");

    if (searchForm && searchInput) {
        searchInput.addEventListener(
            "input",
            debounce(function () {
                const query = this.value.trim();

                if (query.length >= 3) {
                    performSearch(query);
                } else if (searchResults) {
                    searchResults.style.display = "none";
                    blogList.style.display = "block";
                }
            }, 300)
        );

        searchForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const query = searchInput.value.trim();

            if (query.length >= 3) {
                window.location.href = `/blog/search?q=${encodeURIComponent(
                    query
                )}`;
            }
        });
    }

    function performSearch(query) {
        fetch(`/api/blog/search?q=${encodeURIComponent(query)}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Expect HTML response
            })
            .then((htmlResponse) => {
                if (!searchResults) {
                    // Create search results container if it doesn't exist
                    searchResults = document.createElement("div");
                    searchResults.classList.add("search-results");
                    // Ensure blogList exists before trying to insertBefore
                    if (blogList && blogList.parentNode) {
                        blogList.parentNode.insertBefore(
                            searchResults,
                            blogList
                        );
                    } else {
                        // Fallback if blogList is not found (e.g., on search results page)
                        // Attempt to find a suitable parent or append to body
                        const mainContent =
                            document.querySelector("main") || document.body; // Adjust selector if needed
                        mainContent.appendChild(searchResults);
                    }
                }

                // Display the search results container and hide the original blog list
                if (blogList) {
                    blogList.style.display = "none";
                }
                searchResults.style.display = "block";

                // Insert the HTML received from the server
                searchResults.innerHTML = htmlResponse;
            })
            .catch((error) => {
                console.error("Error fetching search results:", error);
                // Optionally display an error message to the user in searchResults
                if (searchResults) {
                    searchResults.innerHTML =
                        '<p class="text-center text-red-500">Sorry, an error occurred while searching. Please try again later.</p>';
                    if (blogList) {
                        blogList.style.display = "none";
                    }
                    searchResults.style.display = "block";
                }
            });
    }

    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function () {
            const context = this,
                args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                func.apply(context, args);
            }, wait);
        };
    }
});
