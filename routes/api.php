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

Route::post('/tgbot', 'Telegram@handle');
Route::get('/tgbot/set', 'Telegram@set');

Route::middleware(['api', 'publicapi'])->group(function () {
    Route::get('/week/{week}', 'Api@getWeek');
	Route::get('/agenda/{week}', 'Api@getAgenda');
	Route::get('/full/{week}', 'Api@getFullWeek');
});

