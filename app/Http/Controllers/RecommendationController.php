<?php

namespace App\Http\Controllers;

    use App\Models\Recommendation;
    use Carbon\Carbon;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use JWTAuth;
    use Illuminate\Support\Facades\Crypt;
    use App\Models\User;

class RecommendationController extends Controller
{
    public function register(Request $request){
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;
        $request = json_decode($request->getContent(), true);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $recommendation = new Recommendation();
        $recommendation->idEntryQuery = $request["idEntryQuery"];
        $recommendation->recommendUser = $request["recommendUser"];
        $recommendation->idCompany = $idCompany;
        $recommendation->recommendDate = Carbon::now()->timestamp;
        $recommendation->recommendBy = $user->id;
        $recommendation->status = "Pendiente";
        $recommendation->save();

        return response()->json(compact('recommendation'),201);
    }

    public function registerMany($recommendations){
        Recommendation::insert($recommendations);
    }

    public function update(Request $request, $id){
        $request = json_decode($request->getContent(), true);
        $recommendation = Recommendation::find($id);

        if(!$recommendation) return response()->json(['error' => 'Recomendación no encontrada'], 400);

        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $recommendation->recommendUser = $request["recommendUser"];
        $recommendation->recommendDate = Carbon::now()->timestamp;
        $recommendation->save();

        return response()->json(compact('recommendation'),201);
    }

    public function find($id){
        $recommendation = Recommendation::find($id);
        if(!$recommendation) return response()->json(['error' => 'error en la busqueda'], 400);

        return response()->json(compact('recommendation'),201);
    }

    public function list() {
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $term = $request->has("term") ? $request->get("term") : "";
        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        $recommendations = Recommendation::join('users', 'users.id', 'recommendations.recommendBy')
            ->where("idCompany", $idCompany)
            ->where("recommendBy", $user->id)->where("recommendations.deleted", false)
            ->get(['recommendations.*', 'users.firstName AS userFirstName', 'users.lastName AS userLastName']);

        return $recommendations;
    }

    public function listMyRecommendations(Request $request){
        $user = Auth::user();
        if(!$user) return response()->json(['error' => 'Credenciales no encontradas, vuelva a iniciar sesión.'], 400);

        $idCompany = $request->has("idCompany") ? $request->get("idCompany") : null;

        return Recommendation::join('users', 'users.id', 'recommendations.recommendBy')->join('entry_queries', 'entry_queries.id', 'recommendations.idEntryQuery')
            ->where("recommendations.idCompany", $idCompany)
            ->where("recommendUser", $user->id)->where("recommendations.status", "Pendiente")->where("recommendations.deleted", false)
            ->get(['recommendations.*', 'users.firstName AS userFirstName', 'users.lastName AS userLastName', 'users.avatar as userAvatar', 'users.sex as userSex',
                'entry_queries.reason as queryReason']);
    }

    public function listExistingRecommendations($idEntryQuery){
        $user = Auth::user();

        $pendingRecommendations = Recommendation::where("idEntryQuery", $idEntryQuery)
            ->where("status", "Pendiente")
            ->where("deleted", false)->get();

        $userIds = [];
        foreach ($pendingRecommendations as $recommendation){
            if($recommendation->recommendUser  && !in_array($recommendation->recommendUser, $userIds))
                $userIds[] = $recommendation->recommendUser;

            if($recommendation->recommendBy && !in_array($recommendation->recommendBy , $userIds))
                $userIds[] = $recommendation->recommendBy;
        }

        $users = User::whereIn("id", $userIds)->get()->keyBy('id');

        foreach ($pendingRecommendations as $recommendation){
            $recommendation->user = $recommendation->recommendUser ? $users[$recommendation->recommendUser] : null;
            $recommendation->by = $recommendation->recommendBy ? $users[$recommendation->recommendBy] : null;
        }

        return $pendingRecommendations;
    }

    public function listByEntryQuery($idEntryQuery){
        $recommendations = Recommendation::where("idEntryQuery", $idEntryQuery)->where("status", "Pendiente")->where("deleted", false)->get();
        $userRecommendations = [];
        foreach ($recommendations as $recommendation) {
            array_push($userRecommendations, $recommendation->recommendUser);
        }

        return $userRecommendations;
    }

    public function delete($id){
        $recommendation = Recommendation::find($id);

        if(!$recommendation) return response()->json(['error' => 'error en la busqueda'], 400);

        $recommendation->deleted = true;
        $recommendation->save();

        return response()->json(['success' => 'Error reportado eliminado'], 201);
    }

}

