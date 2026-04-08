<?php

declare(strict_types=1);

namespace ApplicationTest;

use Application\Module;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Session\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Application\Module;
 */
class ModuleTest extends TestCase
{
    public function testProvidesConfig(): void
    {
        $module = new Module();
        $config = $module->getConfig();

        self::assertArrayHasKey('router', $config);
        self::assertArrayHasKey('controllers', $config);
    }

    public function testCheckAuthenticationRedirectsWhenNotAuthenticated(): void
    {
        $module = new Module();
        $event = new MvcEvent();
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('home');
        $event->setRouteMatch($routeMatch);
        $response = new Response();
        $event->setResponse($response);

        $module->checkAuthentication($event);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaders()->get('Location')->getFieldValue());
    }

    public function testCheckAuthenticationDoesNotRedirectWhenAuthenticated(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $container = new Container('auth');
        $container->offsetSet('userId', 1);

        $module = new Module();
        $event = new MvcEvent();
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('home');
        $event->setRouteMatch($routeMatch);
        $response = new Response();
        $event->setResponse($response);

        $module->checkAuthentication($event);

        self::assertNotSame(302, $response->getStatusCode());

        $container->getManager()->getStorage()->clear('auth');
    }
}
