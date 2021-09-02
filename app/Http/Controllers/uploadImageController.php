<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class uploadImageController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
    }


    public function updateProfilePicture(Request $request) {

        if($request->hasFile('image')){
            $validation = Validator::make($request->all(),
            [
                'image'=>'mimes:jpeg,jpg,png,gif|max:10000'
            ]);

            if ($validation->fails()){
                $response=array('status'=>'error','errors'=>$validation->errors()->toArray());
                return response()->json($response);
            }
            
            $uniqueId=uniqid();
            $original_name=$request->file('image')->getClientOriginalName();
            $size=$request->file('image')->getSize();
            $extension=$request->file('image')->getClientOriginalExtension();

            $name=$uniqueId.'.'.$extension;
            $path=$request->file('image')->storeAs('public/users/avatar',$name);

            $rute ='storage/users/avatar/'.$name;

            return $rute;
        }
    }
}
