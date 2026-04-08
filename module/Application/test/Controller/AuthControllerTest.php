<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\AuthController;
use Application\Form\LoginForm;
use Application\Service\AuthService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger as FlashMessengerPlugin;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    private AuthService $authService;
    private LoginForm $loginForm;
    private AuthController $controller;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthService::class);
        $this->loginForm = new LoginForm();
        $this->controller = new AuthController($this->authService, $this->loginForm);
        $this->controller->setPluginManager($this->createPluginManager());
        $this->controller->setEvent(new MvcEvent());
    }

    public function testLoginActionReturnsViewModelOnGet(): void
    {
        $this->authService->method('isAuthenticated')->willReturn(false);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->controller->getEvent()->setRequest($request);

        $result = $this->controller->loginAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
    }

    public function testLoginActionRedirectsOnValidPost(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $this->authService->expects(self::once())
            ->method('login')
            ->with('test@example.com', 'secret')
            ->willReturn(true);

        $form = clone $this->loginForm;
        $data = [
            'email' => 'test@example.com',
            'password' => 'secret',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));

        $this->setControllerRequest($request);

        $result = $this->controller->loginAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testLoginActionReturnsViewModelWhenFormInvalid(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $form = clone $this->loginForm;
        $data = [
            'email' => 'invalid-email',
            'password' => '123',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));

        $this->setControllerRequest($request);

        $result = $this->controller->loginAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
    }

    public function testLogoutActionRedirectsToLogin(): void
    {
        $this->authService->expects(self::once())
            ->method('logout');

        $request = new Request();
        $this->setControllerRequest($request);

        $result = $this->controller->logoutAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testLoginActionRedirectsWhenAlreadyAuthenticated(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->setControllerRequest($request);

        $result = $this->controller->loginAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    private function createPluginManager(): PluginManager
    {
        $serviceManager = new ServiceManager();
        $pluginManager = new PluginManager($serviceManager);

        $redirect = $this->createMock(Redirect::class);
        $redirect->method('toRoute')
            ->willReturnCallback(static function () {
                $response = new Response();
                $response->setStatusCode(302);

                return $response;
            });

        $flashMessenger = new FlashMessengerPlugin();

        $pluginManager->setService('redirect', $redirect);
        $pluginManager->setService('flashMessenger', $flashMessenger);

        return $pluginManager;
    }

    private function setControllerRequest(Request $request): void
    {
        $reflection = new \ReflectionProperty($this->controller, 'request');
        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, $request);
        $this->controller->getEvent()->setRequest($request);
    }
}
