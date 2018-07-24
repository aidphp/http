<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\ServerRequestFactory;
use Aidphp\Http\ServerRequest;
use Aidphp\Http\UploadedFile;
use Aidphp\Http\Uri;
use InvalidArgumentException;

class ServerRequestFactoryTest extends TestCase
{
    public function getUriFromGlobals()
    {
        $server = [
            'SERVER_NAME'       => 'www.exemple.com',
            'SERVER_PROTOCOL'   => 'HTTP/1.0',
            'REQUEST_METHOD'    => 'POST',
            'QUERY_STRING'      => 'id=10&user=foo',
            'HTTP_HOST'         => 'www.exemple.com',
            'HTTPS'             => '1',
            'SERVER_PORT'       => '80',
            'REQUEST_URI'       => '/blog/article.php?id=10&user=foo',
        ];

        return [
            'Normal request' => [
                'http://www.exemple.com/blog/article.php?id=10&user=foo',
                $server,
            ],
            'HTTPS missing' => [
                'http://www.exemple.com/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTPS' => null]),
            ],
            'Secure request' => [
                'https://www.exemple.com/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTPS' => 'on', 'SERVER_PORT' => '443']),
            ],
            'HTTP_HOST missing' => [
                'http://www.exemple.com/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTP_HOST' => null]),
            ],
            'HTTP_HOST with port' => [
                'http://www.exemple.com:8324/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTP_HOST' => 'www.exemple.com:8324']),
            ],
            'No query String' => [
                'http://www.exemple.com/blog/article.php',
                array_merge($server, ['REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => '']),
            ],
            'Different port' => [
                'http://www.exemple.com:8324/blog/article.php?id=10&user=foo',
                array_merge($server, ['SERVER_PORT' => '8324']),
            ],
            'Empty server variable' => [
                'http://localhost',
                [],
            ],
        ];
    }

    /**
     * @dataProvider getUriFromGlobals
     */
    public function testGetUriFromGlobals($expected, $serverParams)
    {
        $req = (new ServerRequestFactory())->createFromGlobals($serverParams);
        $this->assertEquals(new Uri($expected), $req->getUri());
    }

    public function testCreateServerRequestFromGlobals()
    {
        $server = [
            'SERVER_PROTOCOL' => '1.1',
            'HTTP_HOST'       => 'example.com',
            'HTTP_ACCEPT'     => 'application/json',
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_URI'     => '/foo/bar?bar=baz',
        ];

        $cookies =
        $get     =
        $post    = ['bar' => 'baz'];
        $files   = ['files' => [
            'tmp_name' => 'file.txt',
            'size'     => 0,
            'error'    => 0,
            'name'     => 'foo.bar',
            'type'     => 'text/plain',
        ]];

        $expectedFiles = [
            'files' => new UploadedFile('file.txt', 0, 0, 'foo.bar', 'text/plain')
        ];

        $req = (new ServerRequestFactory())->createFromGlobals($server, $get, $post, $cookies, $files);

        $this->assertInstanceOf(ServerRequest::class, $req);
        $this->assertSame($cookies, $req->getCookieParams());
        $this->assertSame($get, $req->getQueryParams());
        $this->assertSame($post, $req->getParsedBody());
        $this->assertEquals($expectedFiles, $req->getUploadedFiles());
        $this->assertEmpty($req->getAttributes());
        $this->assertSame('1.1', $req->getProtocolVersion());
        $this->assertSame('http://example.com/foo/bar?bar=baz', $req->getUri()->__toString());
    }

    public function getFiles()
    {
        return [
            'Single file' => [
                [
                    'file' => [
                        'name' => 'MyFile.txt',
                        'type' => 'text/plain',
                        'tmp_name' => '/tmp/php/php1h4j1o',
                        'error' => '0',
                        'size' => '123',
                    ],
                ],
                [
                    'file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                        ),
                ],
            ],
            'Empty file' => [
                [
                    'image_file' => [
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => '4',
                        'size' => '0',
                    ],
                ],
                [
                    'image_file' => new UploadedFile(
                        '',
                        0,
                        UPLOAD_ERR_NO_FILE,
                        '',
                        ''
                        ),
                ],
            ],
            'Already Converted' => [
                [
                    'file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                        ),
                ],
                [
                    'file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                        ),
                ],
            ],
            'Already Converted array' => [
                [
                    'file' => [
                        new UploadedFile(
                            '/tmp/php/php1h4j1o',
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                            ),
                        new UploadedFile(
                            '',
                            0,
                            UPLOAD_ERR_NO_FILE,
                            '',
                            ''
                            ),
                    ],
                ],
                [
                    'file' => [
                        new UploadedFile(
                            '/tmp/php/php1h4j1o',
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                            ),
                        new UploadedFile(
                            '',
                            0,
                            UPLOAD_ERR_NO_FILE,
                            '',
                            ''
                            ),
                    ],
                ],
            ],
            'Multiple files' => [
                [
                    'text_file' => [
                        'name' => 'MyFile.txt',
                        'type' => 'text/plain',
                        'tmp_name' => '/tmp/php/php1h4j1o',
                        'error' => '0',
                        'size' => '123',
                    ],
                    'image_file' => [
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => '4',
                        'size' => '0',
                    ],
                ],
                [
                    'text_file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                        ),
                    'image_file' => new UploadedFile(
                        '',
                        0,
                        UPLOAD_ERR_NO_FILE,
                        '',
                        ''
                        ),
                ],
            ],
            'Nested files' => [
                [
                    'file' => [
                        'name' => [
                            0 => 'MyFile.txt',
                            1 => 'Image.png',
                        ],
                        'type' => [
                            0 => 'text/plain',
                            1 => 'image/png',
                        ],
                        'tmp_name' => [
                            0 => '/tmp/php/hp9hskjhf',
                            1 => '/tmp/php/php1h4j1o',
                        ],
                        'error' => [
                            0 => '0',
                            1 => '0',
                        ],
                        'size' => [
                            0 => '123',
                            1 => '7349',
                        ],
                    ],
                    'nested' => [
                        'name' => [
                            'other' => 'Flag.txt',
                            'test' => [
                                0 => 'Stuff.txt',
                                1 => '',
                            ],
                        ],
                        'type' => [
                            'other' => 'text/plain',
                            'test' => [
                                0 => 'text/plain',
                                1 => '',
                            ],
                        ],
                        'tmp_name' => [
                            'other' => '/tmp/php/hp9hskjhf',
                            'test' => [
                                0 => '/tmp/php/asifu2gp3',
                                1 => '',
                            ],
                        ],
                        'error' => [
                            'other' => '0',
                            'test' => [
                                0 => '0',
                                1 => '4',
                            ],
                        ],
                        'size' => [
                            'other' => '421',
                            'test' => [
                                0 => '32',
                                1 => '0',
                            ],
                        ],
                    ],
                ],
                [
                    'file' => [
                        0 => new UploadedFile(
                            '/tmp/php/hp9hskjhf',
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                            ),
                        1 => new UploadedFile(
                            '/tmp/php/php1h4j1o',
                            7349,
                            UPLOAD_ERR_OK,
                            'Image.png',
                            'image/png'
                            ),
                    ],
                    'nested' => [
                        'other' => new UploadedFile(
                            '/tmp/php/hp9hskjhf',
                            421,
                            UPLOAD_ERR_OK,
                            'Flag.txt',
                            'text/plain'
                            ),
                        'test' => [
                            0 => new UploadedFile(
                                '/tmp/php/asifu2gp3',
                                32,
                                UPLOAD_ERR_OK,
                                'Stuff.txt',
                                'text/plain'
                                ),
                            1 => new UploadedFile(
                                '',
                                0,
                                UPLOAD_ERR_NO_FILE,
                                '',
                                ''
                                ),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFiles
     */
    public function testGetNormalizeFiles($files, $expected)
    {
        $req = (new ServerRequestFactory())->createFromGlobals(['REQUEST_METHOD' => 'POST'], [], [], [], $files);
        $this->assertEquals($expected, $req->getUploadedFiles());
    }

    public function testCreateServerRequestWithInvalidFiles()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value in files specification');

        (new ServerRequestFactory())->createFromGlobals(['REQUEST_METHOD' => 'POST'], [], [], [], ['test' => 'something']);
    }
}