<?php

use Illuminate\Http\Request;

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
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => '/v1', 'namespace' => 'Api\V1'], function(){
    Route::group(['prefix' => '/users', 'as' => 'users.'], function(){
        Route::get('/', 'UsersController@get')->name('get');
        Route::get('/find', 'UsersController@find')->name('find');
        Route::post('/create', 'UsersController@create')->name('create');
        Route::patch('/update', 'UsersController@update')->name('update');
        Route::delete('/delete', 'UsersController@delete')->name('delete');
    });

    Route::group(['prefix' => '/servers', 'as' => 'servers.'], function(){
        Route::get('/', 'ServersController@get')->name('get');
        Route::get('/find', 'ServersController@find')->name('find');
        Route::post('/create', 'ServersController@create')->name('create');
        Route::patch('/update', 'ServersController@update')->name('update');
        Route::delete('/delete', 'ServersController@delete')->name('delete');
    });

    Route::group(['prefix' => '/config', 'as' => 'config.'], function(){
        Route::get('/', 'ConfigController@get')->name('get');

        Route::group(['prefix' => '/services', 'as' => 'services.'], function(){
            Route::get('/', 'ServicesController@get')->name('get');
            Route::post('/create', 'ServicesController@create')->name('create');
            Route::patch('/{id}/update', 'ServicesController@update')->name('update');
            Route::delete('/delete', 'ServicesController@delete')->name('delete');
        });

        Route::group(['prefix' => '/groups', 'as' => 'groups.'], function(){
            Route::get('/', 'GroupsController@get')->name('get');
            Route::post('/create', 'GroupsController@create')->name('create');
            Route::patch('/{id}/update', 'GroupsController@update')->name('update');
            Route::delete('/delete', 'GroupsController@delete')->name('delete');
        });

        Route::group(['prefix' => '/departments', 'as' => 'departments.'], function(){
            Route::get('/', 'DepartmentsController@get')->name('get');
            Route::post('/create', 'DepartmentsController@create')->name('create');
            Route::patch('/{id}/update', 'DepartmentsController@update')->name('update');
            Route::delete('/delete', 'DepartmentsController@delete')->name('delete');
        });
        
    });
});