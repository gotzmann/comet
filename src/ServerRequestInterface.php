<?php

declare(strict_types=1);

namespace Meteor;

interface ServerRequestInterface extends \Psr\Http\Message\ServerRequestInterface
{
    public function setAttribute($attribute, $value): ServerRequestInterface;
    public function getSession(): ?Session;
}
