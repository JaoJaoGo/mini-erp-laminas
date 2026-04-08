<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\AuthController;
use Application\Controller\HomeController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ApplicationControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        parent::setUp();
    }

    public function testLoginRouteCanBeAccessed(): void
    {
        $this->dispatch('/login', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(AuthController::class);
        $this->assertControllerClass('AuthController');
        $this->assertMatchedRouteName('login');
        $this->assertQuery('form');
    }

    public function testHomeRouteRedirectsToLoginWhenUserIsNotAuthenticated(): void
    {
        $this->dispatch('/', 'GET');
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/login');
        $this->assertMatchedRouteName('home');
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }
}
