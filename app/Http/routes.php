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

Route::get('/', 'PagesController@index')->name('home');

Route::get('logfiles', 'LogfilesController@index')->name('logfiles');
Route::get('logfiles/create', 'LogfilesController@create')->name('logfiles.create');
Route::get('logfiles/{id}', 'LogfilesController@show');

Route::post('logfiles', 'LogfilesController@store');

Route::get('/backend/logfiles', 'BackendController@logfiles');

Route::get('/backend/logfile/{filename}/{extension}/{start?}/{stop?}', 'BackendController@logfile');
