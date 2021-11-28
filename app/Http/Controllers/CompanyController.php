<?php

namespace App\Http\Controllers;

use App\Http\Controllers\uploadImageController;
use App\Models\Company;
use App\Models\UserCompany;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Http;

class CompanyController extends Controller
{
    public function register(Request $request){
        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        $company = new Company();
        $this->updateCompanyValues($company, $request);
        $company->save();

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController();
            $company->avatar = $tasks_controller->updateFile($request->file('image'), "companies/avatar", $company->id."_".Carbon::now()->timestamp);
            $company->save();
        }

        return response()->json($company,201);
    }

    public function update(Request $request, $id){
        $company = Company::find($id);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        if(!$company){
            return response()->json(['error' => 'Empresa no encontrada'], 400);
        }

        $this->updateCompanyValues($company, $request);
        $company->save();

        if($request->oldImage){
            $newImage = substr($request->oldImage, 8);
            $image_path = storage_path().'/app/public/'."".$newImage;
            if (@getimagesize($image_path)){
                unlink($image_path);
            }
        }

        if($request->hasFile('image')){
            $tasks_controller = new uploadImageController();
            $company->avatar = $tasks_controller->updateFile($request->file('image'), "companies/avatar", $company->id."_".Carbon::now()->timestamp);
            $company->save();
        }

        return response()->json($company);
    }

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
            $company = Company::where('name', $request->name)->where('deleted', false)->first();
            if($company && $company->id != $request->id){
                $errorMessage = new \stdClass();
                $errorMessage->email = [
                    "La empresa ya está registrada."
                ];
            }
        }else{
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }


    private function updateCompanyValues($company, $request){
        $company->name = $request->name;
        $company->address = $request->address;
        $company->adminName = $request->adminName;
        $company->ruc = $request->ruc;
        $company->email = $request->email;
        $company->phone = $request->phone;
        if($request->avatar){
            $company->avatar = $request->avatar;
        }
        $company->description = $request->description;
    }

    public function find($id){
        $company = Company::find($id);

        if(!$company){
            return response()->json(['error' => 'Empresa no encontrada'], 400);
        }

        return $company;
    }

    public function list(){
        return Company::where('deleted', '!=', true)->orderBy("name")->get();
    }

    public function listWithUsersCount(Request $request){
        $term = $request->has("term") ? $request->get("term") : "";

        $companies = Company::where('companies.deleted', '!=', true)->groupBy('companies.id')->get();

        foreach ($companies as $company){
            $company->usersCount = count(UserCompany::where("idCompany", $company->id)->where("deleted", false)->get());
        }

        if($term){
            $companies = $companies->filter(function ($company) use ($term) {
                return str_contains(strtolower($company->name), strtolower($term)) !== false;
            })->values()->all();
        }

        return $companies;
    }

    public function delete($id){
        $company = Company::find($id);

        if(!$company){
            return response()->json(['error' => 'Empresa no encontrada'], 400);
        }

        $company->deleted = true;
        $company->save();

        return response()->json(['success' => 'Empresa Eliminada'], 201);
    }

    public function deleteImage(Request $request){
        $imageLink = $request->imageLink;
        $companyId = $request->id;

        $company = Company::find($companyId);
        $image_path = storage_path().'/app/public/'."".$imageLink;
        if (@getimagesize($image_path) && $company){
            unlink($image_path);
            $company->avatar = null;
            $company->save();

            return response()->json(compact('company'),201);
        }else{
            return response()->json(['error' => 'Empresa no encontrada / Archivo no encontrado'], 400);
        }
    }

    public function importERPCompanies(){
        try {
            $res1 = Http::post('http://apitest.softnet.cl/login', [
                "username" => "usuario",
                "password" => "demo",
                "rut"=> "22222222-2",
            ]);
            $res1 = $res1->json();
            $erpToken = $res1["token"];

            $res2 = Http::withHeaders([
                'token' => $erpToken,
            ])->get('http://apitest.softnet.cl/datoEmpresa', []);
            $res2 = $res2->json();

            $newCompanies = [];
            $companies = Company::where("deleted", false)->get()->keyBy("ruc");
            foreach ($res2 as $erpCompany) {
                if(!array_key_exists($erpCompany["rut_empresa"], $companies->toArray())){

                    $new = [
                        'name' => $erpCompany["razon"],
                        'address' => $erpCompany["direccion"].", ".$erpCompany["comuna"].", ".$erpCompany["ciudad"],
                        'email' => $erpCompany["web"],
                        'phone' => $erpCompany["telefono"],
                        'ruc' => $erpCompany["rut_empresa"],
                        'adminName' => $erpCompany["nombre_representante1"],
                        'avatar' => null,
                        'description' => $erpCompany["giro1"].". ".$erpCompany["giro2"],
                        'deleted' => 0,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];

                    array_push($newCompanies, $new);
                }
            }

            Company::insert($newCompanies);
            return response()->json("Import exitoso, ".count($newCompanies)." empresas registradas",201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
