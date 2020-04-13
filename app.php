<?php
declare(strict_types=1);

use Workerman\Worker;
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

use Illuminate\Database\Capsule\Manager as Capsule;
// Set the event dispatcher used by Eloquent models... (optional)
//use Illuminate\Events\Dispatcher;
//use Illuminate\Container\Container;

//require_once __DIR__ . '/vendor/autoload.php';

//global $app, $capsule, $sql;

//use Handlers\ConsumerServicePaymentHandler;
//use Middleware\JsonBodyParser;

require_once __DIR__ . '/vendor/autoload.php';

$dirs = ['handlers', 'middleware', 'models'];
foreach($dirs as $dir) {
    foreach(glob(__DIR__ . "/$dir/*.php") as $file) {
        //echo "\n$file";
        require $file;
    }
}

/*
$consumerServicePaymentHandler = function(SlimRequest $request, SlimResponse $response, $args)
{
    var_dump($response->getBody());
    $response->getBody()->write("{Comet} ConsumerServicePaymentHandler!");
    return $response;
};
*/

// The very first function which runs ONLY ONCE and bootstrap the WHOLE app
function bootstrap()
{
    global $capsule, $worker, $sql;

    // TODO DO we need this or better global static?
    $capsule = new Capsule;

    // TODO getenv from ENV
    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => '192.168.99.1', // 'host.docker.internal',
        'database'  => 'hello',
        'username'  => 'hello',
        'password'  => 'hello',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ]);

    // Set the event dispatcher used by Eloquent models... (optional)
    //$capsule->setEventDispatcher(new Dispatcher(new Container));

    // Make this Capsule instance available globally via static methods... (optional)
    $capsule->setAsGlobal();

    // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
    //$capsule->bootEloquent();

    // Init PDO statements

    //global $statement, $fortune, $random, $update;
    $pdo = new PDO('mysql:host=192.168.99.1;dbname=hello', 'hello', 'hello',
        [ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false ]
    );
    //$statement = $pdo->prepare('SELECT id,randomNumber FROM World WHERE id=?');
    $sql = $pdo->prepare('SELECT id, message FROM fortune');
    //$random    = $pdo->prepare('SELECT randomNumber FROM World WHERE id=?');
    //$update    = $pdo->prepare('UPDATE World SET randomNumber=? WHERE id=?');

    // TODO Include user-defined bootstrap()
}

//get("1", "2", "3");


// Initialization code for EACH worker - it runs when worker starts working
function init()
{
    // TODO Refactor routing for stand-alone handlers

    global $app, $capsule, $sql, $the,
        $consumerServicePaymentHandler;

        //var_dump($the);

    // Init Slim App and HTTP Handlers

    $app = AppFactory::create();
    $app->setBasePath("/api/v1"); // TODO Make ENV BASE_PATH

    $middleware = new JsonBodyParserMiddleware();
    $app->add($middleware);

    $app->get('/servicePaymentHandler',
        $consumerServicePaymentHandler);

//    $app->get('/servicePaymentHandler',
//        get);

    $app->post('/servicePaymentHandler',
        $consumerServicePaymentHandler);

    $app->get('/', function(SlimRequest $request, SlimResponse $response, $args) {
        $response->getBody()->write("{Slimmer} Root!");
        return $response;
    });

    $app->get('/hello', function (SlimRequest $request, SlimResponse $response, $args) {
        $response->getBody()->write("{Slimmer} Hello!");
        $new_response = $response->withHeader('testheader', 'itworks');
        return $new_response;
    });

    $app->get('/error', function (SlimRequest $request, SlimResponse $response, $args) {
        $null->wtf(); // Null Exception
    });

    $app->get('/json', function (SlimRequest $request, SlimResponse $response, $args) {
        $data = [ 'name' => 'Bob', 'age' => 40 ];
        //return $response->withJson($data);
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    });

    $app->get('/pdo', function (SlimRequest $request, SlimResponse $response, $args) {
        global $sql;
        $sql->execute();
        $payload = json_encode($sql->fetchAll());
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    });

    $app->get('/orm', function (SlimRequest $request, SlimResponse $response, $args) {
        $fortunes = Capsule::table('fortune')->get();
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

    // TODO HTTP vs HTTPS + '://' + PORT
    // TODO Headers
    //$uri = (new UriFactory())->createUri($request->uri());
    //$uri = (new UriFactory())->createUri("http://" . $request->host() . $request->path() . $request->queryString());
    $uri = (new UriFactory())->createUri($request->path());
    //echo "\nURI=" . "http://" . $request->host() . $request->path() . $request->queryString();
    //echo "\nPATH=" . $request->path();
    //$uri = (new UriFactory())->createUri($request->path() . $request->queryString());
    //$uri = (new UriFactory())->createUri("http://local.host:1980/hello");
    $body = (new StreamFactory)->createStream($request->rawBody());
    $headers = (new Headers())->setHeaders($request->header());
    //$req = new SlimRequest($request->method(), $uri, $headers, $request->cookie(), $_SERVER, $body);
    $req = new SlimRequest($request->method(), $uri, $headers, $request->cookie(), [], $body);

    $ret = $app->handle($req);

    $response = new WorkermanResponse(
        $ret->getStatusCode(),
        $ret->getHeaders(),
        $ret->getBody()
    );

    return $response;
}


/*
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\Request;

function init()
{
    global $statement, $fortune, $random, $update;
//    $pdo = new PDO('mysql:host=tfb-database;dbname=hello_world',
//        'benchmarkdbuser', 'benchmarkdbpass',
//        [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//        PDO::ATTR_EMULATE_PREPARES    => false]
    //);
    //$statement = $pdo->prepare('SELECT id,randomNumber FROM World WHERE id=?');
    //$fortune   = $pdo->prepare('SELECT id,message FROM Fortune');
    //$random    = $pdo->prepare('SELECT randomNumber FROM World WHERE id=?');
    //$update    = $pdo->prepare('UPDATE World SET randomNumber=? WHERE id=?');
}

function router(Request $request)
{
    switch ($request->path()) {

        case '/hello':
            return new Response(200, [
                //'Content-Type' => 'text/plain',
                //'Date'         => Header::$date
            ], '[Workerman] Hello!');

        case '/plaintext':
            return new Response(200, [
                'Content-Type' => 'text/plain',
                'Date'         => Header::$date
            ], 'Hello, World!');

        case '/json':
            return new Response(200, [
                'Content-Type' => 'application/json',
                'Date'         => Header::$date
            ], json_encode(['message' => 'Hello, World!']));

        case '/db':
            return db();

        case '/fortune':
            // By default use 'Content-Type: text/html; charset=utf-8';
            return fortune();

        case '/query':
            return query($request);

        case '/update':
            return updateraw($request);

       case '/info':
            ob_start();
            phpinfo();
            return new Response(200, ['Content-Type' => 'text/plain'], ob_get_clean());

        default:
            return new Response(404, [], 'Error 404');
    }
}

function db()
{
    global $statement;

    $statement->execute([mt_rand(1, 10000)]);

    return new Response(200, [
        'Content-Type' => 'application/json',
        'Date'         => Header::$date
    ], json_encode($statement->fetch()));
}

function query($request)
{
    global $statement;

    $query_count = 1;
    $q = $request->get('q');
    if ($q > 1) {
        $query_count = min($q, 500);
    }

    while ($query_count--) {
        $statement->execute([mt_rand(1, 10000)]);
        $arr[] = $statement->fetch();
    }

    return new Response(200, [
        'Content-Type' => 'application/json',
        'Date'         => Header::$date
    ], json_encode($arr));
}

function updateraw($request)
{
    global $random, $update;

    $query_count = 1;
    $q = $request->get('q');
    if ($q > 1) {
        $query_count = min($q, 500);
    }

    while ($query_count--) {
        $id = mt_rand(1, 10000);
        $random->execute([$id]);
        $world = ['id' => $id, 'randomNumber' => $random->fetchColumn()];
        $update->execute(
            [$world['randomNumber'] = mt_rand(1, 10000), $id]
        );

        $arr[] = $world;
    }

    // $pdo->beginTransaction();
    // foreach($arr as $world) {
    //     $update->execute([$world['randomNumber'], $world['id']]);
    // }
    // $pdo->commit();
    return new Response(200, [
        'Content-Type' => 'application/json',
        'Date'         => Header::$date
    ], json_encode($arr));
}

function fortune()
{
    global $fortune;

    $fortune->execute();

    $arr    = $fortune->fetchAll(PDO::FETCH_KEY_PAIR);
    $arr[0] = 'Additional fortune added at request time.';
    asort($arr);

    $html = '';
    foreach ($arr as $id => $message) {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $html .= "<tr><td>$id</td><td>$message</td></tr>";
    }

    return new Response(200, [
        'Date'         => Header::$date
    ], '<!DOCTYPE html><html><head><title>Fortunes</title></head><body><table><tr><th>id</th><th>message</th></tr>'
        .$html.
        '</table></body></html>');
}

*/
