import { profileFormSelectors } from "./profile-form-selectors";

export const profileFormElements = {
    telegram: null,
    viberPhone: null,
    whatsappPhone: null,
    visibleValue: null,
    profileMessangerSelect: null,
    profileMessangerSelectTrigger: null,
    profileMessangerSelectDropdown: null,
    profileMessangerSelectOptions: null,
    form: null,
    login: null,
    experience: null,
    scopeOfActivity: null,
    userPreviewName: null,
};

export const initProfileFormElements = () => {
    profileFormElements.telegram = $(profileFormSelectors.telegram);
    profileFormElements.viberPhone = $(profileFormSelectors.viberPhone);
    profileFormElements.whatsappPhone = $(profileFormSelectors.whatsappPhone);
    profileFormElements.visibleValue = $(profileFormSelectors.visibleValue);
    profileFormElements.profileMessangerSelect = $(
        profileFormSelectors.profileMessangerSelect
    );
    profileFormElements.profileMessangerSelectTrigger = $(
        profileFormSelectors.profileMessangerSelectTrigger
    );
    profileFormElements.profileMessangerSelectDropdown = $(
        profileFormSelectors.profileMessangerSelectDropdown
    );
    profileFormElements.profileMessangerSelectOptions = $(
        profileFormSelectors.profileMessangerSelectOptions
    );
    profileFormElements.form = $(profileFormSelectors.form);
    profileFormElements.login = $(profileFormSelectors.login);
    profileFormElements.experience = $(profileFormSelectors.experience);
    profileFormElements.scopeOfActivity = $(
        profileFormSelectors.scopeOfActivity
    );
    profileFormElements.userPreviewName = $(
        profileFormSelectors.userPreviewName
    );
};
