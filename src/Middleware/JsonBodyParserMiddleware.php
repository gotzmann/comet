<?php

declare(strict_types=1);

namespace Meteor\Middleware;

use JsonException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * JsonBodyParserMiddleware - DEPRECATED!
 *
 * self::$app->add(new JsonBodyParserMiddleware());
 * @package Meteor\Middleware
 */
class JsonBodyParserMiddleware implements MiddlewareInterface
{
    /**
     * @throws JsonException
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $body = (string) $request->getBody();
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($json);
            }
        }

        return $handler->handle($request);
    }
}
