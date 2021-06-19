<?php

namespace App\Http\Controllers;

    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Validator;
    use JWTAuth;
    use Tymon\JWTAuth\Exceptions\JWTException;
    use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{
    public function authenticate(Request $request){
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token'));
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
        if($error){
            return response()->json($error, 400);
        }

        $user = new User();
        $this->updateUserValues($user, $request);
        $user->save();

        $token = JWTAuth::fromUser($user); // ??

        return response()->json(compact('user','token'),201);
    }

    public function update(Request $request, $id){
        $user = User::find($id);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        if(!$user){
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $this->updateUserValues($user, $request);
        $user->save();

        return response()->json($user);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:80',
            'lastName' => 'required|string|max:80',
            'email' => 'required|string|email|max:255'.($request->id ? '' : '|unique:users'),
            'phone' => 'string|max:15',
            'dob' => 'required|string',
            'password' => 'required|string|min:4',
            'type' => 'required|string',
            'company' => 'nullable|string'
        ]);

        return $validator->fails() ? $validator->errors()->toJson() : null;
    }


    private function updateUserValues($user, $request){
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->dob = date('Y-m-d',strtotime($request->dob));
        $user->password = Crypt::encryptString($request->password);
        $user->type = $request->type;
        $user->company = $request->company;
    }

    public function find($id){
        $user = User::find($id);

        if(!$user){
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $user->password = Crypt::decryptString($user->password);

        return $user;
    }

    public function list(){
        return User::where('deleted', '!=', true)->orderBy("firstName")->orderBy("lastName")->get();
    }

    public function delete($id){
        $user = User::find($id);

        if(!$user){
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        $user->deleted = true;
        $user->save();

        return response()->json(['success' => 'Usuario Eliminado'], 201);
    }
}

