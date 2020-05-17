<?php
declare(strict_types=1);

namespace Comet;

//use Slim\Psr7\Response as SlimResponse;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Response as BaseResponse;

class Response extends BaseResponse implements ResponseInterface
{    
/*    
    public function withBody(string $body)
    {
        $clone = clone $this;
        $clone->body->write($body);
        return $clone;
    }
*/    
/*
    public function asJson()
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
*/    

    public function withJson($data, $status = null, $encodingOptions = 0)
    {
        $json = json_encode($data, $encodingOptions);
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        $new = $this->withBody(new Body(fopen('php://temp', 'r+')));
        $new->getBody()->write($json);

        $new = $new->withHeader('Content-Type', 'application/json;charset=utf-8');
        if (isset($status)) {
            return $new->withStatus($status);
        }
        return $new;
    }

}

/*

class Response implements ResponseInterface
{
    private $underlyingResponse;

    public function __construct()
    {
        $this->underlyingResponse = new UnderlyingResponse();
    }

    public function getStatusCode()
    {
        return $this->underlyingResponse->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $new = clone $this;
        $new->underlyingResponse = $new->underlyingResponse->withStatus($code, $reasonPhrase);

        return $new;
    }

    public function withJson($data, $status = null, $encodingOptions = 0)
    {
        $json = json_encode($data, $encodingOptions);
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        $new = $this->withBody(new Body(fopen('php://temp', 'r+')));
        $new->getBody()->write($json);


        $new = $new->withHeader('Content-Type', 'application/json;charset=utf-8');
        if (isset($status)) {
            return $new->withStatus($status);
        }
        return $new;
    }
}

*/