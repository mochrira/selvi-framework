<?php

use Selvi\Database\Manager;
use Selvi\Database\Migration;
use Selvi\Input\Request;
use Selvi\Routing\Route;

require '../vendor/autoload.php';

define('BASEPATH', __DIR__);
date_default_timezone_set('Asia/Jakarta');
header("Access-Control-Allow-Headers: Content-Type, Authorization, authorization");
header("Access-Control-Allow-Methods: OPTIONS, GET, POST, PATCH, DELETE");

require '../app/Config/exception.php';
require './Config/database.php';
// require '../app/Config/routes.php';
Selvi\View::addPath(BASEPATH ."/views");

Route::get("/setup", function(Request $request, Migration $migration){
  /** @var Selvi\Database\Drivers\MySQL\MySQLSchema $mainSchema */
  $mainSchema = Manager::get('main');
  $mainSchema->createDatabase('test_create');

  $clientConfig = $mainSchema->getConfig();
  $clientConfig['database'] = 'test_create';
  Manager::add('client', $clientConfig);
  Migration::addMigration('client', BASEPATH.'/Migrations/client');
  /** @var Selvi\Database\Drivers\MySQL\MySQLSchema $clientSChema */
  $migration->run('client', 'up');
  return jsonResponse(['createAndRunMigration' => 'OK']);

  // $schema = $request->post("schema");
  // $direction = $request->post("direction");
  // $step =  $request->post("step");
  // $all = $request->post("all");
  // if ($step == "") $step = null;
  // if ($all == "") $all = null;
  // $result = $migration->run(schema:$schema, direction:$direction, stepArgs:$step, stepAll:$all);
  // return jsonResponse($result, 200);
});

Route::get("/", function(){
 return view("home.php")->render();
});

Selvi\Framework::run();