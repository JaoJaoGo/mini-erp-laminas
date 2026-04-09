<?php

declare(strict_types=1);

namespace Application;


use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    public function onBootstrap(MvcEvent $event): void
    {
        $application = $event->getApplication();
        $eventManager = $application->getEventManager();

        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'checkAuthentication'], -100);
    }

    public function checkAuthentication(MvcEvent $event): void
    {
        $match = $event->getRouteMatch();

        if (!$match) {
            return;
        }

        $routeName = $match->getMatchedRouteName();

        $publicRoutes = [
            'login',
            'register',
        ];

        if (in_array($routeName, $publicRoutes, true)) {
            return;
        }

        $session = new Container('auth');

        if (empty($session->userId)) {
            $response = $event->getResponse();

            $response->getHeaders()->addHeaderLine('Location', '/login');
            $response->setStatusCode(302);

            return;
        }
    }
}
