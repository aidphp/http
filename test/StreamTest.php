<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\Stream;
use InvalidArgumentException;
use RuntimeException;

class StreamTest extends TestCase
{
    protected function getResource()
    {
        return fopen('php://temp', 'wb+');
    }

    public function testConstructorInitializesProperties()
    {
        $resource = $this->getResource();
        fwrite($resource, 'data');
        $stream = new Stream($resource);

        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $this->assertInternalType('array', $stream->getMetadata());
        $this->assertEquals(4, $stream->getSize());
        $this->assertFalse($stream->eof());

        $stream->close();
    }

    public function testConstructorWithInvalidResource()
    {
        $this->expectException(InvalidArgumentException::class);
        $stream = new Stream(['RESOURCE']);
    }

    public function testToString()
    {
        $resource = $this->getResource();
        fwrite($resource, 'data');
        $stream = new Stream($resource);

        $this->assertEquals('data', (string) $stream);
        $this->assertEquals('data', (string) $stream);

        $stream->close();
    }

    public function testToStringWithoutResource()
    {
        $resource = $this->getResource();
        fwrite($resource, 'data');
        $stream = new Stream($resource);
        $stream->close();

        $this->assertEquals('', (string) $stream);
        $this->assertEquals('', (string) $stream);
    }

    public function testClose()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);
        $stream->close();

        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertNull($stream->getSize());
        $this->assertEmpty($stream->getMetadata());
    }

    public function testGetSize()
    {
        $size = filesize(__FILE__);
        $resource = fopen(__FILE__, 'r');
        $stream = new Stream($resource);

        $this->assertEquals($size, $stream->getSize());
        // Load from cache
        $this->assertEquals($size, $stream->getSize());

        $stream->close();
    }

    public function testEnsuresSizeIsConsistent()
    {
        $resource = $this->getResource();
        $this->assertEquals(3, fwrite($resource, 'foo'));
        $stream = new Stream($resource);

        $this->assertEquals(3, $stream->getSize());
        $this->assertEquals(4, $stream->write('test'));
        $this->assertEquals(7, $stream->getSize());
        $this->assertEquals(7, $stream->getSize());

        $stream->close();
    }

    public function testTell()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);

        $this->assertEquals(0, $stream->tell());
        $stream->write('foo');
        $this->assertEquals(3, $stream->tell());
        $stream->seek(1);
        $this->assertEquals(1, $stream->tell());
        $this->assertSame(ftell($resource), $stream->tell());

        $stream->close();
    }

    public function testTellWithoutResource()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to determine stream position');

        $stream->tell();
    }

    public function testEof()
    {
        $resource = $this->getResource();
        fwrite($resource, 'data');
        $stream = new Stream($resource);

        $this->assertFalse($stream->eof());
        $stream->read(4);
        $this->assertTrue($stream->eof());

        $stream->close();
    }

    public function testSeekWithoutResource()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to seek to stream position 0 with whence ' . SEEK_SET);

        $stream->seek(0);
    }

    public function testWriteWithoutResource()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to write to stream');

        $stream->write('NOTHING');
    }

    public function testReadWithoutResource()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot read from non-readable stream');

        $stream->read(10);
    }

    public function testGetsContents()
    {
        $resource = $this->getResource();
        fwrite($resource, 'data');
        $stream = new Stream($resource);

        $this->assertEquals('', $stream->getContents());
        $stream->seek(0);
        $this->assertEquals('data', $stream->getContents());
        $this->assertEquals('', $stream->getContents());

        $stream->close();
    }

    public function testGetsContentsWithoutResource()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);
        $stream->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to get stream contents');

        $stream->getContents();
    }
}