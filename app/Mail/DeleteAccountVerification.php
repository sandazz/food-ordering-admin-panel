<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeleteAccountVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationUrl;
    public $userEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(string $verificationUrl, string $userEmail)
    {
        $this->verificationUrl = $verificationUrl;
        $this->userEmail = $userEmail;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Confirm Account Deletion - Moi ! Poke')
                    ->view('emails.delete-account')
                    ->with([
                        'verificationUrl' => $this->verificationUrl,
                        'email' => $this->userEmail,
                    ]);
    }
}
