# Mini ERP Laminas

Projeto de estudo utilizando Laminas MVC + Doctrine ORM + MySQL.

O objetivo é construir um mini ERP simples com autenticação, categorias, produtos e pedidos, servindo como laboratório para aprender Laminas, Doctrine, arquitetura MVC e relacionamentos entre entidades.

## Stack

* PHP 8.3
* Laminas MVC
* Doctrine ORM
* MySQL 8
* Docker + Docker Compose
* Apache

## Requisitos

Antes de começar, você precisa ter instalado:

* Docker
* Docker Compose
* Git

## Clonando o projeto

```bash
git clone URL_DO_REPOSITORIO
cd mini-erp-laminas
```

## Subindo o ambiente

Suba os containers:

```bash
docker compose up --build -d
```

Verifique se os containers estão rodando:

```bash
docker compose ps
```

## Entrando no container da aplicação

```bash
docker compose exec laminas bash
```

## Instalando as dependências do projeto

Dentro do container:

```bash
composer install
```

Caso seja a primeira vez rodando o projeto, instale também os pacotes principais:

```bash
composer require doctrine/doctrine-orm-module
composer require laminas/laminas-db laminas/laminas-form laminas/laminas-session
```

## Configuração do banco

O projeto utiliza MySQL via Docker.

Credenciais padrão:

```txt
Host: mysql
Port: 3306
Database: mini_erp_laminas
User: laminas
Password: laminas
```

Para acesso externo pelo DBeaver:

```txt
Host: 127.0.0.1
Port: 3307
Database: mini_erp_laminas
User: laminas
Password: laminas
```

## Criando o banco e tabelas

Dentro do container da aplicação:

```bash
php vendor/bin/doctrine-module orm:info
php vendor/bin/doctrine-module orm:schema-tool:update --dump-sql
php vendor/bin/doctrine-module orm:schema-tool:update --force
```

## Rodando o projeto

A aplicação ficará disponível em:

```txt
http://localhost:8080
```

## Estrutura principal

```txt
module/Application/src/Controller
module/Application/src/Entity
module/Application/src/Form
module/Application/src/Service
module/Application/view/application
config/autoload
```

## Resetando completamente o ambiente

Caso precise apagar o banco e recriar tudo:

```bash
docker compose down -v
docker compose up --build -d
```

## Comandos úteis

Entrar no container:

```bash
docker compose exec laminas bash
```

Ver logs:

```bash
docker compose logs -f
```

Ver logs apenas do MySQL:

```bash
docker compose logs mysql
```

Ver logs apenas da aplicação:

```bash
docker compose logs laminas
```

Parar containers:

```bash
docker compose down
```

Parar containers e apagar volumes:

```bash
docker compose down -v
```