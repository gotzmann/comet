<?php
declare(strict_types=1);

namespace Comet\Factory;

class CometPsr17Factory extends \Slim\Factory\Psr17\Psr17Factory
{
    protected static string $responseFactoryClass = 'Comet\Factory\ResponseFactory';
    protected static string $streamFactoryClass = 'Comet\Factory\StreamFactory';
    protected static string $serverRequestCreatorClass = 'Comet\Request';
    protected static string $serverRequestCreatorMethod = 'fromGlobals';
}
