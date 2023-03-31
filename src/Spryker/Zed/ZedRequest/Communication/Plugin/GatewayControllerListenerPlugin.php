<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ZedRequest\Communication\Plugin;

use Generated\Shared\Transfer\MessageTransfer;
use LogicException;
use ReflectionObject;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Shared\Messenger\MessengerConstants;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;
use Spryker\Zed\Messenger\MessengerConfig;
use Spryker\Zed\ZedRequest\Business\Client\Request;
use Spryker\Zed\ZedRequest\Business\Client\Response;
use Spryker\Zed\ZedRequest\Communication\Plugin\TransferObject\TransferServer;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @method \Spryker\Zed\ZedRequest\Communication\ZedRequestCommunicationFactory getFactory()
 * @method \Spryker\Zed\ZedRequest\Business\ZedRequestFacadeInterface getFacade()
 * @method \Spryker\Zed\ZedRequest\ZedRequestConfig getConfig()
 */
class GatewayControllerListenerPlugin extends AbstractPlugin implements GatewayControllerListenerInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     *
     * @return callable|null
     */
    public function onKernelController(ControllerEvent $event)
    {
        $currentController = $event->getController();
        if (!is_array($currentController)) {
            return $currentController;
        }

        $controller = $currentController[0];
        $action = $currentController[1];

        if (!($controller instanceof AbstractGatewayController)) {
            return $currentController;
        }

        $newController = function () use ($controller, $action) {
            MessengerConfig::setMessageTray(MessengerConstants::IN_MEMORY_TRAY);

            $requestTransfer = $this->getRequestTransfer($controller, $action);

            $this->setCustomersLocaleIfPresent($requestTransfer);
            $this->setCustomersCurrencyIfPresent($requestTransfer);

            $result = $controller->$action($requestTransfer->getTransfer(), $requestTransfer);
            $response = $this->getResponse($controller, $result);

            return TransferServer::getInstance()
                ->setResponse($response)
                ->send();
        };

        $event->setController($newController);

        return null;
    }

    /**
     * @deprecated Will be removed after dynamic multi-store is always enabled.
     *
     * @param \Spryker\Zed\ZedRequest\Business\Client\Request $request
     *
     * @return void
     */
    protected function setCustomersLocaleIfPresent(Request $request)
    {
        $localeTransfer = $this->getLocaleMetaTransfer($request);
        if ($localeTransfer && !$this->getFactory()->getIsDynamicStoreModeEnabled()) {
            $this->getFactory()->getStore()->setCurrentLocale($localeTransfer->getLocaleName());
        }
    }

    /**
     * @deprecated Will be removed after dynamic multi-store is always enabled.
     *
     * @param \Spryker\Zed\ZedRequest\Business\Client\Request $request
     *
     * @return \Generated\Shared\Transfer\LocaleTransfer|null
     */
    protected function getLocaleMetaTransfer(Request $request)
    {
        /** @var \Generated\Shared\Transfer\LocaleTransfer|null $localeTransfer */
        $localeTransfer = $request->getMetaTransfer('locale');

        return $localeTransfer;
    }

    /**
     * @deprecated Will be removed after dynamic multi-store is always enabled.
     *
     * @param \Spryker\Zed\ZedRequest\Business\Client\Request $request
     *
     * @return void
     */
    protected function setCustomersCurrencyIfPresent(Request $request)
    {
        $currencyTransfer = $this->getCurrencyMetaTransfer($request);
        if ($currencyTransfer && !$this->getFactory()->getIsDynamicStoreModeEnabled()) {
            $this->getFactory()->getStore()->setCurrencyIsoCode($currencyTransfer->getCode());
        }
    }

    /**
     * @deprecated Will be removed after dynamic multi-store is always enabled.
     *
     * @param \Spryker\Zed\ZedRequest\Business\Client\Request $request
     *
     * @return \Generated\Shared\Transfer\CurrencyTransfer|null
     */
    protected function getCurrencyMetaTransfer(Request $request)
    {
        /** @var \Generated\Shared\Transfer\CurrencyTransfer|null $currencyTransfer */
        $currencyTransfer = $request->getMetaTransfer('currency');

        return $currencyTransfer;
    }

    /**
     * @param \Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController $controller
     * @param string $action
     *
     * @throws \LogicException
     *
     * @return \Spryker\Zed\ZedRequest\Business\Client\Request
     */
    private function getRequestTransfer(AbstractGatewayController $controller, $action)
    {
        $classReflection = new ReflectionObject($controller);
        $methodReflection = $classReflection->getMethod($action);
        $parameters = $methodReflection->getParameters();
        $countParameters = count($parameters);

        if ($countParameters >= 2) {
            throw new LogicException('Only one transfer object can be received in yves-action');
        }

        $parameter = array_shift($parameters);
        if ($parameter) {
            $class = $parameter->getClass();
            if (!$class) {
                throw new LogicException('You need to specify a class for the parameter in the yves-action.');
            }

            $this->validateClassIsTransferObject($class->getName());
        }

        return TransferServer::getInstance()->getRequest();
    }

    /**
     * @param \Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController $controller
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $result
     *
     * @return \Spryker\Zed\ZedRequest\Business\Client\Response
     */
    protected function getResponse(AbstractGatewayController $controller, $result)
    {
        $response = new Response();

        if ($result instanceof TransferInterface) {
            $response->setTransfer($result);
        }

        $this->setGatewayControllerMessages($controller, $response);
        $this->setMessengerMessages($response);

        $response->setSuccess($controller->isSuccess());

        return $response;
    }

    /**
     * @param \Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController $controller
     * @param \Spryker\Zed\ZedRequest\Business\Client\Response $response
     *
     * @return void
     */
    protected function setGatewayControllerMessages(AbstractGatewayController $controller, Response $response)
    {
        $response->addSuccessMessages($controller->getSuccessMessages());
        $response->addInfoMessages($controller->getInfoMessages());
        $response->addErrorMessages($controller->getErrorMessages());
    }

    /**
     * @param \Spryker\Zed\ZedRequest\Business\Client\Response $response
     *
     * @return void
     */
    protected function setMessengerMessages(Response $response)
    {
        $messengerFacade = $this->getFactory()->getMessengerFacade();

        /** @var \Generated\Shared\Transfer\FlashMessagesTransfer|null $messagesTransfer */
        $messagesTransfer = $messengerFacade->getStoredMessages();
        if ($messagesTransfer === null) {
            return;
        }

        $response->addErrorMessages(
            $this->createResponseMessages(
                $messagesTransfer->getErrorMessages(),
            ),
        );
        $response->addInfoMessages(
            $this->createResponseMessages(
                $messagesTransfer->getInfoMessages(),
            ),
        );
        $response->addSuccessMessages(
            $this->createResponseMessages(
                $messagesTransfer->getSuccessMessages(),
            ),
        );
    }

    /**
     * @param array $messages
     * @param array<\Generated\Shared\Transfer\MessageTransfer> $storedMessages
     *
     * @return array<\Generated\Shared\Transfer\MessageTransfer>
     */
    protected function createResponseMessages(array $messages, array $storedMessages = [])
    {
        foreach ($messages as $message) {
            $responseMessage = new MessageTransfer();
            $responseMessage->setValue($message);
            $storedMessages[] = $responseMessage;
        }

        return $storedMessages;
    }

    /**
     * @param string $className
     *
     * @throws \LogicException
     *
     * @return bool
     */
    protected function validateClassIsTransferObject($className)
    {
        if (substr($className, 0, 16) === 'Generated\Shared') {
            return true;
        }

        if ($className === 'Spryker\Shared\Kernel\Transfer\TransferInterface') {
            return true;
        }

        throw new LogicException('Only transfer classes are allowed in yves action as parameter');
    }
}
