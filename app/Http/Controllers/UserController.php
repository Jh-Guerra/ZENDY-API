<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\UserCompany;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{

    public function authenticate(Request $request){
        $credentials = $request->only('username', 'password', 'rut');

        try {
            if($credentials["rut"]){
                $userCompany = UserCompany::where("username", $credentials["username"])->where("rutCompany", $credentials["rut"])->where("deleted", false)->first();
                if(!$userCompany) return response()->json(['error' => 'Credenciales inválidas'], 400);

                $user = User::where("id", $userCompany->idUser)->where("deleted", false)->first();
                if(!$user) return response()->json(['error' => 'Credenciales inválidas'], 400);

                if(!\Hash::check($request->password, $user->password)) return response()->json(['error' => 'Credenciales inválidas'], 400);

                $company = Company::where("ruc", $credentials["rut"])->where("deleted", false)->first();
                $user->idCompany = $company->id;
                $user->company = $company;

                $role = Role::find($user->idRole);
                $role->permissions = json_decode($role->permissions, true);
                $role->sectionIds = json_decode($role->sectionIds, true);
                if($company->helpDesks != null){
                    $role->sections = Section::whereIn("id", $role->sectionIds)->where("active", true)->where("deleted", false)->get();
               }
                  else {
                    $role->sections = Section::whereIn("id", array_diff($role->sectionIds, array('4')))->where("active", true)->where("deleted", false)->get();
                }
                $user->companies = $user->companies ? json_decode($user->companies, true) : [];

                $helpDesk=null;
                $helpDeskName="pruebaBack";
                $token = JWTAuth::fromUser($user);
                if (!$token) return response()->json(['error' => 'Credenciales inválidas'], 400);
            }else{
                $user = User::where("username", $credentials["username"])->where("deleted", false)->first();
                if(!$user || $user->idRole!=1) return response()->json(['error' => 'Credenciales inválidas'], 400);

                if(!\Hash::check($request->password, $user->password)) return response()->json(['error' => 'Credenciales inválidas'], 400);

                $user->idCompany = null;
                $role = Role::find($user->idRole);
                $role->permissions = json_decode($role->permissions, true);
                $role->sectionIds = json_decode($role->sectionIds, true);
                $role->sections = Section::whereIn("id", $role->sectionIds)->where("active", true)->where("deleted", false)->get();
                $user->companies = $user->companies ? json_decode($user->companies, true) : [];
                $helpDesk=1;
                $helpDeskName="pruebaBack";
                $user->helpDesk = Company::where("isHelpDesk", true)->where("deleted", false)->first();
                $user->idHelpDesk = $user->helpDesk->id;
                $token = JWTAuth::fromUser($user);
                if (!$token) return response()->json(['error' => 'Credenciales inválidas'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token', 'user', 'role', 'helpDesk', 'helpDeskName'));
    }

    public function authenticateErp(Request $request){
        $credentials = $request->only('username', 'password', 'rut');

        if($credentials["rut"]){
            $userCompany = UserCompany::where("username", $credentials["username"])->where("rutCompany", $credentials["rut"])
                ->where("deleted", false)->first();
            if(!$userCompany) return response()->json(['error' => 'Credenciales inválidas'], 400);

            $user = User::where("id", $userCompany->idUser)->where("deleted", false)->first();
            if(!$user) return response()->json(['error' => 'Credenciales inválidas'], 400);

            $hashedPassword = $credentials["password"];
            $decryptedPassword = Crypt::decryptString($user->encrypted_password);
            if(!\Hash::check($decryptedPassword, $hashedPassword)) return response()->json(['error' => 'Credenciales inválidas'], 400);

            $company = Company::where("id", $userCompany->idCompany)->where("deleted", false)->first();
            $user->idCompany = $company->id;
            $user->company = $company;
        }else{
            $user = User::where("username", $credentials["username"])->where("idRole", 1)->where("deleted", false)->first();
            if(!$user) return response()->json(['error' => 'Credenciales inválidas'], 400);

            $hashedPassword = $credentials["password"];
            $decryptedPassword = Crypt::decryptString($user->encrypted_password);
            if(!\Hash::check($decryptedPassword, $hashedPassword)) return response()->json(['error' => 'Credenciales inválidas'], 400);
        }

        $role = Role::find($user->idRole);
        $role->permissions = json_decode($role->permissions, true);
        $role->sectionIds = json_decode($role->sectionIds, true);
        $role->sections = Section::whereIn("id", $role->sectionIds)->where("active", true)->where("deleted", false)->get();

        try {
            $token = JWTAuth::fromUser($user);
            if (!$token) return response()->json(['error' => 'Credenciales inválidas3'], 400);

        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

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
        if ($error) return response()->json($error, 400);

        $user = new User();
        $this->updateUserValues($user, $request);
        $user->password = bcrypt($request->password);
        $user->encrypted_password = Crypt::encryptString($request->password);
        $user->save();

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController;
            $user->avatar = $tasks_controller->updateFile($request->file('image'), "users/avatar", $user->id."_".Carbon::now()->timestamp);
            $user->save();
        }

        if($user->idRole != 1){
            $this->updateUserCompanies($user, $request);
        }

        $token = JWTAuth::fromUser($user); // ??

        return response()->json(compact('user', 'token'), 201);
    }//

    public function update(Request $request, $id){
        $user = User::find($id);
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $error = $this->validateFields($request);
        if ($error) return response()->json($error, 400);

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

        if($user->idRole != 1){
            $this->updateUserCompanies($user, $request);
        }

        return response()->json($user);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:80',
            'lastName' => 'required|string|max:80',
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'string|max:15',
            'dob' => 'required|string',
            'idRole' => 'required|string'
        ]);

        $errorMessage = null;
        if (!$validator->fails()) {
            if($request->idRole != 1){
                $userCompany = UserCompany::where("username", $request->username)->whereIn("idCompany", $request->companies ? $request->companies : [])
                    ->where("deleted", false)->first();
                if($userCompany){
                    if (!$request->id || ($userCompany->idUser != $request->id)) {
                        $company = Company::find($userCompany->idCompany);
                        $errorMessage = new \stdClass();
                        $errorMessage->email = [
                            "El nombre de usuario ya existe en la empresa ".$company->name
                        ];
                    }
                }
            }else{
                $user = User::where("username", $request->username)->where("idRole", 1)->where("deleted", false)->first();
                if($user){
                    if (!$request->id || ($user->id != $request->id)) {
                        $errorMessage = new \stdClass();
                        $errorMessage->email = [
                            "El nombre de usuario ya existe"
                        ];
                    }
                }
            }

        } else {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    private function updateUserValues($user, $request){
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->sex = $request->sex;
        $user->phone = $request->phone;
        $user->dob = date('Y-m-d', strtotime($request->dob));
        $user->idRole = $request->idRole;
        $user->companies = $request->companies ? json_encode($request->companies, true) : null;
        if($request->avatar){
            $user->avatar = $request->avatar;
        }
    }

    public function updateUserCompanies($user, $request){
        $userCompanies = UserCompany::where("idUser", $user->id)->where("deleted", false)->pluck("id");
        UserCompany::destroy($userCompanies);

        $newUserCompanies = [];
        $companies = Company::whereIn("id", $request->companies)->where("deleted", false)->get();
        foreach ($companies as $company) {
            $new = [
                'idUser' => $user->id,
                'idCompany' => $company->id,
                'rutCompany' => $company->ruc,
                'username' => $request->username,
                'deleted' => false,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            array_push($newUserCompanies, $new);
        }
        UserCompany::insert($newUserCompanies);
    }

    public function find($id){
        $user = User::find($id);
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $user->companies = $user->companies ? json_decode($user->companies, true) : [];
        if(count($user->companies) > 0){
            $companies = Company::whereIn("id", $user->companies)->where("deleted", false)->get();
            $user->mappedCompanies = $companies;
        }

        return $user;
    }

    public function list(Request $request){
        $term = $request->has("term") ? $request->get("term") : "";

        $users = User::join('roles', 'users.idRole', '=', 'roles.id')
            ->where('users.deleted', '!=', true);
        $this->searchUser($users, $term);

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);

        return $users;
    }

    public function addObjectValues($users, $idCompany){
        $company = Company::find($idCompany);

        if($company){
            foreach ($users as $user) {
                $user->company = $company;
            }
        }
    }

    public function listAvailable(Request $request){
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        $roles = $request->has("roles") ? $request->get("roles") : [];
        $users = User::join('roles', 'roles.id', '=', 'users.idRole')
            ->where('users.deleted', false)
            ->where('users.companies', 'LIKE', '%' . "\"".$idCompany."\"" . '%')
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
        $this->addObjectValues($users, $idCompany);

        return $users;
    }

    public function listAdmins(Request $request){
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $users = User::join('roles', 'roles.id', '=', 'users.idRole')->where('users.deleted', false)
            ->where('users.id', '!=', $user->id)
            ->where('roles.name', "SuperAdmin");

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
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $term = $request->has('term') ? $request->get('term') : '';

        if (!$idCompany) return response()->json(['error' => 'Seleccione una empresa'], 400);

        $users = User::join('roles', 'roles.id', '=', 'users.idRole')
            ->where('users.deleted', false)
            ->where('users.companies', 'LIKE', '%' . "\"".$idCompany."\"" . '%')
            ->where("users.id", "!=", $user->id);

        if($term){
            $users->where(function ($query) use ($term) {
                $query->where('users.firstName', 'LIKE', '%' . $term . '%')
                    ->orWhere('users.lastName', 'LIKE', '%' . $term . '%');
            });
        }

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);

        return $users;
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

        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

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
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        $users = User::join('roles', 'users.idRole', '=', 'roles.id')
            ->where('users.companies', 'LIKE', '%' . "\"".$idCompany."\"" . '%')
            ->where('users.deleted', '!=', true);
        $this->searchUser($users, $term);

        $users->offset($start * $limit)->take($limit);

        $users = $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'roles.name AS roleName']);
        $this->addObjectValues($users, $idCompany);

        return $users;
    }

    public function listCompanyNotify(Request $request){
        $user = Auth::user();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);

        $term = $request->has('term') ? $request->get('term') : '';

        $users = Company::where('companies.deleted', false);

        if($term){
            $users->where(function ($query) use ($term) {
                $query->where('companies.name', 'LIKE', '%' . $term . '%');
            });
        }

        $users = $users->orderBy("name")->get();

        return $users;
    }

    public function importERPUsers()
    {
        try {
            $res1 = Http::post('http://apitest.softnet.cl/login', [
                "username" => "usuario",
                "password" => "demo",
                "rut" => "22222222-2",
            ]);
            $res1 = $res1->json();
            $erpToken = $res1["token"];

            $res2 = Http::withHeaders([
                'token' => $erpToken,
            ])->get('http://apitest.softnet.cl/datoUsuario', []);
            $res2 = $res2->json();

            $newUsers = [];
            $users = User::where("deleted", false)->get()->keyBy("username");
            $erpCompanies = Company::where("deleted", false)->get()->keyBy("ruc");

            foreach ($res2 as $erpUser) {
                if (!array_key_exists($erpUser["usuario"], $users->toArray())) {

                    $new = [
                        'firstName' => $erpUser["nombre"],
                        'lastName' => " ",
                        'username' => $erpUser["usuario"],
                        'ruc' => $erpUser["rut_usuario"],
                        'email' => $erpUser["email"],
                        'password' => bcrypt($erpUser["rut_usuario"]),
                        'dob' => null,
                        'phone' => null,
                        'sex' => "O",
                        'idRole' => $erpUser["usuario"] == "admin" ? 1 : 5,
                        'idCompany' => null,
                        'companies' => null,
                        'avatar' => null,
                        'deleted' => false,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];

                    array_push($newUsers, $new);
                }
            }

            User::insert($newUsers);
            return response()->json("Import exitoso, " . count($newUsers) . " usuarios registrados", 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updatePassword(Request $request, $id)
    {

        $users = User::find($id);

        $this->validate($request, [
            'password' => 'required',
            'encrypted_password' => 'required',
        ]);
        $error = null;

        if (!$users) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }
        $hashedPassword = Auth::user()->password;
        if (\Hash::check($request->password, $hashedPassword)) {
            $users->password = bcrypt($request->encrypted_password);
            $users->encrypted_password = Crypt::encryptString($request->encrypted_password);

            $users->save();
            return response()->json(['success' => 'Cambio de contraseña exitosa.'], 201);
        } else {
            return response()->json(['error' => 'Contraseña actual incorrecta.'], 400);
        }
    }

    public function findUserByUserName($userName, Request $request){
        $rut = $request->has("rut") ? $request->get("rut") : "";

        if($rut){
            $userCompany = UserCompany::where("username", $userName)->where("rutCompany", $rut)->where("deleted", false)->first();
            if (!$userCompany) return response()->json(['error' => 'Usuario no encontrado'], 400);

            $user = User::where("id", $userCompany->idUser)->where("deleted", false)->first();
            if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);
        }else{
            $user = User::where('username', $userName)->where("idRole", 1)->where('deleted', false)->first();
            if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        return $user;
    }

    public function changeHelpDesk(Request $request, $id){

        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $role = Role::find($user->idRole);
        $role->permissions = json_decode($role->permissions, true);
        $role->sectionIds = json_decode($role->sectionIds, true);
        $role->sections = Section::whereIn("id", $role->sectionIds)->where("active", true)->where("deleted", false)->get();
        $token = JWTAuth::fromUser($user);
        if (!$token) return response()->json(['error' => 'Credenciales inválidas'], 400);

        $user->helpDesk = Company::where("isHelpDesk", true)->where("id", $request->id)->where("deleted", false)->first();
        $user->idHelpDesk = $user->helpDesk->id;

        return response()->json(compact('token', 'user', 'role'));
    }
}

