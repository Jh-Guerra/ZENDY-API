<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $this->updateChatValues($chat, $user, $users);
        $chat->save();
        $participants = [];

        $userRequest = [
            'idUser' => $user->id,
            'idChat' => $chat->id,
            'type' => "admin",
            'erp' => true,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'state' => "active",
            'active' => true
        ];

        $clientRequest = [
            'idUser' => $client["id"],
            'idChat' => $chat->id,
            'type' => "client",
            'erp' => false,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'state' => "active",
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
            return response()->json(['Usuario' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);
        }

        if(!$client){
            return response()->json(['Cliente' => 'Necesita seleccionar al menos un cliente'], 400);
        }

        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Client";
        $chat->state = "Vigente";
        $chat->idCompany = $user->idCompany;
        $chat->idUser = $user->id;
        $chat->messages = 0;
        $chat->recommendations = 0;
    }
}
