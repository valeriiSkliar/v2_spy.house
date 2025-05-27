<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $username;
    public $loginUrl;
    public $dashboardUrl;
    public $telegramUrl;
    public $supportEmail;
    public $unsubscribeUrl;

    /**
     * Create a new message instance.
     *
     * @param string $username
     * @param string $loginUrl
     * @param string $dashboardUrl
     * @param string $telegramUrl
     * @param string $supportEmail
     * @param string $unsubscribeUrl
     */
    public function __construct($username, $loginUrl, $dashboardUrl, $telegramUrl, $supportEmail, $unsubscribeUrl)
    {
        $this->username = $username;
        $this->loginUrl = $loginUrl;
        $this->dashboardUrl = $dashboardUrl;
        $this->telegramUrl = $telegramUrl;
        $this->supportEmail = $supportEmail;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to Partners.House!')
            ->view('emails.welcome');
    }
}
