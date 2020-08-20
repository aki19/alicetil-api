<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('encryption', ['uses' => 'UtilController@encryption']);
    $router->post('convert_listloader', ['uses' => 'UtilController@convert_listloader']);
});

$router->group(['prefix' => 'api/jira'], function () use ($router) {
    $router->get('get_sprint_list', ['uses' => 'JiraController@get_sprint_list']);
    $router->post('get_issue_list', ['uses' => 'JiraController@get_issue_list']);
});
