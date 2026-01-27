<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API;

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

Route::middleware(['api_key'])->group(function (){

    // Temporary routes for full check
    Route::post('/temporary-route/transport-ids', [\App\Http\Controllers\TemporaryController::class, 'transport_ids']);
    Route::post('/temporary-route/driver-ids', [\App\Http\Controllers\TemporaryController::class, 'driver_ids']);
    Route::post('/temporary-route/transport-driver-pairs', [\App\Http\Controllers\TemporaryController::class, 'transport_driver_pairs']);
    Route::post('/temporary-route/transport-status-pairs', [\App\Http\Controllers\TemporaryController::class, 'transport_status_pairs']);
    Route::post('/temporary-route/transports-info', [\App\Http\Controllers\TemporaryController::class, 'transports_info']);
    Route::post('/temporary-route/drivers-info', [\App\Http\Controllers\TemporaryController::class, 'drivers_info']);

    Route::post('/temporary-route/transport-fix', [\App\Http\Controllers\TemporaryController::class, 'transport_fix']);

    Route::post('/transport/request', [API\DamaroController::class, 'transport_request'])->name('transport.request');
    Route::post('/transport/url', [API\DamaroController::class, 'transport_url'])->name('transport.url');
    Route::post('/transports/exist', [API\DamaroController::class, 'transports_exist'])->name('transports.exist');
    Route::post('/transport/delete', [API\DamaroController::class, 'transport_delete'])->name('transport.delete');
    Route::post('/transport/payment', [API\DamaroController::class, 'transport_payment'])->name('transport.payment');
    Route::post('/transport/existing', [API\DamaroController::class, 'existing_transports'])->name('transport.existing');
    Route::post('/transport/modify_driver_notice', [API\DamaroController::class, 'modify_driver_notice'])->name('transport.modify_driver_notice');
    Route::post('/transport/assign_driver_link', [API\DamaroController::class, 'assign_driver_link'])->name('transport.assign_driver_link');

    Route::post('/transports/get', [API\TransportsController::class, 'get'])->name('transports.get');
    Route::post('/transport/file', [API\TransportsController::class, 'file'])->name('transport.file');
    Route::post('/transport/file/success', [API\TransportsController::class, 'success'])->name('transport.success');
    Route::post('/transport/data', [API\TransportsController::class, 'data'])->name('transport.data');
    Route::post('/transport/status', [API\TransportsController::class, 'status'])->name('transport.status');
    Route::post('/transport/exists', [API\TransportsController::class, 'exists'])->name('transport.exists');
    Route::post('/transport/bill-delete', [API\TransportsController::class, 'bill_delete'])->name('transport.bill_delete');
    Route::post('/transport/docs-delete', [API\TransportsController::class, 'docs_delete'])->name('transport.docs_delete');

    Route::post('/unsubscribes/get', [API\EmailUnsubscribesController::class, 'get'])->name('unsubscribes.get');
    Route::post('/unsubscribes/delete', [API\EmailUnsubscribesController::class, 'delete'])->name('unsubscribes.delete');
    Route::post('/unsubscribes/url', [API\EmailUnsubscribesController::class, 'url'])->name('unsubscribe.url');

});

