<?php

namespace App\Http\Controllers;

use App\Events\CierreConsulta;
use App\Events\ContarConsultas;
use App\Events\notificationMessage;
use App\Models\Chat;
use App\Models\Company;
use App\Models\EntryQuery;
use App\Models\Message;
use App\Models\Participant;
use App\Models\UserCompany;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatController extends Controller
{

    public function find($id)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);
        // dd($user->id);
        $chat = Chat::find($id);
        if (!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->user = User::find($chat->idUser);
        $ValidatorUserParticipations = Participant::where('idUser', $user->id)->where('idChat', $id)->first();
        // dd($ValidatorUserParticipations);
        $UserParticipations = Participant::where('idChat', $id)->get();
        // dd($UserParticipations[1]);
        $dataUser = User::where('id', $UserParticipations[1]['idUser'])->first();
        // dd($dataUser);
        if ($chat->idCompany)
            if ($ValidatorUserParticipations->type == 'Admin') {
                $chat->company = Company::find($chat->idCompany)->where('id', json_decode($dataUser->companies))->first();
                //dd(Company::find($chat->idCompany)->where('id', json_decode($dataUser->companies))->first());
            } else {
                $chat->company = Company::find($chat->idCompany);
            }
        // // if()
        //     $chat->company = Company::find($chat->idCompany)->where('id',json_decode($user->companies))->first();

        $participants =  Participant::join("users", "users.id", "participants.idUser")->where("participants.idChat", $chat->id)->where("participants.deleted", false)
            ->orderBy("users.firstName")->orderBy("users.lastName")->get(["participants.*"]);
        foreach ($participants as $participant) {
            $userData = User::find($participant->idUser);
            $participant->user = $userData;
        }

        if (!$chat->name) {
            $chatName = "";
            if ($chat->scope == "Personal") {
                foreach ($participants as $participant) {
                    if ($participant->idUser != $user->id && $participant->user) {
                        $chatName = $chatName . $participant->user->firstName . ' ' . $participant->user->lastName;
                    }
                }
            }

            if ($chat->scope == "Grupal") {
                foreach ($participants as $participant) {
                    if ($participant->idUser != $user->id && $participant->user) {
                        $chatName = $chatName . $participant->user->firstName . ' ' . $participant->user->lastName . ", ";
                    }
                }
                $chatName = $chatName . "Tú";
            }
            $chat->name = $chatName;
        }

        $chat->participants = $participants;

        return response()->json(compact('chat'), 201);
    }

    public function findImages($id)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $images = Message::where("idChat", $id)->where("image", "!=", "")->where("deleted", false)->pluck("image");
        if (!$images) return response()->json(['error' => 'El chat no cuenta con imagenes'], 400);

        return response()->json(compact('images'), 201);
    }

    public function listActive(Request $request)
    {
        $user = JWTAuth::toUser($request->bearerToken());

        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);
        $chatIds = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->pluck("idChat");
        $participations = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->get()->keyBy("idChat");

        $isQuery = $request->has("isQuery") ? ($request->get("isQuery") == "true") : false;
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $idHelpDesk = $request->has("idHelpDesk") ? $request->get("idHelpDesk") : null;

        $chats = Chat::whereIn("id", $chatIds)->where("idCompany", $idHelpDesk ? $idHelpDesk : $idCompany)->where("isQuery", $isQuery)->where("deleted", false)->where("status", $request->status)->orderByDesc("updated_at")->get();
        // $chats = Chat::whereIn("id", $chatIds)->where("idCompany",$idCompany)->where("isQuery", $isQuery)->where("deleted", false)->where("status", $request->status)->orderByDesc("updated_at")->get();

        $participants = Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->where("deleted", false)->get();
        $userIds =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->pluck("idUser")->unique();

        $companyIds = [];
        $lastMessageIds = [];
        $lastUserIds = [];
        foreach ($chats as $chat) {
            if ($chat->idCompany && !in_array($chat->idCompany, $companyIds))
                $companyIds[] = $chat->idCompany;

            if ($chat->lastMessageUserId && !in_array($chat->lastMessageUserId, $lastUserIds))
                $lastUserIds[] = $chat->lastMessageUserId;

            if ($chat->lastMessageId && !in_array($chat->lastMessageId, $lastMessageIds))
                $lastMessageIds[] = $chat->lastMessageId;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');
        $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');
        $lastMessages = Message::whereIn("id", $lastMessageIds)->get()->keyBy('id');
        $lastUsers = User::whereIn("id", $lastUserIds)->get()->keyBy('id');

        $participantsByChat = new \stdClass();
        $participantsByChatNames = new \stdClass();
        foreach ($participants as $participant) {
            $participant->user = $users[$participant->idUser];

            $idChat = $participant->idChat;
            $currentChatParticipants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
            array_push($currentChatParticipants, $participant);
            $participantsByChat->$idChat = $currentChatParticipants;

            $currentParticipantsName = property_exists($participantsByChatNames, $idChat) ? $participantsByChatNames->$idChat : "";
            $participantsByChatNames->$idChat = $currentParticipantsName . $participant->user->firstName . " " . $participant->user->lastName . ", ";
        }

        foreach ($chats as $chat) {
            $chat->user = $chat->idUser ? ($chat->idUser == $user->id ? $user : $users[$chat->idUser]) : null;
            $chat->company = $chat->idCompany ? $companies[$chat->idCompany] : null;
            $chat->lastMessage = $chat->lastMessageId ? $lastMessages[$chat->lastMessageId] : null;
            $chat->lastMessageUser = $chat->lastMessageUserId ? ($chat->lastMessageUserId == $user->id ? $user : $lastUsers[$chat->lastMessageUserId]) : null;
            $chat->participation = $participations[$chat->id];
            $idChat = $chat->id;

            if (!$chat->name)
                $chat->name = property_exists($participantsByChatNames, $idChat) ? substr($participantsByChatNames->$idChat, 0, -2) : ($user->firstName . " " . $user->lastName);

            $chat->participants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
        }

        $term = $request->has("term") ? $request->get("term") : "";
        if ($term) {
            $chats = $chats->filter(function ($chat) use ($term) {
                $filter = "";
                if ($chat->name) {
                    $filter = strtolower($chat->name);
                }
                //$this->chatEmpresa(str_contains(strtolower($filter), strtolower($term)) !== false);
                return str_contains(strtolower($filter), strtolower($term)) !== false;
            });
        }
        //$this->chatEmpresa($chats->values()->all());
        return $chats->values()->all();
    }

    public function chatEmpresa($chats)
    {
        try {

            for ($i=0; $i <count($chats) ; $i++) {
                if ( $chats[$i]['scope'] == 'Personal') {
                    $userChats[] = $chats[$i]['idUser'];
                }
            }
            dd($userChats);


        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function listAvailableUsersByCompany(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $roles = $request->has("roles") ? $request->get("roles") : [];
        $term = $request->has("term") ? $request->get("term") : "";
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $company = Company::find($idCompany);

        $users = User::join('roles', 'roles.id', '=', 'users.idRole')
            ->where('users.companies', 'LIKE', '%' . "\"" . $idCompany . "\"" . '%')
            ->where('users.deleted', false)
            ->where('users.id', '!=', $user->id)->whereIn('roles.name', $roles);

        if ($term) {
            $users->where(function ($query) use ($term) {
                $query->where('firstName', 'LIKE', '%' . $term . '%')
                    ->orWhere('lastName', 'LIKE', '%' . $term . '%');
            });
        }

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);
        foreach ($users as $user) {
            $user->companyName = $company->name;
        }

        return $users;
    }

    public function delete($id)
    {
        $chat = Chat::find($id);

        if (!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->deleted = true;
        $chat->save();

        return response()->json(['success' => 'Chat Eliminado'], 201);
    }

    public function finalize($id, Request $request)
    {
        $request = json_decode($request->getContent(), true);
        //dd($id);
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat = Chat::find($id);
        if (!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->status = "Finalizado";
        $chat->finalizeDate = Carbon::now()->timestamp;
        //$chat->finalizeStatus = 'Completado';// Activar para pruebas locales
        $chat->finalizeStatus = $request["finalizeStatus"]; //Descomentarlo en produccion
        //$chat->finalizeDescription = null; //Activar para pruebas locales
        $chat->finalizeDescription = $request["finalizeDescription"];//Descomentarlo en produccion
        $chat->finalizeUser = $user->id;
        $chat->save();

        //Finalizar todas las consultas repetidas del usuario
        $idEntryQuery = Chat::where('id',$id)->first()->idEntryQuery;
        $idCreatedBy = EntryQuery::where('id',$idEntryQuery)->first()->createdBy;
        EntryQuery::where('createdBy',$idCreatedBy)->where('status','Pendiente')->update(['deleted'=> 1]);

        $idEntryQuery = Chat::where('id', $id)->first()->idEntryQuery;
        $idUser = EntryQuery::where('id', $idEntryQuery)->first()->createdBy;
        $participante = User::where('id', $idUser)->get();
        $mensaje = "Su última consulta ha sido finalizada, tenga buen día";
        $avatar = !!isset($user->avatar) ? "api/" . $user->avatar : 'static/media/defaultAvatarMale.edd5e438.jpg';
        $contenido = [
            'modulo'     => null,
            'idConsulta' => $idEntryQuery,
            'idUser'     => $idUser,
            'usuario'    => $user->firstName,
            'avatar'     => $avatar,
            'mensaje'    => $mensaje,
        ];
        event(new CierreConsulta($idUser, $contenido));
        $this->sendNotification($user->id, $participante, $mensaje, $avatar);

        $participants = Participant::where("idChat", $id)->where("idUser", "!=", $user->id)->where("active", true)->get();
        Participant::where('idChat', $id)->update(['active' => false, 'outputDate' => date('Y-m-d', Carbon::now()->timestamp),'pendingMessages' => 0]);

        $users = User::where('companies', Auth::user()->companies)->whereIn('idRole', [4,3])->get();

        for ($i=0; $i <count($users) ; $i++) {
            event(new ContarConsultas($users[$i]['id'],$this->CountPendientes()));
        }

        foreach ($participants as $participant) {
            event(new notificationMessage($participant["idUser"], $id));
        }

        return response()->json(compact('chat'), 201);
    }

    public function sendNotification($user, $participants, $message, $avatar)
    {

        $firebaseToken = [];
        for ($i = 0; $i < count($participants); $i++) {

            $firebaseToken[$i] = User::where('id', '=', $participants[$i]->id)
                ->select('device_token')
                ->first()
                ->device_token;
        }
        $SERVER_API_KEY = 'AAAAIRNx9HA:APA91bFmqjiXmsV4kTGSiTcy2qC-ShtiGFJK9M2MupnYV_Cci4QWrc1Y7R6KA8DhSIO_-a49OaFNo1CCN1EbpB_ClerGdxAAqgJtTrTULuAYof42LYaI_JmVKbl54x1hKgXfZooYWxt4';

        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => "Consulta finalizada",
                "body" => $message,
                "icon" => "https://www.zendy.cl/" . $avatar,
                "click_action" => null,
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

    public function nameChat($id, Request $request)
    {

        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat = Chat::find($id);
        if (!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        $chat->name = $request->name;
        $chat->save();

        $participants = Participant::where("idChat", $id)->where("idUser", "!=", $user->id)->where("active", true)->get();
        foreach ($participants as $participant) {
            event(new notificationMessage($participant["idUser"], $id));
        }

        return response()->json(compact('chat'), 201);
    }

    public function listFinalize(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $from = $request->has("fromDate") ? $request->get("fromDate") : "";
        $fromDate1 = (int)$from;
        $to = $request->has("toDate") ? $request->get("toDate") : "";
        $fromTo1 = (int)$to;

        $isQuery = $request->has("isQuery") ? ($request->get("isQuery") == "true") : false;
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $idHelpDesk = $request->has("idHelpDesk") ? $request->get("idHelpDesk") : null;

        $chatIds = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->pluck("idChat");
        $participations = Participant::where("idUser", $user->id)->where("status", "Activo")->where("deleted", false)->get()->keyBy("idChat");

        $chats = Chat::whereIn("id", $chatIds)->where("idCompany", $idHelpDesk ? $idHelpDesk : $idCompany)->where("deleted", false)->where("isQuery", $isQuery)->where("status", ["Finalizado", "Cancelado"])->whereBetween('finalizeDate', [$fromDate1, $to])->orderByDesc("updated_at")->get();

        $participants = Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->where("deleted", false)->get();
        $userIds =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user->id)->pluck("idUser")->unique();

        $companyIds = [];
        $lastMessageIds = [];
        $lastUserIds = [];
        foreach ($chats as $chat) {
            if ($chat->idCompany && !in_array($chat->idCompany, $companyIds))
                $companyIds[] = $chat->idCompany;

            if ($chat->lastMessageUserId && !in_array($chat->lastMessageUserId, $lastUserIds))
                $lastUserIds[] = $chat->lastMessageUserId;

            if ($chat->lastMessageId && !in_array($chat->lastMessageId, $lastMessageIds))
                $lastMessageIds[] = $chat->lastMessageId;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');
        $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');
        $lastMessages = Message::whereIn("id", $lastMessageIds)->get()->keyBy('id');
        $lastUsers = User::whereIn("id", $lastUserIds)->get()->keyBy('id');

        $participantsByChat = new \stdClass();
        $participantsByChatNames = new \stdClass();
        foreach ($participants as $participant) {
            $participant->user = $users[$participant->idUser];

            $idChat = $participant->idChat;
            $currentChatParticipants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
            array_push($currentChatParticipants, $participant);
            $participantsByChat->$idChat = $currentChatParticipants;

            $currentParticipantsName = property_exists($participantsByChatNames, $idChat) ? $participantsByChatNames->$idChat : "";
            $participantsByChatNames->$idChat = $currentParticipantsName . $participant->user->firstName . " " . $participant->user->lastName . ", ";
        }

        foreach ($chats as $chat) {
            $chat->user = $chat->idUser ? ($chat->idUser == $user->id ? $user : $users[$chat->idUser]) : null;
            $chat->company = $chat->idCompany ? $companies[$chat->idCompany] : null;
            $chat->lastMessage = $chat->lastMessageId ? $lastMessages[$chat->lastMessageId] : null;
            $chat->lastMessageUser = $chat->lastMessageUserId ? ($chat->lastMessageUserId == $user->id ? $user : $lastUsers[$chat->lastMessageUserId]) : null;
            $chat->participation = $participations[$chat->id];
            $idChat = $chat->id;

            if (!$chat->name)
                $chat->name = property_exists($participantsByChatNames, $idChat) ? substr($participantsByChatNames->$idChat, 0, -2) : ($user->firstName . " " . $user->lastName);

            $chat->participants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
        }

        $term = $request->has("term") ? $request->get("term") : "";
        if ($term) {
            $chats = $chats->filter(function ($chat) use ($term) {
                $filter = "";
                if ($chat->name) {
                    $filter = strtolower($chat->name);
                }
                return str_contains(strtolower($filter), strtolower($term)) !== false;
            });
        }

        return $chats->values()->all();
    }

    public function UsersHD()
    {
        try {
            //Luego cambiar el id estatico por -> Auth::user()->id o para una prueba pasar a 98 -> adminzendy
            $companies = User::where('id', Auth::user()->id)->first()->companies;

            $ids = User::where('companies', $companies)->get();

            for ($i = 0; $i < count($ids); $i++) {
                $data[] = array(
                    'id' => $ids[$i]['id'],
                    'username' => $ids[$i]['username']
                );
            }

            return $data;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function searchlistFinalize(Request $request)
    {
        // $user = Auth::user();

        if (!is_null($request['id'])) {
            $user = User::where('id', $request['id'])->get();
            if (!$user) return response()->json(['error' => 'Usuario HelpDesk no encontrado.'], 400);
        } else {
            $user = User::where('companies', Auth::user()->companies)->whereIn('idRole', [3, 4])->get();
        }


        for ($i = 0; $i < count($user); $i++) {

            $from = $request->has("fromDate") ? $request->get("fromDate") : "";
            $fromDate1 = (int)$from;
            $to = $request->has("toDate") ? $request->get("toDate") : "";
            $fromTo1 = (int)$to;

            $isQuery = $request->has("isQuery") ? ($request->get("isQuery") == "true") : false;
            $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
            $idHelpDesk = $request->has("idHelpDesk") ? $request->get("idHelpDesk") : null;
            $todo = [];

            $chatIds = Participant::where("idUser", $user[$i]['id'])->where("status", "Activo")->where("deleted", false)->pluck("idChat");
            $participations = Participant::where("idUser", $user[$i]['id'])->where("status", "Activo")->where("deleted", false)->get()->keyBy("idChat");

            $chats = Chat::whereIn("id", $chatIds)->where("idCompany", $idHelpDesk ? $idHelpDesk : $idCompany)->where("deleted", false)->where("isQuery", $isQuery)->where("status", ["Finalizado", "Cancelado"])->whereBetween('finalizeDate', [$fromDate1, $to])->orderByDesc("updated_at")->get();

            $participants = Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user[$i]['id'])->where("deleted", false)->get();
            $userIds =  Participant::wherein("idChat", $chatIds)->where("idUser", "!=", $user[$i]['id'])->pluck("idUser")->unique();

            $companyIds = [];
            $lastMessageIds = [];
            $lastUserIds = [];
            foreach ($chats as $chat) {
                if ($chat->idCompany && !in_array($chat->idCompany, $companyIds))
                    $companyIds[] = $chat->idCompany;

                if ($chat->lastMessageUserId && !in_array($chat->lastMessageUserId, $lastUserIds))
                    $lastUserIds[] = $chat->lastMessageUserId;

                if ($chat->lastMessageId && !in_array($chat->lastMessageId, $lastMessageIds))
                    $lastMessageIds[] = $chat->lastMessageId;
            }

            $users = User::whereIn("id", $userIds)->get()->keyBy('id');
            $companies = Company::whereIn("id", $companyIds)->get()->keyBy('id');
            $lastMessages = Message::whereIn("id", $lastMessageIds)->get()->keyBy('id');
            $lastUsers = User::whereIn("id", $lastUserIds)->get()->keyBy('id');

            $participantsByChat = new \stdClass();
            $participantsByChatNames = new \stdClass();
            foreach ($participants as $participant) {
                $participant->user = $users[$participant->idUser];

                $idChat = $participant->idChat;
                $currentChatParticipants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
                array_push($currentChatParticipants, $participant);
                $participantsByChat->$idChat = $currentChatParticipants;

                $currentParticipantsName = property_exists($participantsByChatNames, $idChat) ? $participantsByChatNames->$idChat : "";
                $participantsByChatNames->$idChat = $currentParticipantsName . $participant->user->firstName . " " . $participant->user->lastName . ", ";
            }

            foreach ($chats as $chat) {
                $chat->user = $chat->idUser ? ($chat->idUser == $user[$i]['id'] ? $user[$i] : $users[$chat->idUser]) : null;
                $chat->company = $chat->idCompany ? $companies[$chat->idCompany] : null;
                $chat->lastMessage = $chat->lastMessageId ? $lastMessages[$chat->lastMessageId] : null;
                $chat->lastMessageUser = $chat->lastMessageUserId ? ($chat->lastMessageUserId == $user[$i]['id'] ? $user[$i] : $lastUsers[$chat->lastMessageUserId]) : null;
                $chat->participation = $participations[$chat->id];
                $idChat = $chat->id;

                if (!$chat->name)
                    $chat->name = property_exists($participantsByChatNames, $idChat) ? substr($participantsByChatNames->$idChat, 0, -2) : ($user[$i]['firstName'] . " " . $user[$i]['lastName']);

                $chat->participants = property_exists($participantsByChat, $idChat) ? $participantsByChat->$idChat : [];
            }

            // $term = $request->has("term") ? $request->get("term") : "";
            // if ($term) {
            //     $chats = $chats->filter(function ($chat) use ($term) {
            //         $filter = "";
            //         if ($chat->name) {
            //             $filter = strtolower($chat->name);
            //         }
            //         return str_contains(strtolower($filter), strtolower($term)) !== false;
            //     });
            // }

            // $todo[$i] = $chats->values()->all();
            if(count($chats) > 0){
                array_push($todo, $chats->values()->all());
            }
        }

        return $todo;
    }

    public function CountPendientes()
    {
        try {
            $hd = json_decode(Auth::user()->companies);
            $count = EntryQuery::where('status','Pendiente')->where('deleted',false)->where('idHelpdesk',$hd[0])->count();
            return $count;
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function imageChat($id, Request $request)
    {

        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $chat = Chat::find($id);
        if (!$chat) return response()->json(['error' => 'Chat no encontrado'], 400);

        if ($request->hasFile('image')) {

            if(!is_null($chat->imgChat)){
                $image_path = public_path() . '/'. $chat->imgChat;
                if (@getimagesize($image_path)) {
                    unlink($image_path);
                }
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $name = $id . "_" . Carbon::now()->timestamp;
            $file->storeAs('public/group/', $name .'.'. $extension);

            $chat->imgChat = 'storage/group/'. $name .'.'. $extension;
            $chat->save();

            $participants = Participant::where("idChat", $id)->where("idUser", "!=", $user->id)->where("active", true)->get();
            foreach ($participants as $participant) {
                event(new notificationMessage($participant["idUser"], $id));
            }

            return response()->json(compact('chat'), 201);
        }

        return response()->json(['error' => 'Archivo no encontrado'], 400);

    }

}
