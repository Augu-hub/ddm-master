<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $email;
    public string $password;
    public string $appUrl;

    public function __construct(string $name, string $email, string $password, string $appUrl)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->appUrl = $appUrl;
    }

    public function build()
    {
        return $this->subject('Vos accès DIADDEM')
            ->view('emails.user_credentials');
    }
}
