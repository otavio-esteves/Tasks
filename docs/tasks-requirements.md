# Requisitos do Tasks

O Tasks organiza demandas de qualquer organização por equipes e categorias, com prioridades, prazos, checklists e histórico. Cada instalação atende uma organização.

## Funcionalidades

- Administradores cadastram equipes (por exemplo, Operações, Atendimento e Financeiro) e categorias próprias de cada equipe.
- Usuários vinculados a uma equipe acessam somente as tarefas dessa equipe; administradores acessam todas as equipes.
- Tarefas têm título, categoria, localização opcional, observação, prazo opcional e prioridade. A categoria pertence à mesma equipe da tarefa.
- Tarefas novas recebem um código único `TASK-000001`, baseado no ID. Códigos de registros existentes são preservados.
- Os estados são pendente, em andamento e concluída, com criação em pendente por padrão.
- Checklists detalham etapas e permitem marcar ou desmarcar sua conclusão.
- O histórico registra criação, alterações e transições de status com o usuário responsável.
- A listagem oferece busca, paginação, filtros e contadores por equipe.
- Tarefas, equipes e categorias usam exclusão lógica.
- As telas protegidas exigem autenticação e e-mail verificado.

## Tecnologia e integridade

- PHP 8.2 ou superior, Laravel 12, Livewire 3/Volt, Blade, Tailwind e Vite.
- Ambiente oficial com Laravel Sail, PostgreSQL e Redis.
- Policies controlam acesso; casos de uso validam coerência entre equipe, categoria e tarefa.
- Chaves estrangeiras preservam vínculos, e transações mantêm tarefa, checklist e histórico consistentes.
- A suíte cobre regras de negócio, isolamento por equipe e migração do esquema anterior.

## Estrutura de dados

- `teams`: equipes internas da organização.
- `users.team_id`: vínculo do usuário à equipe; vínculo nulo significa ausência de equipe, sem conceder privilégios.
- `users.is_admin`: privilégio administrativo explícito, falso por padrão.
- `categories.team_id`: categorias da equipe.
- `tasks`: tarefas vinculadas à equipe e à categoria.
- `task_checklists.task_id`: etapas da tarefa.
- `task_histories.task_id`: histórico da tarefa, com autoria e metadados.

## Evoluções futuras

Atribuição de um responsável à tarefa e hospedagem de múltiplas organizações isoladas na mesma instalação exigem funcionalidades próprias; não fazem parte do comportamento atual.
