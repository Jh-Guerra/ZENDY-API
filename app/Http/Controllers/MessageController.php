<?php

namespace App\Http\Controllers;

use App\Events\notificationMessage;
use App\Events\sendMessage;
use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Chat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{

    public function register(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesi贸n.'], 400);

        $message = new Message();

        $message->createdBy = $user->id;
        $message->createdDate = Carbon::now()->timestamp;
        $message->idChat = $request["idChat"];
        $message->message = $request["message"];
        $message->resend = $request["resend"];
        $message->originalUserId = $user->id;
        $message->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if ($request->hasFile('image')) {
            $path = $uploadImageController->updateFile($request->file('image'), "messages/" . $message->idChat, "image_" . Carbon::now()->timestamp);
            $message->image = $path;
            $fileSaved = true;
        }

        if ($request->hasFile('file')) {
            $path = $uploadImageController->updateFile($request->file('file'), "messages/" . $message->idChat, "file_" . Carbon::now()->timestamp);
            $message->file = $path;
            $fileSaved = true;
        }

        if ($fileSaved) $message->save();

        $chat = Chat::find($request["idChat"]);
        if($chat){
            $chat->messages = $chat->messages ? $chat->messages + 1 : 1;
            $chat->lastMessageUserId = $user->id;
            $chat->lastMessageId = $message->id;
            $chat->save();

            $participants = Participant::where("idChat", $chat->id)->where("idUser", "!=", $user->id)->where("deleted", false)->get();
            foreach ($participants as $participant) {
                $participant->pendingMessages = $participant->pendingMessages ? $participant->pendingMessages+1 : 1;
                event(new notificationMessage($participant["idUser"],null));
                $participant->save();
            }
        }

        event(new sendMessage($message, $request["idChat"], $user));

        $this->sendNotification($user, $participants, $message,$request["idChat"]);

        return response()->json(compact('message'), 201);

    }

    public function sendNotification($user, $participants, $message, $chat)
        {
            $firebaseToken= [];
            for ($i=0; $i <count($participants); $i++) {

                $firebaseToken[$i] = User::where('id','=',$participants[$i]->idUser)
                                        ->select('device_token')
                                        ->first()
                                        ->device_token;

            }
            $linkus = "/chats/".$chat;

            // $firebaseToken = User::where('id', $request->usuario)
            //                 ->pluck('device_token')
            //                 ->all();
            // $firebaseToken = User::whereNotNull('device_token')->pluck('device_token')->all();

            $SERVER_API_KEY = 'AAAAIRNx9HA:APA91bFmqjiXmsV4kTGSiTcy2qC-ShtiGFJK9M2MupnYV_Cci4QWrc1Y7R6KA8DhSIO_-a49OaFNo1CCN1EbpB_ClerGdxAAqgJtTrTULuAYof42LYaI_JmVKbl54x1hKgXfZooYWxt4';

            $data = [
                "registration_ids" => $firebaseToken,
                "notification" => [
                    "title" => $user->firstName." te envi贸 un mensaje",
                    "body" => $message->message,
                    "icon" => "https://www.test.zendy.cl/static/media/logo.30d6b517.png",
                    "click_action" => $linkus,
                    "content_available" => true,
                    "priority" => "high",
                ]
            ];
            $dataString = json_encode($data);

            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $response = curl_exec($ch);

            return $response;
        }

    public function find($id){
        $message = Message::find($id);

        if(!$message) return response()->json(['error' => 'Mensaje no encontrado'], 400);

        return response()->json(compact('message'),201);
    }

    public function list($idChat, Request $request){
        $messages = Message::join('users', 'users.id', 'messages.createdBy')->where("messages.idChat", $idChat)->where("messages.deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $messages->where(function ($query) use ($term) {
                $query->where('message', 'LIKE', '%'.$term.'%');
            });
        }

        return $messages->get(["messages.*", "users.firstName as userFirstName", "users.lastName as userLastName", "users.id as userId", "users.avatar as userAvatar", "users.sex as userSex"]);
    }

    public function delete($id){
        $message = Message::find($id);

        if(!$message) return response()->json(['error' => 'Mensaje no encontrado'], 400);

        $message->deleted = true;
        $message->save();

        return response()->json(['success' => 'Mensaje Eliminado'], 201);
    }
}
