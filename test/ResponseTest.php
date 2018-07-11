<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\Response;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

class ResponseTest extends TestCase
{
    public function testConstructor()
    {
        $res = new Response();
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('1.1', $res->getProtocolVersion());
        $this->assertSame('OK', $res->getReasonPhrase());
        $this->assertSame([], $res->getHeaders());
        $this->assertInstanceOf(StreamInterface::class, $res->getBody());
        $this->assertSame('', (string) $res->getBody());
    }

    public function testConstructorWithStatusCode()
    {
        $res = new Response(404);
        $this->assertSame(404, $res->getStatusCode());
        $this->assertSame('Not Found', $res->getReasonPhrase());
    }

    public function testConstructorWithHeaders()
    {
        $res = new Response(200, [
            'X-Foo' => ['Foo', 'Bar'],
            'x-foo' => ['Foobar', 'Foo'],
        ], $this->createMock('Psr\Http\Message\StreamInterface'));
        $this->assertEquals(['X-Foo' => ['Foo', 'Bar', 'Foobar', 'Foo']], $res->getHeaders());
        $this->assertEquals('Foo,Bar,Foobar,Foo', $res->getHeaderLine('x-foo'));
    }

    public function testConstructorWithBody()
    {
        $body = $this->createMock('Psr\Http\Message\StreamInterface');
        $res = new Response(200, [], $body);
        $this->assertInstanceOf(StreamInterface::class, $res->getBody());
    }

    public function testConstructorWithNullBody()
    {
        $res = new Response(200, [], null);
        $this->assertInstanceOf(StreamInterface::class, $res->getBody());
    }

    public function testConstructorWithReason()
    {
        $res = new Response(200, [], null, '1.1', 'bar');
        $this->assertSame('bar', $res->getReasonPhrase());
    }

    public function testWithStatusCode()
    {
        $res1 = new Response();
        $res2 = $res1->withStatus(201);
        $this->assertNotSame($res1, $res2);
        $this->assertSame(201, $res2->getStatusCode());
        $this->assertSame('Created', $res2->getReasonPhrase());
    }

    public function testWithStatusCodeAndReason()
    {
        $res1 = new Response();
        $res2 = $res1->withStatus(201, 'Foo');
        $this->assertNotSame($res1, $res2);
        $this->assertSame(201, $res2->getStatusCode());
        $this->assertSame('Foo', $res2->getReasonPhrase());
    }

    public function testWithSameStatusCode()
    {
        $res1 = new Response();
        $res2 = $res1->withStatus(200);
        $this->assertSame($res1, $res2);
    }

    public function testWithSameStatusCodeAndReason()
    {
        $res1 = new Response();
        $res2 = $res1->withStatus(200, 'OK');
        $this->assertSame($res1, $res2);
    }

    /**
     * @dataProvider getInvalidCode
     */
    public function testInvalidStatusCode($code)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP status code "' . $code . '" provided');

        new Response($code);
    }

    public function getInvalidCode()
    {
        return [
            [600],
            [99]
        ];
    }
}