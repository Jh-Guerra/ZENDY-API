<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{

    public function find($id){
        $user = Auth::user();
        $chat = Chat::find($id);
        $Participants =  Participant::where("idChat", $chat->id)->get();

        if(!$chat){
            return response()->json(['error' => 'Chat no encontrado'], 400);
        }

        $chat->user = User::find($chat->idUser);
        if($chat->idCompany)
            $chat->company = Company::find($chat->idCompany);

        $ParticipantsData = array();
        foreach($Participants as $Participant){
            $userData = User::find($Participant->idUser);
            $userData['typeParticipant'] = $Participant->type;
            array_push($ParticipantsData,$userData);
        }

        $chatName = "";
        foreach($ParticipantsData as $Participant){
            if($Participant->id != $user->id){
                $chatName = $chatName. '' .$Participant->firstName. ' ' .$Participant->lastName.', ';
            }
        }

        if(!$chat->name){
            $chat->name = substr($chatName, 0, -2);
        }else{
            $chat->name = $chat->name;
        }
        $chat->participants = $ParticipantsData;

        return response()->json(compact('chat'),201);
    }

    public function list(Request $request){
        $user = Auth::user();

        $status = $request->has("status") ? $request->get("status") : "Vigente";

        $listOfParticipations =  Participant::where("idUser", $user->id)->where("deleted", false)->pluck("idChat");
        $activeChats = Chat::find($listOfParticipations)->where("status", $status);
        $listParticipants = Participant::wherein("idChat", $listOfParticipations)->get();
        $userIds =  Participant::wherein("idChat", $listOfParticipations)->pluck("idUser")->unique();

        $companyIds = [];
        foreach ($activeChats as $chat){
            if($chat->idCompany && !in_array($chat->idCompany, $companyIds))
                $companyIds[] = $chat->idCompany;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');
        $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');

        foreach ($activeChats as $chat){
            $chat->user = $chat->idUser ? $users[$chat->idUser] : null;

            $chatParticipants = [];
            $chatName = "";
            foreach ($listParticipants as $Participant){
                if($Participant->idChat == $chat->id){
                    $chatParticipants[] = $users[$Participant->idUser];
                    if($Participant->idUser != $user->id){
                        $chatName = $chatName. '' .($users[$Participant->idUser])->firstName. ' ' .($users[$Participant->idUser])->lastName.', ';
                    }
                }
            }
            $chat->participants = $chatParticipants;
            if(!$chat->name){
                $chat->name = substr($chatName, 0, -2);
            }else{
                $chat->name = $chat->name;
            }
            $chat->company = $chat->idCompany ? $companies[$chat->idCompany] : null;
        }

        $term = $request->has("term") ? $request->get("term") : "";

        return $this->searchChat($activeChats, $term);
    }

    public function searchChat($activeChats, $term){
        if($term){
            $activeChats = $activeChats->filter(function ($chat) use ($term) {
                $filter = "";
                if($chat->receiver){
                    $filter = strtolower($chat->receiver->firstName." ".$chat->receiver->lastName);
                }
                return str_contains(strtolower($filter), strtolower($term)) !== false;
            });
        }

        return $activeChats->values()->all();
    }

    public function delete($id){
        $chat = Chat::find($id);

        if(!$chat){
            return response()->json(['error' => 'Chat no encontrado'], 400);
        }

        $chat->deleted = true;
        $chat->save();

        return response()->json(['success' => 'Chat Eliminado'], 201);
    }
}
