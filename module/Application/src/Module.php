<?php

declare(strict_types=1);

namespace Application;


use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

/**
 * Módulo principal da aplicação Application.
 *
 * O Module inicializa o evento de bootstrap e verifica a autenticação do usuário
 * em rotas protegidas, redirecionando para a tela de login quando necessário.
 */
class Module
{
    /**
     * Retorna a configuração do módulo Application.
     *
     * @return array A configuração carregada de module.config.php.
     */
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    /**
     * Inicializa o módulo durante o bootstrap da aplicação.
     *
     * Anexa o listener de rota que verifica a autenticação do usuário antes de
     * processar rotas protegidas.
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $application = $event->getApplication();
        $eventManager = $application->getEventManager();

        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'checkAuthentication'], -100);
    }

    /**
     * Verifica a autenticação para rotas protegidas.
     *
     * Rotas públicas definidas em $publicRoutes não exigem autenticação.
     * Se o usuário não estiver autenticado, redireciona para /login.
     */
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
            'store',
            'store/view'
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
