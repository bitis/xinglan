<?php

use App\Models\OrderQuotation;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn() => view('welcome'));

Route::prefix('agreement')->group(function () {
    Route::get('privacy', fn() => view('agreement.privacy'));
    Route::get('user', fn() => view('agreement.user'));
});

Route::prefix('quota')->group(function () {
    Route::get('security/{code}', 'App\Http\Controllers\OrderQuotationController@getBySecurityCode');
});
