<?php

declare(strict_types=1);

namespace Comet;

use Workerman\Worker;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;
use Comet\Request;
use Comet\Response;
use Comet\Factory\CometPsr17Factory;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Exception\HttpNotFoundException;
use Comet\Middleware\JsonBodyParserMiddleware;

class Comet
{
    public const VERSION = '0.5.1';

    private static $app;
    private static $host;
    private static $port;
    private static $logger;
    private static $status;
    private static $debug;

    public function __construct(array $config = null)
    {
        self::$host = $config['host'] ?? 'localhost';
        self::$port = $config['port'] ?? 80;
        self::$debug = $config['debug'] ?? false;
        self::$logger = $config['logger'] ?? null;

        self::$app = AppFactory::create();

        // TODO Load ALL middlewares from /middleware folder OR enable only that was sent via config
        self::$app->add(new JsonBodyParserMiddleware());
    }

    // Magic call to any of the Slim App methods like add, addMidleware, handle, run, etc...
    // See the full list of available methods: https://github.com/slimphp/Slim/blob/4.x/Slim/App.php
    public function __call(string $name, array $args)
    {
        return self::$app->$name(...$args);
    }

    private static function handle(WorkermanRequest $request)
    {
        $req = new Request(
            $request->method(),
            $request->uri(),
            $request->header(),
            $request->rawBody(),
            '1.1',
            [], // $_SERVER,
            $request->cookie(),
            $request->file(),            
            [] // $request->queryString()
        );

        $ret = self::$app->handle($req);

        $response = new WorkermanResponse(
            $ret->getStatusCode(),
            $ret->getHeaders(),
            $ret->getBody()
        );

        return $response;
    }

    public function run($init = null)
    {
        // Suppress Workerman startup message
        global $argv;
        $argv[] = '-q';

        // Some more preparations for Windows hosts
        if (DIRECTORY_SEPARATOR === '\\') {
            if (self::$host === '0.0.0.0') {
                self::$host = '127.0.0.1';
            }
            echo "\n-------------------------------------------------------------------------";
            echo "\nServer               Listen                              Workers   Status";
            echo "\n-------------------------------------------------------------------------\n";
        }

        $worker = new Worker('http://' . self::$host . ':' . self::$port);
        $worker->count = (int)shell_exec('nproc') * 4;
        $worker->name = 'Comet v' . self::VERSION;

        if ($init) {
            $worker->onWorkerStart = $init;
        }

        // Main Loop
        $worker->onMessage = static function ($connection, WorkermanRequest $request) {
            try {
                $response = self::handle($request);
                $connection->send($response);
            } catch (HttpNotFoundException $error) {
                $connection->send(new WorkermanResponse(404));
            } catch(\Throwable $error) {
	            if (self::$debug) {
	                echo "\n[ERR] " . $error->getMessage();
	            }
            	if (self::$logger) {
	                self::$logger->error($error->getMessage());
	            }
              $connection->send(new WorkermanResponse(500));
            }
        };

        Worker::runAll();
    }
}
