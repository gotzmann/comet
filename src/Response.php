<?php
declare(strict_types=1);

namespace Comet;

use Slim\Psr7\Response as SlimResponse;

class Response extends SlimResponse implements ResponseInterface
{
    public function withBody(string $body)
    {
        $clone = clone $this;
        $clone->body->write($body);
        return $clone;
    }
/*
    public function asJson()
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
*/    
}
