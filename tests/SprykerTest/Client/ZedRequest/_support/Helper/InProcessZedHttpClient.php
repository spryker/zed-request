<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Client\ZedRequest\Helper;

use Generated\Shared\Transfer\MessageTransfer;
use LogicException;
use ReflectionProperty;
use Spryker\Client\ZedRequest\Client\Request as ClientRequest;
use Spryker\Client\ZedRequest\Client\Response as ClientResponse;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Shared\Messenger\MessengerConstants;
use Spryker\Shared\ZedRequest\Client\HttpClientInterface;
use Spryker\Shared\ZedRequest\Client\ResponseInterface;
use Spryker\Zed\Kernel\ClassResolver\Controller\ControllerResolver;
use Spryker\Zed\Kernel\Communication\BundleControllerAction;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;
use Spryker\Zed\Kernel\Locator;
use Spryker\Zed\Messenger\MessengerConfig;
use Spryker\Zed\ZedRequest\Business\Client\Request as ZedRequest;
use Spryker\Zed\ZedRequest\Business\Client\Response as ZedResponse;

/**
 * In-process replacement for the Guzzle transport behind AbstractZedClient: dispatches a
 * Client→Zed RPC call straight to the module's GatewayController inside the same PHP process,
 * replicating the request/response handling of GatewayControllerListenerPlugin. Request and
 * response transfers still pass through a JSON round-trip, so the wire serialization semantics
 * are preserved — only the HTTP hop (and with it the second application boot) is skipped.
 */
class InProcessZedHttpClient implements HttpClientInterface
{
    protected static int $requestCounter = 0;

    /**
     * @param int $timeoutInSeconds
     *
     * @return void
     */
    public static function setDefaultTimeout($timeoutInSeconds) // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter, SprykerStrict.TypeHints.ParameterTypeHint.MissingNativeTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
    {
        // Timeouts have no meaning for an in-process call.
    }

    public static function getRequestCounter(): int
    {
        return static::$requestCounter;
    }

    /**
     * @param string $pathInfo
     * @param array<string, \Spryker\Shared\Kernel\Transfer\TransferInterface> $metaTransfers
     * @param array|int|null $requestOptions Ignored — there is no HTTP request to configure.
     */
    public function request( // phpcs:ignore SprykerStrict.TypeHints.ParameterTypeHint.MissingNativeTypeHint
        $pathInfo,
        ?TransferInterface $transferObject = null,
        array $metaTransfers = [],
        $requestOptions = null, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    ): ResponseInterface {
        static::$requestCounter++;

        $zedRequest = $this->buildZedRequest($transferObject, $metaTransfers);
        [$controller, $action] = $this->resolveGatewayControllerAction($pathInfo);

        MessengerConfig::setMessageTray(MessengerConstants::IN_MEMORY_TRAY);

        $this->resetConfigurationValueCache();

        try {
            $result = $controller->$action($zedRequest->getTransfer(), $zedRequest);
        } finally {
            $this->resetConfigurationValueCache();
        }

        return $this->buildClientResponse($controller, $result);
    }

    /**
     * @param array<string, \Spryker\Shared\Kernel\Transfer\TransferInterface> $metaTransfers
     *
     * @throws \LogicException
     */
    protected function buildZedRequest(?TransferInterface $transferObject, array $metaTransfers): ZedRequest
    {
        $clientRequest = new ClientRequest();
        $clientRequest->setSessionId((string)session_id());
        $clientRequest->setTime((string)time());
        $clientRequest->setHost('in-process');

        foreach ($metaTransfers as $name => $metaTransfer) {
            if (!is_string($name) || is_numeric($name) || !$metaTransfer instanceof TransferInterface) {
                throw new LogicException('Adding MetaTransfer failed. Either name missing/invalid or no object of TransferInterface provided.');
            }
            $clientRequest->addMetaTransfer($name, $metaTransfer);
        }

        if ($transferObject) {
            $clientRequest->setTransfer($transferObject);
        }

        return new ZedRequest($this->jsonRoundTrip($clientRequest->toArray()));
    }

    /**
     * @throws \LogicException
     *
     * @return array{0: \Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController, 1: string}
     */
    protected function resolveGatewayControllerAction(string $pathInfo): array
    {
        $pathSegments = array_values(array_filter(explode('/', (string)parse_url($pathInfo, PHP_URL_PATH))));

        if (count($pathSegments) !== 3) {
            throw new LogicException(sprintf('Expected a "/<module>/<controller>/<action>" gateway path, got "%s".', $pathInfo));
        }

        $bundleControllerAction = new BundleControllerAction($pathSegments[0], $pathSegments[1], $pathSegments[2]);
        $controller = (new ControllerResolver())->resolve($bundleControllerAction);

        if (!$controller instanceof AbstractGatewayController) {
            throw new LogicException(sprintf('"%s" is not a gateway controller — the in-process transport only serves gateway calls.', get_class($controller)));
        }

        return [$controller, $bundleControllerAction->getAction() . 'Action'];
    }

    /**
     * The Configuration module's static value cache is shared by the Client and the Zed reader,
     * which read different stores — so in one process the first leg's answer would be served to the
     * second. Clearing it on both sides of the hop restores the per-process resolution.
     */
    protected function resetConfigurationValueCache(): void
    {
        if (!class_exists(AbstractConfigurationValueResolver::class)) {
            return;
        }

        (new ReflectionProperty(AbstractConfigurationValueResolver::class, 'resolvedValueCache'))
            ->setValue(null, []);
    }

    protected function buildClientResponse(AbstractGatewayController $controller, mixed $result): ClientResponse
    {
        $zedResponse = new ZedResponse();

        if ($result instanceof TransferInterface) {
            $zedResponse->setTransfer($result);
        }

        $zedResponse->addSuccessMessages($controller->getSuccessMessages());
        $zedResponse->addInfoMessages($controller->getInfoMessages());
        $zedResponse->addErrorMessages($controller->getErrorMessages());
        $this->addStoredMessengerMessages($zedResponse);
        $zedResponse->setSuccess($controller->isSuccess());

        $clientResponse = new ClientResponse();
        $clientResponse->fromArray($this->jsonRoundTrip($zedResponse->toArray()));

        return $clientResponse;
    }

    protected function addStoredMessengerMessages(ZedResponse $zedResponse): void
    {
        $messagesTransfer = Locator::getInstance()->messenger()->facade()->getStoredMessages();

        if ($messagesTransfer === null) {
            return;
        }

        $zedResponse->addErrorMessages($this->toMessageTransfers($messagesTransfer->getErrorMessages()));
        $zedResponse->addInfoMessages($this->toMessageTransfers($messagesTransfer->getInfoMessages()));
        $zedResponse->addSuccessMessages($this->toMessageTransfers($messagesTransfer->getSuccessMessages()));
    }

    /**
     * @param array<string> $messages
     *
     * @return array<\Generated\Shared\Transfer\MessageTransfer>
     */
    protected function toMessageTransfers(array $messages): array
    {
        $messageTransfers = [];
        foreach ($messages as $message) {
            $messageTransfers[] = (new MessageTransfer())->setValue($message);
        }

        return $messageTransfers;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function jsonRoundTrip(array $data): array
    {
        return json_decode((string)json_encode($data), true);
    }
}
