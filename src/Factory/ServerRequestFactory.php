<?php

namespace Meteor\Factory;

use Meteor\Request as ServerRequest;
use Meteor\ServerRequestInterface;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ServerRequestFactoryInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @throws JsonException
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {

        if (empty($method)) {
            if (!empty($serverParams['REQUEST_METHOD'])) {
                $method = $serverParams['REQUEST_METHOD'];
            } else {
                throw new InvalidArgumentException('Cannot determine HTTP method');
            }
        }

        return new ServerRequest($method);
    }
}
