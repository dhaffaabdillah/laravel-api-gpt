<?php

use App\Http\Controllers\ChatbotController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/history-chat', [ChatbotController::class, 'fetchConversationHistory']);
Route::post('/chat-history', [ChatbotController::class, 'fetchChatHistory']); //  ini untuk history chat sidebar
Route::post('/chatbot', [ChatbotController::class, 'sendMessage']);
Route::post('/analyze-data', [ChatbotController::class, 'analyzeData']);
Route::post('/analyze-excel', [ChatbotController::class, 'analyzeExcel']);
Route::post('/speech-to-text', [ChatbotController::class, 'convertSpeechToText']);
Route::post('/train-model', [ChatbotController::class, 'trainModel']); //  ini untuk history chat sidebar