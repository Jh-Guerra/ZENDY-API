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

class ChatClientController extends Controller
{
    public function register(Request $request){
        $users = json_decode($request->getContent(), true);
        if(count($users) == 0) return response()->json(['error' => 'Necesita seleccionar al menos un usuario'], 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat = new Chat();
        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Cliente";
        $chat->status = "Vigente";
        $chat->idCompany = $user->idCompany;
        $chat->idUser = $user->id;
        $chat->messages = 0;
        $chat->scope = count($users) > 1 ? "Grupal" : "Personal";

        if($chat->scope == "Personal"){
            $chatIds = Participant::where("idUser", $user->id)->where("idCompany", $user->id)->where("status", "Activo")->where("deleted", false)->pluck("idChat");
            $chats = Chat::whereIn("id", $chatIds)->where("status", "Vigente")->where("scope", "Personal")->get();
            $otherParticipantsByChat =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->get(["id", "idUser", "idChat"])->keyBy("idChat");

            $receiver = $users[0];
            foreach ($chats as $c) {
                $otherParticipant = array_key_exists($c->id, $otherParticipantsByChat->toArray()) ? $otherParticipantsByChat[$c->id] : null;
                if($otherParticipant && $receiver && $otherParticipant["idUser"] == $receiver["id"]){
                    $chat = $c;
                    return response()->json(compact('chat'),201);
                }
            }
        }

        $chat->save();

        $participantController = new ParticipantController();
        $participants = [];
        $admin = [
            'idUser' => $user->id,
            'idChat' => $chat->id,
            'type' => "Admin",
            'erp' => false,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'status' => "Activo",
            'active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
        array_push($participants, $admin);

        foreach ($users as $u) {
            $new = [
                'idUser' => $u["id"],
                'idChat' => $chat->id,
                'type' => "Participante",
                'erp' => false,
                'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
                'status' => "Activo",
                'active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($participants, $new);
        }

        $participantController->registerMany($participants);

        return response()->json(compact('chat'),201);
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
