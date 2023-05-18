<?php

declare(strict_types=1);

namespace Meteor\Factory;

use Meteor\Request;
use Slim\Factory\Psr17\Psr17Factory;

class MeteorPsr17Factory extends Psr17Factory
{
    protected static string $responseFactoryClass = ResponseFactory::class;
    protected static string $streamFactoryClass = StreamFactory::class;
    protected static string $serverRequestCreatorClass = Request::class;
    protected static string $serverRequestCreatorMethod = 'fromGlobals';
}
