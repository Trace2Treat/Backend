<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{UserController,TransactionController, FoodController, ProductController, TrashRequestsController, RestaurantController};

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


Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

Route::get('/getFile/{folder}/{filename}', function ($folder,$filename) {
    return response()->file(storage_path('app/public/').$folder.'/'.$filename);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class,'index'])->name('users.index');
        Route::post('/store', [UserController::class,'store'])->name('users.store');
        Route::put('/update/{id}', [UserController::class,'update'])->name('users.update');
        Route::delete('/delete/{id}', [UserController::class,'destroy'])->name('users.delete');
    });

    Route::prefix('foods')->group(function () {
        Route::get('/', [FoodController::class,'index'])->name('foods.index');
        Route::post('/store', [FoodController::class,'store'])->name('foods.store');
        Route::put('/update/{id}', [FoodController::class,'update'])->name('foods.update');
        Route::delete('/delete/{id}', [FoodController::class,'destroy'])->name('foods.delete');
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class,'index'])->name('products.index');
        Route::post('/store', [ProductController::class,'store'])->name('products.store');
        Route::put('/update/{id}', [ProductController::class,'update'])->name('products.update');
        Route::delete('/delete/{id}', [ProductController::class,'destroy'])->name('products.delete');
    });

    Route::prefix('trash-requests')->group(function () {
        Route::get('/', [TrashRequestsController::class,'index'])->name('trash-requests.index');
        Route::post('/store', [TrashRequestsController::class,'store'])->name('trash-requests.store');
        Route::put('/update/{id}', [TrashRequestsController::class,'update'])->name('trash-requests.update');
        Route::post('/change-status/{id}', [TrashRequestsController::class,'changeStatus'])->name('trash-requests.change-status');
        Route::delete('/delete/{id}', [TrashRequestsController::class,'destroy'])->name('trash-requests.delete');
    });

    Route::prefix('restaurant')->group(function () {
        Route::get('/', [RestaurantController::class,'index'])->name('restaurant.index');
        Route::post('/store', [RestaurantController::class,'store'])->name('restaurant.store');
        Route::put('/update/{id}', [RestaurantController::class,'update'])->name('restaurant.update');
        Route::delete('/delete/{id}', [RestaurantController::class,'destroy'])->name('restaurant.delete');
    });

    Route::prefix('transaction')->group(function () {
        Route::get('/', [TransactionController::class,'index'])->name('transaction.index');
        Route::post('/purchase-food', [TransactionController::class,'purchaseFood'])->name('transaction.purchase-food');
        Route::post('/change-status/{id}', [TransactionController::class,'changeStatus'])->name('transaction.change-status');
        Route::post('/store', [TransactionController::class,'store'])->name('transaction.store');
        Route::put('/update/{id}', [TransactionController::class,'update'])->name('transaction.update');
        Route::delete('/delete/{id}', [TransactionController::class,'destroy'])->name('transaction.delete');
    });
});
