# Guia de Início Rápido

## Requisitos

- Docker
- Docker Compose
- Git
- PHP 8.3 (opcional, se executar sem Docker)

## Clonando o repositório

```bash
git clone https://github.com/JaoJaoGo/mini-erp-laminas.git
cd mini-erp-laminas
```

## Subindo o ambiente com Docker

```bash
docker compose up --build -d
```

Verifique os containers:

```bash
docker compose ps
```

## Acessando o container da aplicação

```bash
docker compose exec laminas bash
```

## Instalando dependências

Dentro do container:

```bash
composer install
```

## Configuração do banco de dados

O projeto utiliza MySQL e a configuração está definida em `config/autoload/doctrine.local.php`.

Credenciais padrão:

- Host: `mysql`
- Porta: `3306`
- Banco: `mini_erp_laminas`
- Usuário: `laminas`
- Senha: `laminas`

Para conectar externamente:

- Host: `127.0.0.1`
- Porta: `3307`
- Banco: `mini_erp_laminas`
- Usuário: `laminas`
- Senha: `laminas`

## Inicializando o schema do Doctrine

Dentro do container da aplicação:

```bash
php vendor/bin/doctrine-module orm:info
php vendor/bin/doctrine-module orm:schema-tool:update --dump-sql
php vendor/bin/doctrine-module orm:schema-tool:update --force
```

## Permitindo salvar imagens de produtos na pasta

Garanta que a pasta exista ou possa ser criada:

1. Criar a pasta, se ainda não existir

```bash
docker compose exec laminas sh
mkdir -p /var/www/public/uploads/products
```

2. Dar ownership para o usuário do servidor web

```bash
chown -R www-data:www-data /var/www/public/uploads
chmod -R 755 /var/www/public/uploads
```

### Como verificar se deu certo

Ainda dentro do container:

```bash
ls -ld /var/www/public/uploads
ls -ld /var/www/public/uploads/products
whoami
```

Você quer ver algo parecido com:
- dono/grupo: ```www-data www-data```
- permissão com escrita para owner e grupo

## Usuário inicial

A aplicação agora fornece cadastro de usuários pela interface web. Você pode acessar a página de registro em `/auth/register` ou através do link "Registrar" na página de login.

Para criar o primeiro usuário administrador, você pode:

1. **Via interface web**: Acesse `http://localhost:8080/auth/register` e preencha o formulário
2. **Via SQL** (método alternativo):

```sql
INSERT INTO users (name, email, password, isActive, createdAt, updatedAt)
VALUES ('Admin', 'admin@example.com', '<hash_da_senha>', 1, NOW(), NOW());
```

Use `php -r "echo password_hash('123456', PASSWORD_DEFAULT) . PHP_EOL;"` em PHP para gerar o valor de `<hash_da_senha>`.

## Executando a aplicação

A aplicação fica disponível em:

```txt
http://localhost:8080
```

## Executando testes

Dentro do container:

```bash
# Todos os testes
composer test

# Com relatório legível
vendor/bin/phpunit --testdox

# Testes específicos
vendor/bin/phpunit module/Application/test/Controller/ProductControllerTest.php

# Com cobertura de código
vendor/bin/phpunit --coverage-html coverage/
```

A aplicação inclui **106 testes** que cobrem:

- Autenticação e controle de acesso
- Cadastro de usuários
- CRUD de categorias e produtos com soft delete
- Validações de formulário
- Filtros, buscas e paginação
- Sincronização de relacionamentos
- Queries customizadas do Doctrine
- **Upload de imagens** (validação, substituição, deleção)
- Casos de erro e edge cases

- Iniciar: `docker compose up --build -d`
- Parar: `docker compose down`
- Parar e remover volumes: `docker compose down -v`
- Logs da aplicação: `docker compose logs -f laminas`
- Logs do MySQL: `docker compose logs -f mysql`
