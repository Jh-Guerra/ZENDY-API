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

class NotificationController extends Controller
{
    public function registerCompanyNotification(Request $request){
        $error = $this->validateFields($request);
        if ($error) return response()->json($error, 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notification = new Notification();

        $notification->idCompany = $user->idCompany;
        $notification->byAdmin = $user->idRole == 1;
        $notification->createdBy = $user->id;
        $notification->reason = $request["reason"];
        $notification->description = $request["description"];
        $notification->viewed = 0;
        $notification->allUsersCompany = $request["allUsersCompany"];
        $notification->idError = $request["idError"];
        $notification->solved = $request["solved"];

        $companiesIds = array_map('intval', $request->companiesNotified);
        $notification->companiesNotified = json_encode($companiesIds, true);

        $userIds = array_map('intval', $request->usersNotified);
        $notification->usersNotified = json_encode($userIds, true);

        $notification->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $uploadImageController->updateFile($request->file('image'), "notifications/".$notification->id, "image_".Carbon::now()->timestamp);
            $notification->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $uploadImageController->updateFile($request->file('file'), "notifications/".$notification->id, "file_".Carbon::now()->timestamp);
            $notification->file = $path;
            $fileSaved = true;
        }

        if($fileSaved) $notification->save();

        $notification->companiesNotified = json_decode($notification->companiesNotified, true);
        $notification->usersNotified = json_decode($notification->usersNotified, true);

        $notificationViewedController = new NotificationViewedController();
        $notificationsViewed = [];
        $users = User::whereIn("id", $userIds)->get();

        foreach ($users as $u) {
            $newValue = [
                'idNotification' => $notification->id,
                'viewedIdCompany' => $u->idCompany,
                'viewedBy' => $u->id,
                'status' => "Pendiente",
                'deleted' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($notificationsViewed, $newValue);
        }

        $notificationViewedController->registerMany($notificationsViewed);

        return response()->json(compact('notification'),201);
    }

    public function registerCompaniesNotification(Request $request){
        $error = $this->validateFields($request);
        if ($error) return response()->json($error, 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notification = new Notification();

        $notification->idCompany = $user->idCompany;
        $notification->byAdmin = $user->idRole == 1;
        $notification->createdBy = $user->id;
        $notification->reason = $request["reason"];
        $notification->description = $request["description"];
        $notification->viewed = 0;
        $notification->allUsersCompany = true;
        $notification->idError = $request["idError"];
        $notification->solved = $request["solved"];

        $companiesIds = array_map('intval', $request->companiesNotified);
        $notification->companiesNotified = json_encode($companiesIds, true);

        $userIds = User::whereIn("idCompany", $companiesIds)->where("deleted", false)->pluck("id");
        $notification->usersNotified = json_encode($userIds, true);

        $notification->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $uploadImageController->updateFile($request->file('image'), "notifications/".$notification->id, "image_".Carbon::now()->timestamp);
            $notification->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $uploadImageController->updateFile($request->file('file'), "notifications/".$notification->id, "file_".Carbon::now()->timestamp);
            $notification->file = $path;
            $fileSaved = true;
        }

        if($fileSaved) $notification->save();

        $notification->companiesNotified = json_decode($notification->companiesNotified, true);
        $notification->usersNotified = json_decode($notification->usersNotified, true);

        $notificationViewedController = new NotificationViewedController();
        $notificationsViewed = [];
        $users = User::whereIn("id", $userIds)->get();

        foreach ($users as $u) {
            $newValue = [
                'idNotification' => $notification->id,
                'viewedIdCompany' => $u->idCompany,
                'viewedBy' => $u->id,
                'status' => "Pendiente",
                'deleted' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($notificationsViewed, $newValue);
        }

        $notificationViewedController->registerMany($notificationsViewed);

        return response()->json(compact('notification'),201);
    }

    public function updateNotification($id, Request $request){
        $error = $this->validateFields($request);
        if ($error) return response()->json($error, 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notification = Notification::find($id);
        if(!$notification) return response()->json(['error' => 'Notificación no encontrada'], 400);

        $notification->idCompany = $user->idCompany;
        $notification->byAdmin = $user->idRole == 1;
        $notification->createdBy = $user->id;
        $notification->reason = $request->reason;
        $notification->description = $request->description;
        $notification->idError = $request->idError;
        $notification->solved = $request->solved;

        if($request->image){
            if($request->oldImage){
                $newImage = substr($request->oldImage, 8);
                $image_path = storage_path().'/app/public/'."".$newImage;
                if (@getimagesize($image_path)){
                    unlink($image_path);
                }
            }
            $notification->image = $request->image;
        }

        $companiesIds = array_map('intval', $request->companiesNotified);
        $notification->companiesNotified = json_encode($companiesIds, true);

        $userIds = User::whereIn("idCompany", $companiesIds)->where("deleted", false)->pluck("id");
        $notification->usersNotified = json_encode($userIds, true);

        $notification->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $uploadImageController->updateFile($request->file('image'), "notifications/".$notification->id, "image_".Carbon::now()->timestamp);
            $notification->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $uploadImageController->updateFile($request->file('file'), "notifications/".$notification->id, "file_".Carbon::now()->timestamp);
            $notification->file = $path;
            $fileSaved = true;
        }

        if($fileSaved) $notification->save();

        $notification->companiesNotified = json_decode($notification->companiesNotified, true);
        $notification->usersNotified = json_decode($notification->usersNotified, true);

        return response()->json(compact('notification'),201);
    }

    public function updateListUsersNotified($id, Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notification = Notification::find($id);
        if(!$notification) return response()->json(['error' => 'Notificación no encontrada'], 400);

        $request = json_decode($request->getContent(), true);
        $userIds = $request["usersNotified"];

        $notificationViewedController = new NotificationViewedController();
        $notificationsViewed = [];
        $users = User::whereIn("id", $userIds)->get();

        foreach ($users as $u) {
            $newValue = [
                'idNotification' => $notification->id,
                'viewedIdCompany' => $u->idCompany,
                'viewedBy' => $u->id,
                'status' => "Pendiente",
                'deleted' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($notificationsViewed, $newValue);
        }

        $notificationViewedController->registerMany($notificationsViewed);
        $notification->usersNotified = json_decode($notification->usersNotified, true);
        $newNotifications = array_merge($notification->usersNotified, $userIds);
        $notification->usersNotified = json_encode($newNotifications, true);

        $notification->save();

        $notification->usersNotified = json_decode($notification->usersNotified, true);

        return response()->json(compact('notification'),201);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        $errorMessage = null;
        if($validator->fails()){
            $errorMessage = $validator->errors()->toJson();
        }
        return $errorMessage;
    }

    public function adminList(Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notifications = Notification::where("byAdmin", true)->where('deleted', false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $notifications->where(function ($query) use ($term) {
                $query->where('reason', 'LIKE', '%' . $term . '%');
            });
        }

        $notifications = $notifications->orderBy("created_at", "desc")->get();

        foreach ($notifications as $notification) {
            $notification->companiesNotified = json_decode($notification->companiesNotified, true);
            $notification->usersNotified = json_decode($notification->usersNotified, true);
        }

        return $notifications;
    }

    public function listNotificationsByCompany(Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notificationsIds = NotificationViewed::where("viewedIdCompany", $user->idCompany)->where('deleted', false)->orderBy("created_at", "desc")->pluck("idNotification");
        $notifications = Notification::whereIn("id", $notificationsIds)->where("deleted", false);
        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $notifications->where(function ($query) use ($term) {
                $query->where('reason', 'LIKE', '%'.$term.'%');
            });
        }
        $notifications = $notifications->orderBy("created_at", "desc")->get();

        foreach ($notifications as $notification) {
            $notification->companiesNotified = json_decode($notification->companiesNotified, true);
            $notification->usersNotified = json_decode($notification->usersNotified, true);
        }

        return $notifications;
    }

    public function listNotificationsByUser(Request $request, $status){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        if(!$status){
            $status = "Pendiente";
        }

        $notificationsIds = NotificationViewed::where("viewedIdCompany", $user->idCompany)->where("viewedBy", $user->id)->where("status",'=',$status)->where('deleted', false)->orderBy("created_at", "desc")->pluck("idNotification");
        $notifications = Notification::whereIn("id", $notificationsIds)->where("deleted", false);
        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $notifications->where(function ($query) use ($term) {
                $query->where('reason', 'LIKE', '%' . $term . '%');
            });
        }
        $notifications = $notifications->orderBy("created_at", "desc")->get();

        foreach ($notifications as $notification) {
            $notification->companiesNotified = json_decode($notification->companiesNotified, true);
            $notification->usersNotified = json_decode($notification->usersNotified, true);
        }

        return $notifications;
    }

    public function find($id){
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['error' => 'Notificación no encontrada'], 400);

        $notification->companiesNotified = json_decode($notification->companiesNotified, true);
        $notification->usersNotified = json_decode($notification->usersNotified, true);

        return response()->json(compact('notification'),201);
    }

    public function delete($id){
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['error' => 'Notificación no encontrada'], 400);

        $notification->deleted = true;
        $notification->save();

        NotificationViewed::where('idNotification', $id)->update(['deleted' => true]);

        return response()->json(['success' => 'Notificación Eliminada'], 201);
    }

    public function deleteImage(Request $request){
        $imageLink = $request->imageLink;
        $notificationId = $request->id;

        $notification = Notification::find($notificationId);
        $image_path = storage_path().'/app/public/'."".$imageLink;
        if (@getimagesize($image_path) && $notification){
            unlink($image_path);
            $notification->image = null;
            $notification->save();

            return response()->json(compact('notification'),201);
        }else{
            return response()->json(['error' => 'Notificación no encontrada / Archivo no encontrado'], 400);
        }
    }

    public function updateListCompaniesNotified($id, Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notification = Notification::find($id);
        if(!$notification) return response()->json(['error' => 'Notificación no encontrada'], 400);

        
        $request = json_decode($request->getContent(), true);
        $companiesIds = $request["companiesNotified"];

        $userIds = User::whereIn("idCompany", $companiesIds)->where("deleted", false)->pluck("id");

        $notificationViewedController = new NotificationViewedController();
        $notificationsViewed = [];
        $users = User::whereIn("id", $userIds)->get();

        foreach ($users as $u) {
            $newValue = [
                'idNotification' => $notification->id,
                'viewedIdCompany' => $u->idCompany,
                'viewedBy' => $u->id,
                'status' => "Pendiente",
                'deleted' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($notificationsViewed, $newValue);
        }

        $notificationViewedController->registerMany($notificationsViewed);
        
        $notification->companiesNotified = json_decode($notification->companiesNotified, true);
        $newNotifications = array_merge($notification->companiesNotified, $companiesIds);
        $notification->companiesNotified = json_encode($newNotifications, true);



        $notification->usersNotified  = json_decode($notification->usersNotified , true);
        $newNotifications1 = array_merge($notification->usersNotified ,$userIds->toArray());
        $notification->usersNotified  = json_encode($newNotifications1, true); 


        $notification->save();

        $notification->companiesNotified = json_decode($notification->companiesNotified, true);
        $notification->usersNotified = json_decode($notification->usersNotified, true);

        return response()->json(compact('notification'),201);
    }
}
