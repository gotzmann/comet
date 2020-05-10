<?php
declare(strict_types=1);

namespace Comet;

use Workerman\Worker;
use Workerman\Timer;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Headers;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Comet\Middleware\JsonBodyParserMiddleware;

class Comet
{
    public const VERSION = '0.2.2';

    private $app;
    private $host;
    private $port;
    private $logger;
    private $status;

    public function __construct(array $config)    
    {
        $this->host = $config['host'] ?? 'localhost';                     
        $this->port = $config['port'] ?? 80;                     
        $this->logger = $config['logger'] ?? null;  
        
        $this->app = AppFactory::create();
        // FIXME Base path as config param!        
        
        // FIXME Load ALL middlewares from /middleware folder OR enable only that was sent via config
        $this->app->add(new JsonBodyParserMiddleware());
    }

    // Magic call to any of the Slim App methods like add, addMidleware, handle, run, etc...
    // See the full list of available methods: https://github.com/slimphp/Slim/blob/4.x/Slim/App.php
    public function __call (string $name, array $arguments) 
    {
        return $this->app->$name(...$arguments);
    }

    // Handle EACH request and form response
    private function _handle(WorkermanRequest $request)
    {
        $req = new Request(
            $request->method(),
            (new UriFactory())->createUri($request->path()),
            (new Headers())->setHeaders($request->header()),
            $request->cookie(),
            [], // $_SERVER ?
            (new StreamFactory)->createStream($request->rawBody())
        );
        // FIXME If there no handler for specified route - it does not return any response at all!
        $ret = $this->app->handle($req);
        $response = new WorkermanResponse(
            $ret->getStatusCode(),
            $ret->getHeaders(),
            $ret->getBody()
        );
        return $response;
    }

//    static function run($bootstrap, $init)
    public function run($init = null)
    {

        // Suppress Workerman startup message        
        global $argv;        
        $argv[] = '-q';

        echo "\n-------------------------------------------------------------------------";
        echo "\nServer               Listen                              Workers   Status";
        echo "\n-------------------------------------------------------------------------\n";        

        //----------------------- WORKERMAN -----------------------------
        //Workerman version:4.0.4          PHP version:7.3.5
        //------------------------ WORKERS -------------------------------
        //worker               listen                              processes status
        //none                 http://127.0.0.1:80                 16        [ok]        
        
        // TODO Support HTTPS
        $worker = new Worker('http://' . $this->host . ':' . $this->port);
        // FIXME What the best count number for workers?
        $worker->count = (int) shell_exec('nproc') * 4;
        $worker->name = 'Comet v' . self::VERSION;

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
        //$bootstrap();

        // Initialization code for EACH worker - it runs when worker starts working
        //$worker->onWorkerStart = static function() { $init(); };
        if ($init)
            $worker->onWorkerStart = $init;

        // TODO /favicon.ico = 404 HttpNotFoundException
        // Handle EACH request and form response
        //$worker->onMessage = static function($connection, $request)
        $worker->onMessage = function($connection, WorkermanRequest $request)
        {
            // TODO All errors and exceptions send to log by default?
            try {
                $response = $this->_handle($request);
                $connection->send($response);
            } catch(HttpNotFoundException $error) {
                // TODO Catch it within App:handle and return 404 code
                //$connection->send(new WorkermanResponse(404));
            } catch(\Throwable $error) {
                echo $error->getMessage();
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
    }
}
