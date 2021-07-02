<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{ 
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request){
        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        $message = new Message();
        $this->updateMessageValues($message, $request);
        $message->save();

        return response()->json(compact('message'),201);
    }

    public function update(Request $request, $id)
    {
        $message = Message::find($id);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        if(!$message){
            return response()->json(['error' => 'Mensaje no encontrado'], 400);
        }

        $this->updateMessageValues($message, $request);
        $message->save();

        return response()->json($message);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'codeMessage' => 'required|string|max:80',
            'idUser' => 'required|int',
            'idChat' => 'required|int',
            'dateSend' => 'string|timestamp',
            'message' => 'required|string',
            'numberViewed' => 'int',
            'forwarded' => 'int',
            'idUserOrigin' => 'required|int',
            'image' => 'string',
            'file' => 'string'
        ]);

        $errorMessage = null;
        if(!$validator->fails()){
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    private function updateMessageValues($message, $request){
        $message->codeMessage = $request->codeMessage;
        $message->idUser = $request->idUser;
        $message->idChat = $request->idChat;
        $message->dateSend = $request->dateSend;
        $message->message = $request->message;
        $message->numberViewed = $request->numberViewed;
        $message->forwarded = $request->forwarded;
        $message->idUserOrigin = $request->idUserOrigin;
        $message->image = $request->image;
        $message->file = $request->file;
    }

    public function delete($id){
        $message = Message::find($id);

        if(!$message){
            return response()->json(['error' => 'Mensaje no encontrado'], 400);
        }

        $message->deleted = true;
        $message->save();

        return response()->json(['success' => 'Mensaje Eliminado'], 201);
    }

    public function list(){
        $messages = Message::all();

        return response()->json(compact('messages'),201);
    }

    public function find($id){
        $message = Message::find($id);

        if(!$message){
            return response()->json(['error' => 'Mensaje no encontrado'], 400);
        }

        return response()->json(compact('message'),201);
    }

}
