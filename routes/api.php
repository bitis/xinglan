<?php

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
Route::get('area', 'AreaController@index');

Route::prefix('auth')->group(function () {
    Route::post('login', 'AccountController@login');
    Route::post('register', 'AccountController@register');
    Route::post('logout', 'AccountController@logout')->middleware('auth');
});

Route::middleware('auth')->group(function () {

    Route::post('upload', 'UploadController@form');

    Route::prefix('enum')->group(function () {
        Route::get('goodsType', 'EnumController@goodsType');
        Route::get('companyType', 'EnumController@companyType');
        Route::get('insuranceType', 'EnumController@insuranceType');
        Route::get('menuType', 'EnumController@menuType');
        Route::get('orderStatus', 'EnumController@orderStatus');
        Route::get('orderCloseStatus', 'EnumController@orderCloseStatus');
        Route::get('messageType', 'EnumController@messageType');
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
        Route::get('getByCompany', 'RoleController@getByCompany');
    });

    Route::prefix('account')->group(function () {
        Route::get('detail', 'AccountController@detail');
        Route::post('form', 'AccountController@form');
    });

    Route::prefix('user')->group(function () {
        Route::get('list', 'UserController@index');
        Route::post('form', 'UserController@form');
        Route::get('getByRoles', 'UserController@getByRoles');
    });

    Route::prefix('goodsType')->group(function () {
        Route::get('list', 'GoodsTypeController@index');
        Route::post('form', 'GoodsTypeController@form');
    });

    Route::prefix('company')->group(function () {
        Route::get('list', 'CompanyController@index');
        Route::get('tree', 'CompanyController@tree');
        Route::post('form', 'CompanyController@form');
        Route::get('branch', 'CompanyController@branch');
        Route::get('items', 'CompanyController@items');
    });

    Route::prefix('provider')->group(function () {
        Route::get('list', 'ProviderController@index');
        Route::post('form', 'ProviderController@form');
        Route::get('items', 'ProviderController@items');
    });

    Route::prefix('providerOption')->group(function () {
        Route::get('list', 'ProviderOptionController@index');
        Route::post('form', 'ProviderOptionController@form');
        Route::get('getRepeatRegion', 'ProviderOptionController@getRepeatRegion');
    });

    Route::prefix('order')->group(function () {
        Route::get('customer', 'OrderController@customer');
        Route::get('list', 'OrderController@index');
        Route::post('form', 'OrderController@form');
        Route::post('dispatchCheckUser', 'OrderController@dispatchCheckUser');
        Route::post('dispatchProvider', 'OrderController@dispatchProvider');
        Route::post('accept', 'OrderController@accept');
    });

    Route::prefix('goodsPrice')->group(function () {
        Route::get('list', 'GoodsPriceController@index');
        Route::post('form', 'GoodsPriceController@form');
        Route::get('cats', 'GoodsPriceController@cats');
        Route::get('catsTree', 'GoodsPriceController@catsTree');
    });

    Route::prefix('bidOption')->group(function () {
        Route::get('list', 'BidOptionController@index');
        Route::post('form', 'BidOptionController@form');
    });

    Route::prefix('message')->group(function () {
        Route::get('list', 'MessageController@index');
        Route::post('accept', 'MessageController@accept');
    });
});
