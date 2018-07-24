<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\ServerRequestInterface;

interface ServerRequestFactoryInterface
{
    function createFromGlobals(array $server = null, array $get = null, array $post = null, array $cookies = null, array $files = null): ServerRequestInterface;
}