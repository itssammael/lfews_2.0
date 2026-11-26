<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/filtered-data', [\App\Http\Controllers\Api\DataApiController::class, 'getFilteredData']);

Route::post('/sso/verify-credentials', [\App\Http\Controllers\Auth\SsoVerifyCredentialsController::class, 'verify'])
    ->name('api.sso.verify-credentials');
