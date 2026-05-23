# Escalada para o Céu

Sistema web para organização de grupos, missas, reuniões, escalas, presença e substituições.

## Stack

- Laravel 13
- Inertia.js + React + TypeScript
- Tailwind CSS
- MySQL
- Queue + Scheduler do Laravel
- SMTP configurável por `.env`

## Requisitos

- PHP 8.3+
- Composer
- Node 20+
- npm
- MySQL 8+

## Instalação

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

## Configuração `.env`

Ajuste no `.env`:

- Banco MySQL (`DB_*`)
- SMTP (`MAIL_*`)
- Queue (`QUEUE_CONNECTION=database`)
- URL da aplicação (`APP_URL`)

## Migrar e popular

```bash
php artisan migrate --seed
```

## Usuários seedados

- Admin sistema: `admin@escalada.test` / `password`
- Coordenador: `coord@escalada.test` / `password`
- Membros: `membro1@escalada.test`, `membro2@escalada.test` / `password`

## Rodar local

```bash
composer run dev
```

## Fluxos implementados na interface

- Painel com visão de membro e visão de coordenador
- Calendário em lista mobile de missas e reuniões
- Cadastro por convite: `/convites/{token}`
- Gestão de grupos e geração de convite (membro/coordenador, com ou sem aprovação)
- Tela de eventos para criação de missa/reunião
- Pendências de presença com resposta rápida

## Queue e Scheduler

Rodar worker:

```bash
php artisan queue:work
```

Rodar scheduler:

```bash
php artisan schedule:work
```

Agendamentos implementados:

- lembrete 1 dia antes
- lembrete 1 hora antes
- cobrança de presença
- resumo 24h
- aviso de vagas

Emails enviados incluem: sistema, grupo, evento, data, horário, local, função e botão para abrir o sistema.
No aviso de vagas, os membros aprovados do grupo recebem as funções em aberto da missa.

## Testes

```bash
php artisan test
```

## Build

```bash
npm run build
```

## Comandos finais obrigatórios

```bash
php artisan migrate:fresh --seed
php artisan test
npm run build
```
