<?php
declare(strict_types=1);

namespace Comet\Factory;

class CometPsr17Factory extends \Slim\Factory\Psr17\Psr17Factory
{
    protected static $responseFactoryClass = 'Comet\Factory\ResponseFactory';
    protected static $streamFactoryClass = 'Comet\Factory\StreamFactory';
    protected static $serverRequestCreatorClass = 'Comet\Request';
    protected static $serverRequestCreatorMethod = 'fromGlobals';
}
