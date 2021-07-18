<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', 'App\Http\Controllers\UserController@register');

Route::post('login', 'App\Http\Controllers\UserController@authenticate');

Route::group(['middleware' => ['jwt.verify']], function() {

    Route::post('user','App\Http\Controllers\UserController@getAuthenticatedUser');

    Route::prefix('chats-client')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ChatClientController@register');
    });
});

Route::prefix('companies')->group(function () {
    Route::post('register', 'App\Http\Controllers\CompanyController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\CompanyController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\CompanyController@find');
    Route::get('/list', 'App\Http\Controllers\CompanyController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\CompanyController@delete');
});

Route::prefix('participants')->group(function () {
    Route::post('/register', 'App\Http\Controllers\ParticipantController@register');
    Route::get('/find/{id}', 'App\Http\Controllers\ParticipantController@find');
    Route::put('/update/{id}', 'App\Http\Controllers\ParticipantController@update');
    Route::get('/list', 'App\Http\Controllers\ParticipantController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\ParticipantController@delete');
});

Route::prefix('users')->group(function () {
    Route::post('/update/{id}', 'App\Http\Controllers\UserController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\UserController@find');
    Route::get('/list', 'App\Http\Controllers\UserController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\UserController@delete');
});

Route::prefix('messages')->group(function () {
    Route::post('/register', 'App\Http\Controllers\MessageController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\MessageController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\MessageController@find');
    Route::get('/list', 'App\Http\Controllers\MessageController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\MessageController@delete');
});

Route::prefix('recommendations')->group(function () {
    Route::post('register', 'App\Http\Controllers\RecommendationController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\RecommendationController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\RecommendationController@find');
    Route::get('/list', 'App\Http\Controllers\RecommendationController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\RecommendationController@delete');
});

Route::prefix('chats')->group(function () {
    Route::post('/register', 'App\Http\Controllers\ChatController@register');
    Route::post('/registerClientChat', 'App\Http\Controllers\ChatController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\ChatController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\ChatController@find');
    Route::get('/list', 'App\Http\Controllers\ChatController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\ChatController@delete');
});

Route::prefix('Error')->group(function () {
    Route::post('register', 'App\Http\Controllers\ErrorController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\ErrorController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\ErrorController@find');
    Route::get('/list', 'App\Http\Controllers\ErrorController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\ErrorController@delete');
});

Route::prefix('Notification')->group(function(){
    Route::post('register', 'App\Http\Controllers\NotificationController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\NotificationController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\NotificationController@find');
    Route::get('/list', 'App\Http\Controllers\NotificationController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\NotificationController@delete');

});

Route::prefix('NotificationView')->group(function(){
    Route::post('register', 'App\Http\Controllers\NotificationViewController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\NotificationViewController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\NotificationViewController@find');
    Route::get('/list', 'App\Http\Controllers\NotificationViewController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\NotificationViewController@delete');

});


