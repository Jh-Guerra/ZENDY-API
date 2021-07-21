<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\NotificationView;

class NotificationViewController extends Controller
{
    public function register(Request $request){

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }
        $notificationView = new NotificationView();
        $this->updateNotifactionViewValues($notificationView, $request);
        $notificationView->save();
        return response()->json($notificationView,201);
    }

    public function update(Request $request, $id){
        $notificationView = NotificationView::find($id);
        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }
        if(!$notificationView){
            return response()->json(['error' => 'NotificacionView no encontrada'], 400);
        }
        $this->updateNotifactionViewValues($notificationView, $request);
        $notificationView->save();
        return response()->json($notificationView);
    }

    public function list(){
        return NotificationView::all();
    }

    /* ************************************************************************* */

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'codeNotificationView'  => 'required|string|max:255',
            'idNotification' => 'required|int',
            'idUserCompany' => 'required|int',
            'receptionDate' => 'timestamp',
            'Viewed' => 'required|int'
        ]);
        $errorMessage = null;
        if($validator->fails()){
                $errorMessage = $validator->errors()->toJson();
        }
        return $errorMessage;
    }
    private function updateNotifactionViewValues($notificationView, $request){
        $notificationView->codeNotificationView = $request->codeNotificationView;
        $notificationView->idNotification = $request->idNotification;
        $notificationView->idUserCompany = $request->idUserCompany;
        $notificationView->receptionDate = $request->receptionDate;
        $notificationView->Viewed = $request->Viewed;
        
    }

    public function find($id){
        $notificationView = NotificationView::find($id);
        if(!$notificationView){
            return response()->json(['error' => 'NotificacionView  no encontrada'], 400);
        }
        return $notificationView;
    }

    public function delete($id){
        $notificationView = NotificationView::find($id);
        if(!$notificationView){
            return response()->json(['error' => 'NotificacionView  no encontrada'], 400);
        }
        $notificationView->delete();
        return response()->json(['success' => 'NotificacionView Eliminada'], 201);
    }
}
