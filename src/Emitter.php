<?php

declare(strict_types=1);

namespace Aidphp\Http;

use Interop\Http\EmitterInterface;
use Psr\Http\Message\ResponseInterface;

class Emitter implements EmitterInterface
{
    public function emit(ResponseInterface $res): void
    {
        if (! headers_sent())
        {
            foreach ($res->getHeaders() as $name => $values)
            {
                foreach ($values as $value)
                {
                    header($name . ': ' . $value, false);
                }
            }

            $code = $res->getStatusCode();
            $text = $res->getReasonPhrase();

            header('HTTP/' . $res->getProtocolVersion() . ' ' . $code . ($text ? ' ' . $text : ''), true, $code);
        }

        echo $res->getBody()->__toString();
    }
}