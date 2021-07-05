<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{

    public function register(Request $request){
        $chat = new Chat();

        $this->updateChatValues($chat, $request);
        $chat->save();

        return response()->json(compact('chat'),201);
    }

    public function update(Request $request, $id){
        $chat = Chat::find($id);

        if(!$chat){
            return response()->json(['error' => 'Chat no encontrado'], 400);
        }

        $this->updateChatValues($chat, $request);
        $chat->save();

        return response()->json($chat);
    }

    private function updateChatValues($chat, $request){
        $chat->codeChat = $request->codeChat;
        $chat->startDate = date('Y-m-d',strtotime($request->startDate));
        $chat->endDate = date('Y-m-d',strtotime($request->endDate));
        $chat->participants = array($request->participants);
        $chat->typeChat = $request->typeChat;
        $chat->state = $request->state;
        $chat->idCompany = $request->idCompany;
        $chat->messagesNumbers = $request->messagesNumbers;
        $chat->lastMessage = new($request->lastMessage);
        $chat->recommendationsNumber = $request->recommendationsNumber;
        $chat->idNotification = $request->idNotification;
        $chat->idError = $request->idError;
    }

    public function find($id){
        $chat = Chat::find($id);

        if(!$chat){
            return response()->json(['error' => 'Chat no encontrado'], 400);
        }

        return response()->json(compact('chat'),201);
    }

    public function list(){
        return Chat::where('accepted', '!=', true)->orderBy("codeChat")->get();
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
