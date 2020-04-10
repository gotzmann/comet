<?php

use Workerman\Worker;
//use Workerman\Timer;

/*
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;

use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response as SlimResponse;

use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Factory\AppFactory;
use Slim\Psr7\Headers;

use Illuminate\Database\Capsule\Manager as Capsule;
*/

use Slim\Exception\HttpNotFoundException;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app.php';

global $app, $worker, $capsule, $sql;
//global $config;

// The very first function which runs ONLY ONCE and bootstrap the WHOLE app
bootstrap();

$worker->onWorkerStart = function()
{
    init(); // Initialization code for EACH worker - it runs when worker starts working
};

// TODO /favicon.ico = 404 HttpNotFoundException
// Handle EACH request and form response
$worker->onMessage = static function($connection, $request)
{
    try {
        $response = handle($request);
        $connection->send($response);
    }
    // TODO Catch it within App:handle and return 404 code
    catch (HttpNotFoundException $e) {
    }
};

// Let's go!
Worker::runAll();
