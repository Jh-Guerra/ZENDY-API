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
        $this->updateValues($entryQuery, $user, $request);
        $entryQuery->save();

        $tasks_controller = new uploadImageController;
        $fileSaved = false;
        if($request->hasFile('image1')){
            $path = $tasks_controller->updateFile($request->file('image1'), "entry_queries/".$entryQuery->id, "image1_".Carbon::now()->timestamp);
            $entryQuery->image1 = $path;
            $fileSaved = true;
        }

        if($request->hasFile('image2')){
            $path = $tasks_controller->updateFile($request->file('image2'), "entry_queries/".$entryQuery->id, "image2_".Carbon::now()->timestamp);
            $entryQuery->image2 = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file1')){
            $path = $tasks_controller->updateFile($request->file('file1'), "entry_queries/".$entryQuery->id, "file1_".Carbon::now()->timestamp);
            $entryQuery->file1 = $path;
            $fileSaved = true;
        }

        if($request->hasFile('file2')){
            $path = $tasks_controller->updateFile($request->file('file2'), "entry_queries/".$entryQuery->id, "file2_".Carbon::now()->timestamp);
            $entryQuery->file2 = $path;
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

    private function updateValues($entryQuery, $user, $request){
        $entryQuery->startDate = date('Y-m-d', Carbon::now()->timestamp);
        $entryQuery->status = "Pendiente";
        $entryQuery->idCompany = $user->idCompany;
        $entryQuery->createdBy = $user->id;
        $entryQuery->reason = $request["reason"];
        $entryQuery->description = $request["description"];
        $entryQuery->image1 = $request["image1"];
        //$entryQuery->image1 = $request ->image1;
        $entryQuery->image2 = $request["image2"];
        $entryQuery->file1 = $request["file1"];
        $entryQuery->file2 = $request["file2"];
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

}
