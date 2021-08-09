<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{

    public function find($id){
        $chat = Chat::find($id);

        if(!$chat){
            return response()->json(['error' => 'Chat no encontrado'], 400);
        }

        return response()->json(compact('chat'),201);
    }

    public function list(Request $request){
        $user = Auth::user();

        $status = $request->has("status") ? $request->get("status") : "Vigente";

        $activeChats = Chat::where("idUser", $user->id)->where("status", $status)
            ->where("deleted", false)->get();

        $userIds = [];
        foreach ($activeChats as $chat){
            if($chat->idUser && !in_array($chat->idUser, $userIds))
                $userIds[] = $chat->idUser;

            if($chat->idReceiver && !in_array($chat->idReceiver, $userIds))
                $userIds[] = $chat->idReceiver;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');

        foreach ($activeChats as $chat){
            $chat->user = $chat->idUser ? $users[$chat->idUser] : null;
            $chat->receiver = $chat->idReceiver ? $users[$chat->idReceiver] : null;
        }

        $term = $request->has("term") ? $request->get("term") : "";

        return $this->searchChat($activeChats, $term);
    }

    public function searchChat($activeChats, $term){
        if($term){
            $activeChats = $activeChats->filter(function ($chat) use ($term) {
                return str_contains(strtolower($chat->receiver->firstName." ".$chat->receiver->lastName), strtolower($term)) !== false;
            });
        }

        return $activeChats->values()->all();
    }

    public function delete($id){
        $chat = Chat::find($id);

        if(!$chat){
            return response()->json(['error' => 'Chat no encontrado'], 400);
        }

        $chat->deleted = true;
        $chat->save();

        return response()->json(['success' => 'Chat Eliminado'], 201);
    }
}
