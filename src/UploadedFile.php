<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{
    private $clientFilename;
    private $clientMediaType;
    private $error;
    private $file;
    private $moved = false;
    private $size;
    private $stream;

    public function __construct($streamOrFile, int $size, int $errorStatus, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        if (0 > $errorStatus || 8 < $errorStatus)
        {
            throw new InvalidArgumentException('Invalid error status for UploadedFile');
        }

        $this->error = $errorStatus;
        $this->size  = $size;
        $this->clientFilename  = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($this->isOk())
        {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    public function getStream(): StreamInterface
    {
        $this->validateActive();

        if ($this->stream instanceof StreamInterface)
        {
            return $this->stream;
        }

        return new Stream(fopen($this->file, 'r'));
    }

    public function moveTo($targetPath): void
    {
        $this->validateActive();

        if (! is_string($targetPath) || empty($targetPath))
        {
            throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }

        if (null !== $this->file)
        {
            $this->moved = ('cli' == php_sapi_name()) ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath);
        }
        else
        {
            $this->writeFile($targetPath);
            $this->moved = true;
        }

        if (false === $this->moved)
        {
            throw new RuntimeException('Uploaded file could not be moved to "' . $targetPath . '"');
        }
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    private function setStreamOrFile($streamOrFile): void
    {
        if (is_string($streamOrFile))
        {
            $this->file = $streamOrFile;
        }
        elseif (is_resource($streamOrFile))
        {
            $this->stream = new Stream($streamOrFile);
        }
        elseif ($streamOrFile instanceof StreamInterface)
        {
            $this->stream = $streamOrFile;
        }
        else
        {
            throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    private function isOk(): bool
    {
        return UPLOAD_ERR_OK === $this->error;
    }

    private function validateActive(): void
    {
        if (false === $this->isOk())
        {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved)
        {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    private function writeFile(string $path): void
    {
        $stream = $this->getStream();

        if ($stream->isSeekable())
        {
            $stream->rewind();
        }

        $handle = fopen($path, 'wb');

        while (! $stream->eof())
        {
            fwrite($handle, $stream->read(1048576));
        }

        fclose($handle);
    }
}