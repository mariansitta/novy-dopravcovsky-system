<?php

use App\Http\Controllers\Auth\RegisterController;
use \App\Http\Controllers\TransportsController;
use \App\Http\Controllers\SettingsController;
use \App\Http\Controllers\EmailUnsubscribesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use \App\Http\Controllers\Auth\ForgotPasswordController;
use \App\Http\Controllers\Auth\ResetPasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/unsubscribe', [EmailUnsubscribesController::class, 'unsubscribe'])->name('unsubscribe');

Route::get('/enter/{token}', [TransportsController::class, 'enter'])->name('transports.enter');

Route::middleware(['auth'])->group(function(){

    Route::get('/', [TransportsController::class, 'index'])->name('index');

    //Route::get('/settings/edit', [SettingsController::class, 'edit'])->name('settings.edit');
    //Route::get('/settings/edit-password', [SettingsController::class, 'edit_password'])->name('settings.edit_password');
    //Route::post('settings/update', [SettingsController::class, 'update_data'])->name('settings.update');
    //Route::post('settings/update-password', [SettingsController::class, 'update_password'])->name('settings.update_password');

    Route::post('/transports/bill_document/{id}', [TransportsController::class, 'bill_document'])->name('transports.bill_document');
    Route::post('/transports/doc_document/{id}', [TransportsController::class, 'doc_document'])->name('transports.doc_document');
    Route::post('/transports/documents/{id}', [TransportsController::class, 'documents'])->name('transports.documents');
    Route::post('/transports/document/delete/{id}', [ TransportsController::class, 'document_delete' ])->name('transports.document_delete');
    Route::get('/transports/edit/{id}', [TransportsController::class, 'edit'])->name('transports.edit');
    Route::get('/transports/bill/{id}', [TransportsController::class, 'bill'])->name('transports.bill');
    Route::get('/transports/docs/{id}', [TransportsController::class, 'docs'])->name('transports.docs');
    Route::get('/transports/ajax', [TransportsController::class, '_table'])->name('transports.ajax');
    Route::get('/transports/transport-status/{id}', [TransportsController::class, 'transport_status'])->name('transports.transport_status');
    Route::post('/transports/transport-status-form/{id}', [TransportsController::class, 'transport_status_form'])->name('transports.transport_status_form');

});

Auth::routes([
    'register' => false,
    'reset' => false,
    'confirm' => false,
    'verify' => false
]);
