<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\datosUsuarios;
use App\Models\rutCompanies;
use App\Models\TokenSoftnet;
use App\Models\User;
use App\Models\User2;
use App\Models\UserCompany;
use App\Models\usuariosZendy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class UserSoftnetController extends Controller
{
    public function getUsers()
    {

        try {

            // $token = $this->getTokenSofnet();

            $token = TokenSoftnet::all();

            for ($i = 0; $i < count($token); $i++) {
                $row = Http::withHeaders([
                    'Access-Control-Allow-Origin' => '*',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'token' => $token[$i]['token'],
                ])->get('http://api.softnet.cl/listaUsuarios');

                // return $row->json();
                $query[$i] = $row->json();
            }
            return $query;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function getTokenSofnet()
    {
        try {

            $company = Company::all();
            $token = [];
            for ($i = 0; $i < count($company); $i++) {
                $body[$i] = json_encode([
                    'username' => "erpsoftnet",
                    'password' =>  "softnet2021.,",
                    'rut'      => $company[$i]['ruc']
                ]);
                $login = Http::withBody($body[$i], 'application/json')->post('http://api.softnet.cl/login');
                $valor = (array)json_decode($login);

                if (array_key_exists('token', $valor)) {
                    $rut = TokenSoftnet::where('rut', $company[$i]['ruc'])->first();
                    if (is_null($rut)) {
                        $token = new TokenSoftnet();
                        $token->id_company = $company[$i]['id'];
                        $token->rut = $company[$i]['ruc'];
                        $token->token = $valor['token'];
                        $token->save();
                    }
                }
            }
            DB::commit();
            return "token guardado";
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    
}
