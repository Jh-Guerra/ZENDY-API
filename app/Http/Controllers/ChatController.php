<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function chat(){
        return view('chat');
    }

    public function getCurrentChats(){
        return Chat::where("status", "active")->get();
    }
}
