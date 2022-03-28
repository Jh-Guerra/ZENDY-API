<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class chatMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $urlChat;

    public function __construct($user, $urlChat)
    {
        $this->user = $user;
        $this->urlChat = $urlChat;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_USERNAME'), 'ZENDY TEAM')
            ->view('emails.consulta_zendy')
            ->subject('Consulta aceptada')
            ->with([
                'user'        => $this->user,
                'urlChat'         => $this->urlChat,
            ]);
    }
}
