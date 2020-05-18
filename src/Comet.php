<?php
declare(strict_types=1);

namespace Comet;

use Workerman\Worker;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;
#use Nyholm\Psr7\ServerRequest as Request;
#use Nyholm\Psr7\Response;
#use GuzzleHttp\Psr7\ServerRequest as Request;
#use GuzzleHttp\Psr7\Response;
use Comet\Request;
use Comet\Response;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Comet\Middleware\JsonBodyParserMiddleware;

// TODO Return Comet not workerman in Response Server headers

class Comet
{
    public const VERSION = 'v0.4.2-dev';

    private static $app;
    private $host;
    private $port;
    private $logger;
    private $status;
    private $debug;

    public function __construct(array $config = null)    
    {
        $this->host = $config['host'] ?? 'localhost';                     
        $this->port = $config['port'] ?? 80;
        $this->debug = $config['debug'] ?? false;              
        $this->logger = $config['logger'] ?? null;  
        
        self::$app = AppFactory::create();   
        
        // TODO Load ALL middlewares from /middleware folder OR enable only that was sent via config
        self::$app->add(new JsonBodyParserMiddleware());
    }

    // Magic call to any of the Slim App methods like add, addMidleware, handle, run, etc...
    // See the full list of available methods: https://github.com/slimphp/Slim/blob/4.x/Slim/App.php
    public function __call (string $name, array $args) 
    {
        return self::$app->$name(...$args);
    }
    
    private static function _handle(WorkermanRequest $request)
    {
/*
		// TODO Implement Comet's own Request with cookies as __construct() param
        $req = new Request(
            $request->method(),
            $request->path(),
            $request->header(),
            $request->rawBody()
        );
*/

        $req = new Request(
            $request->method(),
            $request->uri(),
            $request->header(),
            $request->rawBody(),
            '1.1',
            $_SERVER,
            $request->cookie(),
            $request->file(),            
            $request->queryString()
        );

//        $ret = self::$app->handle($req
  //      	->withCookieParams($request->cookie())
    //    );

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
            if ($this->host === '0.0.0.0') {
                $this->host = '127.0.0.1';
            }                        
            echo "\n-------------------------------------------------------------------------";
            echo "\nServer               Listen                              Workers   Status";
            echo "\n-------------------------------------------------------------------------\n";        
        }    
        
        // TODO Support HTTPS
        $worker = new Worker('http://' . $this->host . ':' . $this->port);
        // FIXME What's the optimal count of workers?
        $worker->count = (int) shell_exec('nproc') * 4;
        $worker->name = 'Comet v' . self::VERSION;

        if ($init)
            $worker->onWorkerStart = $init;

        // Main Loop : Request -> Comet -> Response
        $worker->onMessage = static function($connection, WorkermanRequest $request)
        {            
            try {
                $response = self::_handle($request);
                $connection->send($response);
            } catch(HttpNotFoundException $error) {
                $connection->send(new WorkermanResponse(404));
            } catch(\Throwable $error) {
	            if ($this->debug) {
	                echo $error->getMessage();
	            }
            	if ($this->logger) {
	            	$this->logger->error($error->getMessage());
	            }
                $connection->send(new WorkermanResponse(500));
            }
        };
        
        Worker::runAll();
    }
}
