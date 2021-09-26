<?php

namespace App\Http\Controllers;
use App\Models\FrequentQuery;

use Illuminate\Http\Request;

class FrequentQueryController extends Controller
{
    //
    public function find($id){
        $FrequentQuery = FrequentQuery::find($id);

        if(!$FrequentQuery) return response()->json(['error' => 'Consulta Frecuente no encontrada.'], 400);

        return $FrequentQuery;
    }

    public function list(){
        return FrequentQuery::where('deleted', false)->orderBy("name")->get();
    }

}
