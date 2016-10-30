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

$app->get('/', function() use ($app) {
    return "Hi.......... ......... ........ ....... ...... ..... .... ... .. .";
});
 
$app->group(['prefix' => '/','namespace' => 'App\Http\Controllers'], function($app)
{
    $app->get('/societies',          'SocietyController@getSocieties');
    
    // $app->get('/societies/raw',      'SocietyController@getSocietiesRaw');

    $app->get('/societies/{id}',     'SocietyController@getSociety');
    
    $app->get('/urls',               'SocietyController@getUrlsJSON');
        
    $app->get('/timetable',          'SocietyController@timetable');

    $app->get('/timetable/{option}', 'SocietyController@timetable');
    
    // $app->get('/mock',               'MockController@getMock');

});
