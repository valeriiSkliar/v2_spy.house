<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailUpdateConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly int $code
    ) {}

    public function build()
    {
        return $this->view('emails.email-update-confirmation')
            ->subject('Email Update Confirmation')
            ->with(['code' => $this->code]);
    }
}
