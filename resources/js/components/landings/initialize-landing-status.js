const initializeLandingStatus = () => {
    const landingStatusIcons = $(".landing-status-icon");

    if (landingStatusIcons.length > 0) {
        landingStatusIcons.each((index, element) => {
            const status = $(element).data("status");
            console.log(status);
        });
    }
};

export { initializeLandingStatus };
