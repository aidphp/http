<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

class Uri implements UriInterface
{
    private $scheme   = '';
    private $userInfo = '';
    private $host     = '';
    private $port;
    private $path     = '';
    private $query    = '';
    private $fragment = '';

    private static $schemes = [
        'http'  => 80,
        'https' => 443,
    ];

    public function __construct(string $uri = '')
    {
        if ('' !== $uri)
        {
            $this->parseUri($uri);
        }
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->host)
        {
            $authority .= $this->host;

            if ($this->userInfo)
            {
                $authority = $this->userInfo . '@' . $authority;
            }

            if ($this->port)
            {
                $authority .= ':' . $this->port;
            }
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): self
    {
        $scheme = $this->filterScheme($scheme);
        if ($this->scheme === $scheme)
        {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);
        return $new;
    }

    public function withUserInfo($user, $password = null): self
    {
        $info = $user;
        if ($password)
        {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info)
        {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;
        return $new;
    }

    public function withHost($host): self
    {
        $host = $this->filterHost($host);
        if ($this->host === $host)
        {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    public function withPort($port): self
    {
        $port = $this->filterPort($port);
        if ($this->port === $port)
        {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    public function withPath($path): self
    {
        $path = $this->filterPath($path);
        if ($this->path === $path)
        {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    public function withQuery($query): self
    {
        $query = $this->filterQueryOrFragment($query);
        if ($this->query === $query)
        {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    public function withFragment($fragment): self
    {
        $fragment = $this->filterQueryOrFragment($fragment);
        if ($this->fragment === $fragment)
        {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    public function __toString()
    {
        $uri = '';

        if ($this->scheme)
        {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();

        if ($authority)
        {
            $uri .= '//' . $authority;
        }

        $uri .= $this->path;

        if ($this->query)
        {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment)
        {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    private function parseUri(string $uri): void
    {
        $parts = parse_url($uri);

        if (false === $parts)
        {
            throw new InvalidArgumentException('Unable to parse URI');
        }

        $this->scheme   = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo = isset($parts['user']) ? $parts['user'] : '';
        $this->host     = isset($parts['host']) ? $this->filterHost($parts['host']) : '';
        $this->port     = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
        $this->path     = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query    = isset($parts['query']) ? $this->filterQueryOrFragment($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->filterQueryOrFragment($parts['fragment']) : '';

        if (isset($parts['pass']))
        {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    private function filterScheme(string $scheme): string
    {
        $scheme = strtolower($scheme);

        if ('http' !== $scheme && 'https' !== $scheme)
        {
            throw new InvalidArgumentException('Invalid HTTP scheme "' . $scheme . '" provided');
        }

        return $scheme;
    }

    private function filterHost(string $host): string
    {
        return strtolower($host);
    }

    private function filterPort(?int $port): ?int
    {
        if (null === $port)
        {
            return null;
        }

        if (1 > $port || 0xffff < $port)
        {
            throw new InvalidArgumentException('Invalid HTTP port "' . $port . '". Must be between 1 and 65535');
        }

        return (! isset(self::$schemes[$this->scheme]) || $port !== self::$schemes[$this->scheme]) ? $port : null;
    }

    private function filterPath(string $path): string
    {
        return preg_replace_callback('#(?:[^a-zA-Z0-9_\-\.~\pL\)\(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))#u', [$this, 'urlEncode'], $path);
    }

    private function filterQueryOrFragment(string $str): string
    {
        return preg_replace_callback('#(?:[^a-zA-Z0-9_\-\.~\pL!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))#u', [$this, 'urlEncode'], $str);
    }

    private function urlEncode(array $matches): string
    {
        return rawurlencode($matches[0]);
    }
}