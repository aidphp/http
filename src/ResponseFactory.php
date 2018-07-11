<?php

namespace Aidphp\Http;

use Interop\Http\Factory\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse($code = 200): ResponseInterface
    {
        return new Response($code);
    }
}