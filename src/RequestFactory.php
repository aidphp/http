<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Interop\Http\Factory\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class RequestFactory implements RequestFactoryInterface
{
    public function createRequest($method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
}