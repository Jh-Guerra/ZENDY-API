<?php

namespace App\Http\Controllers;
use App\Models\Company;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function list(Request $request){
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $company = Company::where("id", $idCompany)->where("deleted", false)->first();
        if($company->isHelpDesk){
            return Role::where("status", true)->where("id","!=", "4")->get();
        } else {
            return Role::where("status", true)->get();            
        }
    }
}
