# Visão Geral da Aplicação

## Objetivo

O Mini ERP Laminas é um projeto de estudo construído com Laminas MVC e Doctrine ORM. Ele oferece autenticação, gerenciamento de categorias e produtos, além de filtros na listagem.

## Recursos principais

- **Autenticação de usuário** com login, logout e **cadastro de usuários**.
- Dashboard com informação do usuário autenticado.
- **CRUD de categorias** com **soft delete** e **paginação**.
- **CRUD de produtos** com **soft delete**, **paginação** e upload de imagens.
- Filtragem por nome e categoria nas listagens com paginação.
- Controle de acesso básico via sessão.
- Validação de formatos de imagem (JPG, PNG, WEBP).
- Sincronização de categorias em produtos.

## Estrutura principal

- `module/Application/src/Controller` — Controladores da aplicação.
- `module/Application/src/Entity` — Entidades mapeadas pelo Doctrine.
- `module/Application/src/Form` — Formulários e validações.
- `module/Application/src/Service` — Serviços de negócio.
- `module/Application/src/Repository` — Repositórios para acesso a dados.
- `module/Application/src/Response` — Classes para manipulação de respostas.
- `module/Application/view` — Templates de visualização.
- `config/autoload/doctrine.local.php` — Configuração do Doctrine.
- `config/application.config.php` — Configuração principal da aplicação Laminas.
- `module/Application/src/Module.php` — Verificação de autenticação e bootstrap.

## Camadas do sistema

### Controle

- `AuthController` — Gerencia login/logout e **cadastro de usuários**.
- `HomeController` — Exibe dashboard e usuário autenticado.
- `CategoryController` — Lista, cria, edita e exclui categorias com **paginação**.
- `ProductController` — Lista, cria, edita e exclui produtos com **paginação**.

### Serviço

- `AuthService` — Autentica usuário, mantém sessão e recupera o usuário logado.
- `UserService` — Gerencia operações de usuários (verificação de email único, criação).
- `CategoryService` — Lógica de negócio para operações com categorias e **paginação**.
- `ProductService` — Lógica de negócio para operações com produtos e **paginação**.

### Formulário

- `LoginForm` — Validação de e-mail, senha e token CSRF.
- `RegisterForm` — Formulário de cadastro de usuários com validação.
- `CategoryForm` — Formulário para criação e edição de categorias.
- `ProductForm` — Formulário para criação e edição de produtos.

### Repositório

- `CategoryRepository` — Acesso a dados de categorias.
- `ProductRepository` — Acesso a dados de produtos.

### Resposta

- `CategoryResponse` — Manipulação de respostas para operações com categorias.
- `ProductResponse` — Manipulação de respostas para operações com produtos.

### Entidades

- `User` — Usuários do sistema.
- `Category` — Categorias de produtos.
- `Product` — Produtos que podem pertencer a categorias.

## Rotas disponíveis

| Rota | Método | Função | Descrição |
|---|---|---|---|
| `/login` | GET/POST | `AuthController::loginAction` | Tela de login e processamento de credenciais |
| `/register` | GET/POST | `AuthController::registerAction` | **Cadastro de novos usuários** |
| `/logout` | GET | `AuthController::logoutAction` | Encerra sessão do usuário |
| `/` | GET | `HomeController::homeAction` | Dashboard inicial |
| `/categories` | GET | `CategoryController::indexAction` | **Listagem paginada de categorias** |
| `/categories/create` | GET/POST | `CategoryController::createAction` | Cria uma nova categoria |
| `/categories/:id/edit` | GET/POST | `CategoryController::editAction` | Edita categoria existente |
| `/categories/:id/delete` | GET | `CategoryController::deleteAction` | **Exclui categoria (soft delete)** |
| `/products` | GET | `ProductController::indexAction` | **Listagem paginada de produtos** |
| `/products/create` | GET/POST | `ProductController::createAction` | Cria um produto |
| `/products/:id/edit` | GET/POST | `ProductController::editAction` | Edita produto existente |
| `/products/:id/delete` | GET | `ProductController::deleteAction` | **Exclui produto (soft delete)** |

> Observação: a rota `application` existe na configuração, mas a navegação principal usa `home`, `categories` e `products`.

## Fluxo de autenticação

1. O usuário acessa `/login`.
2. O `LoginForm` valida email e senha.
3. O `AuthService` consulta a entidade `User` no banco.
4. Se o usuário existir e estiver ativo, o serviço salva `userId`, `userName` e `userEmail` na sessão.
5. Rotas privadas são protegidas em `Module::checkAuthentication`.
6. Se não autenticado, o usuário é redirecionado para `/login`.

## Funcionalidades Recentes

### Cadastro de Usuários

- **Rota**: `/register`
- **Funcionalidade**: Permite que novos usuários se cadastrem no sistema
- **Validações**: Nome, email único, senha com confirmação, proteção CSRF
- **Componentes**: `RegisterForm`, `UserService`, método `registerAction` no `AuthController`

### Soft Delete

- **Implementação**: Adicionado campo `deletedAt` nas entidades `Category` e `Product`
- **Métodos**: `softDelete()` e `isDeleted()` nas entidades
- **Comportamento**: Exclusões não removem registros do banco, apenas marcam como excluídos
- **Filtragem**: Todas as consultas filtram registros onde `deletedAt IS NULL`

### Paginação

- **Implementação**: Usando Doctrine Paginator com paginação baseada em query parameters
- **Parâmetros**: `page` (página atual), `perPage` (itens por página)
- **Estrutura de resposta**: Array com `items`, `total`, `page`, `perPage`, `totalPages`
- **Aplicado em**: Listagens de categorias e produtos

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

## Testes automatizados

A aplicação possui uma cobertura completa de testes:

### Estrutura de testes

```
module/Application/test/
├── Controller/
│   ├── ApplicationControllerTest.php
│   ├── AuthControllerTest.php
│   ├── CategoryControllerTest.php
│   └── ProductControllerTest.php
├── Service/
│   ├── AuthServiceTest.php
│   ├── CategoryServiceTest.php
│   ├── MetricServiceTest.php
│   └── ProductServiceTest.php
├── Form/
│   ├── CategoryFormTest.php
│   └── ProductFormTest.php
├── Repository/
│   ├── CategoryRepositoryTest.php
│   └── ProductRepositoryTest.php
└── ModuleTest.php
```

### Cobertura

- **85 testes** com **250+ assertions**
- Controllers: Fluxos de requisição, redirecionamentos, erros 404, upload de imagens
- Services: Lógica de negócio, persistência, sincronização, upload/deleção de imagens
- Forms: Validações, requisitos de campo, suporte a multipart
- Repositories: Queries customizadas do Doctrine
- Module: Bootstrap e verificação de autenticação

### Testes de upload de imagens

A aplicação inclui testes específicos para o sistema de upload:

- ✅ Upload bem-sucedido em createAction
- ✅ Upload bem-sucedido em editAction
- ✅ Substituição de imagem antiga em update
- ✅ Limpeza de imagem ao deletar produto
- ✅ Tratamento de exceções de upload (formato, tamanho)
- ✅ Validação de formato de arquivo
- ✅ Atributos e enctype do formulário

### Estratégia de testes

- **Mocks**: Serviços são mockados para isolar camadas
- **Request/Response**: Controllers testam com objetos Laminas reais
- **Callbacks**: QueryBuilder é testado com callbacks para simular comportamentos
- **Reflexão**: Propriedades privadas de controllers acessadas via Reflection para inicialização
