<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{

    public function authenticate(Request $request){
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt(['email' => $credentials["email"], 'password' => $credentials["password"], 'deleted' => false])) {
                return response()->json(['error' => 'Credenciales inválidas'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user = Auth::user();
        $user->isOnline = true;
        $user->save();

        $role = Role::find($user->idRole);
        $role->permissions = json_decode($role->permissions, true);

        return response()->json(compact('token', 'user', 'role'));
    }

    public function getAuthenticatedUser(){
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }

        return response()->json(compact('user'));
    }


    public function register(Request $request){
        $error = $this->validateFields($request);
        if ($error) {
            return response()->json($error, 400);
        }

        $user = new User();
        $this->updateUserValues($user, $request);
        $user->password = Hash::make($request->password);
        $user->save();

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController;
            $user->avatar = $tasks_controller->updateFile($request->file('image'), "users/avatar", $user->id."_".Carbon::now()->timestamp);
            $user->save();
        }

        $token = JWTAuth::fromUser($user); // ??

        return response()->json(compact('user', 'token'), 201);
    }//

    public function update(Request $request, $id){

        $user = User::find($id);

        $error = $this->validateFieldsUpdate($request);
        if ($error) {
            return response()->json($error, 400);
        }

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $this->updateUserValues($user, $request);
        $user->save();

        if($request->oldImage){
            $newImage = substr($request->oldImage, 8);
            $image_path = storage_path().'/app/public/'."".$newImage;
            if (@getimagesize($image_path)){
                unlink($image_path);
            }
        }

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController;
            $user->avatar = $tasks_controller->updateFile($request->file('image'), "users/avatar", $user->id."_".Carbon::now()->timestamp);
            $user->save();
        }

        return response()->json($user);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:80',
            'lastName' => 'required|string|max:80',
            'email' => 'required|string|email|max:255',
            'phone' => 'string|max:15',
            'dob' => 'required|string',
            'idRole' => 'required|string',
            'idCompany' => 'nullable|int'
        ]);

        $errorMessage = null;
        if (!$validator->fails()) {
            $user = User::where('email', $request->email)->where('deleted', false)->first();
            if ($user && $user->id != $request->id) {
                $errorMessage = new \stdClass();
                $errorMessage->email = [
                    "El correo electrónico ya está registrado."
                ];
            }
        } else {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    private function validateFieldsUpdate($request){
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:80',
            'lastName' => 'required|string|max:80',
            'email' => 'required|string|email|max:255',
            'phone' => 'string|max:15',
            'dob' => 'required|string',
            'idRole' => 'required|string',
            'idCompany' => 'nullable|int',
        ]);
        $errorMessage = null;

        return $errorMessage;
    }

    private function updateUserValues($user, $request){
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->email = $request->email;
        $user->sex = $request->sex;
        $user->phone = $request->phone;
        $user->dob = date('Y-m-d', strtotime($request->dob));
        $user->idRole = $request->idRole;
        $user->idCompany = $request->idCompany;
        if($request->avatar){
            $user->avatar = $request->avatar;
        }
    }

    public function find($id){
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        return $user;
    }

    public function list(Request $request){
        $start = 0;
        $limit = 50;

        $term = $request->has("term") ? $request->get("term") : "";

        $users = User::join('roles', 'users.idRole', '=', 'roles.id')
            ->where('users.deleted', '!=', true);
        $this->searchUser($users, $term);

        $users->offset($start * $limit)->take($limit);

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);
        $this->addObjectValues($users);

        return $users;
    }

    public function addObjectValues($users){
        $companyIds = [];
        foreach ($users as $user) {
            if ($user->idCompany && !in_array($user->idCompany, $companyIds))
                $companyIds[] = $user->idCompany;

        }
        if (count($companyIds) > 0) {
            $companies = Company::whereIn('id', $companyIds)->get(["companies.id", "companies.name"])->keyBy('id');

            foreach ($users as $user) {
                if ($user->idCompany)
                    $user->company = $companies[$user->idCompany];

            }
        }
    }

    public function listAvailable(Request $request){
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $roles = $request->has("roles") ? $request->get("roles") : [];
        $users = User::join('roles', 'roles.id', '=', 'users.idRole')->where('users.deleted', false)
            ->where('users.id', '!=', $user->id)
            ->whereIn('roles.name', $roles);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $users->where(function ($query) use ($term) {
                $query->where('firstName', 'LIKE', '%' . $term . '%')
                    ->orWhere('lastName', 'LIKE', '%' . $term . '%');
            });
        }

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);
        $this->addObjectValues($users);

        return $users;
    }

    public function listAdmins(Request $request){
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $users = User::join('roles', 'roles.id', '=', 'users.idRole')->where('users.deleted', false)
            ->where('users.id', '!=', $user->id)
            ->where('roles.name', "Admin");

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $users->where(function ($query) use ($term) {
                $query->where('firstName', 'LIKE', '%' . $term . '%')
                    ->orWhere('lastName', 'LIKE', '%' . $term . '%');
            });
        }

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);

        return $users;
    }

    public function listByCompany($idCompany, Request $request){
        $term = $request->has('term') ? $request->get('term') : '';

        if (!$idCompany) return response()->json(['error' => 'Seleccione una empresa'], 400);

        $users = User::where('idCompany', $idCompany)->where('deleted', false);

        if($term){
            $users->where(function ($query) use ($term) {
                $query->where('firstName', 'LIKE', '%' . $term . '%')
                    ->orWhere('lastName', 'LIKE', '%' . $term . '%');
            });
        }

        return $users->orderBy("firstName")->orderBy("lastName")->get();
    }

    public function searchUser($users, $term){
        if ($term) {
            $users->where(function ($query) use ($term) {
                $query->where('firstName', 'LIKE', '%' . $term . '%')
                    ->orWhere('lastName', 'LIKE', '%' . $term . '%');
            });
        }
    }

    public function delete($id){
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $user->deleted = true;
        $user->save();

        return response()->json(['success' => 'Usuario Eliminado'], 201);
    }

    public function listUserOnline(){
        return User::where('isOnline', '!=', false)->orderBy("LastName")->get();
    }


    public function updateUserOffLine(Request $request, $id){

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $user->isOnline = false;
        $user->save();

        return response()->json($user);
    }

    public function updateUserOnLine(Request $request, $id){

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $user->isOnline = true;
        $user->save();

        return response()->json($user);
    }

    // List getUserCompany
    public function listAvailableSameCompany(Request $request){
        $start = 0;
        $limit = 50;
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $roles = $request->has("roles") ? $request->get("roles") : [];
        $term = $request->has("term") ? $request->get("term") : "";
        //$company = $request->has("idCompany") ? $request->get("idCompany") : "";
        $users = User::join('roles', 'users.idRole', '=', 'roles.id')->where('users.deleted', false)
            ->where('users.idCompany','=',$user->idCompany)
            ->where('users.id', '!=', $user->id)
            ->whereIn('roles.name', $roles);

        $this->searchUser($users, $term);

        $users->offset($start * $limit)->take($limit);

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);
        $this->addObjectValues($users);

        return $users;
    }

    public function deleteImage(Request $request){
        $imageLink = $request->imageLink;
        $userId = $request->id;

        $user = User::find($userId);
        $image_path = storage_path().'/app/public/'."".$imageLink;
        if (@getimagesize($image_path) && $user){
            unlink($image_path);
            $user->avatar = null;
            $user->save();

            return response()->json(compact('user'),201);
        }else{
            return response()->json(['error' => 'Usuario no encontrada / Archivo no encontrado'], 400);
        }
    }

    public function listSameCompany(Request $request){
        $start = 0;
        $limit = 50;
        $user = Auth::user();
        $term = $request->has("term") ? $request->get("term") : "";

        $users = User::join('roles', 'users.idRole', '=', 'roles.id')->where('users.idCompany','=',$user->idCompany)
            ->where('users.deleted', '!=', true);
        $this->searchUser($users, $term);

        $users->offset($start * $limit)->take($limit);

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);
        $this->addObjectValues($users);

        return $users;
    }
}

