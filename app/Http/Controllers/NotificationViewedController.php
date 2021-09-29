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
        $notificationViewed->viewedBy =$user->id;
        $notificationViewed->viewedDate = Carbon::now()->timestamp;

        $notificationViewed->save();

        return response()->json(compact('notificationViewed'),201);
    }

    public function list($notificationId){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        return NotificationViewed::where("idNotification", $notificationId)->where("deleted", false)->get();
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
