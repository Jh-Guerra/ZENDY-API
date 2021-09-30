<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationViewed;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationViewedController extends Controller
{
    public function register(Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notificationViewed = new NotificationViewed();
        $notificationViewed->idNotification = $request->idNotification;
        $notificationViewed->viewedIdCompany =$user->idCompany;
        $notificationViewed->viewedBy =$user->id;
        $notificationViewed->viewedDate = Carbon::now()->timestamp;
        $notificationViewed->status = "Pendiente";

        $notificationViewed->save();

        return response()->json(compact('notificationViewed'),201);
    }

    public function registerMany($notificationsViewed){
        NotificationViewed::insert($notificationsViewed);
    }

    public function list($notificationId){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        return NotificationViewed::where("idNotification", $notificationId)->where("deleted", false)->get();
    }

    public function listByUser(){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);
        
        $notificationsViewed = NotificationViewed::join('notifications', 'notifications.id', 'notification_views.idNotification')->join('users', 'users.id', 'notification_views.viewedBy')
            ->where("notification_views.viewedBy", $user->id)->where("notification_views.status", "Pendiente")
            ->where('notification_views.deleted', false)
            ->get(["notification_views.*", "notifications.reason as reason", "notifications.description as description", "notifications.id as notificationId"]);


        return $notificationsViewed;
    }

    public function find($userId, $notificationId){
        $notificationViewed = NotificationViewed::where("viewedBy", $userId)->where("idNotification", $notificationId)->first();
        if (!$notificationViewed) return response()->json(['error' => 'Este usuario no ha visto la notificación'], 400);

        return response()->json(compact('notificationViewed'),201);
    }

    public function delete($id){
        $notificationViewed = NotificationViewed::find($id);
        if (!$notificationViewed) return response()->json(['error' => 'Registro no encontrado'], 400);

        $notificationViewed->deleted = true;
        $notificationViewed->save();

        return response()->json(['success' => 'Registro eliminado'], 201);
    }

}
