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

    public function updateParticipantValues($participant, $request){
        $participant->idUser = $request->idUser;
        $participant->idChat = $request->idChat;
        $participant->type = $request->type;
        $participant->erp = $request->erp;
        $participant->entryDate = $request->entryDate;
        $participant->outputDate = $request->outputDate;
        $participant->state = $request->state;
        $participant->active = $request->active;
        $participant->sendMessages = $request->sendMessages!=null ? $request->sendMessages : 0;
        $participant->receivedMessages = $request->receivedMessages!=null ? $request->receivedMessages : 0;
    }

    public function validateFields($request){
        $validator = Validator::make($request->all(), [
            'idUser' => 'integer',
            'idChat' => 'integer',
            'type' => 'string|max:255',
            'erp' => 'boolean',
            'entryDate' => 'nullable|string',
            'outputDate' => 'nullable|string',
            'state' => 'nullable|string',
            'active' => 'nullable|boolean',
            'sendMessages' => 'nullable|integer',
            'receivedMessages' => 'nullable|integer'
        ]);

        $errorMessage = null;
        if($validator->fails()){
            $errorMessage = $validator->errors()->toJson();
        }
        return $errorMessage;
    }
}
