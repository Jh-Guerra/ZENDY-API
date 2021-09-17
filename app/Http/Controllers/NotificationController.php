<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function register(Request $request){
        $error = $this->validateFields($request);
        if ($error) return response()->json($error, 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $notification = new Notification();

        $notification->createdBy = $user->id;
        $notification->reason = $request->reason;
        $notification->description = $request->description;
        $notification->viewed = 0;
        $notification->allUsersCompany = $request->allUsersCompany;
        $notification->companiesNotified = json_encode($request->companiesNotified, true);
        $notification->usersNotified = json_encode($request->usersNotified, true);
        $notification->idError = $request->idError;
        $notification->solved = $request->solved;

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

        if($fileSaved) $entryQuery->save();

        return response()->json(compact('notification'),201);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'description' => 'required|string',
            'allUsersCompany' => 'required|boolean'
        ]);

        $errorMessage = null;
        if($validator->fails()){
            $errorMessage = $validator->errors()->toJson();
        }
        return $errorMessage;
    }

    public function adminList(){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        return Notification::where('createdBy', $user->id)->where('deleted', false)->get();
    }

    public function list(){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $userIdWithQuotes= "\"".$user->id."\"";
        return Notification::where("usersNotified", 'LIKE', '%'.$userIdWithQuotes.'%')->where('deleted', false)->get();
    }

    public function find($id){
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['error' => 'Notificación no encontrada'], 400);

        return $notification;
    }

    public function delete($id){
        $notification = Notification::find($id);
        if (!$notification) return response()->json(['error' => 'Notificación no encontrada'], 400);

        $notification->deleted = true;
        $notification->save();

        return response()->json(['success' => 'Notificación Eliminada'], 201);
    }

}
