<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

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

        return response()->json($company);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'address' => 'required|string|max:150',
            'adminName' => 'required|string|max:150',
            'email' => 'required|string|email|max:255',
            'phone' => 'string|max:20',
            'logo' => 'string|nullable',
            'currentBytes' => 'required|int',
            'maxBytes' => 'required|int',
            'description' => 'string|max:2550'
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
        $company->email = $request->email;
        $company->phone = $request->phone;
        $company->logo = $request->logo;
        $company->currentBytes = $request->currentBytes;
        $company->maxBytes = $request->maxBytes;
        $company->avatar = $request->avatar;
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

    public function listWithUsersCount(){
        $companies = Company::join('users', 'users.idCompany', 'companies.id')
                                ->select([
                                    'companies.*', DB::raw('(SELECT COUNT(*) FROM users WHERE users.idCompany = companies.id) as usersCount')
                                ])->where('companies.deleted', '!=', true)->groupBy('companies.id')->get();


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
}
