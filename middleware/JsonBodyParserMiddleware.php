<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

//namespace Middleware;

//require_once __DIR__ . '/../vendor/autoload.php';

class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (strstr($contentType, 'application/json')) {
            $body = (string) $request->getBody();
            $json = json_decode($body, true);
//echo "\nfile_get_contents=" . file_get_contents('php://input');
//            $contents = json_decode(file_get_contents('php://input'), true);
//echo "\ncontents=" . $contents;
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($json);
            }
        }

        return $handler->handle($request);
    }
}
