<?php

namespace App\Http\Controllers;

use App\Models\Error;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ErrorController extends Controller
{
    public function register(Request $request){

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $errorZendy = new Error();
        $this->updateValues($errorZendy, $user, $request);
        $errorZendy->save();

        return response()->json(compact('error'),201);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'module' => 'required|string|max:255',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        $errorMessage = null;
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    private function updateValues($error, $user, $request){
        $error->idCompany = $user->idCompany;
        $error->createdBy = $user->id;
        $error->createdDate = date('Y-m-d', Carbon::now()->timestamp);
        $error->module = $request["module"];
        $error->reason = $request["reason"];
        $error->description = $request["description"];
        $error->image1 = $request["image1"];
        $error->image2 = $request["image2"];
    }

    public function list(Request $request) {
        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $errors = Error::where("reported", $user->id)->where("deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term) {
            $this->searchErrors($errors, $term);
        }

        $errors->get();

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
        if($term) {
            $this->searchErrors($errors, $term);
        }

        $errors = $errors->orderByDesc("createdDate")->get();

        return $errors->values()->all();;
    }

    public function searchErrors($errors, $term){
        if($term){
            $errors->where('reason', 'LIKE', '%' . $term . '%');
        }
    }

    public function find($id) {
        $error = Error::find($id);

        if(!$error)
            return response()->json(['error' => 'Error reportado no encontrado'], 400);

        $error->user = User::find($error->createdBy);
        if($error->idCompany)
            $error->company = Company::find($error->idCompany);

        return response()->json(compact('error'),201);
    }

    public function delete($id) {
        $error = Error::find($id);

        if(!$error)
            return response()->json(['error' => 'Error reportado no encontrado'], 400);

        $error->deleted = true;
        $error->save();

        return response()->json(['success' => 'Error reportado eliminado'], 201);
    }
}
