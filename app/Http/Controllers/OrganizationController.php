<?php

namespace App\Http\Controllers;

use App\Http\Controllers\uploadImageController;
use App\Models\Company;
use App\Models\Organization;
use App\Models\UserCompany;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Http;

class OrganizationController extends Controller
{
    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'address' => 'required|string|max:150',
            'adminName' => 'required|string|max:150',
            'email' => 'required|string|email|max:255',
            'phone' => 'string|max:20',
        ]);

        $errorMessage = null;
        if(!$validator->fails()){
            $organization = Organization::where('name', $request->name)->where('deleted', false)->first();
            if($organization && $organization->id != $request->id){
                $errorMessage = new \stdClass();
                $errorMessage->email = [
                    "La organización ya está registrada."
                ];
            }
        }else{
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    public function register(Request $request){
        $error = $this->validateFields($request);
        if($error) return response()->json($error, 400);

        $organization = new Organization();
        $organization->name = $request->name;
        $organization->address = $request->address;
        $organization->adminName = $request->adminName;
        $organization->ruc = $request->ruc;
        $organization->email = $request->email;
        $organization->phone = $request->phone;
        if($request->avatar){
            $organization->avatar = $request->avatar;
        }
        $organization->description = $request->description;
        $organization->save();

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController();
            $organization->avatar = $tasks_controller->updateFile($request->file('image'), "organizations/avatar", $organization->id."_".Carbon::now()->timestamp);
            $organization->save();
        }

        return response()->json($organization,201);
    }

    public function update(Request $request, $id){
        $organization = Organization::find($id);

        $error = $this->validateFields($request);
        if($error) return response()->json($error, 400);

        if(!$organization) return response()->json(['error' => 'Organización no encontrada'], 400);

        $organization->name = $request->name;
        $organization->address = $request->address;
        $organization->adminName = $request->adminName;
        $organization->ruc = $request->ruc;
        $organization->email = $request->email;
        $organization->phone = $request->phone;
        if($request->avatar){
            $organization->avatar = $request->avatar;
        }
        $organization->description = $request->description;
        $organization->save();

        if($request->oldImage){
            $newImage = substr($request->oldImage, 8);
            $image_path = storage_path().'/app/public/'."".$newImage;
            if (@getimagesize($image_path)){
                unlink($image_path);
            }
        }

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController();
            $organization->avatar = $tasks_controller->updateFile($request->file('image'), "organizations/avatar", $organization->id."_".Carbon::now()->timestamp);
            $organization->save();
        }

        return response()->json($organization);
    }

    public function find($id){
        $organization = Organization::find($id);
        if(!$organization) return response()->json(['error' => 'Organización no encontrada'], 400);

        return $organization;
    }

    public function list(){
        return Organization::where('deleted', '!=', true)->orderBy("name")->get();
    }

    public function listWithUsersCount(Request $request){
        $term = $request->has("term") ? $request->get("term") : "";

        $organizations = Organization::where('organizations.deleted', '!=', true)->groupBy('organizations.id')->get();

        foreach ($organizations as $organization){
            $organization->usersCount = count(UserCompany::where("idOrganization", $organization->id)->where("deleted", false)->get());
        }

        if($term){
            $organizations = $organizations->filter(function ($organization) use ($term) {
                return str_contains(strtolower($organization->name), strtolower($term)) !== false;
            })->values()->all();
        }

        return $organizations;
    }

    public function delete($id){
        $organization = Organization::find($id);
        if(!$organization) return response()->json(['error' => 'Organización no encontrada'], 400);

        $organization->deleted = true;
        $organization->save();

        return response()->json(['success' => 'Organización Eliminada'], 201);
    }

    public function deleteImage(Request $request){
        $imageLink = $request->imageLink;
        $organizationId = $request->id;

        $organization = Organization::find($organizationId);
        $image_path = storage_path().'/app/public/'."".$imageLink;
        if (@getimagesize($image_path) && $organization){
            unlink($image_path);
            $organization->avatar = null;
            $organization->save();

            return response()->json(compact('organization'),201);
        }else{
            return response()->json(['error' => 'Organización no encontrada / Archivo no encontrado'], 400);
        }
    }

}
