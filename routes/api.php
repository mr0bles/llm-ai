<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\LLM\Interfaces\Controllers\EmbeddingController;
use App\RAC\Interfaces\Controllers\RACController;

Route::prefix('v1')->group(function () {
    Route::prefix('embeddings')->group(function () {
        Route::post('/', [EmbeddingController::class, 'store']);
        Route::post('/search', [EmbeddingController::class, 'search']);
        Route::delete('/', [EmbeddingController::class, 'delete']);
    });

    Route::prefix('rac')->group(function () {
        Route::post('/query', [RACController::class, 'query']);
        Route::post('/documents', [RACController::class, 'addDocument']);
    });
});
