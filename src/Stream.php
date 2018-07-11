<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use RuntimeException;

class Stream implements StreamInterface
{
    private $stream;
    private $seekable;
    private $readable;
    private $writable;
    private $uri;
    private $size;

    private static $readWriteHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    public function __construct($resource)
    {
        if (! is_resource($resource))
        {
            throw new InvalidArgumentException('Stream must be a resource');
        }

        $this->stream = $resource;

        $meta = $this->getMetadata();
        $this->seekable = $meta['seekable'];
        $this->readable = isset(self::$readWriteHash['read'][$meta['mode']]);
        $this->writable = isset(self::$readWriteHash['write'][$meta['mode']]);
        $this->uri = $meta['uri'] ?? null;
    }

    public function __toString(): string
    {
        try
        {
            if ($this->seekable)
            {
                $this->rewind();
            }

            return $this->getContents();
        }
        catch (RuntimeException $e)
        {
            return '';
        }
    }

    public function close(): void
    {
        if (null !== $this->stream)
        {
            fclose($this->stream);
            $this->detach();
        }
    }

    public function detach()
    {
        $result = $this->stream;

        $this->stream =
        $this->size   =
        $this->uri    = null;

        $this->readable =
        $this->writable =
        $this->seekable = false;

        return $result;
    }

    public function getSize(): ?int
    {
        if (null === $this->size && $this->stream)
        {
            if ($this->uri)
            {
                clearstatcache(true, $this->uri);
            }

            $stats = fstat($this->stream);

            if (isset($stats['size']))
            {
                $this->size = $stats['size'];
            }
        }

        return $this->size;
    }

    public function tell(): int
    {
        if (null === $this->stream || false === ($result = ftell($this->stream)))
        {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof(): bool
    {
        return null === $this->stream || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (false === $this->seekable || -1 === fseek($this->stream, $offset, $whence))
        {
            throw new RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write($string): int
    {
        $this->size = null;

        if (false === $this->writable || false === ($result = fwrite($this->stream, $string)))
        {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read($length): string
    {
        if (false === $this->readable)
        {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        return fread($this->stream, $length);
    }

    public function getContents(): string
    {
        if (false === $this->readable || false === ($result = stream_get_contents($this->stream)))
        {
            throw new RuntimeException('Unable to get stream contents');
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if (null === $this->stream)
        {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        return null === $key ? $meta : (isset($meta[$key]) ? $meta[$key] : null);
    }
}