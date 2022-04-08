<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\datosUsuarios;
use App\Models\rutCompanies;
use App\Models\TokenSoftnet;
use App\Models\User;
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

    public function insertUsers()
    {

        try {
            $data = usuariosZendy::all();
            $count = 0;

            foreach ($data as $value) {

                $company = Company::where('ruc', $value['ruc'])->first();
                $user = User::where('username', $value['username'])->where('companies', '["' . $company->id . '"]')->first();
                if (isset($company)) {
                    if (!isset($user)) {
                        $userCreate = new User;
                        $userCreate->username = $value['username'];
                        $userCreate->firstName = $value['nombre'];
                        $userCreate->lastName = '';
                        $userCreate->email = $value['email'];
                        $userCreate->password = bcrypt('zendy2022');
                        $userCreate->encrypted_password = null;
                        $userCreate->dob = date("Y-m-d");
                        $userCreate->phone = '';
                        $userCreate->sex = '';
                        $userCreate->idRole = 5;
                        $userCreate->companies = '["' . $company->id . '"]';
                        $userCreate->avatar = null;
                        $userCreate->isOnline = 0;
                        $userCreate->deleted = 0;
                        $userCreate->email_verified_at = null;
                        $userCreate->remember_token = null;
                        $userCreate->device_token = null;
                        $userCreate->created_at = now();
                        $userCreate->updated_at = now();
                        $userCreate->activo = 0;
                        $userCreate->save();

                        $relaCompany = new UserCompany;
                        $relaCompany->idUser = $userCreate->id;
                        $relaCompany->username = $value['username'];
                        $relaCompany->idCompany = $company->id;
                        $relaCompany->rutCompany = $value['ruc'];
                        $relaCompany->deleted = 0;
                        $relaCompany->created_at = now();
                        $relaCompany->updated_at = now();
                        $relaCompany->save();

                        $count++;
                    }
                }
            }
            if ($count == 0) {
                return array(
                    'status' => false,
                    'descripcion' => 'No hay datos que sincronizar',
                    'cantidad' => $count
                );
           } 

            DB::commit();
            return array(
                'status' => true,
                'descripcion' => 'Data sincronizada',
                'cantidad' => $count
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function usersERP()
    {
        try {
            $company = rutCompanies::All();

            $count = 0;
            if (isset($company)) {
                foreach ($company as $value) {

                    $users = datosUsuarios::where('rut', $value->ruc)->get();

                    if (isset($users)) {

                        for ($i = 0; $i < count($users); $i++) {
                            $userZendy = new usuariosZendy;
                            $userZendy->ruc      = $users[$i]['rut'];
                            $userZendy->username = $users[$i]['user'];
                            $userZendy->email    = $users[$i]['nombre_e'];
                            $userZendy->nombre   = $users[$i]['nombre_p'];
                            $userZendy->estado   = 0;
                            $userZendy->save();
                            $count++;
                        }

                        $updateCompanies = Company::findOrFail($value->id_companies);
                        $updateCompanies->estadoSync = 1;
                        $updateCompanies->save();
                    }
                }
                return array(
                    'status' => true,
                    'descripcion' => 'Todos los usuarios han sido sincronizados',
                    'cantidadUsers' => $count
                );
            } else {
                return array(
                    'status' => false,
                    'descripcion' => 'No hay nuevos usuarios que sincronizar',
                    'cantidadUsers' => $count
                );
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
