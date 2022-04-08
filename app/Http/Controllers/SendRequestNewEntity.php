<?php

namespace App\Http\Controllers;

use App\Mail\SendRequestNewEntity as MailSendRequestNewEntity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendRequestNewEntity extends Controller
{
    public function SendRequest(Request $request){
        //sgrado@softnet.cl
        Mail::to('aldair1999.26@gmail.com')->send(new MailSendRequestNewEntity($request->username, $request->rut_empresa));

        return 'Mensaje de solicitud enviada';
    }
}
