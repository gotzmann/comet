<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = new Comet\Comet([ 'debug' => true ]);

$app->get('/hello', function ($request, $response) {
    return $response->with("Hello, Comet!");
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

$app->get('/json2', function ($request, $response) {        
	return $response
    	->with([        
        	"code" => 200, 
        	"message" => "Hello, Comet!",        
    	]);
});

$app->run();
