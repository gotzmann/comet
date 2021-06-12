<?php

namespace Comet;

use Psr\Http\Message\StreamInterface;

/**
 * Trait implementing functionality common to requests and responses.
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

    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version)
    {
        /* EXP
                if ($this->protocol === $version) {
                    return $this;
                }

                $new = clone $this;
                $new->protocol = $version;
                return $new; */
        $this->protocol = $version;
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($header)
    {
        return isset($this->headerNames[strtolower($header)]);
    }

/*

WAS

headers = array(14) {
    ["host"] => array(1) {
        [0] => string(9) "localhost"
    }
    ...
}

headerNames = array(14) {
    ["host"] => string(4) "host"
}

NOW


 */

    public function getHeader($header)
    {
//echo "\n\n --- getHeader COMET\n"; // DEBUG
//echo "\n headers = \n"; // DEBUG
//var_dump($this->headers); // DEBUG
//echo "\n headerNames = \n"; // DEBUG//
//var_dump($this->headerNames); // DEBUG
        $header = strtolower($header);

        if (!isset($this->headerNames[$header])) {
            return [];
        }

        $header = $this->headerNames[$header];

        return $this->headers[$header];
    }

    public function getHeaderLine($header)
    {
//echo "\n\n === getHeaderLine COMET\n";
//var_dump($header); // DEBUG
        return implode(', ', $this->getHeader($header));
    }

    public function withHeader($header, $value)
    {
        // EXP ME $this->assertHeader($header);
        $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($header);
/* EXP
        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }
        $new->headerNames[$normalized] = $header;
        $new->headers[$header] = $value;

        return $new; */

        if (isset($this->headerNames[$normalized])) {
            unset($this->headers[$this->headerNames[$normalized]]);
        }

        $this->headerNames[$normalized] = $header;
        $this->headers[$header] = $value;

        return $this;
    }

    // EXP FIXME!!! Do we need this?
    public function withHeaders(array $headers)
    {
//echo "\n[[[[[ MESSAGE TRAIT : WITH HEADERS ]]]]\n"; // DEBUG
        foreach ($headers as $header => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }

            // EXP:ME $value = $this->trimHeaderValues($value);
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

    public function withAddedHeader($header, $value)
    {
        // EXP:ME $this->assertHeader($header);
        // EXP:ME $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($header);
/* EXP
        $new = clone $this;
        if (isset($new->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];
            $new->headers[$header] = array_merge($this->headers[$header], $value);
        } else {
            $new->headerNames[$normalized] = $header;
            $new->headers[$header] = $value;
        }

        return $new;
*/
        if (isset($this->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];
            $this->headers[$header] = array_merge($this->headers[$header], $value);
        } else {
            $this->headerNames[$normalized] = $header;
            $this->headers[$header] = $value;
        }

        return $this;
    }

    public function withoutHeader($header)
    {
        $normalized = strtolower($header);

        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $header = $this->headerNames[$normalized];
/* EXP
        $new = clone $this;
        unset($new->headers[$header], $new->headerNames[$normalized]);

        return $new; */

        unset($this->headers[$header], $this->headerNames[$normalized]);

        return $this;
    }

    public function getBody()
    {
        if (!$this->stream) {
            $this->stream = \GuzzleHttp\Psr7\Utils::streamFor('');
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body)
    {
        if ($body === $this->stream) {
            return $this;
        }
/* EXP
        $new = clone $this;
        $new->stream = $body;
        return $new; */

        $this->stream = $body;
        return $this;
    }

    // EXP FIXME!!! What the right code here?
    private function setHeaders(array $headers)
    {
        // EXP:ME $this->headerNames = $this->headers = [];
        $this->headers = [];
        $this->headerNames = [];

        foreach ($headers as $header => $value) {
/* EXP:ME
            if (is_int($header)) {
                // Numeric array keys are converted to int by PHP but having a header name '123' is not forbidden by the spec
                // and also allowed in withHeader(). So we need to cast it to string again for the following assertion to pass.
                $header = (string) $header;
            } */

            // EXP:ME $this->assertHeader($header);
            // EXP:ME $value = $this->normalizeHeaderValue($value);

            // EXP:ME Try to double code before
            // EXP:ME $this->assertHeader($header);
//echo "\n*** VALUE BEFORE\n"; // DEBUG
//var_dump($value);
            // string(24) "text/html; charset=utf=8" ===> array(1) { [0] => string(24) "text/html; charset=utf=8" }
            // EXP:ME $value = $this->normalizeHeaderValue($value);
            // Simplify [normalizeHeaderValue] here with just an [array_values] call
            $value = is_array($value) ? array_values($value) : array_values([$value]);
//echo "\n*** VALUE AFTER\n"; // DEBUG
//var_dump($value);

            $normalized = strtolower($header);
            if (isset($this->headerNames[$normalized])) {
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            } else {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }
//*/
// EXP        return $this->withHeaders($headers);

        // EXP:ME Try to return code - NO LOOP!
//        return $this->withHeaders($headers);

    }

    // TODO Do we need this?
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
    // TODO Do we need this?
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

    // TODO Do we need this?
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
