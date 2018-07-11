<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Aidphp\Http\RequestFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestFactoryTest extends TestCase
{
    public function testCreateRequest()
    {
        $factory = new RequestFactory();
        $req = $factory->createRequest('GET', '/path');
        $this->assertInstanceOf(RequestInterface::class, $req);
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/path', $req->getUri()->__toString());
    }

    public function testCreateRequestWithUriInterface()
    {
        $factory = new RequestFactory();
        $uri = $this->createMock(UriInterface::class);
        $req = $factory->createRequest('GET', $uri);
        $this->assertInstanceOf(RequestInterface::class, $req);
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame($uri, $req->getUri());
    }
}