<?php

namespace App\Http\Controllers;

use App\Models\SolicitudesAcceso as ModelsSolicitudesAcceso;
use Illuminate\Http\Request;

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
}
