<?php

declare(strict_types=1);

namespace Meteor;

use JsonException;

/**
 * Class Client
 * Absolutely Expreimental! Please do not use it in production
 * @package Meteor
 */
class Client
{
    /**
     * Client constructor
     */
    public function __construct()
    {
    }

    public static function get($url, $data = null): false|string
    {
        if ($data) {
            $url .= '?' . http_build_query($data);
        }

        return file_get_contents($url);
    }

    /**
     * @throws JsonException
     */
    public static function post($url, $data): false|string
    {
        if (is_array($data)) {
            $data = json_encode($data, JSON_THROW_ON_ERROR);
        }

        $opts = [
            'http' => [
                'method' => "POST",
                'header' =>
                    "Content-type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Connection: close\r\n" .
                    "Content-length: " . strlen($data) . "\r\n",
                'content' => $data,
                'protocol_version' => '1.1',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];

        $ctx = stream_context_create($opts);
        return file_get_contents($url, false, $ctx);
    }
}
