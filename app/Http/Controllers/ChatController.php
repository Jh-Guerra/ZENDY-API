<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{

    public function find($id){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat = Chat::find($id);
        if(!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->user = User::find($chat->idUser);
        if($chat->idCompany)
            $chat->company = Company::find($chat->idCompany);

        $participants =  Participant::where("idChat", $chat->id)->where("deleted", false)->get();
        foreach($participants as $participant){
            $userData = User::find($participant->idUser);
            $participant->user = $userData;
        }

        if(!$chat->name){
            $chatName = "";
            if($chat->scope == "Personal"){
                foreach($participants as $participant){
                    if($participant->idUser != $user->id && $participant->user){
                        $chatName = $chatName.$participant->user->firstName. ' ' .$participant->user->lastName;
                    }
                }
            }

            if($chat->scope == "Grupal"){
                foreach($participants as $participant){
                    if($participant->idUser != $user->id && $participant->user){
                        $chatName = $chatName.$participant->user->firstName. ' ' .$participant->user->lastName.", ";
                    }
                }
                $chatName = substr($chatName, 0, -2);
            }
            $chat->name = $chatName;
        }

        $chat->participants = $participants;

        return response()->json(compact('chat'),201);
    }

    public function listActive(Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chatIds = Participant::where("idUser", $user->id)->where("deleted", false)->pluck("idChat");
        $chats = Chat::whereIn("id", $chatIds)->get();
        $participants = Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->get();
        $userIds =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->pluck("idUser")->unique();

        $companyIds = [];
        foreach ($chats as $chat){
            if($chat->idCompany && !in_array($chat->idCompany, $companyIds))
                $companyIds[] = $chat->idCompany;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');
        $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');

        $participantsByChat = new \stdClass();
        $participantsByChatNames = new \stdClass();
        foreach ($participants as $participant) {
            $participant->user = $users[$participant->idUser];

            $idChat = $participant->idChat;
            $currentChatParticipants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
            array_push($currentChatParticipants, $participant);
            $participantsByChat->$idChat = $currentChatParticipants;

            $currentParticipantsName = property_exists($participantsByChatNames, $idChat) ? $participantsByChatNames->$idChat : "";
            $participantsByChatNames->$idChat = $currentParticipantsName.$participant->user->firstName." ".$participant->user->lastName.", ";
        }

        foreach ($chats as $chat){
            $chat->user = $chat->idUser ? ($chat->idUser == $user->id ? $user : $users[$chat->idUser]) : null;
            $chat->company = $chat->idCompany ? $companies[$chat->idCompany] : null;

            if(!$chat->name)
                $chat->name = substr($participantsByChatNames->{$chat->id}, 0, -2);

            $chat->participants = $participantsByChat->{$chat->id} ? $participantsByChat->{$chat->id} : [];
        }

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $chats = $chats->filter(function ($chat) use ($term) {
                $filter = "";
                if($chat->receiver){
                    $filter = strtolower($chat->receiver->firstName." ".$chat->receiver->lastName);
                }
                return str_contains(strtolower($filter), strtolower($term)) !== false;
            });
        }

        return $chats->values()->all();
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
