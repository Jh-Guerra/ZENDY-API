<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\TokenSoftnet;
use App\Models\User;
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
            $token = '4d1fa790461dd28750d26c82c63359a4a8f2f55b103e6b72f837d2ac0c470c517a5425153de539337ec506b67df98939b4af70339400d8d9c88c00124584980e';

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
            $data = $this->getUsers();
            // return count($data);
            $count = 0;
            // return $data[0];
            for ($i = 0; $i < count($data); $i++) {
                foreach ($data[$i] as $array_data) {
                    $rut_empresa            = $array_data['rut_empresa'];
                    $rut_usuario            = $array_data['rut_usuario'];
                    $nombre                 = $array_data['nombre'];
                    $usuario                = $array_data['usuario'];
                    $email                  = $array_data['email'];

                    $company = Company::where('ruc', $rut_empresa)->first();
                    $user = User::where('username', $usuario)->where('companies', '["'.$company->id.'"]')->first();
                    if (isset($company)) {
                        if (!isset($user)) {
                            $userCreate = new User;
                            $userCreate->username = $usuario;
                            $userCreate->firstName = $nombre;
                            $userCreate->lastName = '';
                            $userCreate->email = $email;
                            $userCreate->password = bcrypt('zendy2022');
                            $userCreate->encrypted_password = Crypt::encryptString('zendy2022');
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

                            $count++;
                        }
                    } else {
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
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
