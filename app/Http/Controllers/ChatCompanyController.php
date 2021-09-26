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

class ChatCompanyController extends Controller
{
    protected $participantController;
    public function __construct()
    {
        $this->participantController = app('App\Http\Controllers\ParticipantController');
    }

    public function register(Request $request){
        $data = json_decode($request->getContent(), true);
        $userIds = $data["userIds"];
        $companyId = $data["companyId"];
        $allChecked = $data["allChecked"] || false;

        if(!$userIds) return response()->json(['error' => 'Ningún usuario seleccionado'], 400);
        if(!$companyId) return response()->json(['error' => 'Ninguna empresa seleccionada'], 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        if($allChecked){
            $activeChat = Chat::where("idUser", $user->id)->where("idCompany", $companyId)
                ->where("allUsers", true)
                ->where("type", "Empresa")->where("status", "Vigente")
                ->where("deleted", false)->first();

            if($activeChat){
                $chat = $activeChat;
                return response()->json(compact('chat'),201);
            }
        }

        $chat = new Chat();
        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Empresa";
        $chat->status = "Vigente";
        $chat->idCompany = $user->idCompany;
        $chat->idUser = $user->id;
        $chat->allUsers = $allChecked;
        $chat->messages = 0;
        $chat->recommendations = 0;
        $chat->scope = "Grupal";
        $chat->save();

        $participants = [];

        $userRequest = [
            'idUser' => $user->id,
            'idChat' => $chat->id,
            'type' => "Admin",
            'erp' => true,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'status' => "Activo",
            'active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
        array_push($participants, $userRequest);

        foreach ($userIds as $userId) {
            $clientRequest = [
                'idUser' => $userId,
                'idChat' => $chat->id,
                'type' => "Participante",
                'erp' => false,
                'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
                'status' => "Activo",
                'active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($participants, $clientRequest);
        }

        $this->participantController->registerMany($participants);

        return response()->json(compact('chat'),201);
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
