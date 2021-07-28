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
            return response()->json(['error' => 'Ya tiene una conversación iniciada con este usuario.'], 400);
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

        foreach ($participants as $i => $participant) {
            $request = new Request();
            $request->replace($participant);
            $this->participantController->register($request);
        }

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

    public function list(){
        $user = Auth::user();

        $activeChats = Chat::where("idUser", $user->id)
            ->where("type", "Cliente")->where("status", "Vigente")
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

        return $activeChats;
    }

}
