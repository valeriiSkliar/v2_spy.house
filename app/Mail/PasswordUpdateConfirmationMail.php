<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordUpdateConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly int $code
    ) {}

    public function build()
    {
        return $this->view('emails.password-update-confirmation')
            ->subject('Password Update Confirmation')
            ->with(['code' => $this->code]);
    }
}
