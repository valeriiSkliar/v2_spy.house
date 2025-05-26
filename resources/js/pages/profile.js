import 'flatpickr/dist/flatpickr.css';
import {
  initChangeEmail,
  initChangePassword,
  initChangePersonalGreeting,
  initUpdateIpRestriction,
  initUpdateNotificationSettings,
  initUpdateProfileSettings,
} from '../components';
import { initEnable2FA } from './profile/enable-2fa';
document.addEventListener('DOMContentLoaded', function () {
  initUpdateProfileSettings();
  initChangeEmail();
  initChangePassword();
  initChangePersonalGreeting();
  initUpdateIpRestriction();
  initUpdateNotificationSettings();
  initEnable2FA();
});
