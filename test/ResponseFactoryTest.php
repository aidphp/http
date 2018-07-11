<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class ResponseFactoryTest extends TestCase
{
    public function testCreateResponse()
    {
        $factory = new ResponseFactory();
        $res = $factory->createResponse();
        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertSame(200, $res->getStatusCode());
    }
}