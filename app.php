<?php
declare(strict_types=1);
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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
// FIXME By default Workerman logs to ./vendor/workerman/workerman.log too - disable it!

//use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Capsule\Manager as ORM;
// Set the event dispatcher used by Eloquent models... (optional)
//use Illuminate\Events\Dispatcher;
//use Illuminate\Container\Container;

//use Handlers\ConsumerServicePaymentHandler;
//use Middleware\JsonBodyParser;

require_once __DIR__ . '/vendor/autoload.php';

// FIXME Use Dotenv OR docker-compose ?
#$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
#$dotenv->load();

if (getenv('DB_TYPE') == '' || getenv('DB_NAME') == '')
    die("\n[ERR] Environment has no DB settings!");

// TODO Move to autoload!
// Include all PHP files except vendors and migrations
foreach(scandir(__DIR__) as $dir) {
    if (!in_array($dir, ['.', '..', 'vendor', 'migrations'])) {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . $dir;
        if (is_dir($dir)) {
            foreach(glob("$dir/*.php") as $file) {
                require $file;
            }
        }
    }
}

// The very first function which runs ONLY ONCE and bootstrap the WHOLE app
function bootstrap()
{
    global $orm, $log, $sql;

    // TODO Clear ENVs
    // После получения переменные окружения больше не нужны и даже могут представлять опасность — например,
    // они могут случайно «утечь» с отображением информации об ошибке. Злоумышленники в первую очередь
    // будут пытаться получить информацию об окружении, поэтому очистка переменных окружения считается хорошим тоном.

    $dbType = getenv('DB_TYPE');
    $dbHost = getenv('DB_HOST');
    $dbPort = getenv('DB_PORT');
    $dbName = getenv('DB_NAME');
    $dbSchema = getenv('DB_SCHEMA');
    $dbUser = getenv('DB_USER');
    $dbPassword = getenv('DB_PASSWORD');

    echo "[INFO] Using database $dbType:$dbName on $dbHost:$dbPort\n";

    // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
    $formatter = new LineFormatter(
        "\n%datetime% >> %channel%:%level_name% >> %message%",
        "Y-m-d H:i:s"
    );
    // TODO Log file name from ENV!
    $stream = new StreamHandler(__DIR__ . '/log/sberprime.log', Logger::INFO);
    $stream->setFormatter($formatter);
    $log = new Logger('sberprime');
    $log->pushHandler($stream);

    // TODO DO we need this or better global static?
    $orm = new ORM;

    // TODO getenv from ENV
    $orm->addConnection([
        'driver'    => $dbType,
        'host'      => $dbHost,
        'port'      => $dbPort,
        'database'  => $dbName,
        'schema'    => $dbSchema,
        'username'  => $dbUser,
        'password'  => $dbPassword,
        'charset'   => 'utf8',
        'prefix'    => ''
    ]);

    // Set the event dispatcher used by Eloquent models... (optional)
    //$orm->setEventDispatcher(new Dispatcher(new Container));

    // Make this ORM instance available globally via static methods... (optional)
    $orm->setAsGlobal();

    // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
    //$orm->bootEloquent();

    // Init PDO statements
    $pdo = new PDO("$dbType:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPassword, [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    $sql = $pdo->prepare('SELECT id, message FROM fortune');

    // TODO Include user-defined bootstrap()
}

// Initialization code for EACH worker - it runs when worker starts working
function init()
{
    // TODO Refactor routing for stand-alone handlers

    global $app, $orm, $sql,
        $servicePaymentHandler,
        $servicePaymentExpiredHandler;

    // Init Slim App and HTTP Handlers

    $app = AppFactory::create();
    $app->setBasePath("/api/v1"); // TODO Make ENV BASE_PATH

    $middleware = new JsonBodyParserMiddleware();
    $app->add($middleware);

    // FIXME If there no GET / POST / URL handler when Postman calls - there are no any response at all!

    $app->post('/servicePaymentHandler',
        $servicePaymentHandler);

    $app->post('/servicePaymentExpiredHandler',
        $servicePaymentExpiredHandler);

    // TODO Add endpoints to show API version and healthcheck

    // TODO Include user-defined init()
}
*/
