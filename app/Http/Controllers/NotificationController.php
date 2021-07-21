<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function register(Request $request){

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        $notification = new Notification();
        $this->updateNotifactionValues($notification, $request);
        $notification->save();

        return response()->json($notification,201);
    }
    public function update(Request $request, $id){
        $notification = Notification::find($id);
        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }
        if(!$notification){
            return response()->json(['error' => 'Notificacion no encontrada'], 400);
        }
        $this->updateNotifactionValues($notification, $request);
        $notification->save();
        return response()->json($notification);
    }


    public function list(){
        return Notification::all();
    }
    

    /* ************************************************************************* */

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'codeNotification' => 'required|string|max:255',
            'idUserERP' => 'required|int',
            'tittle'  => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'image1' => 'string|max:255',
            'image2' => 'string|max:255',
            'numberViewed' => 'int|nullable',
            'userNotified' => 'multiLineString|nullable',
            'creationDate' => 'timestamps|nullable',
            'idError' => 'int|nullable',
            'errorSolved'=> 'int|nullable'

        ]);

        $errorMessage = null;
        if($validator->fails()){
                $errorMessage = $validator->errors()->toJson();
        }
        return $errorMessage;
    }

    private function updateNotifactionValues($notification, $request){
        $notification->codeNotification = $request->codeNotification;
        $notification->idUserERP = $request->idUserERP;
        $notification->tittle = $request->tittle;
        $notification->description = $request->description;
        $notification->image1 = $request->image1;
        $notification->image2 = $request->image2;
        $notification->numberViewed = $request->numberViewed;
        $notification->userNotified = $request->userNotified;
        $notification->creationDate = $request->creationDate    ;
    }
    public function find($id){
        $notification = Notification::find($id);
        if(!$notification){
            return response()->json(['error' => 'Notificacion  no encontrada'], 400);
        }
        return $notification;
    }

    public function delete($id){
        $notification = Notification::find($id);
        if(!$notification){
            return response()->json(['error' => 'Notificacion  no encontrada'], 400);
        }
        $notification->delete();
        return response()->json(['success' => 'Notificacion Eliminada'], 201);
    }

}
