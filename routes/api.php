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






Route::post('/orders','App\Http\Controllers\Api\OrderController@index');
Route::get('/orders/list/{customer_id}','App\Http\Controllers\Api\OrderController@show');
Route::post('/orders/update/{id}','App\Http\Controllers\Api\OrderController@update');
Route::get('/orders/delete/{id}','App\Http\Controllers\Api\OrderController@destroy');