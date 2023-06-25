<?php

use Illuminate\Http\Request;
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

Route::prefix('auth')->group(function () {
    Route::post('login', 'AccountController@login');
    Route::post('register', 'AccountController@register');
    Route::post('logout', 'AccountController@logout')->middleware('auth');
});

Route::middleware('auth')->group(function () {

    Route::prefix('enum')->group(function () {
        Route::get('goodsType', 'EnumController@goodsType');
        Route::get('companyType', 'EnumController@companyType');
        Route::get('insuranceType', 'EnumController@insuranceType');
        Route::get('menuType', 'EnumController@menuType');
    });

    Route::prefix('menu')->group(function () {
        Route::get('list', 'MenuController@index');
        Route::post('form', 'MenuController@form');
        Route::post('delete', 'MenuController@delete');
    });

    Route::prefix('role')->group(function () {
        Route::get('list', 'RoleController@index');
        Route::get('menus', 'RoleController@menus');
        Route::post('form', 'RoleController@form');
        Route::post('permission', 'RoleController@permission');
    });

    Route::prefix('account')->group(function () {
        Route::get('detail', 'AccountController@detail');
        Route::post('form', 'AccountController@form');
    });

    Route::prefix('user')->group(function () {
        Route::get('list', 'UserController@index');
        Route::post('form', 'UserController@form');
    });

    Route::prefix('goodsType')->group(function () {
        Route::get('list', 'GoodsTypeController@index');
        Route::post('form', 'GoodsTypeController@form');
    });

    Route::prefix('company')->group(function () {
        Route::get('list', 'CompanyController@index');
        Route::get('tree', 'CompanyController@tree');
        Route::post('form', 'CompanyController@form');
    });

    Route::prefix('provider')->group(function () {
        Route::get('list', 'ProviderController@index');
        Route::post('form', 'ProviderController@form');
    });
});
