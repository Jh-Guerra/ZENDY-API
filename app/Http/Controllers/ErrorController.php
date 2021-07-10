<?php

namespace App\Http\Controllers;

use App\Models\Error;
use Illuminate\Http\Request;

class ErrorController extends Controller
{
    private function errorValues($Error, $request){
        $Error->idUserCompany = $request->idUserCompany;
        $Error->idCompany = $request->idCompany;
        $Error->registerDate = date('Y-m-d',strtotime($request->registerDate));
        $Error->module = $request->module;
        $Error->description = $request->description;
        $Error->image = $request->image;
        $Error->image2 = $request->image2;
        $Error->falseError = $request->falseError;
        $Error->received = $request->received;
        $Error->corrected = $request->corrected;
        $Error->reported = $request->reported;
    }

    public function register(Request $request) {
        $Error = new Error();
        $this->errorValues($Error, $request);
        $Error->save();

        $token = JWTAuth::fromUser($Error);

        return response()->json(compact('Error','token'),201);
    }

    public function update(Request $request, $id) {
        $Error = Error::find($id);

        if(!$Error){
            return response()->json(['error' => 'reporte de error no encontrado'], 400);
        }

        $this->errorValues($Error, $request);
        $Error->save();

        return response()->json($Error);
    }

    public function list() {
        return Error::where('received', '!=', true)->orderBy("registerDate")->get();
    }

    public function find($id) {
        $Error = Error::find($id);

        if(!$Error){
            return response()->json(['error' => 'reporte de error no encontrado'], 400);
        }

        return $Error;
    }

    public function delete($id) {
        $Error = Error::find($id);

        if(!$Error){
            return response()->json(['error' => 'reporte de error no encontrado'], 400);
        }

        $Error->falseError = true;
        $Error->save();

        return response()->json(['success' => 'reporte marcado como falso error'], 201);
    }
}
