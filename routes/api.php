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
        Route::get('wuSunCheckStatus', 'EnumController@wuSunCheckStatus');
        Route::get('orderPlanType', 'EnumController@orderPlanType');
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
        Route::get('detail', 'OrderController@detail');
        Route::post('dispatchCheckUser', 'OrderController@dispatchCheckUser');
        Route::post('dispatchProvider', 'OrderController@dispatchProvider');
        Route::post('check', 'OrderController@check'); // 物损查勘人员查勘
        Route::post('confirmPlan', 'OrderController@confirmPlan'); // 物损确认方案
    });

    Route::prefix('quotation')->group(function () {
        Route::post('list', 'OrderQuotationController@index'); // 获取某个某单的所有报价 （保险公司开标）
        Route::get('getByOrderId', 'OrderQuotationController@getByOrderId'); // 获取当前公司某工单的报价详情 （物损公司）
        Route::post('form', 'OrderQuotationController@form'); // 提交报价（物损公司）
        Route::post('import', 'OrderQuotationController@import'); // 导入报价明细（物损公司）
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

    Route::prefix('approval')->group(function () {
        Route::prefix('option')->group(function () {
            Route::get('list', 'ApprovalOptionController@index');
            Route::post('form', 'ApprovalOptionController@form');
        });
        Route::get('list', 'ApprovalController@index');
        Route::post('form', 'ApprovalController@form');
    });
});
