const apiBaseEndpoint = "/api";
export const config = {
    apiBaseEndpoint,
    apiProfileSettingsEndpoint: `${apiBaseEndpoint}/profile/settings`,
    apiProfileAvatarEndpoint: `${apiBaseEndpoint}/profile/avatar`,
    apiProfilePasswordUpdateInitiateEndpoint: `${apiBaseEndpoint}/profile/initiate-password-update`,
    apiProfilePasswordUpdateConfirmEndpoint: `${apiBaseEndpoint}/profile/confirm-password-update`,
    apiProfilePasswordCancelEndpoint: `${apiBaseEndpoint}/profile/cancel-password-update`,
};
