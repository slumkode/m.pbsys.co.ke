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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['checkIp'])->group(function () {
    Route::post( '/b2ccallback', 'Callbacks@processB2CRequestCallback' );
    Route::post( "/b2bcallback", 'Callbacks@processB2BRequestCallback' );
    Route::post( '/c2bvalidation', 'Callbacks@C2BRequestValidation' );
    Route::post( '/c2bconfirmation', 'Callbacks@processC2BRequestConfirmation' );
    Route::post( '/accountbalance', 'Callbacks@processAccountBalanceRequestCallback' );
    Route::post( '/reversalcallback', 'Callbacks@processReversalRequestCallBack' );
    Route::post( '/requeststkcallback', 'Callbacks@processSTKPushRequestCallback' );
    Route::post( '/querystkcallback', 'Callbacks@processSTKPushQueryRequestCallback' );
    Route::post( '/transstatcallback', 'Callbacks@processTransactionStatusRequestCallback' );
});
Route::post("/checkout",'Api\Api@checkout');

