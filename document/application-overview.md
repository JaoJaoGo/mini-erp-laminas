# Visão Geral da Aplicação

## Objetivo

O Mini ERP Laminas é um projeto de estudo construído com Laminas MVC e Doctrine ORM. Ele oferece autenticação, gerenciamento de categorias e produtos, além de filtros na listagem.

## Recursos principais

- Autenticação de usuário com login e logout.
- Dashboard com informação do usuário autenticado.
- CRUD de categorias.
- CRUD de produtos.
- Filtragem por nome e categoria nas listagens.
- Controle de acesso básico via sessão.

## Estrutura principal

- `module/Application/src/Controller` — Controladores da aplicação.
- `module/Application/src/Entity` — Entidades mapeadas pelo Doctrine.
- `module/Application/src/Form` — Formulários e validações.
- `module/Application/src/Service` — Serviço de autenticação.
- `module/Application/view` — Templates de visualização.
- `config/autoload/doctrine.local.php` — Configuração do Doctrine.
- `config/application.config.php` — Configuração principal da aplicação Laminas.
- `module/Application/src/Module.php` — Verificação de autenticação e bootstrap.

## Camadas do sistema

### Controle

- `AuthController` — Gerencia login/logout.
- `HomeController` — Exibe dashboard e usuário autenticado.
- `CategoryController` — Lista, cria, edita e exclui categorias.
- `ProductController` — Lista, cria, edita e exclui produtos.

### Serviço

- `AuthService` — Autentica usuário, mantém sessão e recupera o usuário logado.

### Formulário

- `LoginForm` — Validação de e-mail, senha e token CSRF.

### Entidades

- `User` — Usuários do sistema.
- `Category` — Categorias de produtos.
- `Product` — Produtos que podem pertencer a categorias.

## Rotas disponíveis

| Rota | Método | Função | Descrição |
|---|---|---|---|
| `/login` | GET/POST | `AuthController::loginAction` | Tela de login e processamento de credenciais |
| `/logout` | GET | `AuthController::logoutAction` | Encerra sessão do usuário |
| `/` | GET | `HomeController::homeAction` | Dashboard inicial |
| `/categories` | GET | `CategoryController::indexAction` | Listagem de categorias |
| `/categories/create` | GET/POST | `CategoryController::createAction` | Cria uma nova categoria |
| `/categories/:id/edit` | GET/POST | `CategoryController::editAction` | Edita categoria existente |
| `/categories/:id/delete` | GET | `CategoryController::deleteAction` | Exclui categoria |
| `/products` | GET | `ProductController::indexAction` | Listagem de produtos |
| `/products/create` | GET/POST | `ProductController::createAction` | Cria um produto |
| `/products/:id/edit` | GET/POST | `ProductController::editAction` | Edita produto existente |
| `/products/:id/delete` | GET | `ProductController::deleteAction` | Exclui produto |

> Observação: a rota `application` existe na configuração, mas a navegação principal usa `home`, `categories` e `products`.

## Fluxo de autenticação

1. O usuário acessa `/login`.
2. O `LoginForm` valida email e senha.
3. O `AuthService` consulta a entidade `User` no banco.
4. Se o usuário existir e estiver ativo, o serviço salva `userId`, `userName` e `userEmail` na sessão.
5. Rotas privadas são protegidas em `Module::checkAuthentication`.
6. Se não autenticado, o usuário é redirecionado para `/login`.

## Interface

A aplicação usa templates simples em `module/Application/view` com HTML e CSS embutido.

- `header.phtml` — Barra superior e logout.
- `footer.phtml` — Rodapé.
- `listing-toolbar.phtml` — Barra de filtros e ações.
- `listing-table.phtml` — Tabela de listagem de registros.
- `modal-delete.phtml` — Modal para confirmação de exclusão.

## Observações de implementação

- O CRUD de categorias e produtos é tratado diretamente nos controladores.
- A validação de dados é feita no controlador e no formulário de login.
- A aplicação utiliza `Doctrine ORM` para persistência e mapeamento de entidades.
- A rota `CategoryController::view` está configurada nos caminhos de rota, mas não possui ação implementada.
