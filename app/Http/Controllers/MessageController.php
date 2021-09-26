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

    public function register(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $message = new Message();

        $message->createdBy = $user->id;
        $message->idChat = $request["idChat"];
        $message->message = $request["message"];
        $message->viewed = 0;
        $message->resend = $request["resend"];
        $message->originalUserId = $user->id;
        $message->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if ($request->hasFile('image')) {
            $path = $uploadImageController->updateFile($request->file('image'), "messages/" . $message->idChat, "image_" . Carbon::now()->timestamp);
            $message->image = $path;
            $fileSaved = true;
        }

        if ($request->hasFile('file')) {
            $path = $uploadImageController->updateFile($request->file('file'), "messages/" . $message->idChat, "file_" . Carbon::now()->timestamp);
            $message->file = $path;
            $fileSaved = true;
        }

        if ($fileSaved) $message->save();

        return response()->json(compact('message'), 201);

    }

    public function find($id){
        $message = Message::find($id);

        if(!$message) return response()->json(['error' => 'Mensaje no encontrado'], 400);

        return response()->json(compact('message'),201);
    }

    public function list($idChat){
        return Message::join('users', 'users.id', 'messages.createdBy')->where("messages.idChat", $idChat)->where("messages.deleted", false)
            ->get(["messages.*", "users.firstName as userFirstName", "users.lastName as userLastName", "users.id as userId", "users.avatar as userAvatar"]);
    }

    public function delete($id){
        $message = Message::find($id);

        if(!$message) return response()->json(['error' => 'Mensaje no encontrado'], 400);

        $message->deleted = true;
        $message->save();

        return response()->json(['success' => 'Mensaje Eliminado'], 201);
    }
}
