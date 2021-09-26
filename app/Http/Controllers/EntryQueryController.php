<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Company;
use App\Models\EntryQuery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EntryQueryController extends Controller
{
    public function register(Request $request){
        $error = $this->validateFields($request);
        if ($error) {
            return response()->json($error, 400);
        }
        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        //$request = json_decode($request->getContent(), true);

        $entryQuery = new EntryQuery();
        $entryQuery->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $entryQuery->status = "Pendiente";
        $entryQuery->idCompany = $user->idCompany;
        $entryQuery->createdBy = $user->id;
        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        $entryQuery->image = $request["image"];
        $entryQuery->file = $request["file"];
        $entryQuery->module = $request["module"];
        $entryQuery->idfrequentQuery = $request["idfrequentQuery"];
        if($request->isFrequentQuery == null){
            $entryQuery->isFrequentQuery = false;
        } else {
            $entryQuery->isFrequentQuery = $request["isFrequentQuery"];
        }

        $entryQuery->save();

        $uploadImageController = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $uploadImageController->updateFile($request->file('image'), "entry_queries/".$entryQuery->id, "image_".Carbon::now()->timestamp);
            $entryQuery->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $uploadImageController->updateFile($request->file('file'), "entry_queries/".$entryQuery->id, "file_".Carbon::now()->timestamp);
            $entryQuery->file = $path;
            $fileSaved = true;
        }

        if($fileSaved){
            $entryQuery->save();
        }

        return response()->json(compact('entryQuery'),201);
    }

    private function validateFields($request){
        $validator = Validator::make($request->all(), [
            'reason' => 'required',
            'description' => 'required',
        ]);


        $errorMessage = null;
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->toJson();
        }

        return $errorMessage;
    }

    public function find($id){
        $entryQuery = EntryQuery::find($id);
        if(!$entryQuery) return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $entryQuery->user = User::find($entryQuery->createdBy);
        if($entryQuery->idCompany)
            $entryQuery->company = Company::find($entryQuery->idCompany);

        return response()->json(compact('entryQuery'),201);
    }

    public function list(Request $request){
        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQueries = EntryQuery::where("createdBy", $user->id)->where("deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term){
            $this->search($entryQueries, $term);
        }

        $entryQueries->get();

        return response()->json(compact('entryQueries'),201);
    }

    public function listPendings(Request $request){
        $entryQueries = EntryQuery::where("status", "Pendiente")->where("deleted", false);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term)
            $this->search($entryQueries, $term);

        $entryQueries = $entryQueries->orderByDesc("startDate")->get();

        return $entryQueries->values()->all();;
    }

    public function search($entryQueries, $term){
        if($term){
            $entryQueries->where(function ($query) use ($term) {
                $query->where('reason', 'LIKE', '%'.$term.'%');
            });
        }
    }

    public function delete($id){
        $entryQuery = EntryQuery::find($id);
        if(!$entryQuery)
            return response()->json(['error' => 'Consulta no encontrada.'], 400);

        $entryQuery->deleted = true;
        $entryQuery->save();

        return response()->json(compact('entryQuery'),201);
    }

    public function listQuery(Request $request){
        $user = Auth::user();
        if(!$user)
        return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQueries = EntryQuery::where("deleted", false)->where("createdBy", '=',$user->id);

        $term = $request->has("term") ? $request->get("term") : "";
        if($term)
            $this->search($entryQueries, $term);

        $entryQueries = $entryQueries->orderByDesc("startDate")->get();

        return $entryQueries->values()->all();;
    }

    public function update(Request $request, $id){
        $entryQuery = EntryQuery::find($id);

        $error = $this->validateFields($request);
        if($error){
            return response()->json($error, 400);
        }

        if(!$entryQuery){
            return response()->json(['error' => 'Consulta no encontrada'], 400);
        }

        $user = Auth::user();
        if(!$user)
            return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        $entryQuery->image = $request["image"];
        $entryQuery->file = $request["file"];
        $entryQuery->module = $request["module"];
        $entryQuery->idfrequentQuery = $request["idfrequentQuery"];
        $entryQuery->isFrequentQuery = $request["isFrequentQuery"];
        $entryQuery->save();

        $tasks_controller = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image')){
            $path = $tasks_controller->updateFile($request->file('image'), "entry_queries/".$entryQuery->id, "image_".Carbon::now()->timestamp);
            $entryQuery->image = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file')){
            $path = $tasks_controller->updateFile($request->file('file'), "entry_queries/".$entryQuery->id, "file_".Carbon::now()->timestamp);
            $entryQuery->file = $path;
            $fileSaved = true;
        }

        if($fileSaved){
            $entryQuery->save();
        }

        return response()->json(compact('entryQuery'),201);
    }

}
