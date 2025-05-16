function updateBrowserUrl(queryParams) {
    const currentUrl = new URL(window.location.href);
    Object.keys(queryParams).forEach((key) => {
        if (
            queryParams[key] === null ||
            queryParams[key] === undefined ||
            queryParams[key] === ""
        ) {
            currentUrl.searchParams.delete(key);
        } else {
            currentUrl.searchParams.set(key, queryParams[key]);
        }
    });
    // Удаляем 'page' если значение 1 для более чистого URL первой страницы
    if (currentUrl.searchParams.get("page") === "1") {
        currentUrl.searchParams.delete("page");
    }
    history.pushState(queryParams, "", currentUrl.toString());
}

export { updateBrowserUrl };
