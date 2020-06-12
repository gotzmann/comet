<?php               
declare(strict_types=1);

namespace Comet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

// Fast PSR-7 Response implementation

class Response extends GuzzleResponse implements ResponseInterface
{
	use MessageTrait;

    /** @var int */
    private $statusCode = 200;

    /** @var string */
    private $reasonPhrase = '';

    /** @var array Map of standard HTTP status code/reason phrases */
    private static $phrases = [
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
        511 => 'Network Authentication Required',
    ];

    /**
     * @param int                                  $status  Status code
     * @param array                                $headers Response headers
     * @param string|null|resource|StreamInterface $body    Response body
     * @param string                               $version Protocol version
     * @param string|null                          $reason  Reason phrase
     */
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        $this->statusCode = (int) $status;
        $this->setHeaders($headers);

        if ($body !== '' && $body !== null) {
            $this->stream = \GuzzleHttp\Psr7\stream_for($body);
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
     * NB! We expect that 'Content-Type' => 'text/plain' will be set up 
     * by Comet at the last step of the response emitting if needed
     *
     * @param $body Response body as array, object or string
     * @param null $status Optional HTTP Status
     * @param null $headers Optional HTTP Headers
     * @return Response Comet PSR-7 HTTP Response
     */
    public function with($body, $status = null)
    {
        $new = clone $this;

        if ($status) {
            $new->statusCode = (int) $status;
            if (isset(self::$phrases[$status])) {
                $new->reasonPhrase = self::$phrases[$status];
            }
        }

        if (is_array($body) || is_object($body)) {
            $body = json_encode($body);
            if ($body === false) {
                throw new \RuntimeException(json_last_error_msg(), json_last_error());
            }
            $new->setHeaders([ 'Content-Type' => 'application/json; charset=utf-8' ]);
        } 

        $new->stream = \GuzzleHttp\Psr7\stream_for($body);

        return $new;
    }

    /**
     * Set ALL responce headers at once
     *
     * @param $headers
     * @return Response
     */
    public function withHeaders($headers)
    {
        $new = clone $this;
        $new->setHeaders($headers);
        return $new;
    }

    public function withText($body, $status = null)
    {
        $new = clone $this;

        if (isset($status)) {
            $new->statusCode = (int) $status;
            if (isset(self::$phrases[$status])) {
                $new->reasonPhrase = self::$phrases[$status];
            }
        }

        $new->setHeaders([ 'Content-Type' => 'text/plain; charset=utf-8' ]);

        $new->stream = \GuzzleHttp\Psr7\stream_for($body);

        return $new;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $this->assertStatusCodeIsInteger($code);
        $code = (int) $code;
        $this->assertStatusCodeRange($code);

        $new = clone $this;
        $new->statusCode = $code;
        if ($reasonPhrase == '' && isset(self::$phrases[$new->statusCode])) {
            $reasonPhrase = self::$phrases[$new->statusCode];
        }
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    private function assertStatusCodeIsInteger($statusCode)
    {
        if (filter_var($statusCode, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('Status code must be an integer value.');
        }
    }

    private function assertStatusCodeRange($statusCode)
    {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new \InvalidArgumentException('Status code must be an integer value between 1xx and 5xx.');
        }
    }
}
