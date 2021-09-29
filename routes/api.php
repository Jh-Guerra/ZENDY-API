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
    Route::prefix('users')->group(function () {
        Route::post('/update/{id}', 'App\Http\Controllers\UserController@update');
        Route::get('/find/{id}', 'App\Http\Controllers\UserController@find');
        Route::get('/list', 'App\Http\Controllers\UserController@list');
        Route::post('/list-available', 'App\Http\Controllers\UserController@listAvailable');
        Route::get('/list-by-company', 'App\Http\Controllers\UserController@listByCompany');
        Route::delete('/delete/{id}', 'App\Http\Controllers\UserController@delete');
        Route::get('/listUserOnline', 'App\Http\Controllers\UserController@listUserOnline');
        Route::post('/updateStatus/{id}', 'App\Http\Controllers\UserController@updateUserOffLine');
        Route::post('/updateStatusOn/{id}', 'App\Http\Controllers\UserController@updateUserOnLine');
        Route::post('/list-available-sameCompany', 'App\Http\Controllers\UserController@listAvailableSameCompany');

    });

    Route::prefix('companies')->group(function () {
        Route::post('register', 'App\Http\Controllers\CompanyController@register');
        Route::post('/update/{id}', 'App\Http\Controllers\CompanyController@update');
        Route::get('/find/{id}', 'App\Http\Controllers\CompanyController@find');
        Route::get('/list', 'App\Http\Controllers\CompanyController@list');
        Route::get('/list/count/users', 'App\Http\Controllers\CompanyController@listWithUsersCount');
        Route::delete('/delete/{id}', 'App\Http\Controllers\CompanyController@delete');
    });

    Route::prefix('chats')->group(function () {
        Route::get('/find/{id}', 'App\Http\Controllers\ChatController@find');
        Route::get('/active-list', 'App\Http\Controllers\ChatController@listActive');
        Route::delete('/delete/{id}', 'App\Http\Controllers\ChatController@delete');
        Route::post('/finalize/{id}', 'App\Http\Controllers\ChatController@finalize');
    });

    Route::prefix('chats-client')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ChatClientController@register');
        Route::get('/list', 'App\Http\Controllers\ChatClientController@list');
    });

    Route::prefix('chats-company')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ChatCompanyController@register');
//        Route::get('/list', 'App\Http\Controllers\ChatClientController@list');
    });

    Route::prefix('chats-internal')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ChatInternalController@register');
        Route::get('/list', 'App\Http\Controllers\ChatInternalController@list');
    });

    Route::prefix('entry-queries')->group(function () {
        Route::post('/register', 'App\Http\Controllers\EntryQueryController@register');
        Route::get('/find/{id}', 'App\Http\Controllers\EntryQueryController@find');
        Route::post('/update/{id}', 'App\Http\Controllers\EntryQueryController@update');
        Route::get('/list', 'App\Http\Controllers\EntryQueryController@list');
        Route::get('/list-pendings', 'App\Http\Controllers\EntryQueryController@listPendings');
        Route::get('/list-query/{status}', 'App\Http\Controllers\EntryQueryController@listQuery');
        Route::delete('/delete/{id}', 'App\Http\Controllers\EntryQueryController@delete');
        Route::post('/accept/{id}', 'App\Http\Controllers\EntryQueryController@accept');
        Route::post('/{id}/recommend', 'App\Http\Controllers\EntryQueryController@recommendUser');
    });

    Route::prefix('errors')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ErrorController@register');
        Route::get('/find/{id}', 'App\Http\Controllers\ErrorController@find');
        Route::get('/list', 'App\Http\Controllers\ErrorController@list');
        Route::get('/list-by-user', 'App\Http\Controllers\ErrorController@listByUser');
        Route::delete('/delete/{id}', 'App\Http\Controllers\ErrorController@delete');
    });

    Route::prefix('notifications')->group(function(){
        Route::post('/register-company', 'App\Http\Controllers\NotificationController@registerCompanyNotification');
        Route::post('/register-companies', 'App\Http\Controllers\NotificationController@registerCompaniesNotification');
        Route::get('/find/{id}', 'App\Http\Controllers\NotificationController@find');
        Route::get('/admin/list', 'App\Http\Controllers\NotificationController@adminList');
        Route::get('/list', 'App\Http\Controllers\NotificationController@list');
        Route::delete('/delete/{id}', 'App\Http\Controllers\NotificationController@delete');
    });

    Route::prefix('recommendations')->group(function () {
        Route::post('register', 'App\Http\Controllers\RecommendationController@register');
        Route::post('/update/{id}', 'App\Http\Controllers\RecommendationController@update');
        Route::get('/find/{id}', 'App\Http\Controllers\RecommendationController@find');
        Route::get('/list', 'App\Http\Controllers\RecommendationController@list');
        Route::get('/list-my-recommendations', 'App\Http\Controllers\RecommendationController@listMyRecommendations');
        Route::delete('/delete/{id}', 'App\Http\Controllers\RecommendationController@delete');
        Route::get('/list-by-entry-query/{idEntryQuery}', 'App\Http\Controllers\RecommendationController@listByEntryQuery');
        Route::get('/list-existing-recommendations/{idEntryQuery}','App\Http\Controllers\RecommendationController@listExistingRecommendations');
    });

    Route::prefix('frequentQueries')->group(function () {
        Route::get('/find/{id}', 'App\Http\Controllers\FrequentQueryController@find');
        Route::get('/list', 'App\Http\Controllers\FrequentQueryController@list');
    });

    Route::prefix('messages')->group(function () {
        Route::post('/register', 'App\Http\Controllers\MessageController@register');
        Route::get('/find/{id}', 'App\Http\Controllers\MessageController@find');
        Route::get('/list/{idChat}', 'App\Http\Controllers\MessageController@list');
        Route::delete('/delete/{id}', 'App\Http\Controllers\MessageController@delete');
    });

    Route::prefix('module')->group(function () {
        Route::get('/find/{id}', 'App\Http\Controllers\ModuleController@find');
        Route::get('/list', 'App\Http\Controllers\ModuleController@list');
    });
});

Route::prefix('participants')->group(function () {
    Route::post('/register/{id}', 'App\Http\Controllers\ParticipantController@register');
    Route::get('/find/{id}', 'App\Http\Controllers\ParticipantController@find');
    Route::put('/update/{id}', 'App\Http\Controllers\ParticipantController@update');
    Route::get('/list', 'App\Http\Controllers\ParticipantController@list');
    Route::post('/delete', 'App\Http\Controllers\ParticipantController@delete');
});



Route::prefix('NotificationView')->group(function(){
    Route::post('register', 'App\Http\Controllers\NotificationViewController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\NotificationViewController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\NotificationViewController@find');
    Route::get('/list', 'App\Http\Controllers\NotificationViewController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\NotificationViewController@delete');

});

