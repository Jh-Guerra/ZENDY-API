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

    public function updateFile($file, $path, $name) {
        $original_name = $file->getClientOriginalName();
        $size = $file->getSize();
        $extension = $file->getClientOriginalExtension();

        $nameExt = $name.'.'.$extension;
        $path = $file->storeAs('public/'.$path, $nameExt);

        return 'storage/'.$path."/".$nameExt;
    }
}
