<?php

namespace App\Http\Controllers;

use App\Models\Error;
use App\Models\Notification;
use App\Models\User;
use App\Models\Company;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Contracts\Providers\Storage;

class ErrorController extends Controller
{
    public function register(Request $request){

        $error = $this->validateFields($request);
        if($error)
            return response()->json($error, 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $errorZendy = new Error();
        $errorZendy->idCompany = $user->idCompany;
        $errorZendy->createdBy = $user->id;
        $errorZendy->idModule = $request["idModule"];
        $errorZendy->reason = $request["reason"];
        $errorZendy->description = $request["description"];
        $errorZendy->image = $request["image"];
        $errorZendy->file = $request["file"];
        $errorZendy->status = "Pendiente";
        $errorZendy->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $uploadImageController->updateFile($request->file('image'), "error/".$errorZendy->id, "image_".Carbon::now()->timestamp);
            $errorZendy->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $uploadImageController->updateFile($request->file('file'), "error/".$errorZendy->id, "file_".Carbon::now()->timestamp);
            $errorZendy->file = $path;
            $fileSaved = true;
        }

        if($fileSaved){
            $errorZendy->save();
        }

        return response()->json(compact('errorZendy'),201);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        $errorMessage = null;
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    public function update(Request $request, $id){
        $errorZendy = Error::find($id);

        $errorMessage = $this->validateFields($request);
        if ($errorMessage) {
            return response()->json($errorMessage, 400);
        }

        if (!$errorZendy) {
            return response()->json(['error' => 'Error reportado no encontrado'], 400);
        }

        $errorZendy->idCompany = $request->idCompany;
        $errorZendy->createdBy = $request->createdBy;
        $errorZendy->idModule = $request->idModule;
        $errorZendy->reason = $request->reason;
        $errorZendy->description = $request->description;
        if($request->image){
            if($request->oldImage){
                $newImage = substr($request->oldImage, 8);
                $image_path = storage_path().'/app/public/'."".$newImage;
                if (@getimagesize($image_path)){
                    unlink($image_path);
                }
            }
            $errorZendy->image = $request->image;
        }
        if($request->file){
            $errorZendy->file = $request->file;
        }
        $errorZendy->status = "Pendiente";
        $errorZendy->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $uploadImageController->updateFile($request->file('image'), "error/".$errorZendy->id, "image_".Carbon::now()->timestamp);
            $errorZendy->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $uploadImageController->updateFile($request->file('file'), "error/".$errorZendy->id, "file_".Carbon::now()->timestamp);
            $errorZendy->file = $path;
            $fileSaved = true;
        }

        if($fileSaved){
            $errorZendy->save();
        }

        return response()->json($errorZendy);
    }

    public function list(Request $request) {
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $errors = Error::whereIn("status", ["Pendiente", "Aceptado"])->where("fake", false)->where("deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $errors->where('reason', 'LIKE', '%' . $term . '%');
        }

        $errors = $errors->orderByDesc("created_at")->get();

        foreach ($errors as $error) {
            if($error->idCompany){
                $error->company = Company::find($error->idCompany);
            }
        }
        return  $errors;
    }

    public function listByUser(Request $request) {
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $errors = Error::where("createdBy", $user->id)->where("idCompany", $user->idCompany)->where("deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $errors->where('reason', 'LIKE', '%' . $term . '%');
        }

        $errors = $errors->orderByDesc("created_at")->get();

        return $errors;
    }

    public function find($id) {
        $error = Error::find($id);

        if(!$error) return response()->json(['error' => 'Error reportado no encontrado'], 400);

        $error->user = User::find($error->createdBy);
        if($error->idCompany)
            $error->company = Company::find($error->idCompany);
        if($error->idModule)
            $error->module = Module::find($error->idModule);

        $Notification = Notification::where("idError", $error->id)->get();
        $error->notifications = $Notification;

        return response()->json(compact('error'),201);
    }

    public function confirmError($id) {
        $error = Error::find($id);

        if (!$error) {
            return response()->json(['error' => 'Error reportado no encontrado'], 400);
        }

        $error->status = "Aceptado";
        $error->received = true;
        $error->save();

        $error->user = User::find($error->createdBy);
        if($error->idCompany)
            $error->company = Company::find($error->idCompany);

        return response()->json(compact('error'),201);
    }

    public function errorSolved($id) {
        $error = Error::find($id);

        if (!$error) {
            return response()->json(['error' => 'Error reportado no encontrado'], 400);
        }

        $error->status = "Resuelto";
        $error->fixed = true;
        $error->save();

        return response()->json(compact('error'),201);
    }

    public function fakeError($id) {
        $error = Error::find($id);

        if (!$error) {
            return response()->json(['error' => 'Error reportado no encontrado'], 400);
        }

        $error->status = "Falso";
        $error->fake = true;
        $error->save();

        return response()->json(['success' => 'El error Reportado fue considerado como fake'], 201);
    }

    public function delete($id) {
        $error = Error::find($id);

        if(!$error)
            return response()->json(['error' => 'Error reportado no encontrado'], 400);

        $error->deleted = true;
        $error->save();

        return response()->json(['success' => 'Error reportado eliminado'], 201);
    }

    public function deleteImage(Request $request){
        $imageLink = $request->imageLink;
        $errorId = $request->id;

        $error = Error::find($errorId);
        $image_path = storage_path().'/app/public/'."".$imageLink;
        if (@getimagesize($image_path) && $error){
            unlink($image_path);
            $error->image = null;
            $error->save();

            return response()->json(compact('error'),201);
        }else{
            return response()->json(['error' => 'Error reportado no encontrado / Archivo no encontrado'], 400);
        }
    }
}
