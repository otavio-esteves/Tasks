# Arquitetura

## Visão geral

O projeto segue uma separação pragmática entre interface, aplicação e domínio, sem introduzir abstrações desnecessárias.

## Camadas

### `app/Livewire`

Responsável por:

- receber input do usuário
- aplicar autorização
- chamar casos de uso
- lidar com mensagens de sucesso/erro
- renderizar views

Componentes Livewire não devem concentrar regra de negócio complexa nem montar queries extensas diretamente.

### `app/Application`

Responsável por:

- coordenar casos de uso
- validar contexto operacional
- encapsular listagens reutilizáveis
- organizar DTOs simples de entrada e saída

Exemplos atuais:

- `CreateServiceOrder`
- `UpdateServiceOrder`
- `GetServiceOrder`
- `ListServiceOrders`
- `ListCategories`
- `ListSecretariats`

### `app/Domain`

Responsável por:

- regras centrais de negócio
- enums
- exceções de domínio
- invariantes que não devem depender da UI

Exemplos atuais:

- `ServiceOrderStatus`
- exceções de `ServiceOrder`

### `app/Models`

Responsável por:

- persistência Eloquent
- relacionamentos
- casts
- scopes simples
- regras pequenas que pertencem ao agregado persistido

Exemplo atual:

- `ServiceOrder` concentra geração do código final a partir do `id` e transição explícita de status

### `app/Policies`

Responsável por:

- autorização explícita
- isolamento entre admin e usuários de secretaria

## Onde ficam as regras de domínio

As regras devem ficar no ponto mais próximo da invariável que protegem.

- regra de transição de status: domínio/modelo de `ServiceOrder`
- regra de categoria pertencer à mesma secretaria: caso de uso de `ServiceOrder`
- regra de acesso a recursos: `Policies`

Evitar colocar essas regras:

- só em Blade
- só em redirect de rota
- só em evento de interface

## Onde ficam os casos de uso

Casos de uso ficam em `app/Application/<Contexto>`.

Critério prático:

- se a operação tem entrada clara, regra própria e pode ser chamada fora da UI, ela deve virar caso de uso
- se a consulta é repetida ou tem paginação/filtro relevante, ela pode virar classe de listagem

## Como evitar regressão de acoplamento

- não mover regra de negócio de volta para Livewire
- não depender de `findOrFail($id)` sem escopo/autorização
- não confiar em navegação para segurança
- não duplicar consultas complexas em múltiplos componentes
- não expor exceções técnicas cruas para o usuário
- sempre que uma regra crítica for adicionada, criar teste correspondente

## Fluxo recomendado para novas features

1. Definir regra de autorização
2. Definir invariantes de domínio
3. Criar ou ajustar caso de uso
4. Manter a UI como casca fina
5. Cobrir com teste de feature ou integração

## Limites atuais

- A arquitetura segue uma separação clara onde `Application` depende de contratos e `Infrastructure` fornece a implementação Eloquent.
- Alguns documentos antigos do projeto podem descrever roadmap futuro e divergir do estado atual do código; considere o código e o README como fontes de verdade.
- O checklist de ODS está funcional para persistência básica; novas regras de validação do checklist podem ser adicionadas futuramente.
