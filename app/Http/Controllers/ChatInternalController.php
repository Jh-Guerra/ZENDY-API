<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Participant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;

class ChatInternalController extends Controller
{
    public function register(Request $request){
        $participantController = new ParticipantController();

        $chat = new Chat();
        $users = json_decode($request->getContent(), true);
        if (!$users || count($users) == 0) return response()->json(['error' => 'Necesita seleccionar al menos un cliente'], 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Interno";
        $chat->status = "Vigente";
        $chat->idCompany = $user->idCompany;
        $chat->idUser = $user->id;
        $chat->messages = 0;
        $chat->recommendations = 0;
        $chat->scope = count($users) > 1 ? "Grupal" : "Personal";

        if(count($users) == 1){
            $chatIds = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->pluck("idChat");
            $otherChats =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->get(["id", "idUser", "idChat"])->keyBy("idChat");
            $chats = Chat::whereIn("id", $chatIds)->get();
            $receiver = $users[0];

            foreach ($chats as $c) {
                $otherChat = array_key_exists($c->id, $otherChats->toArray()) ? $otherChats[$c->id] : null;
                if($c->status == "Vigente" && $c->scope == "Personal" && $otherChat != null && $otherChat["idUser"] == $receiver["id"]){
                    $chat = $c;
                    return response()->json(compact('chat'),201);
                }
            }
        }

        $chat->save();
        $participants = [];

        $adminRequest = [
            'idUser' => $user->id,
            'idChat' => $chat->id,
            'type' => "Admin",
            'erp' => true,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'status' => "Activo",
            'active' => true
        ];
        array_push($participants, $adminRequest);

        foreach ($users as $u) {
            $new = [
                'idUser' => $u["id"],
                'idChat' => $chat->id,
                'type' => "Participante",
                'erp' => false,
                'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
                'status' => "Activo",
                'active' => true
            ];

            array_push($participants, $new);
        }

        $participantController->registerMany($participants);

        return response()->json(compact('chat'),201);
    }

    public function list(Request $request){
        $user = Auth::user();

        $activeChats = Chat::where("idUser", $user->id)
            ->where("type", "Interno")->where("status", "Vigente")
            ->where("deleted", false)->get();

        $userIds = [];
        foreach ($activeChats as $chat){
            if(!in_array($chat->idUser, $userIds))
                $userIds[] = $chat->idUser;

            if(!in_array($chat->idReceiver, $userIds))
                $userIds[] = $chat->idReceiver;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');

        foreach ($activeChats as $chat){
            $chat->user = $users[$chat->idUser];
            $chat->receiver = $users[$chat->idReceiver];
        }

        $term = $request->has("term") ? $request->get("term") : "";

        return $this->searchChatClient($activeChats, $term);
    }

    public function searchChatClient($activeChats, $term){
        if($term){
            $activeChats = $activeChats->filter(function ($chat) use ($term) {
                return str_contains(strtolower($chat->receiver->firstName." ".$chat->receiver->lastName), strtolower($term)) !== false;
            });
        }

        return $activeChats->values()->all();
    }

}
