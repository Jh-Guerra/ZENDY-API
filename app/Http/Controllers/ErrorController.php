<?php

namespace App\Http\Controllers;

use App\Models\Error;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ErrorController extends Controller
{
    public function register(Request $request){
        $error = new Error();
        $user = Auth::user();

        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $this->updateValues($error, $user, $request->getContent());
        $error->save();

        return response()->json(compact('error'),201);
    }

    private function updateValues($error, $user, $request){
        $error->idCompany = $request["idCompany"];
        $error->createdBy = $user->id;
        $error->createdDate = Carbon::now()->timestamp;
        $error->module = $request["module"];
        $error->description = $request["description"];
        $error->image1 = $request["image1"];
        $error->image2 = $request["image2"];
        $error->file1 = $request["file2"];
        $error->file2 = $request["file2"];
    }

    public function list(Request $request) {
        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $errors = Error::where('reported', false);

        if($user->idCompany)
            $errors->where("idCompany", $user->idCompany);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term)
            $this->searchErrors($errors, $term);

        return $errors->orderByDesc("createdDate")->get();
    }

    public function listByUser(Request $request) {
        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $errors = Error::where("createdBy", $user->id)->where("deleted", false);

        if($user->idCompany)
            $errors->where("idCompany", $user->idCompany);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term)
            $this->searchErrors($errors, $term);

        return $errors->orderByDesc("createdDate")->get();
    }

    public function searchErrors($errors, $term){
        $errors->where('reason', 'LIKE', '%'.$term.'%');
    }

    public function find($id) {
        $Error = Error::find($id);

        if(!$Error)
            return response()->json(['error' => 'Error reportado no encontrado'], 400);

        return $Error;
    }

    public function delete($id) {
        $Error = Error::find($id);

        if(!$Error)
            return response()->json(['error' => 'Error reportado no encontrado'], 400);

        $Error->deleted = true;
        $Error->save();

        return response()->json(['success' => 'Error reportado eliminado'], 201);
    }
}
