<?php

declare(strict_types=1);

namespace Meteor;

use Meteor\Psr\MessageTrait;
use JsonException;
use Psr\Http\Message\StreamInterface;

/**
 * Fast PSR-7 Response implementation
 * @package Meteor
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    private int $statusCode;

    private string $reasonPhrase = '';

    private static array $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    public function __construct(
        int $status = 200,
        array $headers = [],
        StreamInterface|string $body = null,
        string $version = '1.1',
        ?string $reason = null
    ) {
        $this->statusCode = $status;
        $this->setHeaders($headers);

        if ($body !== '' && $body !== null) {
            $this->stream = Utils::streamFor($body);
        }

        $this->protocol = $version;

        if (!$reason && isset(self::$phrases[$this->statusCode])) {
            $this->reasonPhrase = self::$phrases[$this->statusCode];
        } else {
            $this->reasonPhrase = $reason;
        }
    }

    /**
     * Smart method returns right type of Response for any type of content
     * NB! We expect that 'Content-Type' => 'text/html' will be set up
     * by Meteor at the last step of the response emitting if needed
     *
     * @throws JsonException
     */
    public function with(mixed $body, ?int $status = null): ResponseInterface
    {
        if ($status) {
            $this->statusCode = $status;
            if (isset(self::$phrases[$status])) {
                $this->reasonPhrase = self::$phrases[$status];
            }
        }

        if (is_array($body) || is_object($body)) {
            $body = json_encode($body, JSON_THROW_ON_ERROR);
            if ($body === false) {
                throw new \RuntimeException(json_last_error_msg(), json_last_error());
            }
            $this->setHeaders([ 'Content-Type' => 'application/json; charset=utf-8' ]);
        }

        $this->stream = Utils::streamFor($body);

        return $this;
    }

    /**
     * Set ALL responce headers at once
     *
     * @param $headers
     * @return Response
     */
    public function withHeaders($headers): ResponseInterface
    {
        $this->setHeaders($headers);
        return $this;
    }

    public function withText($body, $status = null): ResponseInterface
    {
        if (isset($status)) {
            $this->statusCode = (int) $status;
            if (isset(self::$phrases[$status])) {
                $this->reasonPhrase = self::$phrases[$status];
            }
        }

        $this->setHeaders([ 'Content-Type' => 'text/plain; charset=utf-8' ]);

        $this->stream = Utils::streamFor($body);

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): ?string
    {
        return $this->reasonPhrase;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $this->statusCode = $code;
        if ($reasonPhrase === '' && isset(self::$phrases[$this->statusCode])) {
            $reasonPhrase = self::$phrases[$this->statusCode];
        }
        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    public function __toString(): string
    {
        $msg = 'HTTP/'
            . $this->protocol . ' '
            . $this->statusCode . ' '
            . $this->reasonPhrase;

        $headers = $this->getHeaders();

        if (empty($headers)) {
            $msg .= "\r\nContent-Length: " . $this->getBody()->getSize() .
                "\r\nContent-Type: text/html; charset=utf-8" .
                "\r\nConnection: keep-alive";
        } else {
            if (
                '' === $this->getHeaderLine('Transfer-Encoding') &&
                '' === $this->getHeaderLine('Content-Length')
            ) {
                $msg .= "\r\nContent-Length: " . $this->getBody()->getSize();
            }

            if ('' === $this->getHeaderLine('Content-Type')) {
                $msg .= "\r\nContent-Type: text/html; charset=utf-8";
            }

            if ('' === $this->getHeaderLine('Connection')) {
                $msg .= "\r\nConnection: keep-alive";
            }

            foreach ($headers as $name => $values) {
                if (is_array($values)) {
                    $values = implode(', ', $values);
                }

                $msg .= "\r\n" . $name . ": " . $values;
            }
        }

        return $msg . "\r\n\r\n" . $this->getBody();
    }
}
