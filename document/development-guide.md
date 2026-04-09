# Guia de Desenvolvimento

## Estrutura do projeto

```
mini-erp-laminas/
├── bin/                    # Scripts utilitários
├── config/                 # Configurações Laminas e Doctrine
├── data/                   # Cache e proxies Doctrine
├── document/               # Documentação (este arquivo)
├── module/Application/     # Módulo principal
│   ├── config/             # Configuração de rotas e serviços
│   ├── src/                # Código fonte
│   │   ├── Controller/     # Controladores
│   │   ├── Entity/         # Entidades Doctrine
│   │   ├── Form/           # Formulários
│   │   ├── Repository/     # Repositórios de dados
│   │   ├── Response/       # Classes de resposta
│   │   ├── Service/        # Serviços de negócio
│   │   └── Module.php      # Bootstrap do módulo
│   ├── test/               # Testes unitários
│   └── view/               # Templates
├── public/                 # Document root
├── vendor/                 # Dependências Composer
├── composer.json           # Configuração Composer
├── docker-compose.yml      # Ambiente Docker
├── Dockerfile              # Imagem da aplicação
├── phpunit.xml.dist        # Configuração PHPUnit
├── psalm.xml               # Configuração Psalm
└── phpcs.xml               # Configuração PHP CodeSniffer
```

## Dependências principais

- **PHP 8.3** — Linguagem base.
- **Laminas MVC** — Framework web.
- **Doctrine ORM** — Mapeamento objeto-relacional.
- **MySQL 8** — Banco de dados.
- **Docker** — Ambiente de desenvolvimento.

## Comandos de desenvolvimento

### Composer

- Instalar dependências: `composer install`
- Atualizar dependências: `composer update`
- Limpar cache de configuração: `composer clear-config-cache`
- Verificar código: `composer cs-check`
- Corrigir código: `composer cs-fix`
- Executar testes: `composer test`
- Análise estática: `composer static-analysis`

### Doctrine

- Verificar status: `php vendor/bin/doctrine-module orm:info`
- Gerar SQL: `php vendor/bin/doctrine-module orm:schema-tool:update --dump-sql`
- Aplicar schema: `php vendor/bin/doctrine-module orm:schema-tool:update --force`
- Limpar cache: `php vendor/bin/doctrine-module orm:clear-cache:metadata`

### PHPUnit

- Executar todos os testes: `vendor/bin/phpunit`
- Executar testes específicos: `vendor/bin/phpunit --filter TestClass`
- Cobertura de código: `vendor/bin/phpunit --coverage-html coverage/`

### Psalm

- Análise estática: `vendor/bin/psalm`
- Análise com estatísticas: `vendor/bin/psalm --stats`

### PHP CodeSniffer

- Verificar código: `vendor/bin/phpcs`
- Corrigir automaticamente: `vendor/bin/phpcbf`

## Testes

### Estrutura de testes

Os testes cobrem todas as camadas da aplicação:

- **Controllers** — Testes de integração para lógica de requisição/resposta
  - `AuthControllerTest` — Login, logout, redirecionamentos, **cadastro de usuários**
  - `CategoryControllerTest` — CRUD, filtros, erros 404, **paginação**
  - `ProductControllerTest` — CRUD, validações, sincronização de categorias, upload de imagens, **paginação**
  - `ApplicationControllerTest` — Autenticação e rotas públicas

- **Services** — Testes unitários de lógica de negócio
  - `AuthServiceTest` — Autenticação, sessão, busca de usuário
  - `UserServiceTest` — **Verificação de email único, criação de usuários**
  - `CategoryServiceTest` — Operações CRUD, persistência, **soft delete**
  - `ProductServiceTest` — Normalização, sincronização, filtros, upload e deleção de imagens, **soft delete**
  - `MetricServiceTest` — Agregações e contagens, **filtragem por soft delete**

- **Forms** — Testes de validação
  - `CategoryFormTest` — Validação de campos obrigatórios
  - `ProductFormTest` — Validação de preço, estoque, campo de imagem com enctype
  - `RegisterFormTest` — **Validação de cadastro de usuários (nome, email, senha)**

- **Repositories** — Testes de consultas customizadas
  - `CategoryRepositoryTest` — Filtros, agrupamento, **paginação**
  - `ProductRepositoryTest` — Filtros por categoria, contagem por status, **paginação**

- **Module** — Testes de configuração e bootstrap
  - `ModuleTest` — Verificação de autenticação, configuração

- **Functional** — Testes funcionais de integração e lógica de negócio
  - `FunctionalTest` — Soft delete, paginação, relacionamentos, validações de dados

### Executando testes

```bash
# Todos os testes
composer test
# ou
vendor/bin/phpunit

# Testes específicos
vendor/bin/phpunit module/Application/test/Controller/ProductControllerTest.php

# Com relatório legível
vendor/bin/phpunit --testdox

# Com cobertura de código
vendor/bin/phpunit --coverage-html coverage/
```

### Cobertura de testes

A aplicação possui **117 testes** com **351 assertions** cobrindo:

- ✅ Fluxos de autenticação (login, logout, redirecionamento)
- ✅ **Cadastro de usuários** (registro, validação de email único)
- ✅ CRUD completo (create, read, update, delete)
- ✅ **Soft delete** (exclusão lógica, filtragem automática)
- ✅ **Paginação** (Doctrine Paginator, parâmetros de query)
- ✅ Validações de formulário
- ✅ Filtros de listagem
- ✅ Sincronização de relacionamentos (categorias-produtos)
- ✅ Casos de erro (404, validação inválida)
- ✅ Normalização de dados (moeda, IDs)
- ✅ Queries customizadas do Doctrine
- ✅ **Upload de imagens** — Formatos válidos, tamanho máximo, tratamento de erros
- ✅ **Substituição de imagens** — Delete de arquivo antigo, conservação de imagem atual
- ✅ **Validação de arquivo** — MIME type, extensão, tamanho
- ✅ **Testes funcionais** — Lógica de paginação, soft delete, relacionamentos, validações de dados

## Análise estática

```bash
composer static-analysis
# ou
vendor/bin/psalm
```

## Padrões de código

### PHP CodeSniffer

O projeto usa PSR-12 como padrão de código. Verifique com:

```bash
composer cs-check
```

Para correção automática:

```bash
composer cs-fix
```

### Estrutura de commits

Use mensagens descritivas em português ou inglês. Exemplos:

- `feat: adicionar validação de email no login`
- `fix: corrigir bug na listagem de produtos`
- `docs: atualizar documentação de instalação`

## Desenvolvimento local

### Sem Docker

1. Instale PHP 8.3 e MySQL.
2. Configure banco de dados local.
3. Execute `composer install`.
4. Configure `config/autoload/local.php` com credenciais locais.
5. Execute `composer serve` para servidor embutido.

### Com Docker

1. `docker compose up --build -d`
2. `docker compose exec laminas bash`
3. `composer install`
4. Configure Doctrine para usar `mysql` como host.

## Debugging

### Logs

- Logs da aplicação: `docker compose logs -f laminas`
- Logs do MySQL: `docker compose logs -f mysql`

### Cache

Limpe caches quando houver problemas de configuração:

```bash
composer clear-config-cache
php vendor/bin/doctrine-module orm:clear-cache:metadata
```

### Sessão

A sessão é armazenada em `Laminas\Session\Container` com chave `auth`. Para depurar:

```php
$session = new Container('auth');
var_dump($session->getArrayCopy());
```

## Extensões recomendadas

Para desenvolvimento em VS Code:

- PHP Extension Pack
- Docker
- GitLens
- PHPUnit Test Explorer

## Considerações de segurança

- Senhas são hasheadas com `password_hash()` e verificadas com `password_verify()`.
- Formulários incluem token CSRF.
- Sessão é limpa no logout.
- Rotas privadas são protegidas por middleware.

## Próximas melhorias

- Adicionar logs de auditoria.
- Melhorar validações e tratamento de erros.
- Implementar cache para melhorar performance.