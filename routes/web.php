<?php

use App\Http\Middleware\RequestApi;
use Illuminate\Support\Facades\Route;

// $prefix_file_manager_route = 'filemanager';
// $middleware_file_manager = \config('lfm.middleware');

// Route::group(['prefix' => $prefix_file_manager_route, 'middleware' => $middleware_file_manager], function () {
//     if (class_exists("\UniSharp\LaravelFilemanager\Lfm")) {
//         \UniSharp\LaravelFilemanager\Lfm::routes();
//     }
// });

Route::get('/', function () {
    return view('welcome');
});

$apiRoutePrefix = \config('appstra.admin_route_prefix');

Route::group([
    'prefix' => $apiRoutePrefix,
    'namespace' => 'App\Http\Controllers\Frontend', 'as' => 'appstra.',
], function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::group(['prefix' => 'public'], function () {
            Route::group(['prefix' => 'peraturan'], function () {
                Route::get('view-doc/', 'PeraturanController@documentViewer');
            });
        });
    });


    Route::get('testing/', 'TestController@roles');

});
