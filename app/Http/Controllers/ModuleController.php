<?php

namespace App\Http\Controllers;
use App\Models\Module;

use Illuminate\Http\Request;

class ModuleController extends Controller
{
    //
    public function find($id){
        $Module = Module::find($id);

        if(!$Module) return response()->json(['error' => 'Modulo no encontrado.'], 400);

        return $Module;
    }

    public function list(){
        return Module::where('active', true)->orderBy("name")->get();
    }

}
