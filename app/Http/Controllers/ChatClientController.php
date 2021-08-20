<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;

class ChatClientController extends Controller
{
    protected $participantController;
    public function __construct()
    {
        $this->participantController = app('App\Http\Controllers\ParticipantController');
    }

    public function register(Request $request){
        $chat = new Chat();
        $users = json_decode($request->getContent(), true);
        $client = ($users && $users[0]) ? $users[0] : null;
        $user = Auth::user();

        $this->updateChatValues($chat, $user, $client);

        $activeChat = Chat::where("idUser", $user->id)->where("idReceiver", $client["id"])
            ->where("type", "Cliente")->where("status", "Vigente")
            ->where("deleted", false)->first();

        if($activeChat){
            return response()->json(compact('activeChat'),201);
        }

        $chat->save();
        $participants = [];

        $userRequest = [
            'idUser' => $user->id,
            'idChat' => $chat->id,
            'type' => "Admin",
            'erp' => true,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'status' => "active",
            'active' => true
        ];

        $clientRequest = [
            'idUser' => $client["id"],
            'idChat' => $chat->id,
            'type' => "Cliente",
            'erp' => false,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'status' => "active",
            'active' => true
        ];

        array_push($participants, $userRequest);
        array_push($participants, $clientRequest);

        $this->participantController->registerMany($participants);

        return response()->json(compact('chat'),201);
    }

    private function updateChatValues($chat, $user, $client){
        if(!$user){
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);
        }

        if(!$client){
            return response()->json(['error' => 'Necesita seleccionar al menos un cliente'], 400);
        }

        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Cliente";
        $chat->status = "Vigente";
        $chat->idCompany = $user->idCompany;
        $chat->idUser = $user->id;
        $chat->idReceiver = $client["id"];
        $chat->messages = 0;
        $chat->recommendations = 0;
    }

    public function list(Request $request){
        $user = Auth::user();

        $activeChats = Chat::where("idUser", $user->id)
            ->where("type", "Cliente")->where("status", "Vigente")
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
