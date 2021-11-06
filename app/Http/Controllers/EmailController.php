<?php

namespace App\Http\Controllers;

use App\Mail\sendMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Mail;

class EmailController extends Controller
{
    public function contact(Request $request, $id){
        $user = User::find($id);
        $email = $request->email;
        $data = [
            'name'          => $user->firstName." ".$user->lastName,
            'userName'      => $user->userName,
            'password'      => Crypt::decryptString($user->encrypted_password),
        ];
        Mail::to($email)->send(new sendMail($data));

        return 'Mensaje Enviado';
    }
}
