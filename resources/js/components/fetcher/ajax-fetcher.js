// import $ from "jquery";

const ajaxFetcher = {
    get: (url, data) => $.ajax({ url, method: "GET", data }),
};

const initAjaxFetcher = () => {
    const token = localStorage.getItem("bt");
    if (!token) {
        console.error("API token not found in localStorage (key: bt)");
        return;
    }
    $.ajaxSetup({
        credentials: "omit",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            Authorization: "Bearer " + token,
        },
    });
};

export { initAjaxFetcher, ajaxFetcher };
