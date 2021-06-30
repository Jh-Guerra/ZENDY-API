<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParticipantController extends Controller
{
    public function register(Request $request)
    {
        $error = $this->validateFields($request);

        if($error){
            return response()->json($error, 400);
        }

        $participant = new Participant();

        $this->updateParticipantValues($participant, $request);

        $participant->save();

        return response()->json(compact('participant'),201);
    }

    public function update(Request $request, $id)
    {
        $participant = Participant::find($id);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        if(!$participant){
            return response()->json(['error' => 'Participante no encontrado'], 400);
        }

        $this->updateParticipantValues($participant, $request);
        $participant->save();

        return response()->json($participant);
    }

    public function find($id)
    {
        $participant = Participant::find($id);

        if(!$participant){
            return response()->json(['error' => 'Participante no encontrado'], 400);
        }

        return response()->json(compact('participant'),201);
    }

    public function list()
    {
        $participants = Participant::all();

        return response()->json(compact('participants'),201);
    }

    public function delete($id)
    {
        $participant = Participant::find($id);

        if(!$participant){
            return response()->json(['error' => 'Participante no encontrado'], 400);
        }

        $participant->delete();

        return response()->json(['success' => 'Participante Eliminado'], 201);
    }

    private function updateParticipantValues($participant, $request){
        $participant->codeCompetitor = $request->codeCompetitor;
        $participant->idUser = $request->idUser;
        $participant->idChat = $request->idChat;
        $participant->role = $request->role;
        $participant->ERP = $request->ERP;
        $participant->dateAdmission = $request->dateAdmission;
        $participant->departureDate = $request->departureDate;
        $participant->state = $request->state;
        $participant->active = $request->active;
        $participant->numberMessageSend = $request->numberMessageSend;
        $participant->numberMessageRrceived = $request->numberMessageRrceived;
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'codeCompetitor' => 'nullable|integer',
            'idUser' => 'nullable|string|max:255',
            'idChat' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'ERP' => 'nullable|integer',
            'dateAdmission' => 'nullable|date',
            'departureDate' => 'nullable|date',
            'state' => 'nullable|integer',
            'active' => 'nullable|integer',
            'numberMessageSend' => 'nullable|integer',
            'numberMessageRrceived' => 'nullable|integer'
        ]);

        $errorMessage = null;
        if($validator->fails()){
            $errorMessage = $validator->errors()->toJson();
        }
        return $errorMessage;
    }
}
