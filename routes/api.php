<?php

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



});

Route::prefix('users')->group(function () {
    Route::post('/update/{id}', 'App\Http\Controllers\UserController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\UserController@find');
    Route::get('/list', 'App\Http\Controllers\UserController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\UserController@delete');
});

Route::prefix('Recommendation')->group(function () {
    Route::post('register', 'App\Http\Controllers\RecommendationController@register');
    Route::post('/update/{id}', 'App\Http\Controllers\RecommendationController@update');
    Route::get('/find/{id}', 'App\Http\Controllers\RecommendationController@find');
    Route::get('/list', 'App\Http\Controllers\RecommendationController@list');
    Route::delete('/delete/{id}', 'App\Http\Controllers\RecommendationController@delete');
});
