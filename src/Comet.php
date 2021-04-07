<?php
declare(strict_types=1);

namespace Comet;

use Comet\Factory\CometPsr17Factory;
use Comet\Middleware\JsonBodyParserMiddleware;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Exception\HttpNotFoundException;
use Workerman\Worker;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;

class Comet
{
    public const VERSION = '1.2.1';

    // TODO Implement Redirect Helper
    // TODO Move both Form and JSON Body parsers to Request constructor or Middleware
    // TODO Clean FromGlobals method
    // TODO Suppress Workerman output on forkWorkersForWindows
    // TODO Use Worker::safeEcho for console out?

    /**
     * @property \Slim\App $app
     */
    private static $app;

    // TODO Store set up variables within single Config struct
    private static $host;
    private static $port;    
    private static $logger;
    private static $status;
    private static $debug;
    private static $init;
    private static $container;

    private static $defaultMimeType = 'text/html; charset=utf-8';
    private static $rootDir;
    private static $mimeTypeMap;
    private static $serveStatic = false;
    private static $staticDir;
    private static $staticExtensions;

    private static $config = [];
    private static $jobs = [];

    public function __construct(array $config = null)
    {
        self::$host = $config['host'] ?? '0.0.0.0';
        self::$port = $config['port'] ?? 8080;    
        self::$debug = $config['debug'] ?? false;
        self::$logger = $config['logger'] ?? null;
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
            self::$config['workers'] = 1; // Windows can't hadnle multiple processes with PHP and have no "nproc" command
        } else {
        	self::$config['workers'] = $config['workers'] ?? (int) shell_exec('nproc') * 4;
        }

        // Using Comet PSR-7 and PSR-17
        $provider = new Psr17FactoryProvider();
        $provider::setFactories([ CometPsr17Factory::class ]);
        AppFactory::setPsr17FactoryProvider($provider);
	
        // Using Container
        if (self::$container) {
            AppFactory::setContainer(self::$container);
        }

        self::$app = AppFactory::create();
        self::$app->add(new JsonBodyParserMiddleware());
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

    /* 	TODO
    	@@@ Error: multi workers init in one php file are not support @@@
		@@@ See http://doc.workerman.net/faq/multi-woker-for-windows.html @@@
	*/
	// TODO Return Job ID
	/*
		Windows Hack
        Timer::add(INTERVAL,
        function() use ($app, $logger) {
            $id = rand(1, $app->getConfig('workers'));
            if ($id == 1) 
                Job::run();            
        });

	*/

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
    	self::$staticDir = $dir;
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
     * @param WorkermanRequest $request
     * @return WorkermanResponse
     */
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
            [
                'REMOTE_ADDR' => $request->connection->getRemoteIp(),
            ],
            $request->cookie(),
            $request->file(),
            $queryParams
        );

    	$req->setAttribute('connection', $request->connection);

        $ret = self::$app->handle($req);

        $headers = $ret->getHeaders();

        if (!isset($headers['Server'])) {
            $headers['Server'] = 'Comet v' . self::VERSION;
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=utf-8';
        }

        // Save session data to disk if needed
        if ($req->getSession()) {
            if (count($req->getSession()->all())) {
                // If there no PHPSESSID between request cookies AND response headers, we should send session cookie to browser
                // TODO What to do if request cookie PHPSESSID is not equal to response?
                $defaultSessionName = Session::sessionName();
                if (!array_key_exists($defaultSessionName, $request->cookie()) &&
                    (!array_key_exists('cookie', $headers) ||
                        (array_key_exists('cookie', $headers) &&
                            strpos($headers['cookie'], $defaultSessionName) === false))) {
                    $cookie_params = \session_get_cookie_params();
                    $session_id = $req->getSession()->getId();
                    $cookie = 'PHPSESSID' . '=' . $session_id
                        . (empty($cookie_params['domain']) ? '' : '; Domain=' . $cookie_params['domain'])
                        . (empty($cookie_params['lifetime']) ? '' : '; Max-Age=' . ($cookie_params['lifetime']))
                        . (empty($cookie_params['path']) ? '' : '; Path=' . $cookie_params['path'])
                        . (empty($cookie_params['samesite']) ? '' : '; SameSite=' . $cookie_params['samesite'])
                        . (!$cookie_params['secure'] ? '' : '; Secure')
                        . (!$cookie_params['httponly'] ? '' : '; HttpOnly');
                    $headers['Set-Cookie'] = $cookie;
                }
                // Save session to storage otherwise it would be saved on destruct()
                $req->getSession()->save();
            }
        }

        return new WorkermanResponse(
            $ret->getStatusCode(),
            $headers,
            $ret->getBody()
        );
    }

    /**
     * Run Comet server
     */
    public function run()
    {
        // Write worker output to log file if exists
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
        $worker->count = self::$config['workers'];
        $worker->name = 'Comet v' . self::VERSION;

        if (self::$init)
            $worker->onWorkerStart = self::$init;

        // TODO Add timers to the single main worker for Windows hosts!
        // FIXME We should use 1) free and maybe 2) random port, not fixed 65432.
        //       That also allow start more than 104 jobs 
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

        // Main Loop
        $worker->onMessage = static function($connection, WorkermanRequest $request)
        {
            try {
            	// TODO Refactor web-server as standalone component
            	// TODO Distinguish relative and absolute directories
            	// TODO HTTP Cache, MIME Types, Multiple Domains, Check Extensions

                // Serve static files first
                if (self::$serveStatic && $request->method() === 'GET') {

                    $publicDir = self::$rootDir . '/' . self::$staticDir;
                	$parts = \pathinfo($request->uri());
                    $filename = $publicDir . '/' . $parts['dirname'] . '/' . $parts['basename'];
                    $fileparts = pathinfo($parts['basename']);
                    $extension = key_exists('extension', $fileparts) ? $fileparts['extension'] : '';
                    $path = str_replace("\\", '/', realpath($filename));

                    // Do security checks first!
                    // Requested file MUST EXISTS, be inside of public root,
                    // do not have PHP extension or be hidden (starts with dot)

                    if (strpos($path, $publicDir) === 0 &&
                        strlen($path) >= strlen($publicDir) &&
                        strpos($parts['basename'], '.') !== 0 &&
                        $extension != 'php' &&
                        is_file($filename)
                    ) {
                        return self::sendFile($connection, $filename);
                    }
            	} 

                // Proceed with other handlers
                $response = self::_handle($request);
                $connection->send($response);

            } catch(HttpNotFoundException $error) {
                $connection->send(new WorkermanResponse(404));
            } catch(\Throwable $error) {
                if (self::$debug) {
                    echo "\n[ERR] " . $error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage();
                }
                if (self::$logger) {
                    self::$logger->error($error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage());
                }
                $connection->send(new WorkermanResponse(500));
            }
        };

       	// Suppress Workerman startup message
        global $argv;
        $argv[] = '-q';

        // Write Comet startup message to log file and show on screen
        $jobsInfo = count(self::$jobs) ? ' / ' . count(self::$jobs) . ' jobs' : ''; 
      	$hello = $worker->name . ' [' . self::$config['workers'] . ' workers' . $jobsInfo . '] ready on http://' . self::$host . ':' . self::$port;
       	if (self::$logger) {
            self::$logger->info($hello);
       	}

        if (DIRECTORY_SEPARATOR === '\\') {
            echo "\n-------------------------------------------------------------------------";
            echo "\nServer               Listen                              Workers   Status";
            echo "\n-------------------------------------------------------------------------\n";
        } else {
            echo $hello . "\n";
        }

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
    	// TODO Move MIME initialization to class constructor
        // TODO Enable trunk transfer for BIG files
        // TODO Dig into 304 status processing

	    $mime_file = __DIR__ . '/mime.types';

        if (!is_file($mime_file)) {
            echo "\n[ERR] mime.type file not found!";
            return;
        }

        $items = file($mime_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($items)) {
            echo "\n[ERR] mime.type content fails!";
            return;
        }

        foreach ($items as $content) {
            if (preg_match("/\s*(\S+)\s+(\S.+)/", $content, $match)) {
                $mime_type                      = $match[1];
                $workerman_file_extension_var   = $match[2];
                $workerman_file_extension_array = explode(' ', substr($workerman_file_extension_var, 0, -1));
                foreach ($workerman_file_extension_array as $workerman_file_extension) {
                    self::$mimeTypeMap[$workerman_file_extension] = $mime_type;
                }
            }
        }
/*
        // Check 304.
        $info = stat($file_name);
        $modified_time = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' GMT' : '';
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info) {
            // Http 304.
            if ($modified_time === $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
                // 304
                Http::header('HTTP/1.1 304 Not Modified');
                // Send nothing but http headers..
                $connection->close('');
                return;
            }
        }

        // Http header.
        if ($modified_time) {
            $modified_time = "Last-Modified: $modified_time\r\n";
        }
*/
        $file_size = filesize($file_name);
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $content_type = isset(self::$mimeTypeMap[$extension]) ? self::$mimeTypeMap[$extension] : self::$defaultMimeType;
        $header = "HTTP/1.1 200 OK\r\n";
        $header .= "Content-Type: $content_type\r\n";
        $header .= "Connection: keep-alive\r\n";
//        $header .= $modified_time;
        $header .= "Content-Length: $file_size\r\n\r\n";
//        $trunk_limit_size = 1024*1024;
//        if ($file_size < $trunk_limit_size) {
            return $connection->send($header . file_get_contents($file_name), true);
//        }
//        $connection->send($header, true);
/*
        // Read file content from disk piece by piece and send to client.
        $connection->fileHandler = fopen($file_name, 'r');
        $do_write = function()use($connection)
        {
            // Send buffer not full.
            while(empty($connection->bufferFull))
            {
                // Read from disk.
                $buffer = fread($connection->fileHandler, 8192);
                // Read eof.
                if($buffer === '' || $buffer === false)
                {
                    return;
                }
                $connection->send($buffer, true);
            }
        };
        // Send buffer full.
        $connection->onBufferFull = function($connection)
        {
            $connection->bufferFull = true;
        };
        // Send buffer drain.
        $connection->onBufferDrain = function($connection)use($do_write)
        {
            $connection->bufferFull = false;
            $do_write();
        };
        $do_write();
*/
    }
}
