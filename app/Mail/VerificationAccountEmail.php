<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationAccountEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $loginUrl;
    public $telegramUrl;
    public $supportEmail;
    public $unsubscribeUrl;

    /**
     * Create a new message instance.
     *
     * @param string $code
     * @param string $loginUrl
     * @param string $telegramUrl
     * @param string $supportEmail
     * @param string $unsubscribeUrl
     */
    public function __construct($code, $loginUrl, $telegramUrl, $supportEmail, $unsubscribeUrl)
    {
        $this->code = $code;
        $this->loginUrl = $loginUrl;
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
        return $this->subject('Account Verification - Spy.House')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.verification-account');
    }
}
