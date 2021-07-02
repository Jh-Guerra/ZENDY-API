<?php

namespace App\Http\Controllers;

    use App\Models\Recommendation;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Http\Request;
    use JWTAuth;
    use Illuminate\Support\Facades\Crypt;

class RecommendationController extends Controller
{
    private function recommendationValues($recommendation, $request){
        $recommendation->codeRecommendation = $request->codeRecommendation;
        $recommendation->idChat = $request->idChat;
        $recommendation->idUser = $request->idUser;
        $recommendation->accepted = $request->accepted;
        $recommendation->acceptanceDate = date('Y-m-d',strtotime($request->recomendationDate));
        $recommendation->idRecommendedBy = $request->idRecommendedBy;
        $recommendation->recomendationDate =  date('Y-m-d',strtotime($request->recomendationDate));
    }

    public function register(Request $request){
        $recommendation = new Recommendation();
        $this->recommendationValues($recommendation, $request);
        $recommendation->save();

        $token = JWTAuth::fromUser($recommendation);

        return response()->json(compact('recommendation','token'),201);
    }

    public function update(Request $request, $id){
        $recommendation = Recommendation::find($id);

        if(!$recommendation){
            return response()->json(['error' => 'error en la busqueda'], 400);
        }

        $this->recommendationValues($recommendation, $request);
        $recommendation->save();

        return response()->json($recommendation);
    }

    public function find($id){
        $recommendation = Recommendation::find($id);

        if(!$recommendation){
            return response()->json(['error' => 'error en la busqueda'], 400);
        }

        return response()->json(compact('recommendation'),201);
    }

    public function list(){
        return Recommendation::where('accepted', '!=', true)->orderBy("codeRecommendation")->get();
    }

    public function delete($id){
        $recommendation = Recommendation::find($id);

        if(!$recommendation){
            return response()->json(['error' => 'error en la busqueda'], 400);
        }

        $recommendation->accepted = true;
        $recommendation->save();

        return response()->json(['success' => 'Recomendacion Aceptada'], 201);
    }
}

