<?php

use App\Http\Controllers\Api\HotelSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('hotels')->group(function () {
    Route::get('/search', [HotelSearchController::class, 'search']);
});
