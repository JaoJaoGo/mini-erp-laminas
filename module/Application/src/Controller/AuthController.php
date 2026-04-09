<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Form\LoginForm;
use Application\Form\RegisterForm;
use Application\Service\AuthService;
use Application\Service\UserService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * Controlador responsável por gerenciar as ações de autenticação, como login e logout.
 * Este controlador utiliza o AuthService para realizar as operações de autenticação e o LoginForm para validar os dados de login.
 * As ações disponíveis são:
 * 
 * - loginAction: Exibe o formulário de login e processa as tentativas de login
 * - logoutAction: Realiza o logout do usuário e redireciona para a página de login
 * 
 * O controlador verifica se o usuário já está autenticado antes de exibir o formulário de login, e redireciona para a página inicial caso esteja. Durante o processo de login, ele valida os dados do formulário e utiliza o AuthService para autenticar o usuário, exibindo mensagens de erro apropriadas em caso de falha.
 */
class AuthController extends AbstractActionController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly LoginForm $loginForm,
        private readonly RegisterForm $registerForm,
        private readonly UserService $userService,
    ) { }

    public function loginAction(): ViewModel|\Laminas\Http\Response
    {
        if ($this->authService->isAuthenticated()) {
            return $this->redirect()->toRoute('home');
        }

        $form = $this->loginForm;
        $request = $this->getRequest();

        if (!$request instanceof Request || !$request->isPost()) {
            return new ViewModel([
                'form' => $form,
            ]);
        }

        $data = $request->getPost()->toArray();
        $form->setData($data);

        if (!$form->isValid()) {
            $this->flashMessenger()->addErrorMessage('Preencha os campos corretamente.');

            return new ViewModel([
                'form' => $form,
            ]);
        }

        $validatedData = $form->getData();

        $authenticated = $this->authService->login(
            $validatedData['email'],
            $validatedData['password'],
        );

        if (!$authenticated) {
            $this->flashMessenger()->addErrorMessage('E-mail ou senha inválidos.');

            return new ViewModel([
                'form' => $form,
            ]);
        }

        return $this->redirect()->toRoute('home');
    }

    public function registerAction(): ViewModel|Response
    {
        if ($this->authService->isAuthenticated()) {
            return $this->redirect()->toRoute('home');
        }

        $form = clone $this->registerForm;
        $request = $this->getRequest();

        if (!$request instanceof Request || !$request->isPost()) {
            return (new ViewModel([
                'form' => $form,
            ]))->setTemplate('application/auth/register');
        }

        $data = $request->getPost()->toArray();
        $form->setData($data);

        if (!$form->isValid()) {
            $this->flashMessenger()->addErrorMessage('Preencha os campos corretamente.');

            return (new ViewModel([
                'form' => $form,
            ]))->setTemplate('application/auth/register');
        }

        $validatedData = $form->getData();

        if ($this->userService->emailExists($validatedData['email'])) {
            $form->get('email')->setMessages([
                'emailExists' => 'Já existe um usuário cadastrado com este e-mail.',
            ]);

            return (new ViewModel([
                'form' => $form,
            ]))->setTemplate('application/auth/register');
        }

        $this->userService->create(
            $validatedData['name'],
            $validatedData['email'],
            $validatedData['password'],
        );

        $this->flashMessenger()->addSuccessMessage('Conta criada com sucesso. Agora você pode entrar.');

        return $this->redirect()->toRoute('login');
    }

    public function logoutAction(): Response
    {
        $this->authService->logout();

        return $this->redirect()->toRoute('login');
    }
}