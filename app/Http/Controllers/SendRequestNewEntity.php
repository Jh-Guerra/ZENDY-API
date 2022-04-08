<?php

namespace App\Http\Controllers;

use App\Mail\SendRequestNewEntity as MailSendRequestNewEntity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendRequestNewEntity extends Controller
{
    public function SendRequest(Request $request){
        
        try {
            $superadmins = json_decode(User::where('idRole',1)->where('activo',1)->get());
           
            for ($i=0; $i <count($superadmins) ; $i++) { 
                Mail::to($superadmins[$i]->email)->send(new MailSendRequestNewEntity($request->username, $request->rut_empresa));
            }
            
            return 'Mensaje de solicitud enviada';
            
        } catch (\Throwable $th) {
            throw $th;
        }
        
    }
}
