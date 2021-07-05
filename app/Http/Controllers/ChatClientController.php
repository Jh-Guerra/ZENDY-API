<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatClientController extends Controller
{
    public function register(Request $request){
        $chat = new Chat();
        $users = json_decode($request->getContent(), true);
        $user = Auth::user();

        $this->updateChatValues($chat, $user, $users);
        $chat->save();

        return response()->json(compact('chat'),201);
    }

    private function updateChatValues($chat, $user, $users){
        $client = ($users && $users[0]) ? $users[0] : null;

        if(!$user){
            return response()->json(['Usuario' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);
        }

        if(!$client){
            return response()->json(['Cliente' => 'Necesita seleccionar al menos un cliente'], 400);
        }

        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
//        $chat->participants = array($user->id, $client->id);
//        $chat->adminParticipants = array($user->id);
        $chat->type = "Client";
        $chat->state = "Vigente";
        $chat->idCompany = $user->idCompany;
        $chat->messages = 0;
        $chat->recommendations = 0;
    }
}
