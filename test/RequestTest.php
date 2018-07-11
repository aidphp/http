<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

class RequestTest extends TestCase
{
    public function testConstructorWithUriString()
    {
        $req = new Request('GET', '/');
        $this->assertInstanceOf(UriInterface::class, $req->getUri());
        $this->assertEquals('/', (string) $req->getUri());
    }

    public function testConstructor()
    {
        $uri = $this->createMock('Psr\Http\Message\UriInterface');
        $req = new Request('GET', $uri);
        $this->assertInstanceOf(UriInterface::class, $req->getUri());
        $this->assertSame($uri, $req->getUri());
        $this->assertSame('GET', $req->getMethod());
    }

    public function testConstructorWithBody()
    {
        $uri  = $this->createMock('Psr\Http\Message\UriInterface');
        $body = $this->createMock('Psr\Http\Message\StreamInterface');
        $req  = new Request('GET', $uri, [], $body);
        $this->assertInstanceOf(StreamInterface::class, $req->getBody());
        $this->assertSame($body, $req->getBody());
    }

    public function testConstructorWithNullBody()
    {
        $uri = $this->createMock('Psr\Http\Message\UriInterface');
        $req = new Request('GET', $uri, [], null);
        $this->assertInstanceOf(StreamInterface::class, $req->getBody());
    }

    public function testConstructorWithHeaders()
    {
        $req = new Request('GET', $this->createMock('Psr\Http\Message\UriInterface'), [
            'X-Foo' => ['Foo', 'Bar'],
            'x-foo' => ['Foobar', 'Foo'],
        ]);
        $this->assertEquals(['X-Foo' => ['Foo', 'Bar', 'Foobar', 'Foo']], $req->getHeaders());
        $this->assertEquals('Foo,Bar,Foobar,Foo', $req->getHeaderLine('x-foo'));
    }

    public function testWithRequestTarget()
    {
        $uri1 = $this->createMock('Psr\Http\Message\UriInterface');
        $req1 = new Request('GET', $uri1);
        $req2 = $req1->withRequestTarget('*');
        $this->assertEquals('*', $req2->getRequestTarget());
        $this->assertEquals('/', $req1->getRequestTarget());
    }

    public function testInvalidRequestTarget()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request target provided; cannot contain whitespace');

        $req = new Request('GET', $this->createMock('Psr\Http\Message\UriInterface'));
        $req->withRequestTarget('/foo bar');
    }

    public function testGetRequestTarget()
    {
        $req = new Request('GET', 'http://foo.com/baz?bar=bam');
        $this->assertEquals('/baz?bar=bam', $req->getRequestTarget());
    }

    public function testWithMethod()
    {
        $uri1 = $this->createMock('Psr\Http\Message\UriInterface');
        $req1 = new Request('GET', $uri1);

        $req2 = $req1->withMethod('POST');
        $this->assertNotSame($req1, $req2);
        $this->assertSame('GET', $req1->getMethod());
        $this->assertSame('POST', $req2->getMethod());

        $req3 = $req1->withMethod('get');
        $this->assertNotSame($req1, $req3);
    }

    public function testSameInstanceWhithSameMethod()
    {
        $uri1 = $this->createMock('Psr\Http\Message\UriInterface');
        $req1 = new Request('GET', $uri1);
        $req2 = $req1->withMethod('GET');
        $this->assertSame($req1, $req2);
    }

    public function testInvalidMethod()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method "INVALID METHOD" provided');

        new Request('INVALID METHOD', $this->createMock('Psr\Http\Message\UriInterface'));
    }

    public function testWithUri()
    {
        $uri1 = $this->createMock('Psr\Http\Message\UriInterface');
        $req1 = new Request('GET', $uri1);
        $uri2 = $this->createMock('Psr\Http\Message\UriInterface');
        $req2 = $req1->withUri($uri2);
        $this->assertNotSame($req1, $req2);
        $this->assertSame($uri1, $req1->getUri());
        $this->assertSame($uri2, $req2->getUri());
    }

    public function testSameInstanceWhithSameUri()
    {
        $uri1 = $this->createMock('Psr\Http\Message\UriInterface');
        $req1 = new Request('GET', $uri1);
        $req2 = $req1->withUri($uri1);
        $this->assertSame($req1, $req2);
    }

    public function testHostIsAddedFirst()
    {
        $req = new Request('GET', 'http://foo.com/baz?bar=bam', ['Foo' => 'Bar']);
        $this->assertEquals([
            'Host' => ['foo.com'],
            'Foo'  => ['Bar'],
        ], $req->getHeaders());
    }

    public function testPreservingHostWithUri()
    {
        $req1 = new Request('GET', 'http://foo.com/baz?bar=bam', ['Host' => 'a.com']);
        $this->assertEquals(['Host' => ['a.com']], $req1->getHeaders());

        $uri = $this->createMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->never())
            ->method('getHost');
        $req2 = $req1->withUri($uri, true);
        $this->assertEquals('a.com', $req2->getHeaderLine('Host'));
    }

    public function testOverridesHostWithUri()
    {
        $req1 = new Request('GET', 'http://foo.com/baz?bar=bam');
        $this->assertEquals(['Host' => ['foo.com']], $req1->getHeaders());

        $uri = $this->createMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->once())
            ->method('getHost')
            ->willReturn('www.baz.com');
        $uri->expects($this->once())
            ->method('getPort')
            ->willReturn(null);
        $req2 = $req1->withUri($uri);
        $this->assertEquals('www.baz.com', $req2->getHeaderLine('Host'));
    }

    public function testAddsPortToHeader()
    {
        $req1 = new Request('GET', 'http://foo.com:8124/bar');
        $this->assertEquals('foo.com:8124', $req1->getHeaderLine('host'));
    }

    public function testReplacePortHeader()
    {
        $req1 = new Request('GET', 'http://foo.com:8124/bar');

        $uri = $this->createMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->once())
            ->method('getHost')
            ->willReturn('foo.com');
        $uri->expects($this->once())
            ->method('getPort')
            ->willReturn(8125);
        $req2 = $req1->withUri($uri);

        $this->assertEquals('foo.com:8124', $req1->getHeaderLine('host'));
        $this->assertEquals('foo.com:8125', $req2->getHeaderLine('host'));
    }
}