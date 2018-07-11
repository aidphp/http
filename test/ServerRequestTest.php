<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\ServerRequest;
use Psr\Http\Message\UriInterface;

class ServerRequestTest extends TestCase
{
    public function testConstructor()
    {
        $req = new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface'));
        $this->assertInstanceOf(UriInterface::class, $req->getUri());
        $this->assertSame('GET', $req->getMethod());
    }

    public function testServerParams()
    {
        $params = ['foo' => 'bar'];
        $req = new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface'), [], null, '1.1', $params);
        $this->assertSame($params, $req->getServerParams());
    }

    public function testCookieParams()
    {
        $req = new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface'));
        $params = ['foo' => 'bar'];
        $req2 = $req->withCookieParams($params);
        $this->assertNotSame($req2, $req);
        $this->assertEmpty($req->getCookieParams());
        $this->assertSame($params, $req2->getCookieParams());
    }

    public function testUploadedFiles()
    {
        $req = new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface'));
        $files = ['file' => $this->createMock('Psr\Http\Message\UploadedFileInterface')];
        $req2 = $req->withUploadedFiles($files);
        $this->assertNotSame($req2, $req);
        $this->assertSame([], $req->getUploadedFiles());
        $this->assertSame($files, $req2->getUploadedFiles());
    }

    public function testQueryParams()
    {
        $req = new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface'));
        $params = ['foo' => 'bar'];
        $req2 = $req->withQueryParams($params);
        $this->assertNotSame($req2, $req);
        $this->assertEmpty($req->getQueryParams());
        $this->assertSame($params, $req2->getQueryParams());
    }

    public function testParsedBody()
    {
        $req = new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface'));
        $params = ['foo' => 'bar'];
        $req2 = $req->withParsedBody($params);
        $this->assertNotSame($req2, $req);
        $this->assertEmpty($req->getParsedBody());
        $this->assertSame($params, $req2->getParsedBody());
    }

    public function testAttributes()
    {
        $req = new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface'));
        $this->assertEmpty($req->getAttributes());
        $this->assertEmpty($req->getAttribute('name'));
        $this->assertEquals('default', $req->getAttribute('name', 'default'));

        $req2 = $req->withAttribute('name', 'value');
        $this->assertNotSame($req2, $req);
        $this->assertEquals('value', $req2->getAttribute('name'));
        $this->assertEquals(['name' => 'value'], $req2->getAttributes());

        $req3 = $req2->withAttribute('other', 'otherValue');
        $this->assertNotSame($req3, $req2);
        $this->assertEquals(['name' => 'value', 'other' => 'otherValue'], $req3->getAttributes());

        $req4 = $req3->withoutAttribute('other');
        $this->assertNotSame($req4, $req3);
        $this->assertEquals(['name' => 'value'], $req4->getAttributes());
    }

    public function testNullAttribute()
    {
        $req = (new ServerRequest('GET', $this->createMock('Psr\Http\Message\UriInterface')))->withAttribute('name', null);
        $this->assertSame(['name' => null], $req->getAttributes());
        $this->assertNull($req->getAttribute('name', 'default'));

        $req2 = $req->withoutAttribute('name');
        $this->assertSame([], $req2->getAttributes());
        $this->assertSame('default', $req2->getAttribute('name', 'default'));
    }
}