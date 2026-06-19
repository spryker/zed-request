<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ZedRequest\Communication\Plugin\TransferObject;

use LogicException;
use Spryker\Shared\Config\Config;
use Spryker\Shared\ZedRequest\Client\ResponseInterface;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use Spryker\Zed\ZedRequest\Business\Client\Request;
use Spryker\Zed\ZedRequest\Business\Model\Repeater;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;

class TransferServer
{
    /**
     * @var static|null
     */
    protected static $instance;

    /**
     * @var bool
     */
    protected $repeatIsActive = false;

    /**
     * @var \Spryker\Zed\ZedRequest\Business\Client\Request|null
     */
    protected $request;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $httpRequest;

    /**
     * @var \Spryker\Shared\ZedRequest\Client\ResponseInterface
     */
    protected $response;

    /**
     * @var \Spryker\Zed\ZedRequest\Business\Model\Repeater
     */
    protected $repeater;

    private function __construct(Repeater $repeater)
    {
        $this->repeater = $repeater;
    }

    /**
     * @param \Spryker\Zed\ZedRequest\Business\Model\Repeater|null $repeater
     *
     * @return static
     */
    public static function getInstance(?Repeater $repeater = null)
    {
        if (static::$instance) {
            return static::$instance;
        }

        if ($repeater === null) {
            $repeater = new Repeater();
        }

        static::$instance = new static($repeater);

        return static::$instance;
    }

    /**
     * This method intended to be used in development environment only!
     *
     * @return void
     */
    public function activateRepeating()
    {
        $this->repeatIsActive = true;
    }

    /**
     * @return \Spryker\Zed\ZedRequest\Business\Client\Request
     */
    public function getRequest()
    {
        if (!$this->request) {
            if ($this->repeatIsActive) {
                /** @phpstan-var string|null */
                $mvc = $this->getHttpRequest()->query->get('mvc');
                $this->request = new Request(
                    $this->repeater->getRepeatData($mvc)['params'],
                );
            } else {
                /** @phpstan-var string */
                $content = $this->getHttpRequest()->getContent();
                $transferValues = json_decode($this->decodeTransferContent($content), true);
                $this->request = new Request($transferValues);
                $this->repeater->setRepeatData($this->request, $this->httpRequest);
            }
        }

        return $this->request;
    }

    /**
     * @throws \LogicException
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function getHttpRequest()
    {
        if ($this->httpRequest === null) {
            throw new LogicException('No Http Request found in TransferServer. Maybe you try to access data from it before the request object is injected.');
        }

        return $this->httpRequest;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $httpRequest
     *
     * @return $this
     */
    public function setRequest(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;

        return $this;
    }

    /**
     * @param \Spryker\Shared\ZedRequest\Client\ResponseInterface $response
     *
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function send()
    {
        $jsonResponse = new JsonResponse($this->response->toArray(), Response::HTTP_OK, ['X-Zed-Host' => 1]);
        if ($this->repeatIsActive) {
            $jsonResponse->setEncodingOptions(JSON_PRETTY_PRINT);
        }

        if ($this->isBase64TransferEncodingEnabled() && !$this->repeatIsActive) {
            $jsonResponse->setContent(base64_encode((string)$jsonResponse->getContent()));
        }

        return $jsonResponse;
    }

    protected function decodeTransferContent(string $content): string
    {
        return $this->isBase64TransferEncodingEnabled() ? (string)base64_decode($content) : $content;
    }

    protected function isBase64TransferEncodingEnabled(): bool
    {
        return Config::get(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, false);
    }
}
