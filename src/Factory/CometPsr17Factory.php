<?php
declare(strict_types=1);

namespace Comet\Factory;

class CometPsr17Factory extends \Slim\Factory\Psr17\Psr17Factory
{

//    protected static $responseFactoryClass = 'Nyholm\Psr7\Factory\Psr17Factory';
  //  protected static $streamFactoryClass = 'Nyholm\Psr7\Factory\Psr17Factory';
//    protected static $serverRequestCreatorClass = 'Nyholm\Psr7Server\ServerRequestCreator';
  //  protected static $serverRequestCreatorMethod = 'fromGlobals';

//    protected static $responseFactoryClass = 'Http\Factory\Guzzle\ResponseFactory';
  //  protected static $streamFactoryClass = 'Http\Factory\Guzzle\StreamFactory';
//    protected static $serverRequestCreatorClass = 'GuzzleHttp\Psr7\ServerRequest';
  //  protected static $serverRequestCreatorMethod = 'fromGlobals';

    protected static $responseFactoryClass = 'Comet\Factory\ResponseFactory';
    protected static $streamFactoryClass = 'Http\Factory\Guzzle\StreamFactory';
    protected static $serverRequestCreatorClass = 'Comet\Request';
    protected static $serverRequestCreatorMethod = 'fromGlobals';
/* 
    public static function getServerRequestCreator(): ServerRequestCreatorInterface
    {
        // Comet Psr17Factory implements all factories in one unified factory
        $psr17Factory = new static::$responseFactoryClass();

        $serverRequestCreator = new static::$serverRequestCreatorClass(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        return new ServerRequestCreator($serverRequestCreator, static::$serverRequestCreatorMethod);
    }
*/
}

/*

namespace Nyholm\Psr7\Factory;

use Nyholm\Psr7\{Request, Response, ServerRequest, Stream, UploadedFile, Uri};
use Psr\Http\Message\{RequestFactoryInterface, RequestInterface, ResponseFactoryInterface, ResponseInterface, ServerRequestFactoryInterface, ServerRequestInterface, StreamFactoryInterface, StreamInterface, UploadedFileFactoryInterface, UploadedFileInterface, UriFactoryInterface, UriInterface};

final class Psr17Factory implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (2 > \func_num_args()) {
            // This will make the Response class to use a custom reasonPhrase
            $reasonPhrase = null;
        }

        return new Response($code, [], null, '1.1', $reasonPhrase);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = @\fopen($filename, $mode);
        if (false === $resource) {
            if ('' === $mode || false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'])) {
                throw new \InvalidArgumentException('The mode ' . $mode . ' is invalid.');
            }

            throw new \RuntimeException('The file ' . $filename . ' cannot be opened.');
        }

        return Stream::create($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return Stream::create($resource);
    }

    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        if (null === $size) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }
}

*/