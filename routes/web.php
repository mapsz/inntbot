<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/test',  'ParseController@nalog');
Route::get('/put/task',  'TaskController@put');



Route::get('/task',  'TaskController@task');


Route::get('/test',  function(){

    (new App\Tasks\ZaChestnyiBiznes())->parse(1);
});

Route::get('/run/queued/task',  'QueueController@runTask');



Route::get('/', function () {
    return view('welcome');
});
