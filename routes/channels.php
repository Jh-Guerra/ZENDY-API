<?php

use Illuminate\Support\Facades\Broadcast;
use \App\Models\User;
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
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('chats.{id}', function ($user, $id) {
    $participant = Participant::where("idChat", $id)->where("idUser", $user->id)->where("deleted", false)->first();
    return $participant != null;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    $User = User::where("id", $id)->where("deleted", false)->first();
    return $User != null;
});

Broadcast::channel('consulta.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('mensaje.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('aceptarConsulta.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('cierreConsulta.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('mensajeActivo.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

Broadcast::channel('cantidadNoti.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});

