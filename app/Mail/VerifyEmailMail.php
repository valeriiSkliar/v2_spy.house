<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly int $code
    ) {}

    public function build()
    {
        return $this->view('emails.verify-email')
            ->subject('Account Verification - Spy.House')
            ->with(['code' => $this->code]);
    }
}
