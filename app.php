<?php
declare(strict_types=1);

//use Workerman\Worker;
//use Workerman\Timer;

use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;

use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response as SlimResponse;

use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Factory\AppFactory;
use Slim\Psr7\Headers;

//use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Capsule\Manager as ORM;
// Set the event dispatcher used by Eloquent models... (optional)
//use Illuminate\Events\Dispatcher;
//use Illuminate\Container\Container;

//use Handlers\ConsumerServicePaymentHandler;
//use Middleware\JsonBodyParser;

require_once __DIR__ . '/vendor/autoload.php';

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
    global $orm, $sql;

    // TODO DO we need this or better global static?
    $orm = new ORM;

    // TODO getenv from ENV
    $orm->addConnection([
        'driver'    => 'mysql',
        //'host'      => '192.168.99.1', // 'host.docker.internal',
        'host'      => '127.0.0.1',
        'database'  => 'hello',
        'username'  => 'hello',
        'password'  => 'hello',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ]);

    // Set the event dispatcher used by Eloquent models... (optional)
    //$orm->setEventDispatcher(new Dispatcher(new Container));

    // Make this ORM instance available globally via static methods... (optional)
    $orm->setAsGlobal();

    // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
    //$orm->bootEloquent();

    // Init PDO statements

    //global $statement, $fortune, $random, $update;
    //$pdo = new PDO('mysql:host=192.168.99.1;dbname=hello', 'hello', 'hello',
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=hello', 'hello', 'hello', [
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

    // TODO Remove after tests
    $app->get('/hello', function (SlimRequest $request, SlimResponse $response, $args) {
        $response->getBody()->write("{Slimmer} Hello!");
        $new_response = $response->withHeader('testheader', 'itworks');
        return $new_response;
    });

    // TODO Remove after tests
    $app->get('/error', function (SlimRequest $request, SlimResponse $response, $args) {
        $null->wtf(); // Null Exception
    });

    // TODO Remove after tests
    $app->get('/pdo', function (SlimRequest $request, SlimResponse $response, $args) {
        global $sql;
        $sql->execute();
        $payload = json_encode($sql->fetchAll());
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    });

    // TODO Remove after tests
    $app->get('/orm', function (SlimRequest $request, SlimResponse $response, $args) {
        $fortunes = ORM::table('fortune')->get();
        $payload = json_encode($fortunes);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    });

    // TODO Include user-defined init()
}

// Handle EACH request and form response
function handle(WorkermanRequest $request)
{
    global $app;

    $req = new SlimRequest(
        $request->method(),
        (new UriFactory())->createUri($request->path()),
        (new Headers())->setHeaders($request->header()),
        $request->cookie(),
        [], // $_SERVER ?
        (new StreamFactory)->createStream($request->rawBody())
    );

    $ret = $app->handle($req);

    $response = new WorkermanResponse(
        $ret->getStatusCode(),
        $ret->getHeaders(),
        $ret->getBody()
    );

    return $response;
}
