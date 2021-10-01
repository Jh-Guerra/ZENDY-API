<?php

use Illuminate\Support\Facades\Broadcast;
use \App\Models\Chat;
use \App\Models\Participant;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('chats.{id}', function ($user, $id) {
    $participant = Participant::where("idChat", $id)->where("idUser", $user->id)->where("deleted", false)->first();
    return $participant != null;
});
