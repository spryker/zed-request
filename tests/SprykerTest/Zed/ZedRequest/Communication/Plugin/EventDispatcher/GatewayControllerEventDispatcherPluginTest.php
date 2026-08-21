<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ZedRequest\Communication\Plugin\EventDispatcher;

use Codeception\Test\Unit;
use LogicException;
use ReflectionClass;
use ReflectionObject;
use Spryker\Service\Container\Container;
use Spryker\Shared\EventDispatcher\EventDispatcher;
use Spryker\Shared\Kernel\AbstractLocatorLocator;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use Spryker\Zed\ZedRequest\Business\Model\Repeater;
use Spryker\Zed\ZedRequest\Communication\Plugin\EventDispatcher\GatewayControllerEventDispatcherPlugin;
use Spryker\Zed\ZedRequest\Communication\Plugin\TransferObject\TransferServer as CoreTransferServer;
use SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture\GatewayController;
use SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture\NotGatewayController;
use SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture\Request;
use SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture\TransferServer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ZedRequest
 * @group Communication
 * @group Plugin
 * @group EventDispatcher
 * @group GatewayControllerEventDispatcherPluginTest
 * Add your own group annotations below this line
 */
class GatewayControllerEventDispatcherPluginTest extends Unit
{
    /**
     * @var \SprykerTest\Zed\ZedRequest\ZedRequestCommunicationTester
     */
    protected $tester;

    public function setUp(): void
    {
        parent::setUp();
        $this->unsetLocator();
    }

    /**
     * We need to unset the Locator instance because we are using the Locator for Yves and for Zed
     * When it first get instantiated by Yves it wont have the proper Proxies configured
     *
     * @return void
     */
    protected function unsetLocator(): void
    {
        $reflectionClass = new ReflectionClass(AbstractLocatorLocator::class);
        $reflectedProperty = $reflectionClass->getProperty('instance');
        $reflectedProperty->setValue(null);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->resetTransferServer();
    }

    public function testWhenControllerIsGatewayControllerPluginMustReturnInstanceOfClosure(): void
    {
        $controller = new GatewayController();
        $action = 'goodAction';

        $event = $this->tester->createControllerEvent([$controller, $action]);
        $event = $this->dispatchEvent($event, KernelEvents::CONTROLLER);

        $controllerCallable = $event->getController();
        $this->assertTrue(is_callable($controllerCallable));
        $this->assertInstanceOf('\Closure', $controllerCallable);
    }

    public function testWhenControllerIsNotAGatewayControllerPluginMustReturnPassedCallable(): void
    {
        $action = 'badAction';
        $controller = new NotGatewayController();

        $event = $this->tester->createControllerEvent([$controller, $action]);
        $event = $this->dispatchEvent($event, KernelEvents::CONTROLLER);

        $controllerCallable = $event->getController();
        $this->assertTrue(is_callable($controllerCallable));
        $this->assertNotInstanceOf('\Closure', $controllerCallable);
    }

    public function testIfTwoTransferParameterGivenPluginMustThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only one transfer object can be received in yves-action');

        $action = 'twoTransferParametersAction';
        $controllerCallable = $this->executeMockedListenerTest($action);
        call_user_func($controllerCallable);
    }

    public function testIfTooManyTransferParameterGivenPluginMustThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only one transfer object can be received in yves-action');

        $action = 'tooManyParametersAction';
        $controllerCallable = $this->executeMockedListenerTest($action);
        call_user_func($controllerCallable);
    }

    public function testIfPassedParameterIsNotAClassPluginMustThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You need to specify a class for the parameter in the yves-action.');

        $action = 'noClassParameterAction';
        $controllerCallable = $this->executeMockedListenerTest($action);
        call_user_func($controllerCallable);
    }

    public function testWhenObjectIsNotTransferClassPluginMustThrowException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only transfer classes are allowed in yves action as parameter');

        $transfer = new class () implements TransferInterface
        {
            /**
             * @param bool $isRecursive
             *
             * @return array
             */
            public function toArray($isRecursive = true)
            {
                return [];
            }

            /**
             * @param bool $isRecursive
             *
             * @return array
             */
            public function modifiedToArray($isRecursive = true)
            {
                return [];
            }

            /**
             * @param array $values
             * @param bool $fuzzyMatch
             *
             * @return $this
             */
            public function fromArray(array $values, $fuzzyMatch = false)
            {
                return $this;
            }

            /**
             * @param string $propertyName
             *
             * @return bool
             */
            public function isPropertyModified($propertyName)
            {
                return (bool)$propertyName;
            }
        };
        $controllerCallable = $this->executeMockedListenerTest('notTransferAction', $transfer);
        call_user_func($controllerCallable);
    }

    /**
     * @dataProvider base64EncodingDataProvider
     */
    public function testWhenControllerIsGatewayControllerAndOnlyOneTransferObjectIsGivenActionMustReturnResponse(bool $isBase64Enabled): void
    {
        $this->tester->setConfig(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, $isBase64Enabled);

        $transfer = $this->getTransferMock();
        $controllerCallable = $this->executeMockedListenerTest('goodAction', $transfer);

        $response = call_user_func($controllerCallable);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @dataProvider base64EncodingDataProvider
     */
    public function testTransformMessagesFromController(bool $isBase64Enabled): void
    {
        $this->tester->setConfig(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, $isBase64Enabled);

        $transfer = $this->getTransferMock();
        $controllerCallable = $this->executeMockedListenerTest('transformMessageAction', $transfer);

        $response = call_user_func($controllerCallable);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseContent = $this->decodeResponseContent($response, $isBase64Enabled);

        $this->assertArrayHasKey('infoMessages', $responseContent);
        $this->assertArrayHasKey('errorMessages', $responseContent);
        $this->assertArrayHasKey('successMessages', $responseContent);
        $this->assertArrayHasKey('success', $responseContent);
    }

    /**
     * @return array<string, array<bool>>
     */
    public function base64EncodingDataProvider(): array
    {
        return [
            'base64 disabled' => [false],
            'base64 enabled' => [true],
        ];
    }

    protected function decodeResponseContent(JsonResponse $response, bool $isBase64Enabled): array
    {
        $content = $response->getContent();
        if ($isBase64Enabled) {
            $content = (string)base64_decode($content);
        }

        return json_decode($content, true) ?? [];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\ZedRequest\Business\Model\Repeater
     */
    private function createRepeaterMock(): Repeater
    {
        return $this->getMockBuilder(Repeater::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function initTransferServer(TransferInterface $transferObject): void
    {
        $oldTransferServer = CoreTransferServer::getInstance();
        $this->resetSingleton($oldTransferServer);

        $request = new Request();
        $request->setFixtureTransfer($transferObject);
        TransferServer::getInstance()->setFixtureRequest($request);
    }

    private function resetTransferServer(): void
    {
        $fixtureServer = TransferServer::getInstance();
        $this->resetSingleton($fixtureServer);
        CoreTransferServer::getInstance(
            $this->createRepeaterMock(),
        );
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface|\SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture\TransferServer|\Spryker\Zed\ZedRequest\Communication\Plugin\TransferObject\TransferServer $oldTransferServer
     *
     * @return void
     */
    private function resetSingleton($oldTransferServer): void
    {
        $refObject = new ReflectionObject($oldTransferServer);
        $refProperty = $refObject->getProperty('instance');
        $refProperty->setValue(null);
    }

    /**
     * @param string $action
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface|\stdClass|null $transfer
     *
     * @return callable
     */
    private function executeMockedListenerTest(string $action, $transfer = null): callable
    {
        $controller = new GatewayController();

        if (!$transfer) {
            $transfer = $this->getTransferMock();
        }

        $this->initTransferServer($transfer);

        $event = $this->tester->createControllerEvent([$controller, $action]);
        $event = $this->dispatchEvent($event, KernelEvents::CONTROLLER);

        $controllerCallable = $event->getController();

        return $controllerCallable;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
     * @param string $eventName
     *
     * @return \Symfony\Component\HttpKernel\Event\KernelEvent|\Symfony\Component\HttpKernel\Event\FilterControllerEvent|callable
     */
    protected function dispatchEvent(KernelEvent $event, string $eventName)
    {
        $eventDispatcher = new EventDispatcher();
        $gatewayControllerEventDispatcherPlugin = new GatewayControllerEventDispatcherPlugin();
        $gatewayControllerEventDispatcherPlugin->extend($eventDispatcher, new Container());

        return $eventDispatcher->dispatch($event, $eventName);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Shared\Kernel\Transfer\TransferInterface
     */
    private function getTransferMock(): TransferInterface
    {
        $transfer = $this->getMockBuilder(TransferInterface::class)->getMock();

        return $transfer;
    }
}
