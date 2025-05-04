document.addEventListener("DOMContentLoaded", () => {
    const testBaseToken = document.getElementById("test-base-token");
    const testBaseToken2 = document.getElementById("test-base-token2");

    if (testBaseToken) {
        return;
    }

    testBaseToken?.addEventListener("click", () => {
        console.log("Test Base Token");
        const token = localStorage.getItem("bt");
        if (!token) {
            console.error("API token not found in localStorage (key: bt)");
            return;
        }
        fetch("/api/test-api-token", {
            credentials: "omit",
            headers: {
                Accept: "application/json",
                Authorization: "Bearer " + token,
            },
        })
            .then(async (response) => {
                if (!response.ok) {
                    const errorData = await response
                        .json()
                        .catch(() => ({ message: response.statusText }));
                    console.error(`Error ${response.status}:`, errorData);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Success:", data);
            })
            .catch((error) => {
                // Error already logged in the previous step
                // console.error("Fetch Error:", error);
            });
    });

    if (testBaseToken2) {
        return;
    }

    testBaseToken2?.addEventListener("click", () => {
        console.log("Test Base Token 2");
        const token = localStorage.getItem("bt");
        if (!token) {
            console.error("API token not found in localStorage (key: bt)");
            return;
        }
        fetch("/api/test-api-token2", {
            credentials: "omit",
            headers: {
                Accept: "application/json",
                Authorization: "Bearer " + token,
            },
        })
            .then(async (response) => {
                if (!response.ok) {
                    const errorData = await response
                        .json()
                        .catch(() => ({ message: response.statusText }));
                    console.error(`Error ${response.status}:`, errorData);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Success:", data);
            })
            .catch((error) => {
                // console.error("Fetch Error:", error);
            });
    });
});

// ["read:profile", "read:public", "read:base-token"];
