<?php

use App\Http\Controllers\Api\V1\GoldPriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/gold-prices/all', [GoldPriceController::class, 'index']);
    Route::get('/gold-prices/highlight', [GoldPriceController::class, 'highlight']);
    Route::get('/gold-prices/chart-data', [GoldPriceController::class, 'history']);
});
