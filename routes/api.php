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
        Route::get('/list-by-company/{idCompany}', 'App\Http\Controllers\UserController@listByCompany');
        Route::get('/list-admins', 'App\Http\Controllers\UserController@listAdmins');
        Route::delete('/delete/{id}', 'App\Http\Controllers\UserController@delete');
        Route::get('/listUserOnline', 'App\Http\Controllers\UserController@listUserOnline');
        Route::post('/updateStatus/{id}', 'App\Http\Controllers\UserController@updateUserOffLine');
        Route::post('/updateStatusOn/{id}', 'App\Http\Controllers\UserController@updateUserOnLine');
        Route::post('/list-available-sameCompany', 'App\Http\Controllers\UserController@listAvailableSameCompany');
        Route::post('/deleteImage', 'App\Http\Controllers\UserController@deleteImage');
        Route::get('/list-same-company', 'App\Http\Controllers\UserController@listSameCompany');
        Route::get('/list-company-notify', 'App\Http\Controllers\UserController@listCompanyNotify');
    });

    Route::prefix('companies')->group(function () {
        Route::post('register', 'App\Http\Controllers\CompanyController@register');
        Route::post('/update/{id}', 'App\Http\Controllers\CompanyController@update');
        Route::get('/find/{id}', 'App\Http\Controllers\CompanyController@find');
        Route::get('/list', 'App\Http\Controllers\CompanyController@list');
        Route::get('/list/count/users', 'App\Http\Controllers\CompanyController@listWithUsersCount');
        Route::delete('/delete/{id}', 'App\Http\Controllers\CompanyController@delete');
        Route::post('/deleteImage', 'App\Http\Controllers\CompanyController@deleteImage');
    });

    Route::prefix('chats')->group(function () {
        Route::get('/find/{id}', 'App\Http\Controllers\ChatController@find');
        Route::get('/findImages/{id}', 'App\Http\Controllers\ChatController@findImages');
        Route::get('/active-list', 'App\Http\Controllers\ChatController@listActive');
        Route::delete('/delete/{id}', 'App\Http\Controllers\ChatController@delete');
        Route::post('/finalize/{id}', 'App\Http\Controllers\ChatController@finalize');
        Route::post('/name/{id}', 'App\Http\Controllers\ChatController@nameChat');
        Route::post('/available-by-company', 'App\Http\Controllers\ChatController@listAvailableUsersByCompany');
        Route::get('/finalize-list', 'App\Http\Controllers\ChatController@listFinalize');
    });

    Route::prefix('chats-client')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ChatClientController@register');
    });

    Route::prefix('chats-company')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ChatCompanyController@register');
    });

    Route::prefix('chats-internal')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ChatInternalController@register');
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
        Route::get('/listFrequent', 'App\Http\Controllers\EntryQueryController@listFrequent');
        Route::post('/updateFrequent/{id}', 'App\Http\Controllers\EntryQueryController@updateFrequent');
        Route::post('/deleteImage', 'App\Http\Controllers\EntryQueryController@deleteImage');
        Route::post('/register-frequent', 'App\Http\Controllers\EntryQueryController@registerFrequent');
    });

    Route::prefix('errors')->group(function () {
        Route::post('/register', 'App\Http\Controllers\ErrorController@register');
        Route::post('/update/{id}', 'App\Http\Controllers\ErrorController@update');
        Route::get('/find/{id}', 'App\Http\Controllers\ErrorController@find');
        Route::get('/confirmError/{id}', 'App\Http\Controllers\ErrorController@confirmError');
        Route::delete('/errorSolved/{id}', 'App\Http\Controllers\ErrorController@errorSolved');
        Route::get('/list', 'App\Http\Controllers\ErrorController@list');
        Route::get('/list-by-user', 'App\Http\Controllers\ErrorController@listByUser');
        Route::delete('/delete/{id}', 'App\Http\Controllers\ErrorController@delete');
        Route::delete('/fakeError/{id}', 'App\Http\Controllers\ErrorController@fakeError');
        Route::post('/deleteImage', 'App\Http\Controllers\ErrorController@deleteImage');
    });

    Route::prefix('notifications')->group(function(){
        Route::post('/register-company', 'App\Http\Controllers\NotificationController@registerCompanyNotification');
        Route::post('/register-companies', 'App\Http\Controllers\NotificationController@registerCompaniesNotification');
        Route::post('/update/{id}', 'App\Http\Controllers\NotificationController@updateNotification');
        Route::post('/update-users-notified/{id}', 'App\Http\Controllers\NotificationController@updateListUsersNotified');
        Route::get('/find/{id}', 'App\Http\Controllers\NotificationController@find');
        Route::get('/admin/list', 'App\Http\Controllers\NotificationController@adminList');
        Route::get('/company/list', 'App\Http\Controllers\NotificationController@listNotificationsByCompany');
        Route::get('/user/list/{status}', 'App\Http\Controllers\NotificationController@listNotificationsByUser');
        Route::delete('/delete/{id}', 'App\Http\Controllers\NotificationController@delete');
        Route::post('/deleteImage', 'App\Http\Controllers\NotificationController@deleteImage');
        Route::post('/update-companies-notified/{id}', 'App\Http\Controllers\NotificationController@updateListCompaniesNotified');
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

    Route::prefix('notifications-viewed')->group(function(){
        Route::post('/register', 'App\Http\Controllers\NotificationViewedController@register');
        Route::post('/register-view/{id}', 'App\Http\Controllers\NotificationViewedController@registerViewed');
        Route::get('/find/{userId}/{notificationId}', 'App\Http\Controllers\NotificationViewedController@find');
        Route::get('/list/{notificationId}', 'App\Http\Controllers\NotificationViewedController@list');
        Route::get('/list-by-user', 'App\Http\Controllers\NotificationViewedController@listByUser');
        Route::delete('/delete/{id}', 'App\Http\Controllers\NotificationViewedController@delete');
    });

    Route::prefix('participants')->group(function () {
        Route::post('/register/{id}', 'App\Http\Controllers\ParticipantController@register');
        Route::get('/find/{id}', 'App\Http\Controllers\ParticipantController@find');
        Route::post('/update/{id}', 'App\Http\Controllers\ParticipantController@update');
        Route::get('/list', 'App\Http\Controllers\ParticipantController@list');
        Route::post('/delete', 'App\Http\Controllers\ParticipantController@delete');
        Route::post('/reset-pending-messages/{idChat}', 'App\Http\Controllers\ParticipantController@resetPendingMessages');
    });

});
