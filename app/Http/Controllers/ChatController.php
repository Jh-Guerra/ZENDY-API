<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
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

        $users = User::whereIn("id", $chat->get("idUser"))->get()->keyBy('id');
        $receiver = User::whereIn("id", $chat->get("idReceiver"))->get()->keyBy('id');
        $companies = Company::whereIn("id", $chat->get("allUsers"))->get()->keyBy('id');

        $chat->user = $chat->idUser ? $users[$chat->idUser] : null;
        $chat->receiver = $chat->idReceiver ? $receiver[$chat->idReceiver] : null;
        $chat->company = $chat->idCompany ? $companies[$chat->idCompany] : null;

        return response()->json(compact('chat'),201);
    }

    public function list(Request $request){
        $user = Auth::user();

        $status = $request->has("status") ? $request->get("status") : "Vigente";

        $activeChats = Chat::where("status", $status)
            ->where(function($query) use ($user) {
                $query->where('idUser', $user->id)
                    ->orWhere('idReceiver', $user->id);
            })
            ->where("deleted", false)->get();

        $userIds = [];
        $companyIds = [];
        foreach ($activeChats as $chat){
            if($chat->idUser && !in_array($chat->idUser, $userIds))
                $userIds[] = $chat->idUser;

            if($chat->idReceiver && !in_array($chat->idReceiver, $userIds))
                $userIds[] = $chat->idReceiver;

            if($chat->idCompany && !in_array($chat->idCompany, $userIds))
                $companyIds[] = $chat->idCompany;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');
        $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');

        foreach ($activeChats as $chat){
            $chat->user = $chat->idUser ? $users[$chat->idUser] : null;
            $chat->receiver = $chat->idReceiver ? $users[$chat->idReceiver] : null;
            $chat->company = $chat->idCompany ? $companies[$chat->idCompany] : null;
        }

        $term = $request->has("term") ? $request->get("term") : "";

        return $this->searchChat($activeChats, $term);
    }

    public function searchChat($activeChats, $term){
        if($term){
            $activeChats = $activeChats->filter(function ($chat) use ($term) {
                $filter = "";
                if($chat->receiver){
                    $filter = strtolower($chat->receiver->firstName." ".$chat->receiver->lastName);
                }
                return str_contains(strtolower($filter), strtolower($term)) !== false;
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
