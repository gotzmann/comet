<?php

declare(strict_types=1);

namespace Meteor;

interface ResponseInterface extends \Psr\Http\Message\ResponseInterface
{
    public function with(mixed $body, ?int $status = null): ResponseInterface;
    public function withHeaders($headers): ResponseInterface;
    public function withText($body, $status = null): ResponseInterface;
}
