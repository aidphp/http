<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

class Request implements RequestInterface
{
    use MessageTrait;

    protected $method;
    protected $requestTarget;
    protected $uri;

    public function __construct(string $method, $uri, array $headers = [], StreamInterface $body = null, string $version = '1.1')
    {
        $this->method = $this->filterMethod($method);
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->setHeaders($headers);
        $this->stream = null !== $body ? $body : new Stream(fopen('php://temp', 'wb+'));
        $this->protocol = $this->filterProtocolVersion($version);

        if (! $this->hasHeader('Host'))
        {
            $this->updateHostFromUri();
        }
    }

    public function getRequestTarget(): string
    {
        if (null !== $this->requestTarget)
        {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if (! $target)
        {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if ($query)
        {
            $target .= '?' . $query;
        }

        return $target;
    }

    public function withRequestTarget($requestTarget): self
    {
        if (preg_match('#\s#', $requestTarget))
        {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): self
    {
        $method = $this->filterMethod($method);
        if ($this->method === $method)
        {
            return $this;
        }

        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if ($this->uri === $uri)
        {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;
        if (! $preserveHost || ! $this->hasHeader('Host'))
        {
            $new->updateHostFromUri();
        }
        return $new;
    }

    private function filterMethod(string $method): string
    {
        if (! preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method))
        {
            throw new InvalidArgumentException('Invalid HTTP method "' . $method . '" provided');
        }

        return $method;
    }

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();
        if (! $host)
        {
            return;
        }

        $port = $this->uri->getPort();
        if (null !== $port)
        {
            $host .= ':' . $port;
        }

        if (isset($this->headerNames['host']))
        {
            $header = $this->headerNames['host'];
        }
        else
        {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }

        $this->headers = [$header => [$host]] + $this->headers;
    }
}