<?php

namespace Meteor\Psr;

use Meteor\Utils;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Trait implementing functionality common to requests and responses.
 * @package Meteor
 */
trait MessageTrait
{
    private array $headers = [];

    private array $headerNames  = [];

    private string $protocol = '1.1';

    private ?StreamInterface $stream;

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion(string $version): static
    {
        $this->protocol = $version;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): mixed
    {
        $name = strtolower($name);

        if (!isset($this->headerNames[$name])) {
            return [];
        }

        $name = $this->headerNames[$name];

        return $this->headers[$name];
    }

    public function withHeader(string $name, mixed $value): static
    {
        // Skip assertHeader
        $value = $this->normalizeHeaderValue($value);
        $normalized = strtolower($name);

        if (isset($this->headerNames[$normalized])) {
            unset($this->headers[$this->headerNames[$normalized]]);
        }

        $this->headerNames[$normalized] = $name;
        $this->headers[$name] = $value;

        return $this;
    }

    public function getHeaderLine(string $name): string
    {
        $header = $this->getHeader($name);

        if (is_array($header)) {
            return implode(', ', $header);
        }

        return $header;
    }
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $header => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }

            $this->setNormalizedHeader($header, $value);
        }

        return $this;
    }

    public function withAddedHeader(string $name, mixed $value): static
    {
        $this->setNormalizedHeader($name, $value);
        return $this;
    }

    public function withoutHeader(string $name): static
    {
        $normalized = strtolower($name);

        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $name = $this->headerNames[$normalized];

        unset($this->headers[$name], $this->headerNames[$normalized]);

        return $this;
    }

    public function getBody(): StreamInterface
    {
        if (!$this->stream) {
            $this->stream = Utils::streamFor('');
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body): static
    {
        if ($body === $this->stream) {
            return $this;
        }

        $this->stream = $body;
        return $this;
    }

    private function setHeaders(array $headers): static
    {
        $this->headers = [];
        $this->headerNames = [];

        foreach ($headers as $header => $value) {
            $value = is_array($value) ? array_values($value) : $value;
            $this->setNormalizedHeader($header, $value);
        }

        return $this;
    }

    private function normalizeHeaderValue($value): array
    {
        if (!is_array($value)) {
            return $this->trimHeaderValues([$value]);
        }

        if (count($value) === 0) {
            throw new InvalidArgumentException('Header value can not be an empty array.');
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
    private function trimHeaderValues(array $values): array
    {
        return array_map(static function ($value) {
            if (!is_scalar($value) && null !== $value) {
                throw new InvalidArgumentException(sprintf(
                    'Header value must be scalar or null but %s provided.',
                    is_object($value) ? get_class($value) : gettype($value)
                ));
            }

            return trim((string) $value, " \t");
        }, array_values($values));
    }

    private function setNormalizedHeader(int|string $header, mixed $value): void
    {
        $normalized = strtolower($header);
        if (isset($this->headerNames[$normalized])) {
            $header = $this->headerNames[$normalized];
            $this->headers[$header] = array_merge($this->headers[$header], $value);
        } else {
            $this->headerNames[$normalized] = $header;
            $this->headers[$header] = $value;
        }
    }
}
