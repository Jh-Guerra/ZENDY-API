<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
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
        $user->isOnline = '1';
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

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController;
            $path = $tasks_controller->updateProfilePicture($request);
            $user->avatar = $path;
        }

        $user->save();

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

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController;
            $path = $tasks_controller->updateProfilePicture($request);
            $user->avatar = $path;
        }

        $user->save();

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
        $user->avatar = $request->avatar;
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
        $start = 0;
        $limit = 50;
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $roles = $request->has("roles") ? $request->get("roles") : ["UserEmpresa"];
        $term = $request->has("term") ? $request->get("term") : "";
        $users = User::join('roles', 'users.idRole', '=', 'roles.id')->where('users.deleted', false)
            ->where('users.id', '!=', $user->id)
            ->whereIn('roles.name', $roles);

        $this->searchUser($users, $term);

        $users->offset($start * $limit)->take($limit);

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);
        $this->addObjectValues($users);

        return $users;
    }

    public function listByCompany(Request $request){
        $start = 0;
        $limit = 50;

        $term = $request->has('term') ? $request->get('term') : '';
        $company = $request->has('company') ? $request->get('company') : '';

        if (!$company) {
            return response()->json(['error' => 'Empresa no encontrada'], 400);
        }

        $users = User::where('deleted', '!=', true);

        $this->searchUserByCompany($users, $company);
        $this->searchUser($users, $term);

        $users->offset($start * $limit)->take($limit);

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

    public function searchUserByCompany($users, $company){
        if ($company) {
            $users->where(function ($query) use ($company) {
                $query->where('idCompany', '=', $company);
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
        return User::where('isOnline', '!=', 0)->orderBy("LastName")->get();
    }


    public function updateUserOffLine(Request $request, $id){

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $user->isOnline = $request->isOnline = 0;
        $user->save();

        return response()->json($user);
    }

    public function updateUserOnLine(Request $request, $id){

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $user->isOnline = $request->isOnline = 1;
        $user->save();

        return response()->json($user);
    }

}

