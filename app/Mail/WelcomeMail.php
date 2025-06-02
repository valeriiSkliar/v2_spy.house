<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly User $user
    ) {}

    public function build()
    {
        return $this->view('emails.welcome')
            ->subject('Welcome to Partners.House!')
            ->with(['user' => $this->user]);
    }
}
