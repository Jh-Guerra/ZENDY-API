<?php

namespace App\Http\Controllers;

use App\Events\notificationMessage;
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
    public function register(Request $request){
        $data = json_decode($request->getContent(), true);
        $users = $data["users"];
        $company = $data["company"];
        $allChecked = $data["allChecked"] || false;

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        if(!$users || count($users) == 0) return response()->json(['error' => 'Ningún usuario seleccionado'], 400);
        if(!$company) return response()->json(['error' => 'Ninguna empresa seleccionada'], 400);

        $chat = new Chat();
        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Empresa";
        $chat->status = "Vigente";
        $chat->idCompany = $company["id"];
        $chat->idUser = $user->id;
        $chat->allUsers = $allChecked;
        $chat->messages = 0;
        $chat->scope = count($users) > 1 ? "Grupal" : "Personal";
        $chat->name = $allChecked ? $company["name"] : null;
        $chat->isQuery = false;

        if($allChecked){
            $activeChat = Chat::where("idUser", $user->id)->where("idCompany", $company["id"])
                ->where("allUsers", true)
                ->where("type", "Empresa")->where("status", "Vigente")
                ->where("deleted", false)->first();

            if($activeChat){
                $chat = $activeChat;
                return response()->json(compact('chat'),201);
            }
        }

        $chat->save();

        $participantController = new ParticipantController();
        $participants = [];
        $admin = [
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
        array_push($participants, $admin);

        foreach ($users as $u) {
            $clientRequest = [
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
            array_push($participants, $clientRequest);
        }

        $participantController->registerMany($participants);
        foreach ($participants as $participant) {
            event(new notificationMessage($participant["idUser"],null));
        }

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
