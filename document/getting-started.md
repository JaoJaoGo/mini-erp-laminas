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

## Usuário inicial

A aplicação não fornece cadastro de usuários pela interface. É necessário criar o primeiro usuário diretamente no banco de dados ou por um script PHP.

Um exemplo de inserção usando SQL:

```sql
INSERT INTO users (name, email, password, is_active, created_at, updated_at)
VALUES ('Admin', 'admin@example.com', '<hash_da_senha>', 1, NOW(), NOW());
```

Use `password_hash('sua_senha', PASSWORD_DEFAULT)` em PHP para gerar o valor de `<hash_da_senha>`.

## Executando a aplicação

A aplicação fica disponível em:

```txt
http://localhost:8080
```

## Comandos Docker úteis

- Iniciar: `docker compose up --build -d`
- Parar: `docker compose down`
- Parar e remover volumes: `docker compose down -v`
- Logs da aplicação: `docker compose logs -f laminas`
- Logs do MySQL: `docker compose logs -f mysql`
