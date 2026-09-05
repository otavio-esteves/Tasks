# Evolução do Tasks

## Base atual

- Laravel 12 e Livewire 3 com autenticação e verificação de e-mail.
- Casos de uso e DTOs para equipes, categorias e tarefas.
- Repositórios Eloquent, policies, checklists, histórico e filtros.
- Testes de domínio, interface, autorização e arquitetura.

## Generalização para organizações

- Marca Tasks configurável por `APP_NAME`.
- Equipes substituem unidades municipais; tarefas substituem ordens de serviço.
- Classes, namespaces, rotas, eventos Livewire e formulários usam `Team` e `Task`.
- Migration reversível renomeia tabelas e chaves sem remover registros nem alterar códigos existentes.
- Exemplos usam departamentos e atividades que podem ser adaptados à organização.

## Próximas evoluções possíveis

- Atribuição de responsáveis a tarefas.
- Administração explícita de perfis e do provisionamento de usuários.
- Isolamento entre organizações, se houver necessidade de múltiplos clientes na mesma instalação.

Consulte [os requisitos](tasks-requirements.md), [a arquitetura](architecture-guidelines.md) e [o guia de instalação](setup.md) antes de implementar novas funcionalidades.
