<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SPY House</title>
    <style type="text/css">
        @media screen {
            @font-face {
                font-family: 'Montserrat';
                font-style: normal;
                font-weight: 400;
                src: url(https://fonts.gstatic.com/s/montserrat/v26/JTUHjIg1_i6t8kCHKm4532VJOt5-QNFgpCtr6Hw5aX8.ttf) format('truetype');
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; width: 100% !important;">
    <table
        style="background: #E6EAEC; border-collapse: collapse; width: 100%; font-family: 'Montserrat', Verdana, sans-serif !important; line-height: 1.4;">
        <tr>
            <td style="padding: 25px 5px 35px;">
                <table align="center" style="width:100%; max-width: 600px; border-collapse: collapse;">
                    <tr>
                        <td style="padding-bottom: 20px;">
                            <table style="border-collapse: collapse; width: 100%; min-width: 100%;">
                                <tr>
                                    <td align="left" valign="middle" style="width: 50%;">
                                        <a href="{{ $loginUrl }}" target="_blank" style="display:inline-block;">
                                            <img src="https://dev.vitaliimaksymchuk.com.ua/spy/email/images/logo.png"
                                                alt="Spy.House" width="142" border="0"
                                                style="display: block; max-width: 100%; height: auto;">
                                        </a>
                                    </td>
                                    <td align="right" valign="middle" style="padding-left: 15px; width: 50%;">
                                        <a href="{{ $loginUrl }}" target="_blank"
                                            style="color: #3B4A51; text-decoration: none;">
                                            <img src="https://dev.vitaliimaksymchuk.com.ua/spy/email/images/login.png"
                                                alt="cpa.house" width="37" height="37" border="0"
                                                style="max-width: 100%; display: inline-block; vertical-align: middle; margin-right: 10px;">
                                            <span style="font-weight: bold; font-size: 18px; vertical-align: middle;">{{
                                                __('emails.welcome.login') }}</span>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #ffffff; padding: 15px 15px; border-radius: 10px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td>
                                        <img src="https://dev.vitaliimaksymchuk.com.ua/spy/email/images/thumb.jpg"
                                            alt="Welcome"
                                            style="display: block; margin: 0; width: 100%; height: auto; border-radius: 10px;">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 30px; padding-bottom: 20px;">
                                        <table align="center"
                                            style="width:100%; max-width: 500px; border-collapse: collapse;">
                                            <tr>
                                                <td>
                                                    <p
                                                        style="margin: 0; color: #3B4A51; font-size: 20px; line-height: 130%; text-align: left; padding-bottom: 20px;">
                                                        <strong>{{ __('emails.welcome.greeting', ['username' =>
                                                            $username]) }}</strong>
                                                    </p>
                                                    <p
                                                        style="margin: 0; color: #3B4A51; font-size: 14px; line-height: 130%; text-align: left; padding-bottom: 20px;">
                                                        {{ __('emails.welcome.title') }}
                                                    </p>
                                                    <p
                                                        style="margin: 0; color: #3B4A51; font-size: 14px; line-height: 130%; text-align: left; padding-bottom: 20px;">
                                                        {{ __('emails.welcome.description') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 0; padding-bottom: 35px;">
                                        <a href="{{ $dashboardUrl }}" target="_blank"
                                            style="font-size: 16px; line-height: 1; font-weight: bold; color: #ffffff; text-decoration: none !important; display: inline-block; text-align: center; cursor: pointer; border-radius: 10px; background-color: #3DC98A; padding: 20px 20px; min-width: 200px; box-sizing: border-box;">
                                            {{ __('emails.welcome.go_now_button') }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 0; padding-bottom: 30px;">
                                        <p style="width: 31px; height: 2px; background: #F4F4F6; margin: 0 0 20px 0;">
                                        </p>
                                        <p
                                            style="margin: 0; color: #879399; font-size: 14px; line-height: 130%; text-align: center;">
                                            {{ __('emails.welcome.regards') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 50px; text-align: center;">
                            <table
                                style="width: 100%; border-collapse: collapse; background: #334044; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 25px 15px;">
                                        <table align="center"
                                            style="width: 100%; max-width: 500px; border-collapse: collapse;">
                                            <tr>
                                                <td align="left" valign="middle" style="padding-right: 30px;">
                                                    <p style="margin: 0; color: #D7E0E4; font-size: 15px;">
                                                        {{ __('emails.welcome.telegram_text') }}
                                                    </p>
                                                </td>
                                                <td align="right" valign="middle">
                                                    <a href="{{ $telegramUrl }}" target="_blank"
                                                        style="box-sizing: border-box;">
                                                        <img src="https://dev.vitaliimaksymchuk.com.ua/spy/email/images/chat_btn.png"
                                                            alt="Telegram" width="110" height="50"
                                                            style="vertical-align: middle; min-width: 110px;">
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top: 50px; text-align: center;">
                            <table align="center" style="width: 100%; max-width: 480px; border-collapse: collapse;">
                                <tr>
                                    <td>
                                        <p
                                            style="margin: 0; color: #3B4A51; font-size: 14px; line-height: 130%; text-align: center; padding-bottom: 15px;">
                                            <a href="mailto:{{ $supportEmail }}"
                                                style="text-decoration: none; color: #3B4A51;">
                                                <strong>{{ $supportEmail }}</strong>
                                            </a>
                                        </p>
                                        <p
                                            style="margin: 0; color: #3B4A51; font-size: 14px; line-height: 130%; text-align: center; padding-bottom: 15px;">
                                            {{ __('emails.welcome.footer_newsletter_text') }}
                                            <a href="{{ $unsubscribeUrl }}"
                                                style="text-decoration: underline; color: #3B4A51; font-weight: bold;">
                                                <strong>{{ __('emails.welcome.unsubscribe') }}</strong>
                                            </a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>