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
    public const VERSION = '0.6.5';

    private static $app;
    private static $host;
    private static $port;
    private static $workers;
    private static $logger;
    private static $status;
    private static $debug;
    private static $init;    

    public function __construct(array $config = null)    
    {
        self::$host = $config['host'] ?? '0.0.0.0';                     
        self::$port = $config['port'] ?? 8080;
        self::$workers = $config['workers'] ?? (int) shell_exec('nproc') * 4;
        self::$debug = $config['debug'] ?? false;              
        self::$logger = $config['logger'] ?? null;  
        
        // Using Comet PSR-7 and PSR-17 
		$provider = new Psr17FactoryProvider();
        $provider::setFactories([ CometPsr17Factory::class ]);
		AppFactory::setPsr17FactoryProvider($provider);

		self::$app = AppFactory::create();   
                
        self::$app->add(new JsonBodyParserMiddleware());
    }

    public function init (callable $init) 
    {
		self::$init = $init;        
    }

    // Magic call to any of the Slim App methods like add, addMidleware, handle, run, etc...
    // See the full list of available methods: https://github.com/slimphp/Slim/blob/4.x/Slim/App.php
    public function __call (string $name, array $args) 
    {
        return self::$app->$name(...$args);
    }
    
    private static function _handle(WorkermanRequest $request)
    {
    	if ($request->queryString()) {
    		parse_str($request->queryString(), $queryParams);
    	} else {
    		$queryParams = [];
    	}

        $req = new Request(
            $request->method(),
            $request->uri(),
            $request->header(),
            $request->rawBody(),
            '1.1',
            [], // $_SERVER,
            $request->cookie(),
            $request->file(),            
            $queryParams
        );

   	    $ret = self::$app->handle($req);

   	    $headers = $ret->getHeaders();
        if (!isset($headers['Server'])) {
            $headers['Server'] = "Comet v" . self::VERSION;
        }
		
        $response = new WorkermanResponse(
            $ret->getStatusCode(),
            $headers,
            $ret->getBody()
        );

        return $response;
    }

    public function run($init = null) 
    {        
    	// Suppress Workerman startup message 
    	global $argv;
        $argv[] = '-q'; 

        $workers = self::$workers;
        
        // Some more preparations for Windows hosts
        if (DIRECTORY_SEPARATOR === '\\') {              
            if (self::$host === '0.0.0.0') {
                self::$host = '127.0.0.1';
            }                        
            
            $workers = 1; // Windows can't hadnle multiple processes with PHP

            echo "\n-------------------------------------------------------------------------";
            echo "\nServer               Listen                              Workers   Status";
            echo "\n-------------------------------------------------------------------------\n";        
        }    
        
        $worker = new Worker('http://' . self::$host . ':' . self::$port);
        $worker->count = $workers;
        $worker->name = 'Comet v' . self::VERSION;

        if (self::$init)
            $worker->onWorkerStart = self::$init;

        // Main Loop
        $worker->onMessage = static function($connection, WorkermanRequest $request)
        {            
            try {
                $response = self::_handle($request);
                $connection->send($response);
            } catch(HttpNotFoundException $error) {
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
