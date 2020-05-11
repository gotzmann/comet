<p align="center">
  <img width="600" height="250" src="logo.png">
</p>

# Comet

Comet is a modern PHP framework for building fast REST APIs and microservices.

## Superpowers

Comet gets all superpowers from Slim microframework and Workerman library as well as adds it's own magic sauce.

[Slim](https://github.com/slimphp/Slim) is a micro-framework that helps write simple yet powerful web applications and APIs based on modern PSR standards.

[Workerman](https://github.com/walkor/Workerman) is an asynchronous event-driven framework. It deliver high performance to build fast and scalable network applications. Workerman supports HTTP, Websocket, SSL and other custom protocols. 

## Performance and Latency

PHP is often criticized for its low throughput and high latency. But that's not necessarilty true for modern frameworks. Let's see how Comet outperfroms others.

<p align="center">
  <img width="600" height="250" src="plaintext-performance.jpg">
</p>

As you can see, the right architecture provides it with tenfold advantage over Symfony and other popular frameworks.

<p align="center">
  <img width="600" height="250" src="plaintext-latency.jpg">
</p>

On the other side, latency is so slow even under hard pressure of 1,000 concurrent connections, that Comete can compete with web frameworks based on compile-time languages like Go and Java.

## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install Comet.

```bash
$ composer require gotzmann/comet
```

This will install framework itself and all required dependencies. Comet requires PHP 7.1 or newer.

## Basic Usage

### Simple Hello Comet

Create single app.php file at project root folder with content:

```php
<?php

use Comet\Comet;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Comet();

$app->get('/hello', function ($request, $response, $args) {
	  $response->getBody()->write("Hello, Comet!");      
    return $response;
});

$app->run();
```

Start it from command line:

```bash
$ php app.php start
```

Then open browser on type default address http://localhost:80 - you'll see hello from Comet!

### Simple JSON response

Let's start Comet server listening on custom port and returning JSON payload.

```php
<?php

use Comet\Comet;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Comet([
    'host' => 'localhost',
    'port' => 8080
]);

$app->get('/json', function ($request, $response, $args) {    
    $object = new stdClass();
    $object->data = [ "code" => 200, "message" => "Hello, Comet!" ];
    $payload = json_encode($object);
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json');
});

$app->run();
```

Start Postman and see the JSON resonse from GET http://localhost:8080

### Simple CRUD controller

At first, be sure that your composer.json contains autoload section like this:

```bash
    "autoload": {
        "psr-4": { "\\": "" }
    }
```    

If not, you should add this section and run:

```bash
$ composer install
```    

Create SimpleController.php in Controllers folder:

```php
<?php

namespace Controllers;

use Slim\Psr7\Request;
use Slim\Psr7\Response;

class SimpleController
{    
    private $counter = 0;

    public function getCounter(Request $request, Response $response, $args)
    {
        $response->getBody()->write($this->counter);  
        return $response->withStatus(200);
    }

    public function setCounter(Request $request, Response $response, $args)    
    {        
        $body = (string) $request->getBody();
        $json = json_decode($json, true);
        var_dump($json);
        return $response->withStatus(200);        
    }
}  
```    

Then create Comet server app.php at root folder:

```php
<?php

use Comet\Comet;
use Controllers\SimpleController;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Comet();

$app->setBasePath("/api/v1"); 

$app->get('/counter',
    'Controllers\SimpleController:getCounter');

$app->post('/counter',    
    'Controllers\SimpleController:setCounter');

$app->run();
```