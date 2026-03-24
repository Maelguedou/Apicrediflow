<?php

use App\Http\Controllers\Api\Authcontroller;
use App\Http\Controllers\Api\Client\AdhesionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register',[Authcontroller::class,'register']);
Route::post('/verify',[Authcontroller::class,'verifyOtp']);
Route::post('/Client_login',[Authcontroller::class,'Client_login']);

Route::middleware(['auth:sanctum'])->group(function(){
    Route::post('/join-request',[AdhesionController::class,'JoinExistGroup']);
    Route::post('/ask-request',[AdhesionController::class,'Request']);
    Route::post('/Client_logout',[Authcontroller::class,'Client_logout']);
    Route::get('/Client_tontines',[TontineController::class,'getAll']);
});
