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
        $chat = new Chat();
        $data = json_decode($request->getContent(), true);
        $userIds = $data["userIds"];
        $companyId = $data["companyId"];
        $allChecked = $data["allChecked"] || false;

        if(!$userIds){
            return response()->json(['error' => 'Ningún usuario seleccionado'], 400);
        }

        if(!$companyId){
            return response()->json(['error' => 'Ninguna empresa seleccionada'], 400);
        }

        $user = Auth::user();
        if(!$user){
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);
        }

        if($allChecked){
            $activeChat = Chat::where("idUser", $user->id)->where("idCompany", $companyId)
                ->where("allUsers", true)
                ->where("type", "Empresa")->where("status", "Vigente")
                ->where("deleted", false)->first();

            if($activeChat){
                return response()->json(['error' => 'Ya tiene una conversación iniciada con toda la compañía seleccionada'], 400);
            }
        }

        $this->updateChatValues($chat, $user, $allChecked);
        $chat->save();

        $participants = [];

        $userRequest = [
            'idUser' => $user->id,
            'idChat' => $chat->id,
            'type' => "Admin",
            'erp' => true,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'status' => "active",
            'active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
        array_push($participants, $userRequest);

        $clientUsers = User::whereIn('id', $userIds)->get();
        foreach ($clientUsers as $client) {
            $clientRequest = [
                'idUser' => $client->id,
                'idChat' => $chat->id,
                'type' => "Cliente",
                'erp' => false,
                'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
                'status' => "active",
                'active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($participants, $clientRequest);
        }

        $this->participantController->registerMany($participants);

        return response()->json(compact('chat'),201);
    }

    private function updateChatValues($chat, $user, $allChecked){
        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Empresa";
        $chat->status = "Vigente";
        $chat->idCompany = $user->idCompany;
        $chat->idUser = $user->id;
        $chat->allUsers = $allChecked;
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
