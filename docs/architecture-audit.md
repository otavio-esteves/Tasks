# Auditoria de Arquitetura

Este documento registra o que foi identificado anteriormente e o que já foi corrigido no código atual.

## 1. Estado atual

O projeto está hoje alinhado com uma Clean Architecture pragmática para o escopo existente:

- `Livewire` foi mantido fino nos fluxos principais;
- `Application` concentra casos de uso e DTOs;
- `Domain` contém enum e exceptions de negócio;
- `Infrastructure` implementa persistência via contratos;
- `Models` Eloquent ficaram restritos a persistência, scopes simples e transições simples de estado.

Também existe proteção automatizada básica contra regressões arquiteturais em:

- `tests/Unit/ArchitectureTest.php`

## 2. Correções realizadas

### 2.1. Fluxo de `Task` desacoplado de Eloquent na camada de aplicação

Corrigido.

Situação atual:

- `CreateTask`, `UpdateTask`, `GetTask`, `ListTasks` e `DeleteTask` dependem de `TaskRepository`;
- validação de categoria depende de `CategoryRepository`;
- implementações concretas vivem em `app/Infrastructure/Persistence/Eloquent`.

Impacto:

- menor acoplamento da `Application` com Eloquent;
- centralização do escopo por equipe;
- regras críticas mais testáveis.

### 2.2. Regra “categoria pertence à equipe” centralizada

Corrigido.

Situação atual:

- a regra foi extraída para `EnsureCategoryBelongsToTeam`;
- criação e edição de Tarefa usam a mesma validação.

### 2.3. Módulos administrativos tirados de persistência direta no Livewire

Corrigido.

Situação anterior:

- `CategoryManager` e `TeamManager` persistiam direto com Eloquent.

Situação atual:

- `CategoryManager` usa `SaveCategory`, `GetCategory` e `DeleteCategory`;
- `TeamManager` usa `SaveTeam`, `GetTeam` e `DeleteTeam`;
- persistência foi movida para contratos e repositórios Eloquent.

### 2.4. DTOs padronizados e tipados

Corrigido.

Situação atual:

- DTOs de mutação seguem padrão `Create...Data` e `Update...Data`;
- `Task` usa `ChecklistItemData` em vez de checklist anônimo trafegando internamente pela `Application`;
- os DTOs são `readonly`.

### 2.5. Tratamento de exceptions padronizado na UI

Corrigido.

Situação atual:

- exceptions de domínio continuam sem dependência de HTTP;
- componentes Livewire usam `InteractsWithFriendlyExceptions` para converter exceptions em mensagens de tela.

### 2.6. Autorização reforçada por rota, policy, Livewire e caso de uso crítico

Corrigido e coberto.

Situação atual:

- rotas usam middleware e `can(...)`;
- components Livewire usam `authorize(...)`;
- Tarefa continua protegida por escopo de equipe no caso de uso e no repositório;
- testes cobrem rota, ação Livewire, admin, usuário de equipe e convidado.

### 2.7. Models revisados

Corrigido no escopo necessário.

Situação atual:

- relacionamentos tipados;
- scopes pequenos;
- `Task` mantém enum e transição simples de status;
- factories foram ajustadas para facilitar testes.

## 3. O que continua intencionalmente simples

Nem tudo foi abstraído, por decisão pragmática:

- `Task` ainda gera o código e controla a transição simples de status no model;
- `User::isAdmin()` e `User::belongsToTeam()` continuam no model por serem regras pequenas e estáveis;
- `TaskManager` ainda concentra estado de tela e pequenas regras de checklist estritamente de UI.

Esses pontos não foram movidos porque hoje não configuram regra complexa de aplicação.

## 4. Limitações conhecidas que permanecem

As limitações abaixo ainda existem ou não foram atacadas porque não eram necessárias para o estado atual:

- o projeto não usa uma ferramenta especializada de testes de arquitetura; os guards atuais são leves e baseados em scan;
- a decisão de destino pós-login continua distribuída entre fluxo de autenticação e rota `/dashboard`;
- há documentação histórica em `docs/` que serve como referência antiga, não como fonte oficial do estado atual.

## 5. Fonte oficial para evolução

Para evoluir o projeto com segurança, use nesta tarefa:

1. `README.md`
2. `docs/architecture-guidelines.md`
3. `tests/Unit/ArchitectureTest.php`
4. testes de feature do módulo alterado

## 6. Validação atual

No estado documentado aqui, a suíte completa passa com:

```bash
./vendor/bin/sail artisan test
```

Os testes de arquitetura rodam com:

```bash
./vendor/bin/sail artisan test tests/Unit/ArchitectureTest.php
```
