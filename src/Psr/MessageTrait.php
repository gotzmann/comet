<?php

namespace Comet\Psr;

use Comet\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * Trait implementing functionality common to requests and responses.
 * @package Comet
 */
trait MessageTrait
{
    /** @var array Map of all registered headers, as original name => array of values */
    private $headers = [];

    /** @var array Map of lowercase header name => original name at registration */
    private $headerNames  = [];

    /** @var string */
    private $protocol = '1.1';

    /** @var StreamInterface|null */
    private $stream;

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * @param $version
     * @return $this
     */
    public function withProtocolVersion($version)
    {
        $this->protocol = $version;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param $header
     * @return bool
     */
    public function hasHeader($header)
    {
        return isset($this->headerNames[strtolower($header)]);
    }

    /**
     * @param $header
     * @return array|mixed
     */
    public function getHeader($header)
    {
        $header = strtolower($header);

        if (!isset($this->headerNames[$header])) {
            return [];
        }

        $header = $this->headerNames[$header];

        return $this->headers[$header];
    }

    /**
     * @param $header
     * @return string
     */
    public function getHeaderLine($header)
    {
        return implode(', ', $this->getHeader($header));
    }

    /**
     * @param $header
     * @param $value
     * @return $this
     */
    public function withHeader($header, $value)
    {
        // Skip assertHeader
        $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($header);

        if (isset($this->headerNames[$normalized])) {
            unset($this->headers[$this->headerNames[$normalized]]);
        }

        $this->headerNames[$normalized] = $header;
        $this->headers[$header] = $value;

        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        foreach ($headers as $header => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }

            // Skip trimHeaderValues
            $normalized = strtolower($header);
            if (isset($this->headerNames[$normalized])) {
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            } else {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }
        return $this;
    }

    /**
     * @param $header
     * @param $value
     * @return $this
     */
    public function withAddedHeader($header, $value)
    {
        // Skip assertHeader and normalizeHeaderValue
        $normalized = strtolower($header);
        if (isset($this->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];
            $this->headers[$header] = array_merge($this->headers[$header], $value);
        } else {
            $this->headerNames[$normalized] = $header;
            $this->headers[$header] = $value;
        }

        return $this;
    }

    /**
     * @param $header
     * @return $this
     */
    public function withoutHeader($header)
    {
        $normalized = strtolower($header);

        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $header = $this->headerNames[$normalized];

        unset($this->headers[$header], $this->headerNames[$normalized]);

        return $this;
    }

    /**
     * @return StreamInterface|null
     */
    public function getBody()
    {
        if (!$this->stream) {
            $this->stream = Utils::streamFor('');
        }

        return $this->stream;
    }

    /**
     * @param StreamInterface $body
     * @return $this
     */
    public function withBody(StreamInterface $body)
    {
        if ($body === $this->stream) {
            return $this;
        }

        $this->stream = $body;
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    private function setHeaders(array $headers)
    {
        $this->headers = [];
        $this->headerNames = [];

        foreach ($headers as $header => $value) {
            // Simplify [normalizeHeaderValue] here with just an [array_values] call
            $value = is_array($value) ? array_values($value) : array_values([$value]);

            $normalized = strtolower($header);
            if (isset($this->headerNames[$normalized])) {
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            } else {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }

        return $this;
    }

    /**
     * DEPRECATED
     * @param $value
     * @return string[]
     */
    private function normalizeHeaderValue($value)
    {
        if (!is_array($value)) {
            return $this->trimHeaderValues([$value]);
        }

        if (count($value) === 0) {
            throw new \InvalidArgumentException('Header value can not be an empty array.');
        }

        return $this->trimHeaderValues($value);
    }

    /**
     * DEPRECATED
     *
     * Trims whitespace from the header values.
     *
     * Spaces and tabs ought to be excluded by parsers when extracting the field value from a header field.
     *
     * header-field = field-name ":" OWS field-value OWS
     * OWS          = *( SP / HTAB )
     *
     * @param string[] $values Header values
     *
     * @return string[] Trimmed header values
     *
     * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
     */
    private function trimHeaderValues(array $values)
    {
        return array_map(function ($value) {
            if (!is_scalar($value) && null !== $value) {
                throw new \InvalidArgumentException(sprintf(
                    'Header value must be scalar or null but %s provided.',
                    is_object($value) ? get_class($value) : gettype($value)
                ));
            }

            return trim((string) $value, " \t");
        }, array_values($values));
    }

    /**
     * DEPRECATED
     * @param $header
     */
    private function assertHeader($header)
    {
        if (!is_string($header)) {
            throw new \InvalidArgumentException(sprintf(
                'Header name must be a string but %s provided.',
                is_object($header) ? get_class($header) : gettype($header)
            ));
        }

        if ($header === '') {
            throw new \InvalidArgumentException('Header name can not be empty.');
        }
    }
}
