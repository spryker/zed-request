<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ZedRequest\Client\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class InvalidZedResponseException extends RuntimeException
{
    protected const int RAW_BODY_EXCERPT_LIMIT = 500;

    protected const string RAW_BODY_EMPTY_MESSAGE = '<empty, 0 bytes> - the gateway returned no body at all, which '
        . 'usually means its PHP process terminated before the error handler could render or log anything.';

    public function __construct(
        string $reason,
        ResponseInterface $response,
        string $url,
        ?string $requestId = null
    ) {
        $lines = [
            '[status code] ' . $response->getStatusCode(),
            '[reason phrase] ' . $reason,
            '[url] ' . $url,
        ];

        if ($requestId !== null) {
            $lines[] = '[requestId] ' . $requestId
                . ' - the backend gateway logs this same value in extra.request.requestId';
        }

        $lines[] = '[raw body] ' . $this->describeRawBody($response);

        parent::__construct('Invalid response from Zed' . PHP_EOL . implode(PHP_EOL, $lines));
    }

    /**
     * The excerpt stays HTML-escaped because this message is rendered into an HTML error page by
     * {@link \Spryker\Shared\ErrorHandler\ErrorRenderer\WebHtmlErrorRenderer}.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return string
     */
    protected function describeRawBody(ResponseInterface $response): string
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $content = (string)$body;
        $size = strlen($content);

        if ($size === 0) {
            return static::RAW_BODY_EMPTY_MESSAGE;
        }

        $excerpt = htmlentities(substr($content, 0, static::RAW_BODY_EXCERPT_LIMIT));

        if ($size <= static::RAW_BODY_EXCERPT_LIMIT) {
            return $excerpt;
        }

        return sprintf('%s... (truncated, %d bytes total)', $excerpt, $size);
    }
}
