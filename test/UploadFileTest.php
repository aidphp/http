<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Aidphp\Http\Stream;
use InvalidArgumentException;
use RuntimeException;

class UploadFileTest extends TestCase
{
    protected $cleanup;

    public function setUp()
    {
        $this->cleanup = [];
    }

    public function tearDown()
    {
        foreach ($this->cleanup as $file)
        {
            if (is_scalar($file) && file_exists($file))
            {
                chmod($file, 0777);
                unlink($file);
            }
        }
    }

    protected function getResource()
    {
        return fopen('php://temp', 'wb+');
    }

    public function testConstructorWithFile()
    {
        $uploadedFile = new UploadedFile('php://temp', 0, UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->assertEquals('filename.txt', $uploadedFile->getClientFilename());
        $this->assertEquals('text/plain', $uploadedFile->getClientMediaType());
        $this->assertEquals(0, $uploadedFile->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());
    }

    public function testConstructorWithResource()
    {
        $resource = $this->getResource();
        $uploadedFile = new UploadedFile($resource, 0, UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->assertInstanceOf(StreamInterface::class, $uploadedFile->getStream());
        $this->assertEquals('filename.txt', $uploadedFile->getClientFilename());
        $this->assertEquals('text/plain', $uploadedFile->getClientMediaType());
        $this->assertEquals(0, $uploadedFile->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());

        fclose($resource);
    }

    public function testConstructorWithStream()
    {
        $resource = $this->getResource();
        $stream = new Stream($resource);
        $uploadedFile = new UploadedFile($stream, 0, UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->assertInstanceOf(StreamInterface::class, $uploadedFile->getStream());
        $this->assertSame($stream, $uploadedFile->getStream());
        $this->assertEquals('filename.txt', $uploadedFile->getClientFilename());
        $this->assertEquals('text/plain', $uploadedFile->getClientMediaType());
        $this->assertEquals(0, $uploadedFile->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());

        $stream->close();
    }

    /**
     * @dataProvider getInvalidStatus
     */
    public function testInvalidErrorStatus($status)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid error status for UploadedFile');
        new UploadedFile('php://temp', 0, $status);
    }

    public function getInvalidStatus()
    {
        return [
            'negative' => [-1],
            'too-big'  => [9]
        ];
    }

    /**
     * @dataProvider getInvalidStreamOrFile
     */
    public function testInvalidStreamOrFile($streamOrFile)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream or file provided for UploadedFile');
        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    public function getInvalidStreamOrFile()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    public function testGetStreamFromFile()
    {
        $uploadedFile = new UploadedFile('php://temp', 0, UPLOAD_ERR_OK, 'filename.txt', 'text/plain');
        $stream = $uploadedFile->getStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);

        $stream->close();
    }

    /**
     * @dataProvider getErrorStatus
     */
    public function testGetStreamFromFileWithErrorStatus($status)
    {
        $uploadedFile = new UploadedFile('php://temp', 0, $status);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot retrieve stream due to upload error');

        $uploadedFile->getStream();
    }

    public function getErrorStatus()
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_FILE],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_CANT_WRITE],
            [UPLOAD_ERR_EXTENSION],
        ];
    }

    public function testMoveToWithStream()
    {
        $resource = $this->getResource();
        $stream   = new Stream($resource);
        $stream->write('test upload file');

        $uploadedFile = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');
        $this->assertEquals($stream->getSize(), $uploadedFile->getSize());
        $this->assertEquals('filename.txt', $uploadedFile->getClientFilename());
        $this->assertEquals('text/plain', $uploadedFile->getClientMediaType());

        $this->cleanup[] =
        $targetPath      = tempnam(sys_get_temp_dir(), 'MOVE_TO_OK');

        $uploadedFile->moveTo($targetPath);
        $this->assertFileExists($targetPath);
        $this->assertEquals($stream->__toString(), file_get_contents($targetPath));

        $stream->close();
    }

    public function testMoveToWithFile()
    {
        $this->cleanup[] =
        $sourcePath      = tempnam(sys_get_temp_dir(), 'MOVE_FROM_SOURCE');

        $this->cleanup[] =
        $targetPath      = tempnam(sys_get_temp_dir(), 'MOVE_TO_OK');

        copy(__FILE__, $sourcePath);

        $uploadedFile = new UploadedFile($sourcePath, 100, UPLOAD_ERR_OK, basename($sourcePath), 'text/plain');
        $uploadedFile->moveTo($targetPath);

        $this->assertFileEquals(__FILE__, $targetPath);
    }

    /**
     * @dataProvider getInvalidTargetPath
     */
    public function testMoveToInvalidTargetPath($path)
    {
        $uploadedFile = new UploadedFile('php://temp', 0, UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path provided for move operation; must be a non-empty string');

        $uploadedFile->moveTo($path);
    }

    public function getInvalidTargetPath()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'empty'  => [''],
            'array'  => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    public function testMoveMoreThanOnce()
    {
        $resource = $this->getResource();
        $stream   = new Stream($resource);
        $stream->write('test upload file');

        $uploadedFile = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->cleanup[] =
        $targetPath      = tempnam(sys_get_temp_dir(), 'MOVE_TO_OK');

        $uploadedFile->moveTo($targetPath);
        $this->assertTrue(file_exists($targetPath));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot retrieve stream after it has already been moved');

        $uploadedFile->moveTo($targetPath);
    }

    public function testUnableToMoveTo()
    {
        $this->cleanup[] =
        $sourcePath      = tempnam(sys_get_temp_dir(), 'MOVE_FROM_SOURCE');

        $this->cleanup[] =
        $targetPath      = tempnam(sys_get_temp_dir(), 'MOVE_TO_OK');

        copy(__FILE__, $sourcePath);
        chmod($targetPath, 0444);

        $uploadedFile = new UploadedFile($sourcePath, filesize($sourcePath), UPLOAD_ERR_OK, basename($sourcePath), 'text/plain');

        set_error_handler(function() {return true;}, E_WARNING);

        try
        {
            $uploadedFile->moveTo($targetPath);
        }
        catch (RuntimeException $e)
        {
            $this->assertSame('Uploaded file could not be moved to "' . $targetPath . '"', $e->getMessage());
        }
        finally
        {
            restore_error_handler();
        }
    }
}