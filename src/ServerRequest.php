<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    private $serverParams;
    private $cookieParams;
    private $queryParams;
    private $uploadedFiles;
    private $parsedBody;
    private $attributes = [];

    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        StreamInterface $body = null,
        string $version = '1.1',
        array $serverParams = [],
        array $queryParams  = [],
        $parsedBody = null,
        array $cookieParams  = [],
        array $uploadedFiles = []
    )
    {
        $this->serverParams  = $serverParams;
        $this->queryParams   = $queryParams;
        $this->parsedBody    = $parsedBody;
        $this->cookieParams  = $cookieParams;
        $this->uploadedFiles = $uploadedFiles;
        parent::__construct($method, $uri, $headers, $body, $version);
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): self
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($attribute, $default = null)
    {
        return array_key_exists($attribute, $this->attributes) ? $this->attributes[$attribute] : $default;
    }

    public function withAttribute($attribute, $value): self
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    public function withoutAttribute($attribute): self
    {
        $new = clone $this;
        unset($new->attributes[$attribute]);
        return $new;
    }
}