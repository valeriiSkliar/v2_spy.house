import { debounce } from "@/helpers";
let originalBlogListHtml = "";
let originalPaginationHtml = "";
let isSearchActive = false;
let isLoading = false;
const performSearch = debounce(function (
    query,
    blogList,
    pagination,
    searchResults
) {
    if (query.length < 3) {
        resetToOriginalContent(blogList, pagination, searchResults);
        return;
    }

    setLoadingState(true, searchResults);
    isSearchActive = true;

    const url = new URL(window.location.href);
    url.pathname = "/api/blog/search";
    url.searchParams.set("q", encodeURIComponent(query));

    fetch(url.toString())
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((responseData) => {
            setLoadingState(false, searchResults);

            if (responseData.success) {
                // Скрываем пагинацию при поиске
                pagination.hide();

                if (responseData.data.total > 0) {
                    const newHtml = responseData.data.html;
                    blogList.html(newHtml);
                    blogList.show();
                    searchResults.show();
                    searchResults
                        .find(".search-info")
                        .text(
                            `Найдено результатов: ${responseData.data.total}`
                        );
                } else {
                    // Показываем сообщение "ничего не найдено"
                    const newHtml = responseData.data.html;
                    showNoResultsMessage(blogList, searchResults, newHtml);
                }
            }
        })
        .catch((error) => {
            console.error("Ошибка поиска:", error);
            setLoadingState(false, searchResults);
            showErrorMessage(searchResults);
        });
},
300);

function setLoadingState(isLoading, searchResults) {
    if (isLoading) {
        searchResults.show();
        searchResults.find(".search-info").text("Загрузка...");
    }
}

function showNoResultsMessage(blogList, searchResults, html) {
    console.log(html);
    blogList.html(html);
    blogList.show();
    // searchResults.find(".search-info").text("Результатов не найдено");
}

function showErrorMessage(searchResults) {
    searchResults
        .find(".search-info")
        .text("Произошла ошибка при поиске. Пожалуйста, попробуйте еще раз.");
}

function resetToOriginalContent(blogList, pagination, searchResults) {
    if (isSearchActive) {
        blogList.html(originalBlogListHtml);
        pagination.html(originalPaginationHtml);
        pagination.show();
        searchResults.hide();
        isSearchActive = false;
    }
}

function initBlogSearch() {
    const searchForm = $(".search-form form");
    const searchInput = $(".search-form input[type='search']");
    const searchButton = $(".search-button");
    const blogList = $(".blog-list");
    const pagination = $(".pagination-list");
    let searchResults = $(".search-results");

    originalBlogListHtml = blogList.html();
    originalPaginationHtml = pagination.html();

    if (searchForm && searchInput) {
        searchInput.on("input", function (e) {
            const query = searchInput.val().trim();

            if (query.length > 0) {
                resetToOriginalContent(blogList, pagination, searchResults);
            }

            if (query.length >= 3) {
                performSearch(query, blogList, pagination, searchResults);
            }
        });

        $(document).on("keydown", function (e) {
            if (e.key === "Escape" && isSearchActive) {
                searchInput.val("");
                resetToOriginalContent(blogList, pagination, searchResults);
            }
        });

        searchForm.on("submit", function (e) {
            e.preventDefault();
            const query = searchInput.val().trim();

            if (query.length >= 3) {
                const url = new URL(window.location.href);
                url.pathname = "/blog/search";
                url.searchParams.set("q", query);
                window.location.href = url.toString();
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get("q");
        if (searchQuery && searchQuery.length >= 3) {
            searchInput.val(searchQuery);
            performSearch(searchQuery, blogList, pagination, searchResults);
        }
    }
}
export {
    performSearch,
    setLoadingState,
    showNoResultsMessage,
    showErrorMessage,
    resetToOriginalContent,
    initBlogSearch,
};
