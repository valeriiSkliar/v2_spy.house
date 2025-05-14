import "flatpickr/dist/flatpickr.css";
import {
    initChangeEmail,
    initChangePassword,
    initChangePersonalGreeting,
    initUpdateIpRestriction,
    initUpdateProfileSettings,
    initUpdateNotificationSettings,
} from "../components";
import { config } from "../config";
document.addEventListener("DOMContentLoaded", function () {
    initUpdateProfileSettings();
    initChangeEmail();
    initChangePassword();
    initChangePersonalGreeting();
    initUpdateIpRestriction();
    initUpdateNotificationSettings();
});
