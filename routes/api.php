<?php

use App\Http\Middleware\RequestApi;
use Illuminate\Support\Facades\Route;

$apiRoutePrefix = \config('appstra.admin_route_prefix');

Route::group(['prefix' => $apiRoutePrefix,
    'namespace' => 'App\Http\Controllers\Backend', 'as' => 'appstra.', 'middleware' => [RequestApi::class]], function () {
    Route::group(['prefix' => 'v1'], function () {

        #MAINTENANCE ROUTE
        Route::group(['prefix' => 'maintenance'], function () {
            Route::post('/', 'MaintenanceController@isMaintenance');
        });

        #AUTH ROUTES
        Route::group(['prefix' => 'auth'], function () {
            Route::post('/login', 'AuthController@login');
            Route::post('/logout', 'AuthController@logout');
            Route::post('/refresh-token', 'AuthController@refreshToken');
            Route::post('/register', 'AuthController@register');
            Route::post('/forgot-password', 'AuthController@forgetPassword');
            Route::post('/forgot-password-verify', 'AuthController@validateTokenForgetPassword');
            Route::post('/reset-password', 'AuthController@resetPassword');
            Route::post('/verify', 'AuthController@verify');
            Route::post('/re-request-verification', 'AuthController@reRequestVerification');
            Route::post('/' . env('APPSTRA_SECRET_LOGIN_PREFIX'), 'AuthController@secretLogin');

            Route::get('/authentic-user', function () {
                return auth()->check() ? auth()->user()->authenticatedUser() : 0;
            });
        });

        #DASHBOARD CARD
        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/dash-card', 'DashboardController@cardList');
        });

        #USER_AUTH_PROFILE ROUTES
        Route::group(['prefix' => 'auth/user'], function () {
            Route::get('/', 'AuthController@getAuthenticatedUser');
            Route::put('/change-password', 'AuthController@changePassword');
            Route::put('/email', 'AuthController@updateEmail');
            Route::post('/verify-email', 'AuthController@verifyEmail');
        });

        // USER PROFILE
        Route::group(['prefix' => 'user/profile'], function () {
            Route::get('/', 'UserProfileController@index');
            Route::post('/update', 'UserProfileController@update');
        });

        #CONFIG ROUTES
        Route::group(['prefix' => 'configs'], function () {
            //configs to apply without login, eg. Auth Bg Image
            Route::get('/applyable', 'ConfigurationsController@applyable');
            Route::get('/maintenance', 'ConfigurationsController@isMaintenance');

            //Configs Routes with auth
            Route::get('/', 'ConfigurationsController@browse')->name('configs.browse')->middleware('can:view_config');
            Route::post('/add', 'ConfigurationsController@add')->name('configs.add')->middleware('can:add_config');
            Route::get('/read', 'ConfigurationsController@read')->name('configs.read')->middleware('can:view_config');
            Route::put('/update-multiple', 'ConfigurationsController@updateMultiple')->name('configs.updateMultiple')->middleware('can:edit_config');
            Route::get('/fetch', 'ConfigurationsController@fetch');
            Route::get('/fetch-multiple', 'ConfigurationsController@fetchMultiple');
            Route::put('/edit', 'ConfigurationsController@edit')->name('configs.edit')->middleware('can:edit_config');
            Route::delete('/delete', 'ConfigurationsController@delete')->name('configs.delete')->middleware('can:delete_configs');
        });

        #ROLE ROUTES
        Route::group(['prefix' => 'roles'], function () {
            Route::get('/', 'RoleController@browse')->middleware('can:view_roles');
            Route::get('/list', 'RoleController@list')->middleware('can:view_roles');
            Route::post('/add', 'RoleController@add')->middleware('can:add_roles');
            Route::get('/show/{role:id}', 'RoleController@show')->middleware('can:show_roles');
            Route::put('/update', 'RoleController@update')->middleware('can:edit_roles');
            Route::delete('/delete', 'RoleController@destroy')->middleware('can:delete_roles');

            Route::get('/lst', 'RoleController@browsex')->middleware('can:view_roles');
            Route::get('/read', 'RoleController@read')->middleware('can:view_roles');
            Route::delete('/delete-multiple', 'RoleController@deleteMultiple')->middleware('can:browse_roles');
        });

        #PERMISSION ROUTES
        Route::group(['prefix' => 'permissions'], function () {
            Route::get('/list', 'PermissionController@list')->middleware('can:browse_permissions');
            Route::get('/', 'PermissionController@browse')->middleware('can:browse_permissions');
            Route::get('/read', 'PermissionController@read')->middleware('can:read_permissions');
            Route::put('/update', 'PermissionController@update')->middleware('can:edit_permissions');
            Route::post('/add', 'PermissionController@add')->middleware('can:add_permissions');
            Route::delete('/delete', 'PermissionController@delete')->middleware('can:delete_permissions');
            Route::delete('/delete-multiple', 'PermissionController@deleteMultiple')->middleware('can:delete_permissions');
        });

        #USERS ROUTES
        Route::group(['prefix' => 'users'], function () {
            Route::get('/', 'UserController@browse')->name('users.browse')->middleware('can:view_user');
            Route::post('/add', 'UserController@add')->middleware('can:add_users');
            Route::get('/show', 'UserController@show')->name('users.show')->middleware('can:edit_user');
            Route::post('/edit', 'UserController@update')->name('users.update')->middleware('can:edit_user');
            Route::delete('/delete', 'UserController@destroy')->middleware('can:delete_user');
            Route::delete('/delete-multiple', 'UserController@deleteMultiple')->middleware('can:delete_user');
            Route::get('/trashed', 'UserController@trashed')->name('users.trashed')->middleware('can:restore_user');
        });

        #FILE ROUTES
        Route::group(['prefix' => 'file'], function () {
            Route::get('/view', 'FileController@viewFile');
            Route::get('/download', 'FileController@downloadFile');
            Route::post('/upload', 'FileController@uploadFile')->middleware('can:browse_file');
            Route::delete('/delete', 'FileController@deleteFile');
            Route::get('/browse/lfm', 'FileController@browseFileUsingLfm');
            Route::post('/upload/lfm', 'FileController@uploadFileUsingLfm');
            Route::post('/upload/lfmx', 'FileController@tex');
            Route::get('/delete/lfm', 'FileController@deleteFileUsingLfm');
            Route::get('/mimetypes', 'FileController@availableMimetype');
        });

        #PERATURAN ROUTES
        Route::group(['prefix' => 'peraturan'], function () {
            // Route::get('/show/{peraturan:id}', ['as' => 'peraturan.edit', 'uses' => 'PeraturanController@show']);

            Route::get('/', 'PeraturanController@browse')->name('peraturan.browse')->middleware('can:view_peraturan');
            Route::get('/list', 'PeraturanController@option_list')->name('peraturan.option_list')->middleware('can:view_peraturan');
            Route::post('/add', 'PeraturanController@add')->name('peraturan.add')->middleware('can:add_peraturan');
            Route::get('/show/{peraturan:id}',
                'PeraturanController@show')->name('peraturan.show')->middleware('can:view_peraturan');
            Route::post('/edit', 'PeraturanController@update')->name('peraturan.update')->middleware('can:edit_peraturan');
            Route::delete('/delete', 'PeraturanController@destroy')->name('peraturan.destroy')->middleware('can:delete_peraturan');
            Route::delete('/delete-multiple', 'PeraturanController@deleteMultiple')->name('peraturan.deleteMultiple')->middleware('can:delete_peraturan');
        });

        #KATEGORI PERATURAN ROUTES
        Route::group(['prefix' => 'kategori'], function () {
            Route::get('/list', 'KategoriPeraturanController@option_list');
            Route::get('/', 'KategoriPeraturanController@browse')->name('category.browse')->middleware('can:view_category');
            Route::post('/add', 'KategoriPeraturanController@add')->name('category.add')->middleware('can:add_category');
            Route::get('/read', 'KategoriPeraturanController@show')->name('category.show')->middleware('can:view_category');
            Route::put('/edit', 'KategoriPeraturanController@update')->name('category.update')->middleware('can:edit_category');
            Route::delete('/delete', 'KategoriPeraturanController@destroy')->name('category.destroy')->middleware('can:delete_category');
            Route::delete('/delete-multiple', 'KategoriPeraturanController@deleteMultiple')->name('category.deleteMultiple')->middleware('can:delete_category');
        });

        #NOTULENSI ROUTES
        Route::group(['prefix' => 'notulensi'], function () {
            Route::get('/', 'NotulensiController@browse')->name('notulensi.browse')->middleware('can:view_notulensi');
            // show data from collection
            Route::post('/add', 'NotulensiController@add')->name('notulensi.add')->middleware('can:add_notulensi');
            Route::get('/show/{notulensi:id}', 'NotulensiController@show')->name('notulensi.show')->middleware('can:view_notulensi');
            // end show data from collection
            Route::post('/edit', 'NotulensiController@update')->name('notulensi.update')->middleware('can:edit_notulensi');
            Route::delete('/delete', 'NotulensiController@destroy')->name('notulensi.destroy')->middleware('can:delete_notulensi');
            Route::delete('/delete-multiple', 'NotulensiController@deleteMultiple')->name('notulensi.deleteMultiple')->middleware('can:delete_notulensi');
        });

        #LAPORAN ROUTES
        Route::group(['prefix' => 'laporan'], function () {
            Route::get('/', 'LaporanController@browse')->name('laporan.browse')->middleware('can:view_laporan');
            // show data from collection
            Route::post('/add', 'LaporanController@add')->name('laporan.add')->middleware('can:add_laporan');
            Route::get('/show/{laporan:id}', 'LaporanController@show')->name('laporan.show')->middleware('can:edit_laporan');
            // end show data from collection
            Route::post('/edit', 'LaporanController@update')->name('laporan.update')->middleware('can:edit_laporan');
            Route::delete('/delete', 'LaporanController@destroy')->name('laporan.delete')->middleware('can:delete_laporan');
            Route::delete('/delete-multiple', 'LaporanController@deleteMultiple')->name('laporan.deleteMultiple')->middleware('can:delete_laporan');
        });

        // TESTING
        Route::group(['prefix' => 'testing'], function () {
            Route::get('/abort', 'TestController@abort');
        });

    });

});

// FRONTEND ROUTES
Route::group([
    'prefix' => $apiRoutePrefix,
    'namespace' => 'App\Http\Controllers\Frontend', 'as' => 'appstra.',
    'middleware' => [RequestApi::class],
], function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::group(['prefix' => 'public'], function () {

            //PERATURAN
            Route::group(['prefix' => 'peraturan'], function () {
                Route::get('/', 'PeraturanController@browse');
                Route::get('/show/{peraturan:id}', 'PeraturanController@show');
                Route::get('view-doc/', 'PeraturanController@documentViewer');
            });

            //NOTULENSI
            Route::group(['prefix' => 'notulensi'], function () {
                Route::get('/', 'NotulensiController@browse');
                Route::get('/show/{notulensi:id}', 'NotulensiController@show');
                Route::get('view-doc/', 'NotulensiController@documentViewer');
            });

            //LAPORAN
            Route::group(['prefix' => 'laporan'], function () {
                Route::get('/', 'LaporanController@browse');
                Route::get('/show/{laporan:id}', 'LaporanController@show');
                Route::get('view-doc/', 'LaporanController@documentViewer');
            });

        });
    });

});

Route::group([
    'prefix' => $apiRoutePrefix,
    'namespace' => 'App\Http\Controllers',
    'as' => 'search.',
], function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::group(['prefix' => 'searchable'], function () {
            Route::post('/search-peratuan', 'GlobalSearchController@index');
        });
    });
});
