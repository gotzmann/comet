<?php

declare(strict_types=1);

namespace Meteor;

if (!function_exists("is_windows")) {
    function is_windows(): bool
    {
        return stripos(PHP_OS_FAMILY, 'WIN') === 0;
    }
}

if (!function_exists("is_linux")) {
    function is_linux(): bool
    {
        return stripos(PHP_OS_FAMILY, 'LINUX') === 0;
    }
}

if (!function_exists("is_osx")) {
    function is_osx(): bool
    {
        return stripos(PHP_OS_FAMILY, 'OSX') === 0;
    }
}
