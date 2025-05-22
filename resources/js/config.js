const apiBaseEndpoint = '/api';
export const config = {
  apiBaseEndpoint,
  apiProfileSettingsEndpoint: `${apiBaseEndpoint}/profile/settings`,
  apiProfileAvatarEndpoint: `${apiBaseEndpoint}/profile/avatar`,
  apiProfilePasswordUpdateInitiateEndpoint: `${apiBaseEndpoint}/profile/initiate-password-update`,
  apiProfilePasswordUpdateConfirmEndpoint: `${apiBaseEndpoint}/profile/confirm-password-update`,
  apiProfilePasswordCancelEndpoint: `${apiBaseEndpoint}/profile/cancel-password-update`,
  apiProfileEmailUpdateInitiateEndpoint: `${apiBaseEndpoint}/profile/initiate-email-update`,
  apiProfileEmailUpdateConfirmEndpoint: `${apiBaseEndpoint}/profile/confirm-email-update`,
  apiProfileEmailCancelEndpoint: `${apiBaseEndpoint}/profile/cancel-email-update`,
  apiProfilePersonalGreetingUpdateInitiateEndpoint: `${apiBaseEndpoint}/profile/initiate-personal-greeting-update`,
  apiProfilePersonalGreetingUpdateConfirmEndpoint: `${apiBaseEndpoint}/profile/confirm-personal-greeting-update`,
  apiProfilePersonalGreetingCancelEndpoint: `${apiBaseEndpoint}/profile/cancel-personal-greeting-update`,
  apiProfileIpRestrictionUpdateEndpoint: `${apiBaseEndpoint}/profile/update-ip-restriction`,
  apiProfileNotificationsUpdateEndpoint: `${apiBaseEndpoint}/profile/update-notifications`,
  apiNotificationsMarkAsReadEndpoint: `${apiBaseEndpoint}/notifications/{id}/mark-read`,
  apiNotificationsMarkAllAsReadEndpoint: `${apiBaseEndpoint}/notifications/mark-all-read`,
};

export const debagConfig = {
  debug: false,
};
