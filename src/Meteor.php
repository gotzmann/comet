<?php

declare(strict_types=1);

namespace Meteor;

use Meteor\Config\MeteorConfig;
use Meteor\Factory\MeteorPsr17Factory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Exception\HttpNotFoundException;
use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;
use Workerman\Protocols\Http;
use Workerman\Protocols\Http\Response;

/**
 * Main class of Meteor PHP microframework
 * https://github.com/diego-ninja/meteor
 *
 * @package Meteor
 */
final class Meteor
{
    public const VERSION = '2.3.5';

    private static App $app;

    private static MeteorConfig $config;

    private static ?LoggerInterface $logger = null;

    // Configuration vars
    private static string $host;
    private static int $port;
    private static bool $debug;
    private static mixed $init = null;
    private static int $workers;

    // MIME types for internal web-server
    private static string $mimeFile;
    private static array $mimeTypeMap;
    private static string $defaultMimeType = 'application/json; charset=utf-8';

    // Settings of handling static files by internal web-server
    private static string|array $rootDir;
    private static array $jobs = [];

    public function __construct(
        MeteorConfig $config,
        ?LoggerInterface $logger = null,
        ?ContainerInterface $container = null
    ) {
        self::$config = $config;
        // Set up params with user defined or default values
        self::$workers   = $config->workers;
        self::$host      = $config->host;
        self::$port      = $config->port;
        self::$debug     = $config->debug;
        self::$mimeFile  = __DIR__ . '/mime.types';
        self::$logger    = $logger;

        // Construct correct root dir of the project
        $parts = pathinfo(__DIR__);
        self::$rootDir = str_replace("\\", '/', $parts['dirname']);
        $pos = mb_strpos(self::$rootDir, 'vendor/diego-ninja/meteor');
        if ($pos !== false) {
            self::$rootDir = rtrim(mb_substr(self::$rootDir, 0, $pos), '/');
        }

        if (is_windows()) {
            if (self::$host === '0.0.0.0') {
                self::$host = '127.0.0.1';
            }
            self::$workers = 1;
        }

        if (is_linux()) {
            self::$workers = self::$workers === 0 ? (int) shell_exec('nproc') * 4 : self::$workers;
        }

        if (is_osx()) {
            self::$workers = self::$workers === 0 ? (int) shell_exec('sysctl -n hw.logicalcpu') * 4 : self::$workers;
        }

        // Using Meteor PSR-7 and PSR-17
        $provider = new Psr17FactoryProvider();
        $provider::setFactories([ MeteorPsr17Factory::class ]);
        AppFactory::setPsr17FactoryProvider($provider);

        // Set up Container
        if ($container !== null) {
            AppFactory::setContainer($container);
        }

        // --- Know MIME types for embedded web server
        $this->initMimeTypes();

        // Create SlimPHP App instance
        self::$app = AppFactory::create();
        Session::init();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function withApp(App $app): self
    {
        self::$app = $app;
        return new self(self::$app->getContainer()?->get(MeteorConfig::class), self::$logger, self::$app->getContainer());
    }
    public static function setApp(App $app): void
    {
        self::$app = $app;
    }

    public static function getApp(): App
    {
        return self::$app;
    }

    public function getConfig(): MeteorConfig
    {
        return self::$config;
    }

    /**
     * Set up worker initialization code if needed
     *
     * @param callable $init
     */
    public function init(callable $init): void
    {
        self::$init = $init;
    }

    /**
     * Add periodic $job executed every $interval of seconds
     */
    public function addJob(
        int $interval,
        callable $job,
        array $params = [],
        callable $init = null,
        string $name = '',
        int $workers = 1
    ): void {
        self::$jobs[] = [
            'interval' => $interval,
            'job'      => $job,
            'params'   => $params,
            'init'     => $init,
            'name'     => $name,
            'workers'  => $workers,
        ];
    }

    public function __call(string $name, array $args)
    {
        return self::$app->$name(...$args);
    }

    /**
     * Handle Workerman request to return Workerman response
     */
    private static function handle(ServerRequestInterface $request, ConnectionInterface $connection): ResponseInterface
    {

        $request->setAttribute('REMOTE_ADDR', $connection->getRemoteIp());
        $request->setAttribute('REMOTE_PORT', $connection->getRemotePort());

        /** @var ResponseInterface  $response */
        $response = self::$app->handle($request);
        $headers = $response->getHeaders();

        if (!isset($headers['Server'])) {
            $headers['Server'] = 'Meteor v' . self::VERSION;
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = self::$defaultMimeType;
        }

        // Save session data to disk if needed
        if (count($request->getSession()?->all())) {
            $defaultSessionName = Session::sessionName();
            if (
                !array_key_exists($defaultSessionName, $request->getCookieParams()) &&
                (!array_key_exists('cookie', $headers) ||
                    (!str_contains($headers['cookie'], $defaultSessionName)))
            ) {
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
            $request->getSession()?->save();
        }

        return $response->withHeaders($headers);
    }

    /**
     * Run Meteor server
     */
    public function run(): void
    {
        // Redirect workers output to log file if it exists
        if (self::$logger) {
            foreach (self::$logger->getHandlers() as $handler) {
                if ($handler->getUrl()) {
                    Worker::$stdoutFile = $handler->getUrl();
                    break;
                }
            }
        }

        $this->initJobWorkers();
        $worker = $this->initHttpWorkers();

        // Suppress Workerman startup message
        global $argv;
        $argv[] = '-q';

        // Write Meteor startup message to log file and show on screen
        $jobsInfo = count(self::$jobs) ? ' / ' . count(self::$jobs) . ' jobs' : '';
        $hello = sprintf(
            "%s [%d workers%s] ready on http://%s:%d",
            $worker->name,
            self::$workers,
            $jobsInfo,
            self::$host,
            self::$port
        );

        self::$logger?->info($hello);

        // Special greeting for Windows
        if (is_windows()) {
            echo "\n----------------------------------------------------------------------------------";
            echo "\nServer                        Listen                              Workers   Status";
            echo "\n----------------------------------------------------------------------------------\n";
        } else {
            echo $hello . "\n";
        }

        // Point Workerman to our Request class to use it within onMessage
        Http::requestClass(Request::class);

        $worker->onMessage = static function ($connection, Request $request) {
            try {
                $response = self::handle($request, $connection);
                $connection->send($response);
            } catch (HttpNotFoundException $error) {
                $connection->send(new Response(404));
            } catch (\Throwable $error) {
                if (self::$debug) {
                    echo "\n[ERR] " . $error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage();
                }

                self::$logger?->error($error->getFile() . ':' . $error->getLine() . ' >> ' . $error->getMessage());
                $connection->send(new Response(500));
            }
        };

        Worker::runAll();
    }

    private function initMimeTypes(): void
    {
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
    }

    private function initHttpWorkers(): Worker
    {
        $socketName = sprintf('http://%s:%d', self::$host, self::$port);
        $worker = new Worker($socketName);
        $worker->count = self::$workers;
        $worker->name = 'Meteor v' . self::VERSION;

        if (self::$init) {
            $worker->onWorkerStart = self::$init;
        }

        return $worker;
    }

    private function initJobWorkers(): void
    {
        $counter = 0;
        foreach (self::$jobs as $job) {
            $socketName = sprintf('text://%s:%d', self::$host, 65432 + $counter);
            $w = new Worker($socketName);
            $w->count = $job['workers'];
            $w->name = 'Meteor v' . self::VERSION . ' [job] ' . $job['name'];
            $w->onWorkerStart = static function () use ($job) {
                if (self::$init) {
                    call_user_func(self::$init);
                }
                Timer::add($job['interval'], $job['job']);
            };
        }
    }
}
