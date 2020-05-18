<?php
declare(strict_types=1);

namespace Comet\Http\Factory;

class CometPsr17Factory extends Psr17Factory
{
    protected static $responseFactoryClass = 'Comet\Http\Factory\ResponseFactory';
    protected static $streamFactoryClass = 'Http\Factory\Guzzle\StreamFactory';
    protected static $serverRequestCreatorClass = 'Comet\Http\ServerRequest';
    protected static $serverRequestCreatorMethod = 'fromGlobals';
}
