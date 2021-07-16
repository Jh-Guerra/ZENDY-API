<?php

namespace App\Http\Controllers;

    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Validator;
    use JWTAuth;
    use Tymon\JWTAuth\Exceptions\JWTException;
    use Illuminate\Support\Facades\Storage;

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
        $user->password = Hash::make($request->password);
        $user->save();

        $token = JWTAuth::fromUser($user); // ??

        return response()->json(compact('user','token'),201);
    }

    public function update(Request $request, $id){
        $user = User::find($id);

        $error = $this->validateFieldsUpdate($request);
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
            'email' => 'required|string|email|max:255',
            'phone' => 'string|max:15',
            'dob' => 'required|string',
            'type' => 'required|string',
            'idCompany' => 'nullable|int'
        ]);

        $errorMessage = null;
        if(!$validator->fails()){
            $user = User::where('email', $request->email)->where('deleted', false)->first();
            if($user && $user->id != $request->id){
                $errorMessage = new \stdClass();
                $errorMessage->email = [
                    "El correo electrónico ya está registrado."
                ];
            }
        }else{
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
            'type' => 'required|string',
            'idCompany' => 'nullable|int',

        ]);
        $errorMessage = null;

        return $errorMessage;
    }

    private function updateUserValues($user, $request){
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->dob = date('Y-m-d',strtotime($request->dob));
        $user->type = $request->type;
        $user->idCompany = $request->idCompany;
    }

    public function find($id){
        $user = User::find($id);

        if(!$user){
            return response()->json(['error' => 'Usuario no encontrado'], 400);
        }

        return $user;
    }

    public function list(Request $request){
        $start = 0;
        $limit = 50;
        $term = $request->has("term") ? $request->get("term") : "";
        $users = User::join('companies', 'users.idCompany', '=', 'companies.id')->where('users.deleted', '!=', true);

        if($request->has("term") && $request->get("term")){
            $users->where(function ($query) use ($term) {
                $query->where('firstName', 'LIKE', '%'.$term.'%')
                    ->orWhere('lastName', 'LIKE', '%'.$term.'%');
            });
        }
        $users->offset($start*$limit)->take($limit);

        return $users->orderBy("firstName")->orderBy("lastName")->get(['users.*', 'companies.name AS companyName']);
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

    public function upload(Request $request){
        $validation = Validator::make($request->all(),
          [
              'image'=>'mimes:jpeg,jpg,png,gif|max:10000'
          ]);
  
          if ($validation->fails()){
              $response=array('status'=>'error','errors'=>$validation->errors()->toArray());  
              return response()->json($response);
          }
  
       if($request->hasFile('image')){
  
          $uniqueid=uniqid();
          $original_name=$request->file('image')->getClientOriginalName(); 
          $size=$request->file('image')->getSize();
          $extension=$request->file('image')->getClientOriginalExtension();
  
          $name=$uniqueid.'.'.$extension;
          $path=$request->file('image')->storeAs('public/users/avatar',$name);
          if($path){
              return response()->json(array('status'=>'success','message'=>'Image successfully uploaded','image'=>'/storage/users/avatar/'.$name));
          }else{
              return response()->json(array('status'=>'error','message'=>'failed to upload image'));
          }
      }
  
  }
}

