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

$router->group(['prefix' => 'api', 'middleware' => ['ip']], function () use ($router) {
    $router->post('encryption', ['uses' => 'UtilController@encryption']);
    $router->post('convert_listloader', ['uses' => 'UtilController@convert_listloader']);
});

$router->group(['prefix' => 'api/jira', 'middleware' => ['ip']], function () use ($router) {
    $router->get('get_sprint_list', ['uses' => 'JiraController@get_sprint_list']);
    $router->post('get_sprint_issue_list', ['uses' => 'JiraController@get_sprint_issue_list']);
    $router->post('get_child_issue_list', ['uses' => 'JiraController@get_child_issue_list']);
    $router->post('get_active_issue_list', ['uses' => 'JiraController@get_active_issue_list']);
    $router->post('get_target_issue_list', ['uses' => 'JiraController@get_target_issue_list']);
});
