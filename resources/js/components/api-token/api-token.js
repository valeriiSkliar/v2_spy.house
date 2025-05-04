const apiToken = {
    getToken: () => {
        return localStorage.getItem("bt");
    },
    setToken: (token) => {
        localStorage.setItem("bt", token);
    },
    init: (initialToken) => {
        apiToken.setToken(initialToken);
        const token = apiToken.getToken();
        console.log(token);

        if (!token) {
            console.error("API Token in localStorage not found.");
        }
    },
};

export { apiToken };
