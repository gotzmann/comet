<?php
declare(strict_types=1);

use Workerman\Worker;
use Workerman\Timer;
use Workerman\Protocols\Http\Response as WorkermanResponse;

use Slim\Exception\HttpNotFoundException;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app.php';

$port = getenv('LISTEN_PORT') != '' ? getenv('LISTEN_PORT') : 80;
$host = getenv('LISTEN_HOST') != '' ? getenv('LISTEN_HOST') : '127.0.0.1';
$worker = new Worker("http://$host:$port");
$worker->count = (int) shell_exec('nproc') * 4;

/* Timer will work on Linux only - cause it based on pcntl_alarm()
$counter = 0;
Timer::add(
    1,
    function() use ($counter) {
        echo "\nTimer #$counter...";
    },
    [ '$arg1, $arg2..' ]
);
*/

// The very first function which runs ONLY ONCE and bootstrap the WHOLE app
bootstrap();

// Initialization code for EACH worker - it runs when worker starts working
$worker->onWorkerStart = static function() { init(); };

// TODO /favicon.ico = 404 HttpNotFoundException
// Handle EACH request and form response
$worker->onMessage = static function($connection, $request)
{
    // TODO All errors and exceptions send to log by default?
    try {
        $response = handle($request);
        $connection->send($response);
    } catch(HttpNotFoundException $error) {
        // TODO Catch it within App:handle and return 404 code
    } catch(\Throwable $error) {
        // TODO All others cases - generate HTTP 500 Error ?
        // TODO Send to Monolog?
        // FIXME IF NOT DEBUG, SEND TO CLIENT
        // FIXME IF DEBUG SHOW IN CONSOLE
        // TODO Return 500 error with some error message
        $connection->send(new WorkermanResponse(500));
    }
};

// Let's go!
Worker::runAll();
