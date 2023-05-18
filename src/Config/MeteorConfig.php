<?php

declare(strict_types=1);

namespace Meteor\Config;

class MeteorConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly bool $debug,
        public readonly int $workers,
    ) {}
}