<?php
declare(strict_types=1);

use Comet\Comet;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Comet();

$app->get('/hello', function ($request, $response) {
    $response
        ->getBody()
        ->write("Hello, Comet!");      
    return $response;
});

$app->get('/json', function ($request, $response) {        
    $data = [        
        "code" => 200, 
        "message" => "Hello, Comet!",        
    ];
    $payload = json_encode($data);
    $response
        ->getBody()
        ->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json');
});

$app->run();
