<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SolicitudesAcceso as ModelsSolicitudesAcceso;
use App\Models\UserCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class solicitudesAcceso extends Controller
{
    public function store(Request $request)
    {
        try {
            $ruc = ModelsSolicitudesAcceso::where('ruc',$request->ruc)->first();
            if (!isset($ruc)) {
                $nuevaSolicitud = new ModelsSolicitudesAcceso;
                $nuevaSolicitud->username = $request->username;
                $nuevaSolicitud->ruc = $request->ruc;
                $nuevaSolicitud->estado = 0;
                $nuevaSolicitud->observacion = null;
                $nuevaSolicitud->save();

                return array('status' => true,
                             'descripcion' => 'Se creó correctamente la solicitud');
            }else{

                $ruc = ModelsSolicitudesAcceso::where('ruc',$request->ruc)->Where('estado',1)->first();
                if(isset($ruc)){
                    return array('status' => false,
                    'descripcion' => 'La empresa ya se encuentra registrada');
                }else{
                    return array('status' => false,
                    'descripcion' => 'La empresa ya tiene una solicitud pendiente');
                }

            }


        } catch (\Throwable $th) {
            throw $th;
        }


    }

    public function changeState($id)
    {
        try {
            $state = ModelsSolicitudesAcceso::find($id);
            $state->estado = 1;
            $state->save();

            return 'La empresa fue creada';
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function listCeros()
    {
        try {
            $rucs = ModelsSolicitudesAcceso::where('estado',0)->get();

            return $rucs;

        } catch (\Throwable $th) {
            throw $th;
        }


    }

    public function listSincronizados()
    {
        try {
            $rucs = ModelsSolicitudesAcceso::where('estado',1)->get();

            return $rucs;

        } catch (\Throwable $th) {
            throw $th;
        }


    }

    public function SolicitudesCero()
    {
        try {
            $rucs = ModelsSolicitudesAcceso::where('estado',0)->get();

            $rpta = $this->sincronizacionEmpresas($rucs);

            return $rpta;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function SeleccionSolicitudes(Request $request)
    {
        try {
            $ruc = json_decode(str_replace("'",'',$request['rucs']));
            $rucs = ModelsSolicitudesAcceso::whereIn('ruc', $ruc)->get();

            $ruc = $this->sincronizacionEmpresas($rucs);

            return $ruc;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function sincronizacionEmpresas($rucs)
    {
        try {
            $rucs = json_decode($rucs);

            $count = 0;
            if ((count($rucs)> 0)) {
                for ($i=0; $i <count($rucs) ; $i++) {
                    $data = Http::withHeaders([
                        'Access-Control-Allow-Origin'=>'*',
                        'Content-Type'=> 'application/x-www-form-urlencoded',
                        'token' => 'eb43a27d64dc2dfecf676fe57120d37311e39eb947c179442ffd394a2869157d56dff31b50036656bc71c6a1ff0d41f5156e75cec96778216a754a790c2d2109',
                    ])->get('http://api.softnet.cl/cliente/'.$rucs[$i]->ruc);
                    $value = $data->json();

                        $newCompanie = new Company;
                        $newCompanie->ruc                        =   $value['rut'];
                        $newCompanie->name                       =   $value['nombre_rz'];
                        $newCompanie->description                =   $value['nombre_fantasia'];
                        $newCompanie->phone                      =   $value['telefono'];
                        $newCompanie->email                      =   $value['mail'];
                        $newCompanie->description                =   $value['observacion'];
                        $newCompanie->helpDesks                  =   '["11"]';
                        $newCompanie->address                    =   !empty($value['direccion']) ? $value['direccion'][0]['direccion'] : '';
                        $newCompanie->save();

                        $solicitud = ModelsSolicitudesAcceso::where('ruc',$rucs[$i]->ruc)->first()->id;
                        $this->changeState($solicitud);
                        $count++;

                }
                DB::commit();
                return array('status' => true,
                            'descripcion' => 'Empresas sincronizadas con éxito',
                            'cantidad' => $count);
            }
                return array('status' => false,
                            'descripcion' => 'Sin empresas por sincronizar',
                            'cantidad' => $count);

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }

    public function getTokenSofnet()
    {
        try {

            $company = ModelsSolicitudesAcceso::all();
            $token = [];
            for ($i = 0; $i < count($company); $i++) {
                $body[$i] = json_encode([
                    'username' => "erpsoftnet",
                    'password' =>  "softnet2021.,",
                    'rut'      => $company[$i]['ruc']
                ]);
                $login = Http::withBody($body[$i], 'application/json')->post('http://api.softnet.cl/login');
                $valor = (array)json_decode($login);
                $valores[]=$valor;
                if (array_key_exists('mensaje', $valor)) {
                    $rut = ModelsSolicitudesAcceso::where('ruc', $company[$i]['ruc'])->first();
                    if (is_null($rut)) {
                        $token = ModelsSolicitudesAcceso::find($rut['id']);
                        $token->observacion = 'Modulo inactivo';
                        $token->estado = 0; 
                        $token->save();
                    }
                }
                
            }
            DB::commit();
            return $valores;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function sincronizarUsuarios()
    {
        try {
            //Aqui me quede ATTE: Kent :v
        $AllUsers = ModelsSolicitudesAcceso::All();
        $token = $this->getTokenSofnet();
        $count = 0;
        for ($i=0; $i <count($token) ; $i++) { 
            if (array_key_exists('token', $token[$i])) {
                for ($i=0; $i <count($AllUsers) ; $i++) {   
                    $data = Http::withHeaders([
                        'Access-Control-Allow-Origin'=>'*',
                        'Content-Type'=> 'application/x-www-form-urlencoded',
                        'token' => $token[$i]['token'],
                    ])->get('http://api.softnet.cl/listaUsuarios');
                    $value = $data->json();
                    dd($AllUsers);
                        $company = Company::where('ruc', $value[$i]['rut_empresa'])->first();
                        if ($AllUsers['username'] == $value[$i]['usuario']) {
                        $userCreate = new ModelsSolicitudesAcceso();
                        $userCreate->username = $value[$i]['usuario'];
                        $userCreate->firstName = $value[$i]['nombre'];
                        $userCreate->lastName = '';
                        $userCreate->email = $value[$i]['email'];
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
                        $userCreate->activo = 1;
                        $userCreate->save();
        
                        $solicitud = ModelsSolicitudesAcceso::where('ruc',$AllUsers[$i]->ruc)->first()->id;
                        $this->changeState($solicitud);
                        $count++;

                        $relacion = new UserCompany();
                        $relacion->idUser = $userCreate->id;
                        $relacion->username = $value[$i]['usuario'];
                        $relacion->idCompany = $company->id;
                        $relacion->rutCompany = $value[$i]['rut_empresa'];
                        $relacion->deleted = 0;
                        $relacion->created_at = now();
                        $relacion->updated_at = now();
                        $relacion->save();

                        }
                        
        
                }
                }
        }
            return array('status'=> true,
                        'descripcion' => 'Nuevos usuarios ingresados',
                        'cantidad'=> $count);
        } catch (\Throwable $th) {
            throw $th;
        }
        
        
        
        
    }
}
