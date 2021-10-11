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
        $notificationViewed->viewedBy = $user->id;
        $notificationViewed->viewedDate = Carbon::now()->timestamp;
        $notificationViewed->status = "Pendiente";

        $notificationViewed->save();

        return response()->json(compact('notificationViewed'),201);
    }

    public function registerViewed(Request $request, $id){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notificationViewed = NotificationViewed::where("idNotification", $id)->where("viewedBy", $user->id)->where("deleted", false)->first();
        if(!$notificationViewed) return response()->json(['error' => 'Usted no fue incluido en esta notificación'], 400);

        if($notificationViewed->status == "Visto") return response()->json(compact('notificationViewed'),201);

        $notificationViewed->viewedDate = Carbon::now()->timestamp;
        $notificationViewed->status = "Visto";
        $notificationViewed->save();

        return response()->json(compact('notificationViewed'),201);
    }

    public function registerMany($notificationsViewed){
        NotificationViewed::insert($notificationsViewed);
    }

    public function list($notificationId){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $listNotificationsViewed = NotificationViewed::where("idNotification", $notificationId)->join('companies', 'companies.id', "=", 'notification_views.viewedIdCompany')->join('users as userOne', 'userOne.id', "=", 'notification_views.viewedBy')
            ->join('roles as rolesOne', 'userOne.idRole', "=", 'rolesOne.id')
            ->where('notification_views.deleted', false)
            ->orderByDesc("notification_views.status")
            ->orderBy("userOne.firstName")
            ->orderBy("userOne.LastName")
            ->get(["notification_views.*", "userOne.firstName AS firstName","userOne.lastName AS lastName", "rolesOne.name as rol", "companies.name as companyName", "userOne.email as email"]);

        return $listNotificationsViewed;
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
