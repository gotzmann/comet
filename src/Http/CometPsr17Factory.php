<?php
declare(strict_types=1);

namespace Comet\Http\Factory;

class CometPsr17Factory extends Slim\Factory\Psr17\Psr17Factory
{
    protected static $responseFactoryClass = 'Http\Factory\Guzzle\ResponseFactory';
    protected static $streamFactoryClass = 'Http\Factory\Guzzle\StreamFactory';
    protected static $serverRequestCreatorClass = 'GuzzleHttp\Psr7\ServerRequest';
    protected static $serverRequestCreatorMethod = 'fromGlobals';
}
