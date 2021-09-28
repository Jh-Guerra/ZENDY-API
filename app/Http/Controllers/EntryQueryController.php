<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\EntryQuery;
use App\Models\Participant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EntryQueryController extends Controller
{
    public function register(Request $request){
        $error = $this->validateFields($request);
        if ($error) {
            return response()->json($error, 400);
        }
        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        //$request = json_decode($request->getContent(), true);

        $entryQuery = new EntryQuery();
        $entryQuery->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $entryQuery->status = "Pendiente";
        $entryQuery->idCompany = $user->idCompany;
        $entryQuery->createdBy = $user->id;
        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        $entryQuery->image = $request["image"];
        $entryQuery->file = $request["file"];
        $entryQuery->idModule = $request["idModule"];
        $entryQuery->idFrequentQuery = $request["idFrequentQuery"];
        $entryQuery->isFrequentQuery = $request->isFrequentQuery == true;

        $entryQuery->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $uploadImageController->updateFile($request->file('image'), "entry_queries/".$entryQuery->id, "image_".Carbon::now()->timestamp);
            $entryQuery->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $uploadImageController->updateFile($request->file('file'), "entry_queries/".$entryQuery->id, "file_".Carbon::now()->timestamp);
            $entryQuery->file = $path;
            $fileSaved = true;
        }

        if($fileSaved){
            $entryQuery->save();
        }

        return response()->json(compact('entryQuery'),201);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'reason' => 'required',
            'description' => 'required',
        ]);


        $errorMessage = null;
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    public function find($id){
        $entryQuery = EntryQuery::find($id);
        if(!$entryQuery) return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $entryQuery->user = User::find($entryQuery->createdBy);
        if($entryQuery->idCompany)
            $entryQuery->company = Company::find($entryQuery->idCompany);

        return response()->json(compact('entryQuery'),201);
    }

    public function list(Request $request){
        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQueries = EntryQuery::where("createdBy", $user->id)->where("deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $this->search($entryQueries, $term);
        }

        $entryQueries->get();

        return response()->json(compact('entryQueries'),201);
    }

    public function listPendings(Request $request){
        $entryQueries = EntryQuery::join('users', 'entry_queries.createdBy', '=', 'users.id')->where("status", "Pendiente")->where("entry_queries.deleted", false);
        $term = $request->has("term") ? $request->get("term") : "";
        if($term)
            $this->search($entryQueries, $term);

        $entryQueries = $entryQueries->orderByDesc("startDate")->get(['entry_queries.*', 'users.firstName AS firstName','users.lastName AS lastName' ,'users.avatar AS avatar','users.sex AS sex']);

        return $entryQueries->values()->all();;
    }

    public function search($entryQueries, $term){
        if($term){
            $entryQueries->where(function ($query) use ($term) {
                $query->where('reason', 'LIKE', '%'.$term.'%');
            });
        }
    }

    public function delete($id){
        $entryQuery = EntryQuery::find($id);
        if(!$entryQuery)
            return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $entryQuery->deleted = true;
        $entryQuery->save();

        return response()->json(compact('entryQuery'),201);
    }

    public function listQuery(Request $request, $status){
        $user = Auth::user();
        if(!$user)
        return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQueries = EntryQuery::where("deleted", false)->where("createdBy", '=',$user->id)->where("status",'=',$status);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term)
            $this->search($entryQueries, $term);

        $entryQueries = $entryQueries->orderByDesc("startDate")->get();

        return $entryQueries->values()->all();;
    }

    public function update(Request $request, $id){
        $entryQuery = EntryQuery::find($id);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        if(!$entryQuery){
            return response()->json(['error' => 'Consulta no encontrada'], 400);
        }

        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        $entryQuery->image = $request["image"];
        $entryQuery->file = $request["file"];
        $entryQuery->idModule = $request["idModule"];
        $entryQuery->idFrequentQuery = $request["idFrequentQuery"];
        $entryQuery->isFrequentQuery = $request["isFrequentQuery"];
        $entryQuery->save();

        $tasks_controller = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $tasks_controller->updateFile($request->file('image'), "entry_queries/".$entryQuery->id, "image_".Carbon::now()->timestamp);
            $entryQuery->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $tasks_controller->updateFile($request->file('file'), "entry_queries/".$entryQuery->id, "file_".Carbon::now()->timestamp);
            $entryQuery->file = $path;
            $fileSaved = true;
        }

        if($fileSaved){
            $entryQuery->save();
        }
        $entryQuery->user = User::find($entryQuery->createdBy);
        if($entryQuery->idCompany)
            $entryQuery->company = Company::find($entryQuery->idCompany);




        return response()->json(compact('entryQuery'),201);
    }

    public function accept($id, Request $request){
        $request = json_decode($request->getContent(), true);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQuery = EntryQuery::find($id);
        if(!$entryQuery) return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $queryUser = User::find($entryQuery->createdBy);
        if(!$user) return response()->json(['error' => 'No se encontró al usuario que realizó la consulta.'], 400);

        $userParticipations = Participant::join('chats', 'chats.id', 'participants.idChat')->where("participants.idUser", $user->id)->where("participants.deleted", false)
            ->get(['participants.*', 'chats.scope AS chatScope']);
        $queryUserParticipations = Participant::where("idUser", $queryUser->id)->where("deleted", false)->pluck("idChat")->toArray();

        $thereVigentChat = false;
        foreach ($userParticipations as $userParticipation) {
            if(in_array($userParticipation->chatId, $queryUserParticipations) && $userParticipation->chatScope == "Personal"){
                $thereVigentChat = true;
            }
        }

        if($thereVigentChat) return response()->json(['error' => 'Este usuario ya cuenta con una consulta activa.'], 400);

        $entryQuery->acceptDate = Carbon::now()->timestamp;
        $entryQuery->status = "Aceptado";
        $entryQuery->acceptedBy = $user->id;
        $entryQuery->byRecommend = $request["byRecommend"];
        $entryQuery->save();

        $chat = new Chat();
        $chat->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $chat->type = "Consulta";
        $chat->scope = "Personal";
        $chat->status = "Vigente";
        $chat->idCompany = $queryUser->idCompany;
        $chat->idUser = $user->id;
        $chat->allUsers = 0;
        $chat->messages = 0;
        $chat->recommendations = 0;
        $chat->idEntryQuery = $entryQuery->id;
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

        $chat->name = $queryUser->firstName." ".$queryUser->lastName;
        return response()->json(compact('chat'),201);
    }

    public function recommendUser(Request $request, $id){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $userIds = json_decode($request->getContent(), true);
        $recommendationController = new RecommendationController();

        $newRecommendations = [];
        foreach ($userIds as $userId) {
            $new = [
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
}
