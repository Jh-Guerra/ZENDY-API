<?php

namespace App\Http\Controllers;

use App\Events\ConsultaNotification;
use App\Mail\chatMail;
use App\Mail\ConsultaPendienteMail;
use App\Models\Chat;
use App\Models\Chat_externo;
use App\Models\Company;
use App\Models\CompanyHorario;
use App\Models\EntryQuery;
use App\Models\Participant;
use App\Models\User;
use App\Models\Message;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Recommendation;
use App\Models\UserCompany;
use DateTime;
use Mail;

class EntryQueryController extends Controller
{
    public function register(Request $request)
    {
        $error = $this->validateFields($request);
        if ($error) {
            return response()->json($error, 400);
        }
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        $entryQuery = new EntryQuery();
        $entryQuery->startDate = Carbon::now()->timestamp;
        $entryQuery->status = "Pendiente";
        $entryQuery->idCompany = $idCompany;
        $entryQuery->createdBy = $user->id;
        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        $entryQuery->image = $request["image"];
        $entryQuery->file = $request["file"];
        $entryQuery->idModule = $request["idModule"];
        $entryQuery->isFrequent = $request->isFrequent == true;
        $entryQuery->idHelpdesk = $request["idHelpdesk"];
        $entryQuery->externo = $request["externo"];
        $entryQuery->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if ($request->hasFile('image')) {
            $path = $uploadImageController->updateFile($request->file('image'), "entry_queries/" . $entryQuery->id, "image_" . Carbon::now()->timestamp);
            $entryQuery->image = $path;
            $fileSaved = true;
        }

        if ($request->hasFile('file')) {
            $path = $uploadImageController->updateFile($request->file('file'), "entry_queries/" . $entryQuery->id, "file_" . Carbon::now()->timestamp);
            $entryQuery->file = $path;
            $fileSaved = true;
        }

        if ($fileSaved) {
            $entryQuery->save();
        }

        -$HD = '["' . $request["idHelpdesk"] . '"]';
        $users = User::where('companies', $HD)->where('idRole', 4)->get();
        $contenido = [
            'modulo'     => Module::where('id', $request["idModule"])->first()->name,
            'idConsulta' => $entryQuery->id,
            'idUser'     => $user->id,
            'usuario'    => $user->firstName,
            'avatar'     => !!isset($user->avatar) ? $user->avatar : 'static/media/defaultAvatarMale.edd5e438.jpg',
            'mensaje'    => "Se ha presentado una nueva consulta, haz clic aquí para redirigirte hacia ella",
        ];

        $i = 0;
        while ($i < count($users)) {
            event(new ConsultaNotification($users[$i]['id'], $contenido));
            $i++;
        }

        //LO COMENTADO
        /* $rut = UserCompany::where('idCompany', $idCompany)->first();
               $companyHD = Company::where('id', $rut->idCompany)->first();
        
                $users = User::where('companies', $companyHD->helpDesks)->where('idRole', 4)->get();
                $horario = CompanyHorario::where('id', $companyHD->idHorario)->get();
                $horaActual = Carbon::now()->toTimeString();
                $diaActual = intval(date("w"));
                $horaActual2 = "09:29:59";
                $diaActual2 = 0;
        
                //dd($horario);
        
                $date = Carbon::now();
                $date = $date->add(2, 'day');
                $date = $date->format('Y-m-d');
    
                $dias = [0, 1, 2, 3, 4, 5, 6];
                $diasHabituales = json_decode($horario[0]['Dias']);
        
                for ($i = 0; $i < count(json_decode($horario[0]['MedioDia'])); $i++) {
                    $MD = json_decode($horario[0]['MedioDia']);
                    array_push($diasHabituales, (int)$MD[$i]);
                }
        
                $diaNoPuesto = array_diff($dias, $diasHabituales);
    
                for ($i = 0; $i < count($diaNoPuesto); $i++) {
                    if ($diaActual2 == $diaNoPuesto[$i]) {
                        try {
                            for ($i = 0; $i < count($users); $i++) {
                                $credenciales = 'rut_empresa=' . base64_encode($companyHD->ruc) . '&usuario=' . base64_encode($users[$i]['username']) . '&password=' . $users[$i]['password'];
                                $url = 'https://www.zendy.cl/login?' . $credenciales . '&consulta=' . $entryQuery->id . '&fecha=' . base64_encode($date);
                                Mail::to($users[$i]['email'])->send(new ConsultaPendienteMail($users[$i]['firstName'], $url));
                            }
                        } catch (\Throwable $th) {
                            $error = $th;
                        }
        
                        return 'Correo enviado a usuarios HD - un dia que no se trabajo :D';
                    }
                }
        
                $medioDia = json_decode($horario[0]['MedioDia']);
        
                if (!is_null($horario[0]['MedioDia'])) {
                    for ($i = 0; $i < count($medioDia); $i++) {
        
                        if ($diaActual2 == $medioDia[$i]) {
                            if (strtotime($horaActual2) >= strtotime($horario[0]['HorarioIngresoMD']) && strtotime($horaActual2) < strtotime($horario[0]['HorarioSalidaMD'])) {
                                return response()->json(compact('chat'), 201);
                            } else {
                                try {
                                    for ($i = 0; $i < count($users); $i++) {
                                        $credenciales = 'rut_empresa=' . base64_encode($companyHD->ruc) . '&usuario=' . base64_encode($users[$i]['username']) . '&password=' . $users[$i]['password'];
                                        $url = 'https://www.zendy.cl/login?' . $credenciales . '&consulta=' . $entryQuery->id . '&fecha=' . base64_encode($date);
                                        Mail::to($users[$i]['email'])->send(new ConsultaPendienteMail($users[$i]['firstName']), $url);
                                    }
                                } catch (\Throwable $th) {
                                   $error = $th;
                                }
                                return 'Correo enviado a usuarios HD un medio dia :D';
                            }
                        }
                    }
                }
        
                for ($i = 0; $i < count(json_decode($horario[0]['Dias'])); $i++) {
                    if ($diaActual2 == json_decode($horario[0]['Dias'][$i])) {
                        if (strtotime($horaActual2) >= strtotime($horario[0]['HorarioIngreso']) && strtotime($horaActual2) < strtotime($horario[0]['HorarioSalida'])) {
                            return response()->json(compact('chat'), 201);
                        } else {
                            try {
                                for ($i = 0; $i < count($users); $i++) {
                                    $credenciales = 'rut_empresa=' . base64_encode($companyHD->ruc) . '&usuario=' . base64_encode($users[$i]['username']) . '&password=' . $users[$i]['password'];
                                    $url = 'https://www.zendy.cl/login?' . $credenciales . '&consulta=' . $entryQuery->id . '&fecha=' . base64_encode($date);
                                    Mail::to($users[$i]['email'])->send(new ConsultaPendienteMail($users[$i]['firstName'], $url));
                                }
                           } catch (\Throwable $th) {
                                $error = $th;
                           }
                            return 'Correo enviado a usuarios HD un dia de la semana D:';
                        }
                    }
                } */

        return response()->json(compact('entryQuery'), 201);
    }

    // public function sendNotification($user, $participants, $message, $consulta)
    //     {

    //         $firebaseToken= [];
    //         for ($i=0; $i <count($participants); $i++) {

    //             $firebaseToken[$i] = User::where('id','=',$participants[$i]->id)
    //                                     ->select('device_token')
    //                                     ->first()
    //                                     ->device_token;

    //         }


    //         $linkus = "/consultas/".$consulta;

    //         // $firebaseToken = User::where('id', $request->usuario)
    //         //                 ->pluck('device_token')
    //         //                 ->all();
    //         // $firebaseToken = User::whereNotNull('device_token')->pluck('device_token')->all();

    //         $SERVER_API_KEY = 'AAAAIRNx9HA:APA91bFmqjiXmsV4kTGSiTcy2qC-ShtiGFJK9M2MupnYV_Cci4QWrc1Y7R6KA8DhSIO_-a49OaFNo1CCN1EbpB_ClerGdxAAqgJtTrTULuAYof42LYaI_JmVKbl54x1hKgXfZooYWxt4';

    //         $data = [
    //             "registration_ids" => $firebaseToken,
    //             "notification" => [
    //                 "title" => $user->firstName." te envió un mensaje",
    //                 "body" => $message->message,
    //                 "icon" => "https://www.zendy.cl/static/media/logo.30d6b517.png",
    //                 "click_action" => $linkus,
    //                 "content_available" => true,
    //                 "priority" => "high",
    //             ]
    //         ];
    //         $dataString = json_encode($data);

    //         $headers = [
    //             'Authorization: key=' . $SERVER_API_KEY,
    //             'Content-Type: application/json',
    //         ];

    //         $ch = curl_init();

    //         curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    //         curl_setopt($ch, CURLOPT_POST, true);
    //         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

    //         $response = curl_exec($ch);

    // return $response;
    //}

    private function validateFields($request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required',
            'description' => 'required',
            'idHelpdesk' => 'required|int',
        ]);


        $errorMessage = null;
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    public function find($id)
    {
        $entryQuery = EntryQuery::find($id);
        if (!$entryQuery) return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $entryQuery->user = User::find($entryQuery->createdBy);
        if ($entryQuery->idCompany)
            $entryQuery->company = Company::find($entryQuery->idCompany);

        return response()->json(compact('entryQuery'), 201);
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQueries = EntryQuery::where("createdBy", $user->id)->where("deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if ($term) {
            $this->search($entryQueries, $term);
        }

        $entryQueries->get();

        return response()->json(compact('entryQueries'), 201);
    }

    public function listPendings(Request $request)
    {
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $idHelpdesk = $request->has("idHelpdesk") ? $request->get("idHelpdesk") : null;

        if (!$idHelpdesk) {
            $entryQueries = EntryQuery::join('users', 'entry_queries.createdBy', '=', 'users.id')->where("entry_queries.idHelpdesk", $idCompany)
                ->where("status", "Pendiente")->where("entry_queries.deleted", false);
        } else {
            $entryQueries = EntryQuery::join('users', 'entry_queries.createdBy', '=', 'users.id')
                ->where("status", "Pendiente")->where("entry_queries.idHelpdesk", $idHelpdesk)->where("entry_queries.deleted", false);
        }

        $term = $request->has("term") ? $request->get("term") : "";
        if ($term)
            $this->search($entryQueries, $term);

        $entryQueries = $entryQueries->orderByDesc("startDate")->get(['entry_queries.*', 'users.firstName AS firstName', 'users.lastName AS lastName', 'users.avatar AS avatar', 'users.sex AS sex']);

        return $entryQueries->values()->all();;
    }

    public function search($entryQueries, $term)
    {
        if ($term) {
            $entryQueries->where(function ($query) use ($term) {
                $query->where('reason', 'LIKE', '%' . $term . '%');
            });
        }
    }

    public function delete($id)
    {
        $entryQuery = EntryQuery::find($id);
        if (!$entryQuery)
            return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $entryQuery->deleted = true;
        $entryQuery->save();

        return response()->json(compact('entryQuery'), 201);
    }

    public function listQuery(Request $request, $status, $idHelpdesk)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        if (!$status) {
            $status = "Pendiente";
        }

        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        if (!$idHelpdesk) {
            $entryQueries = EntryQuery::where("deleted", false)->where("idCompany", $idCompany)->where("createdBy", $user->id)->where("status", '=', $status);
        } else {
            $entryQueries = EntryQuery::where("deleted", false)->where("idCompany", $idCompany)->where("idHelpdesk", $idHelpdesk)->where("createdBy", $user->id)->where("status", '=', $status);
        }

        $term = $request->has("term") ? $request->get("term") : "";
        if ($term)
            $this->search($entryQueries, $term);

        $entryQueries = $entryQueries->orderByDesc("startDate")->get();

        return $entryQueries->values()->all();;
    }

    public function update(Request $request, $id)
    {
        $entryQuery = EntryQuery::find($id);

        $error = $this->validateFields($request);
        if ($error) {
            return response()->json($error, 400);
        }

        if (!$entryQuery) {
            return response()->json(['error' => 'Consulta no encontrada'], 400);
        }

        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        if ($request["image"]) {
            if ($request->oldImage) {
                $newImage = substr($request->oldImage, 8);
                $image_path = storage_path() . '/app/public/' . "" . $newImage;
                if (@getimagesize($image_path)) {
                    unlink($image_path);
                }
            }
            $entryQuery->image = $request["image"];
        }
        if ($request["file"]) {
            $entryQuery->file = $request["file"];
        }
        $entryQuery->idModule = $request["idModule"];
        $entryQuery->isFrequent = $request["isFrequent"];
        $entryQuery->save();

        $tasks_controller = new uploadImageController;
        $fileSaved = false;
        if ($request->hasFile('image')) {
            $path = $tasks_controller->updateFile($request->file('image'), "entry_queries/" . $entryQuery->id, "image_" . Carbon::now()->timestamp);
            $entryQuery->image = $path;
            $fileSaved = true;
        }

        if ($request->hasFile('file')) {
            $path = $tasks_controller->updateFile($request->file('file'), "entry_queries/" . $entryQuery->id, "file_" . Carbon::now()->timestamp);
            $entryQuery->file = $path;
            $fileSaved = true;
        }

        if ($fileSaved) {
            $entryQuery->save();
        }
        $entryQuery->user = User::find($entryQuery->createdBy);
        if ($entryQuery->idCompany)
            $entryQuery->company = Company::find($entryQuery->idCompany);

        return response()->json(compact('entryQuery'), 201);
    }

    public function accept($id, Request $request)
    {
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $request = json_decode($request->getContent(), true);

        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQuery = EntryQuery::find($id);
        if (!$entryQuery) return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $queryUser = User::find($entryQuery->createdBy);
        if (!$queryUser) return response()->json(['error' => 'No se encontró al usuario que realizó la consulta.'], 400);

        $queryUserParticipations = Participant::join('chats', 'chats.id', 'participants.idChat')->where("participants.idUser", $queryUser->id)
            ->where("chats.isQuery", true)->where("chats.status", "Vigente")->where("participants.deleted", false)
            ->get();

        if ($queryUserParticipations && count($queryUserParticipations) > 0) return response()->json(['error' => 'Este usuario ya cuenta con una consulta activa.'], 400);

        $entryQuery->acceptDate = Carbon::now()->timestamp;
        $entryQuery->status = "Aceptado";
        $entryQuery->acceptedBy = $user->id;
        //$entryQuery->byRecommend = 0; //para pruebas en local
        $entryQuery->byRecommend = $request["byRecommend"];
        $entryQuery->save();

        if ($entryQuery->byRecommend) {
            $firstRecommendation = Recommendation::where("idEntryQuery", $entryQuery->id)->where("recommendUser", $user->id)->first();
            $firstRecommendation->accepted = true;
            $firstRecommendation->status = "Aceptado";
            $firstRecommendation->save();

            Recommendation::where("idEntryQuery", $entryQuery->id)->where("recommendUser", "!=", $user->id)->update(['accepted' => false, 'status' => "No aceptado"]);
        } else {
            Recommendation::where("idEntryQuery", $entryQuery->id)->update(['accepted' => false, 'status' => "No aceptado"]);
        }

        $chat = new Chat();
        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Consulta";
        $chat->scope = "Personal";
        $chat->status = "Vigente";
        $chat->idCompany = $idCompany;
        $chat->idUser = $user->id;
        $chat->allUsers = 0;
        $chat->messages = 0;
        $chat->isQuery = true;
        $chat->idEntryQuery = $entryQuery->id;
        //$chat->byRecommend = 0; //Para pruebas en local
        $chat->byRecommend = $request["byRecommend"];
        $chat->save();

        $participantController = new ParticipantController();
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

        $clientRequest = [
            'idUser' => $queryUser->id,
            'idChat' => $chat->id,
            'type' => "Participante",
            'erp' => false,
            'entryDate' => date('Y-m-d', Carbon::now()->timestamp),
            'status' => "Activo",
            'active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];

        array_push($participants, $userRequest);
        array_push($participants, $clientRequest);
        $participantController->registerMany($participants);

        $chat->name = $queryUser->firstName . " " . $queryUser->lastName;

        $messageReason = new Message();
        $messageReason->createdBy = $entryQuery->createdBy;
        $messageReason->idChat = $chat->id;
        $messageReason->message = $entryQuery->reason;
        $messageReason->resend = false;
        $messageReason->originalUserId = $entryQuery->createdBy;
        $messageReason->createdDate = Carbon::now()->timestamp;
        $messageReason->save();

        $messageDescription = new Message();
        $messageDescription->createdBy = $entryQuery->createdBy;
        $messageDescription->idChat = $chat->id;
        $messageDescription->message = $entryQuery->description;
        $messageDescription->resend = false;
        $messageDescription->originalUserId = $entryQuery->createdBy;
        $messageDescription->createdDate = Carbon::now()->timestamp;
        $messageDescription->save();

        if ($entryQuery->image) {
            $messageImage = new Message();
            $messageImage->createdBy = $entryQuery->createdBy;
            $messageImage->idChat = $chat->id;
            $messageImage->message = "";
            $messageImage->resend = false;
            $messageImage->originalUserId = $entryQuery->createdBy;
            $messageImage->image = $entryQuery->image;
            $messageImage->createdDate = Carbon::now()->timestamp;
            $messageImage->save();
        }

        if ($entryQuery->file != "") {
            $messageFile = new Message();
            $messageFile->createdBy = $entryQuery->createdBy;
            $messageFile->idChat = $chat->id;
            $messageFile->message = "";
            $messageFile->resend = false;
            $messageFile->originalUserId = $entryQuery->createdBy;
            $messageFile->file = $entryQuery->file;
            $messageFile->createdDate = Carbon::now()->timestamp;
            $messageFile->save();
        }

        $message = new Message();
        $message->createdBy = $user->id;
        $message->idChat = $chat->id;
        $message->message = "Hola, buen dia, su consulta ha sido atendida. En unos minutos nos comunicaremos contigo";
        $message->resend = false;
        $message->originalUserId = $user->id;
        $message->createdDate = Carbon::now()->timestamp;
        $message->save();


        // $userChat = User::where('id', $entryQuery->createdBy)->first();
        // $rut = UserCompany::where('idCompany', $idCompany)->first();

        // if ($entryQuery->externo == '1') {
        //     try {
        //         $date = Carbon::now();
        //         $date = $date->format('Y-m-d');
        //         $credenciales = 'rut_empresa=' . base64_encode($rut->rutCompany) . '&usuario=' . base64_encode($userChat->username) . '&password=' . $userChat->password;
        //         $url = 'https://www.zendy.cl/login?' . $credenciales . '&chat=' . $chat->id . '&fecha=' . base64_encode($date);
        //         Mail::to($userChat->email)->send(new chatMail($userChat->firstName, $url));
        //     } catch (\Throwable $th) {
        //         $error = $th;
        //     }
        // }
        // $companyHD = Company::where('id', $rut->idCompany)->get();
        // $users = User::where('companies', $companyHD[0]['helpDesks'])->where('idRole', 4)->get();
        // $horario = CompanyHorario::where('id', $companyHD[0]['idHorario'])->get();
        // $horaActual = Carbon::now()->toTimeString();
        // $diaActual = intval(date("w"));
        // $horaActual2 = "09:29:59";
        // $diaActual2 = 1;

        // //dd(count($users));

        // $dias = [0, 1, 2, 3, 4, 5, 6];
        // $diasHabituales = json_decode($horario[0]['Dias']);

        // for ($i = 0; $i < count(json_decode($horario[0]['MedioDia'])); $i++) {
        //     $MD = json_decode($horario[0]['MedioDia']);
        //     array_push($diasHabituales, (int)$MD[$i]);
        // }

        // $diaNoPuesto = array_diff($dias, $diasHabituales);

        // for ($i = 0; $i < count($diaNoPuesto); $i++) {
        //     if ($diaActual2 == $diaNoPuesto[$i]) {
        //         try {
        //             for ($i=0; $i <count($users) ; $i++) {
        //                 Mail::to($users[$i]['email'])->send(new ConsultaPendienteMail($users[$i]['firstName']));
        //             }
        //         } catch (\Throwable $th) {
        //                 $error = $th;
        //         }

        //         return 'Correo enviado a usuarios HD - un dia que no se trabajo :D';
        //     }
        // }

        // $medioDia = json_decode($horario[0]['MedioDia']);

        // if (!is_null($horario[0]['MedioDia'])) {
        //     for ($i = 0; $i < count($medioDia); $i++) {

        //         if ($diaActual2 == $medioDia[$i]) {
        //             if (strtotime($horaActual2) >= strtotime($horario[0]['HorarioIngresoMD']) && strtotime($horaActual2) < strtotime($horario[0]['HorarioSalidaMD'])) {
        //                 return response()->json(compact('chat'), 201);

        //             } else {
        //                 try {
        //                     for ($i=0; $i <count($users) ; $i++) {
        //                         Mail::to($users[$i]['email'])->send(new ConsultaPendienteMail($users[$i]['firstName']));
        //                     }
        //                 } catch (\Throwable $th) {
        //                         $error = $th;
        //                 }
        //                 return 'Correo enviado a usuarios HD un medio dia :D';
        //             }
        //         }
        //     }
        // }

        // for ($i = 0; $i < count(json_decode($horario[0]['Dias'])); $i++) {
        //     if ($diaActual2 == json_decode($horario[0]['Dias'][$i])) {
        //         if (strtotime($horaActual2) >= strtotime($horario[0]['HorarioIngreso']) && strtotime($horaActual2) < strtotime($horario[0]['HorarioSalida'])) {
        //             return response()->json(compact('chat'), 201);

        //         } else {
        //             try {
        //                 for ($i=0; $i <count($users) ; $i++) {
        //                     Mail::to($users[$i]['email'])->send(new ConsultaPendienteMail($users[$i]['firstName']));
        //                 }
        //             } catch (\Throwable $th) {
        //                     $error = $th;
        //             }
        //             return 'Correo enviado a usuarios HD un dia de la semana D:';
        //         }
        //     }
        // }
        return response()->json(compact('chat'), 201);
    }



    public function recommendUser(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $userIds = json_decode($request->getContent(), true);
        $recommendationController = new RecommendationController();

        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        $newRecommendations = [];
        foreach ($userIds as $userId) {
            $new = [
                'idCompany' => $idCompany,
                'idEntryQuery' => $id,
                'recommendUser' => $userId,
                'recommendDate' => Carbon::now()->timestamp,
                'recommendBy' => $user->id,
                'status' => "Pendiente",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($newRecommendations, $new);
        }

        $recommendationController->registerMany($newRecommendations);

        return response()->json(['success' => 'Recomendaciones enviadas'], 201);
    }

    public function listFrequent(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $entryQueries = EntryQuery::where("isFrequent", true)->where("idCompany", $idCompany)->where("deleted", false);

        return $entryQueries->orderBy("name")->get();
    }

    public function updateFrequent(Request $request, $id)
    {
        $entryQuery = EntryQuery::find($id);

        if (!$entryQuery) {
            return response()->json(['error' => 'Consulta no encontrada'], 400);
        }

        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQuery->name = $request["name"];
        $entryQuery->isFrequent = $request["isFrequent"] = true;
        $entryQuery->save();

        $entryQuery->user = User::find($entryQuery->createdBy);
        if ($entryQuery->idCompany)
            $entryQuery->company = Company::find($entryQuery->idCompany);

        return response()->json(compact('entryQuery'), 201);
    }

    public function deleteImage(Request $request)
    {
        $imageLink = $request->imageLink;
        $entryQueryId = $request->id;

        $entryQuery = EntryQuery::find($entryQueryId);
        $image_path = storage_path() . '/app/public/' . "" . $imageLink;
        if (@getimagesize($image_path) && $entryQuery) {
            unlink($image_path);
            $entryQuery->image = null;
            $entryQuery->save();

            return response()->json(compact('entryQuery'), 201);
        } else {
            return response()->json(['error' => 'Consulta entrante no encontrada / Archivo no encontrado'], 400);
        }
    }

    public function deleteFile(Request $request)
    {
        $link = $request->link;
        $entryQueryId = $request->id;

        $entryQuery = EntryQuery::find($entryQueryId);
        $file_path = storage_path() . '/app/public/' . $link;
        if ($file_path && $entryQuery) {
            unlink($file_path);
            $entryQuery->file = null;
            $entryQuery->save();

            return response()->json(compact('entryQuery'), 201);
        } else {
            return response()->json(['error' => 'Consulta no encontrada / Archivo no encontrado'], 400);
        }
    }

    public function registerFrequent(Request $request)
    {
        $error = $this->validateFields($request);
        if ($error) {
            return response()->json($error, 400);
        }
        $user = Auth::user();
        if (!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        $entryQuery = new EntryQuery();
        $entryQuery->startDate = Carbon::now()->timestamp;
        $entryQuery->status = "Pendiente";
        $entryQuery->idCompany = $idCompany;
        $entryQuery->createdBy = $user->id;
        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        $entryQuery->image = $request["image"];
        $entryQuery->file = $request["file"];
        $entryQuery->idModule = $request["idModule"];
        $entryQuery->isFrequent = $request->isFrequent == true;

        $entryQuery->save();

        return response()->json(compact('entryQuery'), 201);
    }

    public function getEstadoConsulta($id)
    {
        try {
            $entryQuery = EntryQuery::find($id);
            return $entryQuery->status;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
