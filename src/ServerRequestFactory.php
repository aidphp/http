<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;
use InvalidArgumentException;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function createFromGlobals(array $server = null, array $get = null, array $post = null, array $cookies = null, array $files = null): ServerRequestInterface
    {
        $server  = $server ?: $_SERVER;
        $method  = $server['REQUEST_METHOD'] ?? 'GET';
        $uri     = $this->createUriFromGlobals($server);
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $body    = new Stream(fopen('php://input', 'r'));
        $version = isset($server['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $server['SERVER_PROTOCOL']) : '1.1';
        $files   = $this->normalizeFiles($files ?: $_FILES);

        return new ServerRequest($method, $uri, $headers, $body, $version, $server, $get ?: $_GET, $post ?: $_POST, $cookies ?: $_COOKIE, $files);
    }

    private function createUriFromGlobals(array $server): UriInterface
    {
        $scheme = isset($server['HTTPS']) && 'on' === $server['HTTPS'] ? 'https' : 'http';
        $host   = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost';

        if (preg_match('#\:(\d+)$#', $host, $matches))
        {
            $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
            $port = (int) $matches[1];
        }
        else
        {
            $port = isset($server['SERVER_PORT']) ? $server['SERVER_PORT'] : ($scheme === 'https' ? 443 : 80);
        }

        $uri = $server['REQUEST_URI'] ?? '';

        return new Uri($scheme . '://' . $host . ':' . $port . $uri);
    }

    private function normalizeFiles(array $files): array
    {
        $result = [];
        foreach ($files as $key => $value)
        {
            if ($value instanceof UploadedFileInterface)
            {
                $result[$key] = $value;
            }
            elseif (is_array($value) && isset($value['tmp_name']))
            {
                $result[$key] = $this->createUploadedFileFromSpec($value);
            }
            elseif (is_array($value))
            {
                $result[$key] = $this->normalizeFiles($value);
                continue;
            }
            else
            {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $result;
    }

    private function createUploadedFileFromSpec(array $value)
    {
        if (is_array($value['tmp_name']))
        {
            return $this->normalizeNestedFileSpec($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    private function normalizeNestedFileSpec(array $files = []): array
    {
        $result = [];
        foreach (array_keys($files['tmp_name']) as $key)
        {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];

            $result[$key] = $this->createUploadedFileFromSpec($spec);
        }

        return $result;
    }
}