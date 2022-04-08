<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendRequestNewEntity extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $username;
    public $rut_empresa;
    public function __construct($username, $rut_empresa)
    {
        $this->username = $username;
        $this->rut_empresa = $rut_empresa; 
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_USERNAME'), 'ZENDY TEAM')
            ->view('emails.solicitud_empresa')
            ->subject('Nueva solicitud de empresa registrada')
            ->with([
                'username'         => $this->username,
                'urlConsulta'      => $this->rut_empresa,
            ]);
    }
}
