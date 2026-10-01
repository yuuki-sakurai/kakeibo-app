<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExpenseCsvController;
use App\Http\Controllers\Api\MonthlyBudgetController;
use App\Http\Middleware\PrivateApiResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['web', PrivateApiResponse::class])->group(function () {
    Route::get('auth/session', [AuthController::class, 'session']);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::middleware('auth')->group(function () {
        Route::get('categories', [ExpenseCategoryController::class, 'index']);
        Route::post('categories', [ExpenseCategoryController::class, 'store']);
        Route::post('expense-imports/preview', [ExpenseCsvController::class, 'preview']);
        Route::post('expense-imports', [ExpenseCsvController::class, 'store']);
        Route::get('expenses', [ExpenseController::class, 'index']);
        Route::post('expenses', [ExpenseController::class, 'store']);
        Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->whereNumber('expense');
        Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->whereNumber('expense');
        Route::get('monthly-summary', [ExpenseController::class, 'summary']);
        Route::get('monthly-budget', [MonthlyBudgetController::class, 'show']);
        Route::put('monthly-budget', [MonthlyBudgetController::class, 'update']);
        Route::get('stores', [ExpenseController::class, 'stores']);
    });
});
