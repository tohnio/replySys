<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OsController;
use App\Http\Controllers\WebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ordens-servico', [OsController::class, 'index']);
Route::post('/ordens-servico', [OsController::class, 'store']);
Route::put('/ordens-servico/{id}', [OsController::class, 'update']);
Route::put('/ordens-servico/{id}/status', [OsController::class, 'updateStatus']);
Route::post('/ordens-servico/{id}/redial', [OsController::class, 'redial']);
Route::post('/webhook/n8n', [WebhookController::class, 'handleN8nWebhook']);
Route::get('/webhook/n8n/call-details/{externalCallId}', [WebhookController::class, 'getCallDetails']);
Route::get('/webhook/n8n/client-details/{phone}', [WebhookController::class, 'getClientDetails']);
