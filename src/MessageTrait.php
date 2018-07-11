<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

trait MessageTrait
{
    private $headers = [];
    private $headerNames = [];
    private $protocol = '1.1';
    private $stream;

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version): self
    {
        $version = $this->filterProtocolVersion($version);
        if ($this->protocol === $version)
        {
            return $this;
        }

        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($header): bool
    {
        return isset($this->headerNames[strtolower($header)]);
    }

    public function getHeader($header): array
    {
        $header = strtolower($header);

        return isset($this->headerNames[$header]) ? $this->headers[$this->headerNames[$header]] : [];
    }

    public function getHeaderLine($header): string
    {
        return implode(',', $this->getHeader($header));
    }

    public function withHeader($header, $value): self
    {
        if (! is_array($value))
        {
            $value = [$value];
        }

        $normalized = strtolower($header);
        $new = clone $this;

        if (isset($new->headerNames[$normalized]))
        {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new->headerNames[$normalized] = $header;
        $new->headers[$header] = $value;

        return $new;
    }

    public function withAddedHeader($header, $value): self
    {
        if (! is_array($value))
        {
            $value = [$value];
        }

        $normalized = strtolower($header);
        $new = clone $this;

        if (isset($new->headerNames[$normalized]))
        {
            $header = $this->headerNames[$normalized];
            $new->headers[$header] = array_merge($this->headers[$header], $value);
        }
        else
        {
            $new->headerNames[$normalized] = $header;
            $new->headers[$header] = $value;
        }

        return $new;
    }

    public function withoutHeader($header): self
    {
        $normalized = strtolower($header);

        if (! isset($this->headerNames[$normalized]))
        {
            return $this;
        }

        $header = $this->headerNames[$normalized];

        $new = clone $this;
        unset($new->headers[$header], $new->headerNames[$normalized]);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    public function withBody(StreamInterface $body): self
    {
        if ($body === $this->stream)
        {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;
        return $new;
    }

    private function setHeaders(array $headers): void
    {
        $this->headerNames =
        $this->headers     = [];

        foreach ($headers as $header => $value)
        {
            if (! is_array($value))
            {
                $value = [$value];
            }

            $normalized = strtolower($header);

            if (isset($this->headerNames[$normalized]))
            {
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge($this->headers[$header], $value);
            }
            else
            {
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            }
        }
    }

    private function filterProtocolVersion(string $version): string
    {
        if (! preg_match('#^[1-2]\.[0-1]$#', $version))
        {
            throw new InvalidArgumentException('Invalid HTTP version protocol "' . $version . '" provided');
        }

        return $version;
    }
}