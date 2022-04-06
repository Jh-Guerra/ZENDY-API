<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsultaPendienteMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $user;
    public $urlConsulta;

    public function __construct($username, $urlConsulta)
    {
        $this->username = $username;
        $this->urlConsulta = $urlConsulta;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_USERNAME'), 'ZENDY TEAM')
            ->view('emails.consulta_pendiente')
            ->subject('Nueva consulta ingresada')
            ->with([
                'username'         => $this->username,
                'urlConsulta'      => $this->urlConsulta,
            ]);
    }
}
