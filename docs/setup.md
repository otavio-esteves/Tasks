# Instalação e validação do Tasks

O ambiente de desenvolvimento usa PHP 8.2, Laravel Sail, PostgreSQL 18 e Redis. É necessário ter Docker com Compose disponível.

## Primeira instalação

Antes de executar Sail, instale as dependências que fornecem `vendor/bin/sail`:

```bash
cp .env.example .env
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$PWD:/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --no-interaction --prefer-dist
```

Defina `WWWUSER` e `WWWGROUP` no `.env` com os valores de `id -u` e `id -g`. Ajuste `APP_PORT`, `FORWARD_DB_PORT` e `FORWARD_REDIS_PORT` se as portas padrão já estiverem em uso.

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm ci
./vendor/bin/sail npm run dev
```

O nome padrão é **Tasks**. `APP_NAME` permite personalizar o nome mostrado no título, no login, na navegação e na tela Sobre. Cada instalação atende uma organização, que pode criar suas próprias equipes e categorias. Equipes são unidades internas de acesso; não são organizações independentes com isolamento de tenants.

## Atualizar uma instalação existente

Mantenha o `.env` e as credenciais do banco existentes. Altere `APP_NAME` para `Tasks` e execute:

```bash
./vendor/bin/sail composer install
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

A migration `2026_09_05_000000_rename_organization_task_tables` renomeia as tabelas `secretariats`, `service_orders`, `ods_checklists` e `ods_histories` para `teams`, `tasks`, `task_checklists` e `task_histories`, assim como as respectivas chaves estrangeiras. As migrations anteriores permanecem intactas para suportar bancos existentes e instalações novas.

IDs, vínculos, histórico, exclusões lógicas e códigos existentes são preservados. Tarefas novas recebem códigos `TASK-000001` (baseados no ID). Códigos antigos `ODS-…` continuam pesquisáveis. O método `down()` reverte somente os nomes do esquema, preservando os dados.

As URLs principais são `/admin/equipes` e `/equipes/{team}/tarefas`. URLs antigas de secretarias redirecionam para as novas telas, com autenticação e autorização por equipe.

## Validação

```bash
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail composer analyze
```

A suíte inclui criação e edição de tarefas, checklist, histórico, filtros, autorização por equipe e migração reversível com preservação dos dados. Falhas por ferramentas ou extensões ausentes no host não substituem a validação no Sail.
