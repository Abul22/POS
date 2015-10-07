<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', [
    'as' => 'checkout.index',
    'uses' => 'CheckoutController@index'
]);

Route::post('/', [
    'as' => 'checkout.store',
    'uses' => 'CheckoutController@store'
]);

Route::put('/{cartItem}', [
    'as' => 'checkout.update',
    'uses' => 'CheckoutController@update'
]);

Route::delete('/{cartItem}', [
    'as' => 'checkout.destroy',
    'uses' => 'CheckoutController@destroy'
]);

Route::post('/checkout/{cart}', [
    'as' => 'checkout.checkout',
    'uses' => 'CheckoutController@checkout'
]);

// Authentication routes...
Route::group(['namespace' => 'Auth', 'prefix' => 'auth'], function()
{
    Route::get('login', [
        'as' => 'login',
        'uses' => 'AuthController@getLogin'
    ]);

    Route::post('login', [
        'as' => 'login',
        'uses' => 'AuthController@postLogin'
    ]);

    Route::get('logout', [
        'as' => 'logout',
        'uses' => 'AuthController@getLogout'
    ]);
});

Route::group(['middleware' => 'auth'], function()
{
    Route::resource('product', 'ProductController');

    Route::resource('product.stock', 'StockController',[
        'except' => ['index', 'show']
    ]);
});
