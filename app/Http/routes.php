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
    "as" => "home",
    "uses" => "HomeController@showHome",
]);

Route::get('/auth', function(){
    return "This is the authenticate root";
});

Route::get('/profile', function(){
    return "This is the profile root";
});

Route::get('/dashboard', [
    "as" => "dashboard",
    "middleware" => "auth",
]);

Route::get('/problem/{problem_id}',[
    "uses" => "ProblemController@getProblemByID"
]);

Route::get('/problem/p/{page_id}', [
    "uses" => "ProblemController@getProblemListByPageID"
]);

Route::match(['post','get'], '/problem', [
    "uses" => "ProblemController@getProblem"
]);


Route::get('/status', function(){
    return "This is the status root";
});

Route::get('/contest', function(){
    return "This is the contest root";
});

Route::get('/discuss', function(){
    return "This is the BBS root";
});

Route::match(['post','get'], '/auth/signin', [
    "as" => "signin",
    //"middleware" => "",
    "uses" => "AuthController@loginAction"
]);

Route::get('/auth/logout', [
    "as" => "logout",
    "uses" => "AuthController@logoutAction"
]);

/*
 * These routes below are only use for testing and debugging
 * DO NOT ENABLE IT IN THE PRODUCTION PROJECT
 */

Route::get('/test/getss',[
    "as" => "test.getss",
    "uses" => "TestController@getSession"
]);

Route::get('/test/delss',[
    "as" => "test.delss",
    "uses" => "TestController@destroySession"
]);
