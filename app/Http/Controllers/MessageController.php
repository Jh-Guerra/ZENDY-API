<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Chat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{

    public function register(Request $request){
        //$content = json_decode($request->getContent(), true);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $message = new Message();

        $message->idChat = $request["idChat"];
        $message->message = $request["message"];
        $message->resend = $request["resend"];
        $message->image = $request["image"];
        $message->file = $request["file"];

        $message->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')) {
            $path = $uploadImageController->updateFile($request->file('image'), "messages/".$message->idChat, "image_".Carbon::now()->timestamp);
            $message->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')) {
            $path = $uploadImageController->updateFile($request->file('file'), "messages/".$message->idChat, "file_".Carbon::now()->timestamp);
            $message->file = $path;
            $fileSaved = true;
        }

        if($fileSaved){
            $message->save();
        }

        return response()->json(compact('message'),201);

    }

    public function update(Request $request, $id)
    {
        $message = Message::find($id);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        if(!$message){
            return response()->json(['error' => 'Mensaje no encontrado'], 400);
        }

        //$this->updateMessageValues($message, $request);
        $message->save();

        return response()->json($message);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'message' => 'required',
        ]);

        $errorMessage = null;
        if($validator->fails()){
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    public function delete($id){
        $message = Message::find($id);

        if(!$message){
            return response()->json(['error' => 'Mensaje no encontrado'], 400);
        }

        $message->deleted = true;
        $message->save();

        return response()->json(['success' => 'Mensaje Eliminado'], 201);
    }

    public function list($idChat){

        $messages = Message::where("idChat", $idChat)->where("deleted", false)->get();

        return $messages;
    }

    public function find($id){
        $message = Message::find($id);

        if(!$message){
            return response()->json(['error' => 'Mensaje no encontrado'], 400);
        }

        return response()->json(compact('message'),201);
    }

}
