document.addEventListener("DOMContentLoaded", () => {
    const testBaseToken = document.getElementById("test-base-token");
    const testBaseToken2 = document.getElementById("test-base-token2");

    testBaseToken.addEventListener("click", () => {
        console.log("Test Base Token");
        fetch("/api/test-api-token")
            .then((response) => response.json())
            .then((data) => {
                console.log(data);
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    });
    testBaseToken2.addEventListener("click", () => {
        console.log("Test Base Token");
        fetch("/api/test-api-token2")
            .then((response) => response.json())
            .then((data) => {
                console.log(data);
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    });
});
