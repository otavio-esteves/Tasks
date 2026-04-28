# Prefeitura Connect

Aplicação Laravel 12 + Livewire 3 para gestão municipal de:

- secretarias;
- categorias;
- ordens de serviço (`ServiceOrder` / `ODS`).

O projeto hoje usa uma Clean Architecture pragmática:

- `Livewire` cuida de tela, autorização, validação simples e mensagens;
- `Application` concentra casos de uso e DTOs;
- `Domain` concentra enum e exceptions de negócio;
- `Infrastructure` implementa persistência Eloquent via contratos;
- `Models` Eloquent ficam com relacionamentos, casts, scopes simples e transições simples de estado.

## Requisitos

- PHP `^8.2`
- Composer
- Node.js
- Docker + Sail recomendados para ambiente local
- banco compatível com Laravel 12

## Subindo o projeto

O ambiente oficial do projeto é o Docker com Laravel Sail.

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail php artisan key:generate
./vendor/bin/sail php artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

## Comandos úteis

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail php artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail php artisan route:list
```

## Validação oficial

A validação oficial do projeto deve ser feita via Laravel Sail.

Use esta sequência:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail php artisan test
./vendor/bin/sail pint --test
```

O host local não é a referência oficial para validar o projeto.

Isso significa que:

- erros por falta de extensões PHP no host, como `dom`, `xml` ou `xmlwriter`, não invalidam o projeto por si só;
- ausência de `npm` no host também não invalida o projeto por si só;
- se os comandos via Sail passam, o projeto deve ser considerado válido no ambiente oficial de desenvolvimento.

## Perfis e autorização

- `admin`: usuário com `secretariat_id = null`
- `usuário de secretaria`: usuário vinculado a uma `secretariat`

Regras atuais:

- admin acessa áreas administrativas e qualquer painel de ODS;
- usuário de secretaria não acessa área administrativa;
- usuário de secretaria só acessa e manipula ODS da própria secretaria;
- categoria usada em ODS deve pertencer à mesma secretaria.

A autorização é aplicada em:

- rotas em `routes/web.php`;
- policies em `app/Policies`;
- componentes Livewire com `authorize(...)`;
- casos de uso críticos de `ServiceOrder`, que validam coerência de secretaria, categoria e ordem.

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

- `CreateServiceOrder`
- `UpdateServiceOrder`
- `ListServiceOrders`
- `SaveCategory`
- `SaveSecretariat`

### Domain

Arquivos em:

- `app/Domain`

Responsável por:

- enum `ServiceOrderStatus`;
- exceptions de negócio.

### Infrastructure

Arquivos em:

- `app/Infrastructure`

Responsável por implementar contratos da `Application` com Eloquent:

- `EloquentServiceOrderRepository`
- `EloquentCategoryRepository`
- `EloquentSecretariatRepository`

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
- transição simples de status no model `ServiceOrder`.

## Fluxo de ServiceOrder

### Listagem

1. A rota `/secretarias/{secretariat}/ods` valida acesso com policy.
2. O componente [ServiceOrderManager](./app/Livewire/Secretariat/ServiceOrderManager.php) valida `view` e `viewAny`.
3. O componente chama `ListServiceOrders`.
4. O caso de uso usa `ServiceOrderRepository`.
5. A implementação Eloquent aplica escopo por secretaria, busca e paginação.

### Criação

1. O Livewire monta `CreateServiceOrderData`.
2. O componente chama `CreateServiceOrder`.
3. O caso de uso valida a categoria com `EnsureCategoryBelongsToSecretariat`.
4. O repositório persiste a ODS e o checklist.
5. O model `ServiceOrder` define o status inicial e gera o código final a partir do `id`.

### Edição

1. O Livewire carrega a ODS com `GetServiceOrder`, sempre escopado por secretaria.
2. O formulário é preenchido com `UpdateServiceOrderData::fromServiceOrder(...)`.
3. O componente chama `UpdateServiceOrder`.
4. O caso de uso revalida a categoria da secretaria e atualiza a ODS.

### Exclusão

1. O Livewire chama `DeleteServiceOrder`.
2. O caso de uso usa `GetServiceOrder` para garantir o escopo da secretaria.
3. A exclusão é `soft delete`.

## DTOs usados hoje

Padrões atuais:

- `Create...Data`
- `Update...Data`
- `...ListResult`

Exemplos reais:

- `CreateServiceOrderData`
- `UpdateServiceOrderData`
- `CreateCategoryData`
- `UpdateCategoryData`
- `CreateSecretariatData`
- `UpdateSecretariatData`
- `ServiceOrderListResult`

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
3. cubra isolamento por secretaria quando a feature tocar dados multi-secretaria;
4. atualize o teste de arquitetura se surgir nova regra estrutural simples de proteger.

## Qualidade Estática

O projeto utiliza **Pint** para estilo de código e **Larastan (PHPStan)** para análise estática.

```bash
./vendor/bin/sail composer lint     # Corrige estilo de código
./vendor/bin/sail composer analyze  # Executa análise estática (Nível 5)
```

Configurações:
- `pint.json`: Regras de estilo (Laravel padrão).
- `phpstan.neon`: Configuração da análise estática.

## Testes existentes

A suíte cobre hoje:

- autenticação;
- autorização por perfil;
- acesso por secretaria;
- CRUD administrativo via Livewire;
- fluxo de ODS;
- transição de status;
- guards de arquitetura.

Rodando tudo:

```bash
./vendor/bin/sail php artisan test
```

## Documentação relacionada

- [Diretrizes arquiteturais](./docs/architecture-guidelines.md)
- [Auditoria arquitetural](./docs/architecture-audit.md)
