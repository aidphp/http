<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class MessageTraitTest extends TestCase
{
    protected $message;

    public function setUp()
    {
        $this->message = $this->getObjectForTrait('Aidphp\Http\MessageTrait');
    }

    public function testGetDefaultProtocol()
    {
        $this->assertSame('1.1', $this->message->getProtocolVersion());
    }

    public function testNewInstanceWithNewProtocol()
    {
        $message = $this->message->withProtocolVersion('1.0');
        $this->assertNotSame($this->message, $message);
        $this->assertSame('1.0', $message->getProtocolVersion());
    }

    public function testSameInstanceWithSameProtocol()
    {
        $message = $this->message->withProtocolVersion('1.1');
        $this->assertSame($this->message, $message);
        $this->assertSame('1.1', $message->getProtocolVersion());
    }

    /**
     * @dataProvider getInvalidVersions
     */
    public function testWithInvalidProtocol($version)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP version protocol "' . $version . '" provided');
        $this->message->withProtocolVersion($version);
    }

    public function getInvalidVersions()
    {
        return [
            [ '1' ],
            [ '1.2' ],
            [ '1.2.3' ],
            [ '3' ],
        ];
    }

    public function testGetHeader()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['Foo', 'Bar'], $message->getHeader('X-Foo'));
    }

    public function testGetHeaderLine()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame('Foo,Bar', $message->getHeaderLine('X-Foo'));
    }

    public function testGetHeaders()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
    }

    public function testGetHeadersCaseSensitivity()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        $this->assertNotSame($this->message, $message);
        $this->assertSame([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
    }

    public function testHasHeader()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));

        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    public function testReplaceHeader()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['Foo'], $message->getHeader('X-Foo'));

        $message2 = $message->withHeader('x-foo', 'Bar');
        $this->assertNotSame($message, $message2);
        $this->assertSame(['Bar'], $message2->getHeader('X-Foo'));
    }

    public function testWithAddedHeader()
    {
        $message  = $this->message->withAddedHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);

        $message2 = $message->withAddedHeader('X-Foo', 'Bar');
        $this->assertNotSame($message, $message2);
        $this->assertSame('Foo,Bar', $message2->getHeaderLine('X-Foo'));
    }

    public function testHeaderExistsWithoutValues()
    {
        $message = $this->message->withHeader('X-Foo', []);
        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    public function testHeaderWithoutValues()
    {
        $message = $this->message->withHeader('X-Foo', []);
        $this->assertSame([], $message->getHeader('X-Foo'));
        $this->assertSame('', $message->getHeaderLine('X-Foo'));
    }

    public function testCanRemoveHeaders()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));

        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));

        $message3 = $message2->withoutHeader('X-Foo');
        $this->assertSame($message2, $message3);
        $this->assertFalse($message2->hasHeader('X-Foo'));
    }

    public function testNewInstanceWithNewBody()
    {
        $body1 = $this->createMock('Psr\Http\Message\StreamInterface');
        $message1 = $this->message->withBody($body1);
        $body2 = $this->createMock('Psr\Http\Message\StreamInterface');
        $message2 = $message1->withBody($body2);
        $this->assertNotSame($message1, $message2);
        $this->assertSame($body1, $message1->getBody());
        $this->assertSame($body2, $message2->getBody());
    }

    public function testSameInstanceWithSameBody()
    {
        $body = $this->createMock('Psr\Http\Message\StreamInterface');
        $message1 = $this->message->withBody($body);
        $message2 = $message1->withBody($body);
        $this->assertSame($message1, $message2);
        $this->assertSame($body, $message1->getBody());
        $this->assertSame($body, $message2->getBody());
    }
}