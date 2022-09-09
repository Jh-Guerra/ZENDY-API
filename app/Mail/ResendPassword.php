<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResendPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user_nick;
    public $name;
    public $email;
    public $password;
    public $business;


    /**
     * Create a new message instance.
     *
     * @return void
     */

    public function __construct($user_nick, $name, $email, $password, $business)
    {
        $this->user_nick    = $user_nick;
        $this->name         = $name;
        $this->email        = $email;
        $this->password     = $password;
        $this->business     = $business;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Softnet CRM | Bienvenido')
            ->view('emails.resend_password')
            ->with([
                'user'          => $this->user_nick,
                'password'      => $this->password,
                'name'          => $this->name,
                'email'         => $this->email,
                'business'      => $this->business,
            ]);
    }
}
