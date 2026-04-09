<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\AuthController;
use Application\Form\LoginForm;
use Application\Form\RegisterForm;
use Application\Service\AuthService;
use Application\Service\UserService;
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
    private RegisterForm $registerForm;
    private UserService $userService;
    private AuthController $controller;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthService::class);
        $this->loginForm = new LoginForm();
        $this->registerForm = new RegisterForm();
        $this->userService = $this->createMock(UserService::class);
        $this->controller = new AuthController($this->authService, $this->loginForm, $this->registerForm, $this->userService);
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

    public function testRegisterActionGetReturnsFormView(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->setControllerRequest($request);

        $result = $this->controller->registerAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        self::assertSame('application/auth/register', $result->getTemplate());
    }

    public function testRegisterActionRedirectsWhenAlreadyAuthenticated(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->setControllerRequest($request);

        $result = $this->controller->registerAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testRegisterActionPostValidRedirectsAndCreatesUser(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $this->userService->expects(self::once())
            ->method('emailExists')
            ->with('newuser@example.com')
            ->willReturn(false);

        $this->userService->expects(self::once())
            ->method('create')
            ->with('João Silva', 'newuser@example.com', 'password123');

        $form = clone $this->registerForm;
        $data = [
            'name' => 'João Silva',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);

        $result = $this->controller->registerAction();

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(302, $result->getStatusCode());
    }

    public function testRegisterActionPostInvalidReturnsFormView(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $form = clone $this->registerForm;
        $data = [
            'name' => 'Jo',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => 'different',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);

        $result = $this->controller->registerAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        self::assertSame('application/auth/register', $result->getTemplate());
    }

    public function testRegisterActionPostWithExistingEmailReturnsFormView(): void
    {
        $this->authService->expects(self::once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $this->userService->expects(self::once())
            ->method('emailExists')
            ->with('existing@example.com')
            ->willReturn(true);

        $form = clone $this->registerForm;
        $data = [
            'name' => 'João Silva',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'csrf' => $form->get('csrf')->getValue(),
        ];

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->setPost(new Parameters($data));
        $this->setControllerRequest($request);

        $result = $this->controller->registerAction();

        self::assertInstanceOf(\Laminas\View\Model\ViewModel::class, $result);
        self::assertArrayHasKey('form', $result->getVariables());
        self::assertSame('application/auth/register', $result->getTemplate());
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
