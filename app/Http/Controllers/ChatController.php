<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\Message;
use App\Models\Participant;
use App\Models\User;
use Carbon\Carbon;
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
                        $chat->companyUser = Company::find($participant->user->idCompany);
                    }
                }
            }

            if($chat->scope == "Grupal"){
                foreach($participants as $participant){
                    if($participant->idUser != $user->id && $participant->user){
                        $chatName = $chatName.$participant->user->firstName. ' ' .$participant->user->lastName.", ";
                    }
                }
                $chatName = $chatName."Tú";
            }
            $chat->name = $chatName;
        }

        $chat->participants = $participants;

        return response()->json(compact('chat'),201);
    }

    public function listActive(Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chatIds = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->pluck("idChat");
        $participations = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->get()->keyBy("idChat");
        $chats = Chat::whereIn("id", $chatIds)->where("deleted", false)->where("status",$request->status)->orderBy("updated_at", "desc")->get();
        $participants = Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->where("deleted", false)->get();
        $userIds =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->pluck("idUser")->unique();

        $companyIds = [];
        $lastMessageIds = [];
        $lastUserIds = [];
        foreach ($chats as $chat){
            if($chat->idCompany && !in_array($chat->idCompany, $companyIds))
                $companyIds[] = $chat->idCompany;

            if($chat->lastMessageUserId && !in_array($chat->lastMessageUserId, $lastUserIds))
                $lastUserIds[] = $chat->lastMessageUserId;

            if($chat->lastMessageId && !in_array($chat->lastMessageId, $lastMessageIds))
                $lastMessageIds[] = $chat->lastMessageId;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');
        $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');
        $lastMessages = Message::whereIn("id", $lastMessageIds)->get()->keyBy('id');
        $lastUsers = User::whereIn("id", $lastUserIds)->get()->keyBy('id');

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
            $chat->lastMessage = $chat->lastMessageId ? $lastMessages[$chat->lastMessageId] : null;
            $chat->lastMessageUser = $chat->lastMessageUserId ? ($chat->lastMessageUserId == $user->id ? $user : $lastUsers[$chat->lastMessageUserId]) : null;
            $chat->participation = $participations[$chat->id];
            $idChat = $chat->id;

            if(!$chat->name)
                $chat->name = property_exists($participantsByChatNames, $idChat) ? substr($participantsByChatNames->$idChat, 0, -2) : ($user->firstName." ".$user->lastName);

            $chat->participants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
        }

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $chats = $chats->filter(function ($chat) use ($term) {
                $filter = "";
                if($chat->name){
                    $filter = strtolower($chat->name);
                }
                return str_contains(strtolower($filter), strtolower($term)) !== false;
            });
        }

        return $chats->values()->all();
    }

    public function listAvailableUsersByCompany(Request $request){
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $roles = $request->has("roles") ? $request->get("roles") : [];
        $term = $request->has("term") ? $request->get("term") : "";


        $users = User::join('roles', 'roles.id', '=', 'users.idRole')->join("companies", "companies.id", "=", "users.idCompany")
            ->where("users.idCompany", $user->idCompany)->where('users.deleted', false)
            ->where('users.id', '!=', $user->id)->whereIn('roles.name', $roles);

        if ($term) {
            $users->where(function ($query) use ($term) {
                $query->where('firstName', 'LIKE', '%' . $term . '%')
                    ->orWhere('lastName', 'LIKE', '%' . $term . '%');
            });
        }

        return $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName', 'companies.name as companyName']);
    }

    public function delete($id){
        $chat = Chat::find($id);

        if(!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->deleted = true;
        $chat->save();

        return response()->json(['success' => 'Chat Eliminado'], 201);
    }

    public function finalize($id, Request $request){
        $request = json_decode($request->getContent(), true);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat = Chat::find($id);
        if(!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->status = "Finalizado";
        $chat->finalizeDate = Carbon::now()->timestamp;
        $chat->finalizeStatus = $request["finalizeStatus"];
        $chat->finalizeDescription = $request["finalizeDescription"];
        $chat->finalizeUser = $user->id;
        $chat->save();

        return response()->json(compact('chat'),201);
    }

    public function nameChat($id, Request $request){

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat = Chat::find($id);
        if(!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->name = $request->name;
        $chat->save();

        return response()->json(compact('chat'),201);
    }

    public function listFinalize(Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $from= $request->has("fromDate") ? $request->get("fromDate") : "";
        $fromDate1 = (int)$from;
        $to = $request->has("toDate") ? $request->get("toDate") : "";
        $fromTo1 = (int)$to;
        
        $chatIds = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->pluck("idChat");
        $participations = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->get()->keyBy("idChat");
        
        $chats = Chat::whereIn("id", $chatIds)->where("deleted", false)->where("status",$request->status)->whereBetween('finalizeDate', [$fromDate1 ,$to])->orderBy("updated_at", "desc")->get();
        $participants = Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->where("deleted", false)->get();
        $userIds =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->pluck("idUser")->unique();

        $companyIds = [];
        $lastMessageIds = [];
        $lastUserIds = [];
        foreach ($chats as $chat){
            if($chat->idCompany && !in_array($chat->idCompany, $companyIds))
                $companyIds[] = $chat->idCompany;

            if($chat->lastMessageUserId && !in_array($chat->lastMessageUserId, $lastUserIds))
                $lastUserIds[] = $chat->lastMessageUserId;

            if($chat->lastMessageId && !in_array($chat->lastMessageId, $lastMessageIds))
                $lastMessageIds[] = $chat->lastMessageId;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');
        $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');
        $lastMessages = Message::whereIn("id", $lastMessageIds)->get()->keyBy('id');
        $lastUsers = User::whereIn("id", $lastUserIds)->get()->keyBy('id');

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
            $chat->lastMessage = $chat->lastMessageId ? $lastMessages[$chat->lastMessageId] : null;
            $chat->lastMessageUser = $chat->lastMessageUserId ? ($chat->lastMessageUserId == $user->id ? $user : $lastUsers[$chat->lastMessageUserId]) : null;
            $chat->participation = $participations[$chat->id];
            $idChat = $chat->id;

            if(!$chat->name)
                $chat->name = property_exists($participantsByChatNames, $idChat) ? substr($participantsByChatNames->$idChat, 0, -2) : ($user->firstName." ".$user->lastName);

            $chat->participants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
        }

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $chats = $chats->filter(function ($chat) use ($term) {
                $filter = "";
                if($chat->name){
                    $filter = strtolower($chat->name);
                }
                return str_contains(strtolower($filter), strtolower($term)) !== false;
            });
        }

        return $chats->values()->all();
    }
}
