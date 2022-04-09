<?php

namespace App\Http\Controllers;

use App\Http\Controllers\uploadImageController;
use App\Models\Company;
use App\Models\CompanyHorario;
use App\Models\rutCompanies;
use App\Models\User;
use App\Models\UserCompany;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Http;

class CompanyController extends Controller
{
    public function register(Request $request)
    {
        $error = $this->validateFields($request);
        if ($error) {
            return response()->json($error, 400);
        }

        // dd($request->horarioEntradaLV);
        // $horario = new CompanyHorario();
        // $horario->Dias = $request->Dias;
        // $horario->MedioDia = $request->MedioDia;
        // $horario->HorarioIngreso = $request->HorarioIngreso;
        // $horario->HorarioSalida = $request->HorarioSalida;
        // $horario->HorarioIngresoMD = $request->HorarioIngresoMD;
        // $horario->HorarioSalidaMD = $request->HorarioSalidaMD;
        // $horario->save();

        $company = new Company();
        $this->updateCompanyValues($company, $request);
        $company->save();

        if ($request->hasFile('image')) {
            $tasks_controller = new uploadImageController();
            $company->avatar = $tasks_controller->updateFile($request->file('image'), "companies/avatar", $company->id . "_" . Carbon::now()->timestamp);
            $company->save();
        }

        return response()->json($company, 201);
    }

    public function update(Request $request, $id)
    {

        $company = Company::find($id);
        if (!$company) return response()->json(['error' => 'Empresa no encontrada'], 400);

        $error = $this->validateFields($request);
        if ($error) return response()->json($error, 400);

        $company->name = $request->name;
        $company->address = $request->address;
        $company->adminName = $request->adminName;

        if (strcmp($company->ruc, $request->ruc) !== 0) {
            UserCompany::where("rutCompany", $company->ruc)->update(['rutCompany' => $request->ruc]);
            $company->ruc = $request->ruc;
        }

        $company->email = $request->email;
        $company->phone = $request->phone;
        if ($request->avatar) {
            $company->avatar = $request->avatar;
        }
        $company->description = $request->description;
        /* $company->horarioEntrada = $request->horarioEntrada ? $request->horarioEntrada : null;
        $company->horarioSalida = $request->horarioSalida ? $request->horarioSalida : null; */
        $company->isHelpDesk = filter_var($request->isHelpDesk, FILTER_VALIDATE_BOOLEAN);
        $company->helpDesks = $request->helpDesks ? json_encode($request->helpDesks, true) : null;
        $company->save();

        /* $horario = CompanyHorario::find($company->idHorario);
        $horario->Dias = $request->Dias;
        $horario->MedioDia = $request->MedioDia;
        $horario->HorarioIngreso = $request->HorarioIngreso;
        $horario->HorarioSalida = $request->HorarioSalida;
        $horario->HorarioIngresoMD = $request->HorarioIngresoMD;
        $horario->HorarioSalidaMD = $request->HorarioSalidaMD;
        $horario->save(); */

        if ($request->oldImage) {
            $newImage = substr($request->oldImage, 8);
            $image_path = storage_path() . '/app/public/' . "" . $newImage;
            if (@getimagesize($image_path)) {
                unlink($image_path);
            }
        }

        if ($request->hasFile('image')) {
            $tasks_controller = new uploadImageController();
            $company->avatar = $tasks_controller->updateFile($request->file('image'), "companies/avatar", $company->id . "_" . Carbon::now()->timestamp);
            $company->save();
        }

        return response()->json($company);
    }

    private function validateFields($request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'address' => 'required|string|max:150',
            'adminName' => 'required|string|max:150',
            'email' => 'required|string|email|max:255',
            'phone' => 'string|max:20',
            /* 'Dias' => 'required|string|max:255',
            'MedioDia' => 'string',
            'HorarioIngreso' => 'required|string',
            'HorarioSalida' => 'required|string',
            'HorarioIngresoMD' => 'string',
            'HorarioSalidaMD' => 'string', */
        ]);

        $errorMessage = null;
        if (!$validator->fails()) {
            $company = Company::where('name', $request->name)->where('deleted', false)->first();
            if ($company && $company->id != $request->id) {
                $errorMessage = new \stdClass();
                $errorMessage->email = [
                    "La empresa ya está registrada."
                ];
            }
        } else {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }


    private function updateCompanyValues($company, $request)
    {
        $company->name = $request->name;
        $company->address = $request->address;
        $company->adminName = $request->adminName;
        $company->ruc = $request->ruc;
        $company->email = $request->email;
        $company->phone = $request->phone;

        if ($request->avatar) {
            $company->avatar = $request->avatar;
        }
        $company->description = $request->description;
        // $company->idHorario = $idhorario;
        $company->isHelpDesk = filter_var($request->isHelpDesk, FILTER_VALIDATE_BOOLEAN);
        $company->helpDesks = $request->helpDesks ? json_encode($request->helpDesks, true) : null;
    }

    public function find($id)
    {
        // $company = Company::join("companies_horarios", "companies_horarios.id", "=", "companies.idHorario")
        //     ->select("companies.*", "companies_horarios.*")->where('companies.id', $id)->first();
        $company = Company::where('id', $id)->first();

        $company->helpDesks = $company->helpDesks ? json_decode($company->helpDesks, true) : [];
        if (count($company->helpDesks) > 0) {
            $companies = Company::whereIn("id", $company->helpDesks)->where("deleted", false)->get();
            $company->mappedCompanies = $companies;
        }
        if (!$company) {
            return response()->json(['error' => 'Empresa no encontrada'], 400);

        }
        return $company;
    }

    public function updateHelpDeskCompany($id)
    {
        $companies = Company::where('deleted', '!=', true)->where('isHelpDesk', false)->where('helpDesks', 'LIKE', '%' . "\"$id\"" . '%')->get();

        foreach ($companies as $company) {
            $companyHelpDenk = $company->helpDesks ? json_decode($company->helpDesks, true) : [];
            $newCompanyHelpDenk =  array_diff($companyHelpDenk, array("$id"));
            $company->helpDesks = count($newCompanyHelpDenk) > 0 ?  "[\"" . implode('","', $newCompanyHelpDenk) . "\"]" : "";
            $company->save();
        }
        return response()->json("Actualizacion correcta", 201);
    }

    public function searchCompany(Request $request)
    {
        $value = $request['name'];
        return Company::where('name', "LIKE", "%$value%")->orWhere('ruc', "LIKE", "%$value%")->where('deleted', '!=', true)->orderBy("name")->get();
    }

    public function list()
    {
        return Company::where('deleted', '!=', true)->orderBy("name")->get();
    }

    public function listClient()
    {
        return Company::where('deleted', '!=', true)->where('isHelpDesk', false)->orderBy("name")->get();
    }

    public function listHelpdesk(Request $request)
    {
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        if ($idCompany) {
            $company = Company::find($idCompany);
            if (!$company) return response()->json(['error' => 'Empresa no encontrada'], 400);

            $company->helpDesks = (array) json_decode($company->helpDesks, true);

            return Company::whereIn("id", $company->helpDesks)->where('deleted', '!=', true)->where('isHelpDesk', true)->orderBy("name")->get();
        } else {
            return Company::where('deleted', '!=', true)->where('isHelpDesk', true)->orderBy("name")->get();
        }
    }

    public function listWithUsersCount(Request $request)
    {
        $term = $request->has("term") ? $request->get("term") : "";

        $companies = Company::where('companies.deleted', '!=', true)->groupBy('companies.id')->get();

        foreach ($companies as $company) {
            $company->usersCount = count(UserCompany::where("idCompany", $company->id)->where("deleted", false)->get());
        }

        if ($term) {
            $companies = $companies->filter(function ($company) use ($term) {
                return str_contains(strtolower($company->name), strtolower($term)) !== false;
            })->values()->all();
        }

        return $companies;
    }

    public function delete($id)
    {
        $company = Company::find($id);
        if (!$company) return response()->json(['error' => 'Empresa no encontrada'], 400);

        $company->deleted = true;
        $company->save();

        return response()->json(['success' => 'Empresa Eliminada'], 201);
    }

    public function deleteImage(Request $request)
    {
        $imageLink = $request->imageLink;
        $companyId = $request->id;

        $company = Company::find($companyId);
        $image_path = storage_path() . '/app/public/' . "" . $imageLink;
        if (@getimagesize($image_path) && $company) {
            unlink($image_path);
            $company->avatar = null;
            $company->save();

            return response()->json(compact('company'), 201);
        } else {
            return response()->json(['error' => 'Empresa no encontrada / Archivo no encontrado'], 400);
        }
    }

    public function importERPCompanies()
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
            ])->get('http://apitest.softnet.cl/datoEmpresa', []);
            $res2 = $res2->json();

            $newCompanies = [];
            $companies = Company::where("deleted", false)->get()->keyBy("ruc");
            foreach ($res2 as $erpCompany) {
                if (!array_key_exists($erpCompany["rut_empresa"], $companies->toArray())) {

                    $new = [
                        'name' => $erpCompany["razon"],
                        'address' => $erpCompany["direccion"] . ", " . $erpCompany["comuna"] . ", " . $erpCompany["ciudad"],
                        'email' => $erpCompany["web"],
                        'phone' => $erpCompany["telefono"],
                        'ruc' => $erpCompany["rut_empresa"],
                        'adminName' => $erpCompany["nombre_representante1"],
                        'avatar' => null,
                        'description' => $erpCompany["giro1"] . ". " . $erpCompany["giro2"],
                        'deleted' => 0,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];

                    array_push($newCompanies, $new);
                }
            }

            Company::insert($newCompanies);
            return response()->json("Import exitoso, " . count($newCompanies) . " empresas registradas", 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function cargaHorarios()
    {
        $companies = Company::All();

        for ($i = 0; $i < count($companies); $i++) {
            $horario = new CompanyHorario();
            $horario->Dias = '["1", "2", "3", "4", "5"';
            $horario->MedioDia = '[]';
            $horario->HorarioIngreso = "09:00";
            $horario->HorarioSalida = "18:00";
            $horario->HorarioIngresoMD = null;
            $horario->HorarioSalidaMD = null;
            $horario->save();

            $empresa = Company::find($companies[$i]['id']);
            $empresa->idHorario = $horario->id;
            $empresa->save();
        }
    }
    //QUITAR RUC DE COLUMNA COMPANIES EN USERS
    public function prueba()
    {
        $Companies = User::where('companies','LIKE','%-%')->get();

       //dd($Companies);
        foreach($Companies as $companies)
        {

            $nu = json_decode($companies['companies']);
            $nul = Company::where('ruc',$nu)->get();

            $user = User::find($companies->id);
            $user->companies = '["'.$nul[0]['id'].'"]';
            $user->save();


        }
            return $user;
    }

    public function rut_companies(){

        try {

        $companies = Company::where('helpDesks','<>', null)->whereNotIn('ruc', ['76017114-k', '22222222-2','20608358243'])->get();

        for ($i=0; $i <count($companies) ; $i++) {
                $newRut = new rutCompanies;
                $newRut->id_companies = $companies[$i]['id'];
                $newRut->ruc = $companies[$i]['ruc'];
                $newRut->estado = 0;
                $newRut->save();
            }

        return 'Tabla completada';
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function searchRuc(Request $request){

        try {
            $companie = Company::where('ruc',$request->ruc)->first();
            if (isset($companie)) {
                return array('status' => true,
                            'url' => null,
                            'empresa' => $companie);
            }else{
                return array('status' => false,
                            'url' => 'Link de redireccion a vista de carga para enviar solicitud de agregar empresa a Zendy',
                            'empresa' => false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function usernameRuc(Request $request)
    {
       $companies = Company::where('ruc',$request->ruc)->first();
       if(isset($companies)){
            $username = User::where('username',$request->username)->where('companies','["'.$companies->id.'"]')->first();
            if (isset($username)) {
                $datos[] = $username;
                $datos[] = $companies;
                return array('status' => true,
                        'descripcion' => 'Datos correctos',
                        'datos' => $datos);
            }else{
                return array('status' => false,
                        'descripcion' => 'Nombre de usuario inválido',
                        'datos'=> null);
            }
       }else{
           return array('status' => false,
                        'descripcion' => 'Ruc inválido',
                        'datos'=> null);
       }
    }


}
