const apiTokenHandler = {
    getToken: () => {
        return localStorage.getItem("bt");
    },
    setToken: (token) => {
        localStorage.setItem("bt", token);
    },
    init: (initialToken) => {
        apiTokenHandler.setToken(initialToken);
        const token = apiTokenHandler.getToken();
        console.log(token);

        if (!token) {
            console.error("API Token in localStorage not found.");
        }
    },
};

export { apiTokenHandler };
