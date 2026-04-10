<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\MetricService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Controlador responsável pela exibição do dashboard da aplicação.
 *
 * Este controlador utiliza o MetricService para coletar dados estatísticos e o AuthService
 * para recuperar o usuário atualmente autenticado.
 *
 * Ações disponíveis:
 * - homeAction: exibe o painel inicial com totais e gráficos.
 */
class HomeController extends AbstractActionController
{
    public function __construct(
        private readonly MetricService $metricService,
        private readonly AuthService $authService,
    ) { }

    public function homeAction()
    {
        $dashboardData = $this->metricService->getDashboardData();

        return new ViewModel([
            'user' => $this->authService->getAuthenticatedUser(),
            'totalCategories' => $dashboardData['totalCategories'],
            'totalProducts' => $dashboardData['totalProducts'],
            'productsPerCategoryChart' => $this->metricService->getProductsPerCategoryChart(),
            'productsStatusChart' => $this->metricService->getProductsStatusChart(),
        ]);
    }
}
