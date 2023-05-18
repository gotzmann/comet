<?php

namespace Meteor\Factory;

use Meteor\Request;
use JsonException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @throws JsonException
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method);
    }
}
