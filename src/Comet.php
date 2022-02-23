<?php
declare(strict_types=1);

namespace Comet;

use Comet\Factory\CometPsr17Factory;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Exception\HttpNotFoundException;
use Workerman\Worker;
use Workerman\Protocols\Http;
use Workerman\Protocols\Http\Response;

/**
 * Main class of Comet PHP microframework
 * https://github.com/gotzmann/comet
 *
 * @package Comet
 */
class Comet
{
    public const VERSION = '2.3.5';

    /** @property \Slim\App $app */
    private static $app;

    // Configuration vars
    private static $host;
    private static $port;    
    private static $logger;
    private static $debug;
    private static $init;
    private static $container;
    private static int $workers;

    // MIME types for internal web-server
    private static $mimeFile;
    private static $mimeTypeMap;
    private static $defaultMimeType = 'text/html; charset=utf-8';

    // Settings of handling static files by internal web-server
    private static $rootDir;
    private static $serveStatic = false;
    private static $staticDir;
    private static $staticExtensions;
    // Split static content to parts if file size more than limit of 2 Mb
    private static $trunkLimitSize = 2 * 1024 * 1024;

    private static $config = [];
    private static $jobs = [];

    /**
     * Comet constructor
     *
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        // Set up params with user defined or default values
        self::$workers   = $config['workers']   ?? 0;
        self::$host      = $config['host']      ?? '0.0.0.0';
        self::$port      = $config['port']      ?? 80;
        self::$debug     = $config['debug']     ?? false;
        self::$logger    = $config['logger']    ?? null;
        self::$container = $config['container'] ?? null;

        // Construct correct root dir of the project
        $parts = pathinfo(__DIR__);
		self::$rootDir = str_replace("\\", '/', $parts['dirname']);
		$pos = mb_strpos(self::$rootDir, 'vendor/gotzmann/comet');
        if ($pos !== false) {
        	self::$rootDir = rtrim(mb_substr(self::$rootDir, 0, $pos), '/');
		}        

        // Some more preparations for Windows hosts
        if (DIRECTORY_SEPARATOR === '\\') {
            if (self::$host === '0.0.0.0') {
                self::$host = '127.0.0.1';
            }
            self::$workers = 1; // Windows can't hadnle multiple processes with PHP
        } else {
            if (self::$workers == 0) {
                self::$workers = (int) shell_exec('nproc') * 4; // Linux
                if (self::$workers == 0) {
                    self::$workers = (int) shell_exec('sysctl -n hw.logicalcpu') * 4; // MacOS
                }
            }
        }

        // Using Comet PSR-7 and PSR-17
        $provider = new Psr17FactoryProvider();
        $provider::setFactories([ CometPsr17Factory::class ]);
        AppFactory::setPsr17FactoryProvider($provider);
	
        // Set up Container
        if (self::$container) {
            AppFactory::setContainer(self::$container);
        }

        // --- Know MIME types for embedded web server

        self::$mimeFile = __DIR__ . '/mime.types';
        if (!is_file(self::$mimeFile)) {
            echo "\n[ERR] mime.type file not found!";
        } else {
            $items = file(self::$mimeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!is_array($items)) {
                echo "\n[ERR] Failed to get [mime.type] file content";
            } else {
                foreach ($items as $content) {
                    if (preg_match("/\s*(\S+)\s+(\S.+)/", $content, $match)) {
                        $mime_type = $match[1];
                        $workerman_file_extension_var = $match[2];
                        $workerman_file_extension_array = explode(' ', substr($workerman_file_extension_var, 0, -1));
                        foreach ($workerman_file_extension_array as $workerman_file_extension) {
                            self::$mimeTypeMap[$workerman_file_extension] = $mime_type;
                        }
                    }
                }
            }
        }

        // Create SlimPHP App instance
        self::$app = AppFactory::create();
    }

    /**
     * Return config param value or the config at whole
     *
     * @param string $key
     */
    public function getConfig(string $key = null) {
        if (!$key) {
    	    return self::$config;
        } else if (array_key_exists($key, self::$config)) {
    	    return self::$config[$key];
        } else {
    	    return null;
        }
    }

    /**
     * Set up worker initialization code if needed
     *
     * @param callable $init
     */
    public function init (callable $init)
    {
        self::$init = $init;
    }

    /**
     * Add periodic $job executed every $interval of seconds
     *
     * @param int      $interval
     * @param callable $job
     * @param array    $params
     * @param callable $init
     * @param int      $workers
     * @param string   $name
     */
    public function addJob(int $interval, callable $job, array $params = [], callable $init = null, string $name = '', int $workers = 1) 
    {
    	self::$jobs[] = [ 
    		'interval' => $interval, 
    		'job'      => $job, 
    		'params'   => $params,
    		'init'     => $init,     		 
    		'name'     => $name, 
    		'workers'  => $workers,
    	];
    }

    /**
     * Set folder to serve as root for static files
     *
     * @param string $dir
     * @param array|null $extensions
     */
    public function serveStatic(string $dir, array $extensions = null)
    {
    	self::$serveStatic = true;
    	// If dir specified as UNIX absolute path, or contains Windows disk name, thats enough
        // In other case we should concatenate full path of two parts
        if ($dir[0] == '/' || strpos($dir, ':')) {
            self::$staticDir = $dir;
        } else {
            self::$staticDir = self::$rootDir . '/' . $dir;
        }
    	self::$staticExtensions = $extensions;
    }

    /**
     * Magic call to any of the Slim App methods like add, addMidleware, handle, run, etc...
     * See the full list of available methods: https://github.com/slimphp/Slim/blob/4.x/Slim/App.php
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call (string $name, array $args)
    {
        return self::$app->$name(...$args);
    }

    /**
     * Handle Workerman request to return Workerman response
     *
     * @param Request $request
     * @return Response
     */
    private static function _handle(Request $request)
    {
        /** @var  Comet\Response $response */
        $response = self::$app->handle($request);

        $headers = $response->getHeaders();

        if (!isset($headers['Server'])) {
            $headers['Server'] = 'Comet v' . self::VERSION;
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        // Save session data to disk if needed
        if (count($request->getSession()->all())) {
            // If there no PHPSESSID between request cookies AND response headers, we should send session cookie to browser
            $defaultSessionName = Session::sessionName();
            if (!array_key_exists($defaultSessionName, $request->getCookieParams()) &&
                (!array_key_exists('cookie', $headers) ||
                    (array_key_exists('cookie', $headers) &&
                        strpos($headers['cookie'], $defaultSessionName) === false))) {
                $cookie_params = \session_get_cookie_params();
                $session_id = $request->getSession()->getId();
                $cookie = 'PHPSESSID' . '=' . $session_id
                    . (empty($cookie_params['domain']) ? '' : '; Domain=' . $cookie_params['domain'])
                    . (empty($cookie_params['lifetime']) ? '' : '; Max-Age=' . $cookie_params['lifetime'])
                    . (empty($cookie_params['path']) ? '' : '; Path=' . $cookie_params['path'])
                    . (empty($cookie_params['samesite']) ? '' : '; SameSite=' . $cookie_params['samesite'])
                    . (!$cookie_params['secure'] ? '' : '; Secure')
                    . (!$cookie_params['httponly'] ? '' : '; HttpOnly');
                $headers['Set-Cookie'] = $cookie;
            }

            // Save session to storage otherwise it would be saved on destruct()
            $request->getSession()->save();
        }

        return $response
            ->withHeaders($headers);
    }

    /**
     * Run Comet server
     */
    public function run()
    {
        // Redirect workers output to log file if it exists
        if (self::$logger) {
            foreach(self::$logger->getHandlers() as $handler) {
                if ($handler->getUrl()) {
                    Worker::$stdoutFile = $handler->getUrl();
                    break;
                }
            }
        }

        // Init HTTP workers
        $worker = new Worker('http://' . self::$host . ':' . self::$port);
        $worker->count = self::$workers;
        $worker->name = 'Comet v' . self::VERSION;

        if (self::$init)
            $worker->onWorkerStart = self::$init;

        // Init JOB workers
        $counter = 0;
        foreach (self::$jobs as $job) {
	        $w = new Worker('text://' . self::$host . ':' . strval(65432 + $counter++));
    	    $w->count = $job['workers'];
        	$w->name = 'Comet v' . self::VERSION .' [job] ' . $job['name'];
        	$w->onWorkerStart = function() use ($job) {
      	        if (self::$init)
					call_user_func(self::$init);
            	Timer::add($job['interval'], $job['job']);            		
        	};
        }

       	// Suppress Workerman startup message
        global $argv;
        $argv[] = '-q';

        // Write Comet startup message to log file and show on screen
        $jobsInfo = count(self::$jobs) ? ' / ' . count(self::$jobs) . ' jobs' : ''; 
      	$hello = $worker->name . ' [' . self::$workers . ' workers' . $jobsInfo . '] ready on http://' . self::$host . ':' . self::$port;
       	if (self::$logger) {
            self::$logger->info($hello);
       	}

       	// Special greeting for Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            echo "\n----------------------------------------------------------------------------------";
            echo "\nServer                        Listen                              Workers   Status";
            echo "\n----------------------------------------------------------------------------------\n";
        } else {
            echo $hello . "\n";
        }

        // Point Workerman to our Request class to use it within onMessage
        Http::requestClass(Request::class);

        // --- Main Loop

        $worker->onMessage = static function($connection, Request $request)
        {
            try {
                // --- Serve static files first
                if (self::$serveStatic && $request->getMethod() === 'GET') {

                    $path = $request->getUri()->getPath();
                    $filename = self::$staticDir . '/' . $path;
                    $realFile = realpath($filename);

                    $parts = pathinfo($path);
                    $fileparts = pathinfo($parts['basename']);
                    $extension = key_exists('extension', $fileparts) ? $fileparts['extension'] : '';

                    // --- Do security checks

                    // Requested file MUST EXISTS, be inside of public root,
                    // do not have PHP extension or be hidden (starts with dot)

                    if ($realFile &&
                        is_file($realFile) &&
                        strpos($realFile, realpath(self::$staticDir)) === 0 &&
                        strpos($parts['basename'], '.') !== 0 &&
                        $extension != 'php'
                    ) {
                        return self::sendFile($connection, $realFile);
                    }
                }

                // --- Proceed with other handlers

                $response = self::_handle($request);
                $connection->send($response);

            } catch(HttpNotFoundException $error) {
                $connection->send(new Response(404));
            } catch(\Throwable $error) {
                if (self::$debug) {
                    echo "\n[ERR] " . $error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage();
                }
                if (self::$logger) {
                    self::$logger->error($error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage());
                }
                $connection->send(new Response(500));
            }
        };

        // --- Start event loop

        Worker::runAll();
    }

    /**
     * Transfer file contents with HTTP protocol
     * https://github.com/walkor/workerman-queue/blob/master/Workerman/WebServer.php
     *
     * @param $connection
     * @param $file_name
     */
    public static function sendFile($connection, $file_name)
    {
        $file_size = filesize($file_name);
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $content_type = isset(self::$mimeTypeMap[$extension]) ? self::$mimeTypeMap[$extension] : self::$defaultMimeType;
        $headers  = "HTTP/1.1 200 OK\r\n";
        $headers .= "Content-Type: $content_type\r\n";
        $headers .= "Connection: keep-alive\r\n";
        $headers .= "Content-Length: $file_size\r\n\r\n";

        // --- Send the whole file if size is less than limit

        if ($file_size < self::$trunkLimitSize) {
            return $connection->send($headers . file_get_contents($file_name), true);
        }

        // --- Otherwise, send it part by part
        
        $connection->send($headers, true); 
        
        $connection->fileHandler = fopen($file_name, 'r');

        $do_write = function() use ($connection)
        {
            // Send buffer not full
            while (empty($connection->bufferFull)) {
                // Read from disk by chunks of 64 of 8K blocks - so it some sort of magic constant ~500Kb
                $buffer = fread($connection->fileHandler, 64 * 8 * 1024);
                
                // Stop on EOF
                if($buffer === '' || $buffer === false) {
                    return;
                }

                $connection->send($buffer, true);
            }
        };

        // Send buffer full
        $connection->onBufferFull = function($connection)
        {
            $connection->bufferFull = true;
        };

        // Send buffer drain
        $connection->onBufferDrain = function($connection) use ($do_write)
        {
            $connection->bufferFull = false;
            $do_write();
        };

        $do_write();
    }
}
