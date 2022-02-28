<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateDeviceTokenController extends Controller
{
    public function saveToken(Request $request)
    {
        try {

            $d_tokenA = $request->all();

            $d_user = $d_tokenA['body']['username'];
            $d_token =$d_tokenA['token'];

            $passNull = DB::table('users')
                        ->where('device_token','=',$d_user)
                        ->update(['device_token'=>null]);

            $usuario = DB::table('users')
                        ->where('username','=',$d_user)
                        ->update(['device_token'=>$d_token]);

            return response()->json(['token saved successfully.']);

        } catch (\Throwable $th) {
            throw $th;
        }

    }
}
