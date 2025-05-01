const debounce = (func, wait) => {
    let timeout;
    return function () {
        const context = this,
            args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function () {
            func.apply(context, args);
        }, wait);
    };
};

// Сохраняем оригинальное содержимое
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
        // Возвращаем оригинальное содержимое, если поиск очищен
        resetToOriginalContent(blogList, pagination, searchResults);
        return;
    }

    // Показываем индикатор загрузки
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

document.addEventListener("DOMContentLoaded", function () {
    const searchForm = $(".search-form form");
    const searchInput = $(".search-form input[type='search']");
    const searchButton = $(".search-button");
    // const closeSearchButton = $(
    //     '<button type="button" class="close-search-button">×</button>'
    // );
    const blogList = $(".blog-list");
    const pagination = $(".pagination-nav");
    let searchResults = $(".search-results");

    // // Если блока для результатов поиска нет, создаем его
    // if (searchResults.length === 0) {
    //     $(
    //         '<div class="search-results" style="display:none;"><div class="search-info"></div></div>'
    //     ).insertAfter(searchForm);
    //     searchResults = $(".search-results");
    // }

    // Сохраняем исходную разметку при загрузке страницы
    originalBlogListHtml = blogList.html();
    originalPaginationHtml = pagination.html();

    // Добавляем кнопку закрытия поиска
    // searchInput.after(closeSearchButton);
    // closeSearchButton.hide();

    if (searchForm && searchInput) {
        // Обработка ввода в поле поиска
        searchInput.on("input", function (e) {
            const query = searchInput.val().trim();

            if (query.length > 0) {
                // closeSearchButton.show();
            } else {
                // closeSearchButton.hide();
                resetToOriginalContent(blogList, pagination, searchResults);
            }

            if (query.length >= 3) {
                performSearch(query, blogList, pagination, searchResults);
            }
        });

        // Обработка клика по кнопке закрытия поиска
        // closeSearchButton.on("click", function () {
        //     searchInput.val("");
        //     closeSearchButton.hide();
        //     resetToOriginalContent(blogList, pagination, searchResults);
        // });

        // Обработка нажатия Escape
        $(document).on("keydown", function (e) {
            if (e.key === "Escape" && isSearchActive) {
                searchInput.val("");
                //  closeSearchButton.hide();
                resetToOriginalContent(blogList, pagination, searchResults);
            }
        });

        // Обработка отправки формы поиска
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

        // Проверка URL на наличие параметра поиска при загрузке страницы
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get("q");
        if (searchQuery && searchQuery.length >= 3) {
            searchInput.val(searchQuery);
            // closeSearchButton.show();
            performSearch(searchQuery, blogList, pagination, searchResults);
        }
    }
});
