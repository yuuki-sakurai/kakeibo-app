<?php

use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::post('expenses', [ExpenseController::class, 'store']);
    Route::get('monthly-summary', [ExpenseController::class, 'summary']);
    Route::get('stores', [ExpenseController::class, 'stores']);
});
