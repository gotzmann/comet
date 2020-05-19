<?php

declare(strict_types=1);

namespace Comet;

use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\ServerRequest;

class Request extends ServerRequest implements ServerRequestInterface
{
    private $cookieParams = [];

    /**
     * @param string                               $method       HTTP method
     * @param string|UriInterface                  $uri          URI
     * @param array                                $headers      Request headers
     * @param string|null|resource|StreamInterface $body         Request body
     * @param string                               $version      Protocol version
     * @param array                                $serverParams Typically the $_SERVER superglobal
     * @param array                                $cookies      Request cookies
     * @param array                                $files        Request files
     * @param array                                $query        Query Params
     */
    public function __construct(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = '1.1',
        array $serverParams = [],
        array $cookies = [],
        array $files = [],
        array $query = []
    ) {
        parent::__construct($method, $uri, $headers, $body, $version);

        $this->serverParams = $serverParams;
        $this->cookieParams = $cookies;
        $this->uploadedFiles = $files;
        $this->queryParams = $query;
    }
}
