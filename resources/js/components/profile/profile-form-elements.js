import { profileFormSelectors } from './profile-form-selectors';

export const profileFormElements = {
  submitButton: null,
  messengerType: null,
  messengerContact: null,
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
  profileFormElements.messengerType = $(profileFormSelectors.messengerType);
  profileFormElements.messengerContact = $(profileFormSelectors.messengerContact);
  profileFormElements.profileMessangerSelect = $(profileFormSelectors.profileMessangerSelect);
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
  profileFormElements.scopeOfActivity = $(profileFormSelectors.scopeOfActivity);
  profileFormElements.userPreviewName = $(profileFormSelectors.userPreviewName);
  profileFormElements.submitButton = $(profileFormSelectors.submitButton);
};
