# Tasks

[![CI](https://github.com/otavio-esteves/Tasks/actions/workflows/main.yml/badge.svg)](https://github.com/otavio-esteves/Tasks/actions/workflows/main.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Aplicação Laravel 12 + Livewire 3 para organizar o trabalho de qualquer organização:

- equipes;
- categorias;
- tarefas (`Task`).

O projeto hoje usa uma Clean Architecture pragmática:

- `Livewire` cuida de tela, autorização, validação simples e mensagens;
- `Application` concentra casos de uso e DTOs;
- `Domain` concentra enum e exceptions de negócio;
- `Infrastructure` implementa persistência Eloquent via contratos;
- `Models` Eloquent ficam com relacionamentos, casts, scopes simples e transições simples de estado.

## Requisitos

- PHP `^8.2`
- Composer
- Node.js 24 LTS
- Docker + Sail recomendados para ambiente local
- PostgreSQL 18 e Redis (configurados no Sail)

## Subindo o projeto

Siga o [guia de instalação e atualização](docs/setup.md). Na primeira instalação, o Composer precisa instalar as dependências antes de executar `vendor/bin/sail`.

O Tasks atende uma organização por instalação, com equipes e categorias definidas por ela. Uma equipe pode representar um departamento, área ou grupo de trabalho. O nome exibido pode ser personalizado via `APP_NAME`, cujo padrão é `Tasks`.

A refatoração preserva os dados existentes com uma migration reversível. Tarefas novas usam o prefixo `TASK-`; códigos antigos continuam válidos. Consulte o guia antes de atualizar uma instalação existente.

## Comandos úteis

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail artisan route:list
```

## Validação oficial

A validação oficial do projeto deve ser feita via Laravel Sail.

Use esta sequência:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail composer analyze
```

O host local não é a referência oficial para validar o projeto.

Isso significa que:

- erros por falta de extensões PHP no host, como `dom`, `xml` ou `xmlwriter`, não invalidam o projeto por si só;
- ausência de `npm` no host também não invalida o projeto por si só;
- se os comandos via Sail passam, o projeto deve ser considerado válido no ambiente oficial de desenvolvimento.

## Perfis e autorização

- `admin`: usuário com `is_admin = true`, concedido explicitamente pelo operador do servidor.
- `usuário de equipe`: usuário vinculado a uma `team`.
- `aguardando acesso`: usuário sem equipe e sem privilégio administrativo.

O cadastro público nunca concede privilégios administrativos nem escolhe uma equipe. A verificação de e-mail também não concede esses acessos. Consulte [o procedimento de segurança](docs/security.md) antes de atualizar instalações existentes.

Regras atuais:

- admin acessa áreas administrativas e qualquer painel de tarefas;
- usuário de equipe não acessa área administrativa;
- usuário de equipe só acessa e manipula tarefas da própria equipe;
- categoria usada em uma tarefa deve pertencer à mesma equipe.
- equipes com usuários, categorias ou tarefas ativas não podem ser excluídas;
- categorias com tarefas ativas não podem ser movidas entre equipes;
- mudanças de status de tarefa passam exclusivamente por `ChangeTaskStatus` e geram histórico.

A autorização é aplicada em:

- rotas em `routes/web.php`;
- policies em `app/Policies`;
- componentes Livewire com `authorize(...)`;
- casos de uso críticos de `Task`, que validam coerência de equipe, categoria e tarefa.

## Camadas do projeto

### Interface/UI

Arquivos em:

- `app/Livewire`
- `resources/views`
- `routes/web.php`

Responsável por:

- capturar input;
- validar input simples de tela;
- chamar `authorize(...)`;
- executar casos de uso;
- mostrar mensagens;
- renderizar a resposta.

### Application

Arquivos em:

- `app/Application`

Responsável por:

- casos de uso;
- DTOs;
- contratos de persistência;
- resultados de listagem.

Exemplos atuais:

- `CreateTask`
- `UpdateTask`
- `ListTasks`
- `SaveCategory`
- `SaveTeam`

### Domain

Arquivos em:

- `app/Domain`

Responsável por:

- enum `TaskStatus`;
- exceptions de negócio.

### Infrastructure

Arquivos em:

- `app/Infrastructure`

Responsável por implementar contratos da `Application` com Eloquent:

- `EloquentTaskRepository`
- `EloquentCategoryRepository`
- `EloquentTeamRepository`

### Persistence

Arquivos em:

- `app/Models`
- `database/factories`
- `database/migrations`

Responsável por:

- relacionamentos;
- casts;
- scopes simples;
- factories;
- transição simples de status no model `Task`.

## Fluxo de Task

### Listagem

1. A rota `/equipes/{team}/tarefas` valida acesso com policy.
2. O componente [TaskManager](./app/Livewire/Team/TaskManager.php) valida `view` e `viewAny`.
3. O componente chama `ListTasks`.
4. O caso de uso usa `TaskRepository`.
5. A implementação Eloquent aplica escopo por equipe, busca e paginação.

### Criação

1. O Livewire monta `CreateTaskData`.
2. O componente chama `CreateTask`.
3. O caso de uso valida a categoria com `EnsureCategoryBelongsToTeam`.
4. O repositório persiste a Tarefa e o checklist.
5. O model `Task` define o status inicial e gera o código final a partir do `id`.

### Edição

1. O Livewire carrega a Tarefa com `GetTask`, sempre escopado por equipe.
2. O formulário é preenchido com `UpdateTaskData::fromTask(...)`.
3. O componente chama `UpdateTask`.
4. O caso de uso revalida a categoria da equipe e atualiza a Tarefa.

### Exclusão

1. O Livewire chama `DeleteTask`.
2. O caso de uso usa `GetTask` para garantir o escopo da equipe.
3. A exclusão é `soft delete`.

## DTOs usados hoje

Padrões atuais:

- `Create...Data`
- `Update...Data`
- `...ListResult`

Exemplos reais:

- `CreateTaskData`
- `UpdateTaskData`
- `CreateCategoryData`
- `UpdateCategoryData`
- `CreateTeamData`
- `UpdateTeamData`
- `TaskListResult`

## Como evoluir com segurança

### Criar nova feature

1. Defina a intenção da feature.
2. Crie ou ajuste uma policy se houver novo recurso protegido.
3. Crie DTOs em `app/Application/.../Data` se houver input estruturado.
4. Crie um caso de uso em `app/Application`.
5. Se houver persistência relevante, adicione contrato na `Application` e implementação em `Infrastructure`.
6. Deixe o Livewire fino: estado, validação simples, autorização, chamada do use case e mensagens.
7. Cubra o comportamento com teste de feature e, se fizer sentido, com teste de arquitetura.

### Criar novo caso de uso

Padrão atual:

- nome verbal e explícito;
- método público `handle(...)`;
- dependência de contratos, não de UI;
- retorno de model, DTO ou result object, conforme o caso.

Exemplo de sequência:

1. criar DTOs;
2. criar contrato, se necessário;
3. implementar o use case;
4. implementar adaptador Eloquent em `Infrastructure`;
5. registrar binding em `AppServiceProvider`;
6. usar pelo componente Livewire.

### Criar teste

Tipos usados hoje:

- `Feature` para fluxo real com HTTP, Livewire, autorização e banco;
- `Unit/ArchitectureTest` para guards arquiteturais leves.

Ao adicionar comportamento crítico:

1. cubra o fluxo principal em `tests/Feature`;
2. cubra autorização quando houver recurso protegido;
3. cubra isolamento por equipe quando a feature tocar dados multi-equipe;
4. atualize o teste de arquitetura se surgir nova regra estrutural simples de proteger.

## Qualidade Estática

O projeto utiliza **Pint** para estilo de código e **Larastan (PHPStan)** para análise estática.

```bash
./vendor/bin/sail composer lint     # Corrige estilo de código
./vendor/bin/sail composer analyze  # Executa análise estática (Nível 5)
```

Configurações:
- Pint: regras de estilo padrão do Laravel.
- `phpstan.neon`: Configuração da análise estática.

## Testes existentes

A suíte cobre hoje:

- autenticação;
- autorização por perfil;
- acesso por equipe;
- CRUD administrativo via Livewire;
- fluxo de tarefas;
- transição de status;
- guards de arquitetura.

Rodando tudo:

```bash
./vendor/bin/sail artisan test
```

## Documentação relacionada

- [Diretrizes arquiteturais](./docs/architecture-guidelines.md)
- [Auditoria arquitetural](./docs/architecture-audit.md)
- [Instalação e validação](./docs/setup.md)
- [Instruções para agentes](./AGENTS.md)
