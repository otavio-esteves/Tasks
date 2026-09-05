# AGENTS.md

## Objetivo deste arquivo

Este arquivo contém instruções permanentes para o Codex trabalhar neste projeto.

Leia este arquivo antes de alterar qualquer código.

O objetivo é garantir que o Codex:
- entenda a estrutura do projeto;
- use os comandos corretos;
- respeite o padrão visual definido;
- não altere regras de negócio sem necessidade;
- rode os testes corretos;
- explique claramente o que foi feito.

---

# 1. Contexto do projeto

Este é um projeto Laravel com front-end baseado em Blade, Livewire e/ou componentes reutilizáveis.

O projeto pode usar:
- Laravel;
- Blade;
- Livewire;
- Tailwind CSS;
- Vite;
- Laravel Sail;
- Composer;
- NPM;
- PHPUnit/Pest.

Antes de modificar arquivos, inspecione a estrutura real do projeto.

Não assuma versões ou bibliotecas sem verificar os arquivos do repositório, como:

- `composer.json`;
- `package.json`;
- `vite.config.js`;
- `tailwind.config.js`;
- `routes/web.php`;
- `app/`;
- `resources/views/`;
- `resources/css/`;
- `resources/js/`;
- `database/migrations/`;
- `tests/`.

---

# 2. Regras gerais para o Codex

## Antes de alterar código

Antes de implementar qualquer tarefa:

1. Leia este `AGENTS.md`.
2. Inspecione a estrutura do projeto.
3. Identifique os arquivos relevantes.
4. Entenda o padrão já existente.
5. Faça um plano curto antes de modificar.
6. Só depois aplique as alterações.

## Durante a implementação

- Faça mudanças pequenas e coerentes.
- Prefira reaproveitar componentes existentes.
- Evite duplicação de código.
- Não altere regra de negócio sem pedido explícito.
- Não altere migrations antigas sem necessidade clara.
- Não remova funcionalidades existentes.
- Não faça refatorações gigantescas fora do escopo solicitado.
- Não altere nomes de tabelas, colunas ou relacionamentos sem necessidade.
- Não introduza dependências novas sem justificar.
- Não altere configurações sensíveis sem explicar.

## Regras arquiteturais obrigatórias

Considere [docs/architecture-guidelines.md](docs/architecture-guidelines.md) como a referência oficial de arquitetura do projeto.

Resumo operacional:

- Domain:
  - não depende de Laravel, Eloquent, Livewire, Blade, HTTP, Request, Auth ou container;
  - concentra regras centrais, invariantes, enums, value objects e exceptions de domínio.
- Application:
  - orquestra casos de uso;
  - recebe e devolve DTOs claros;
  - pode depender de Domain e de contratos abstratos;
  - não renderiza UI nem contém detalhe de Livewire.
- Infrastructure:
  - implementa contratos definidos pela Application;
  - concentra integrações e detalhes técnicos.
- Interface/UI:
  - inclui Livewire, Blade, routes e controllers HTTP;
  - Livewire deve ser fino: input, autorização, chamada de use case e renderização;
  - não colocar regra de negócio complexa ou persistência complexa direto em componente.
- Persistence:
  - inclui Eloquent models, migrations, factories e seeders;
  - models não devem concentrar regras complexas de aplicação.

Direção de dependência:

- Domain não depende de camadas externas.
- Application depende de Domain.
- Infrastructure implementa contratos da Application.
- Interface/UI chama Application.
- Persistence contém detalhes de Eloquent.

Convenções obrigatórias:

- use cases com nomes verbais e explícitos, por exemplo `CreateTask`, `UpdateTask`, `ListTasks`;
- DTOs na Application com nomes como `*Data`, `*Input`, `*Output` ou `*Result`;
- exceptions de domínio no Domain, com nomes semânticos e sem detalhe técnico;
- policies são a camada oficial de autorização de acesso;
- toda regra crítica nova deve vir acompanhada de teste.

Ao alterar arquitetura ou adicionar feature:

1. preserve comportamento atual;
2. prefira refatoração incremental;
3. não mova regra de volta para Livewire ou Blade;
4. não trate Eloquent model como substituto de use case;
5. não faça refatoração arquitetural grande sem pedido explícito.

## Após implementar

Ao final de cada tarefa:

1. Liste os arquivos alterados.
2. Explique resumidamente o que mudou.
3. Informe os comandos executados.
4. Informe se os testes passaram ou falharam.
5. Se algum comando falhar, mostre o erro real.
6. Não diga que testou se não conseguiu testar.

---

# 3. Comandos do projeto

## Ambiente local com Laravel Sail

Este projeto pode usar Laravel Sail no ambiente local.

Quando Sail estiver disponível, prefira os comandos abaixo.

### Subir containers

```bash
./vendor/bin/sail up -d
```

### Rodar testes

Use:

```bash
./vendor/bin/sail artisan test
```

ou:

```bash
./vendor/bin/sail test
```

Nunca use:

```bash
./vendor/bin/sail php artisan test
```

Esse comando está incorreto para este projeto.

### Rodar comandos Artisan

Use o formato:

```bash
./vendor/bin/sail artisan comando
```

Exemplos:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
```

### Composer com Sail

```bash
./vendor/bin/sail composer install
./vendor/bin/sail composer update
```

### NPM com Sail

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail npm run dev
```

---

# 4. Ambiente sem Sail

Se Sail não estiver disponível, tente usar comandos locais equivalentes.

## Instalar dependências PHP

```bash
composer install
```

## Configurar ambiente

Se não existir `.env`, copie:

```bash
cp .env.example .env
```

Depois gere a chave:

```bash
php artisan key:generate
```

## Rodar migrations

```bash
php artisan migrate
```

## Rodar testes

```bash
php artisan test
```

## Instalar dependências JS

```bash
npm install
```

## Build do front-end

```bash
npm run build
```

---

# 5. Se comandos falharem

Se algum comando falhar, não ignore.

Informe claramente:

- o comando executado;
- o erro recebido;
- a possível causa;
- o que ainda precisa ser feito.

Exemplos de falhas comuns:

## `vendor/bin/sail` não existe

Provável causa: dependências PHP ainda não foram instaladas.

Tente:

```bash
composer install
```

Depois tente novamente:

```bash
./vendor/bin/sail artisan test
```

## Docker não está rodando

Provável causa: Docker Desktop, Docker Engine ou serviço Docker está parado.

Informe o erro e recomende ao usuário rodar localmente:

```bash
docker ps
```

e depois:

```bash
./vendor/bin/sail up -d
```

## Permissão negada no Docker

Provável causa: usuário local não pertence ao grupo `docker`.

Não tente corrigir automaticamente sem autorização.

Informe que o usuário pode precisar executar:

```bash
sudo usermod -aG docker $USER
```

Depois será necessário sair e entrar novamente na sessão do Linux.

## Banco de dados indisponível

Se os testes falharem por banco de dados:

1. Verifique `.env`;
2. Verifique `.env.testing`;
3. Verifique se os containers estão ativos;
4. Verifique se as migrations foram executadas.

Não altere configuração de banco sem necessidade.

---

# 6. Codex local, Cloud e sandbox

## Codex local

Quando o Codex estiver rodando localmente no computador do usuário, ele pode conseguir usar Sail, Docker, Composer, NPM e PHP locais, dependendo das permissões.

Se um comando for bloqueado por sandbox ou aprovação, explique isso claramente.

Não invente que o comando foi executado.

## Codex Cloud ou ambiente remoto

Quando estiver em ambiente remoto/cloud, não assuma que Docker ou Sail estarão disponíveis.

Nesse caso, prefira comandos sem Sail:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan test
npm install
npm run build
```

Se faltar extensão PHP, serviço de banco, Node, NPM ou permissão, informe claramente.

---

# 7. Estrutura esperada do projeto

Verifique a estrutura real antes de agir, mas normalmente:

## Backend Laravel

- `app/Models/`
- `app/Http/Controllers/`
- `app/Http/Requests/`
- `app/Services/`
- `app/Actions/`
- `app/Policies/`
- `app/Providers/`
- `routes/web.php`
- `routes/api.php`
- `database/migrations/`
- `database/seeders/`
- `tests/Feature/`
- `tests/Unit/`

## Front-end

- `resources/views/`
- `resources/views/components/`
- `resources/views/layouts/`
- `resources/css/`
- `resources/js/`
- `public/`

## Livewire, se existir

- `app/Livewire/`
- `resources/views/livewire/`

---

# 8. Convenções de arquitetura

## Controllers

Controllers devem ser simples.

Evite colocar regra de negócio pesada dentro de controllers.

Prefira mover lógica para:

- Services;
- Actions;
- Form Requests;
- Policies;
- Models, quando fizer sentido;
- Query objects, quando houver consultas complexas.

## Requests

Validações devem ficar preferencialmente em Form Requests.

Evite validação extensa diretamente no controller.

## Models

Models podem conter:

- relacionamentos;
- casts;
- scopes;
- accessors/mutators simples;
- regras diretamente ligadas ao domínio do próprio model.

Evite models gigantes com responsabilidades demais.

## Services e Actions

Use Services ou Actions quando uma operação:

- tiver vários passos;
- for reutilizada;
- envolver regra de negócio;
- envolver persistência em múltiplas tabelas;
- precisar ficar testável.

## Policies

Use Policies para autorização quando o projeto já seguir esse padrão.

Não espalhe regras de autorização complexas diretamente em views ou controllers.

---

# 9. Convenções de banco de dados

## Migrations

- Não edite migrations antigas que já representam histórico do projeto, salvo se a tarefa pedir isso explicitamente.
- Para mudanças novas, crie nova migration.
- Use nomes claros.
- Defina foreign keys quando apropriado.
- Defina índices quando houver busca frequente por determinada coluna.
- Use cascade/restrict/nullOnDelete de forma consciente.

## Seeders

- Não altere seeders sem necessidade.
- Se criar dados de teste, mantenha-os pequenos e claros.

## Factories

- Prefira factories para testes.
- Não dependa de dados manuais para testes automatizados.

---

# 10. Convenções de testes

Sempre que possível, rode testes após alterações.

## Com Sail

```bash
./vendor/bin/sail artisan test
```

ou:

```bash
./vendor/bin/sail test
```

## Sem Sail

```bash
php artisan test
```

## Testes específicos

Se alterar uma funcionalidade específica, rode primeiro o teste relacionado.

Exemplo:

```bash
php artisan test --filter=NomeDoTeste
```

ou com Sail:

```bash
./vendor/bin/sail artisan test --filter=NomeDoTeste
```

## Se não houver testes

Se o projeto não tiver testes suficientes, informe isso claramente.

Não diga que a alteração está totalmente validada se não houver testes.

---

# 11. Convenções de front-end

## Objetivo visual

O front-end deve ter aparência administrativa, limpa, sóbria e profissional.

Evite estilo excessivamente arredondado, infantil ou “fofinho”.

A interface deve parecer um sistema de gestão organizacional.

## Regras visuais obrigatórias

- Usar cantos pouco arredondados.
- Preferir `border-radius` entre `4px` e `6px`.
- Evitar `rounded-xl`, `rounded-2xl` e `rounded-full`, salvo em casos específicos.
- Evitar sombras fortes.
- Evitar gradientes desnecessários.
- Evitar excesso de espaçamento.
- Manter densidade visual compacta.
- Manter alinhamento consistente.
- Manter tabelas legíveis.
- Manter formulários objetivos.
- Manter botões padronizados.
- Manter cards discretos.
- Evitar mudanças visuais isoladas que quebrem a consistência global.

## Tailwind CSS

Se o projeto usa Tailwind, prefira classes consistentes.

Use padrões parecidos com:

```html
rounded-md
border
bg-white
shadow-sm
text-sm
px-3
py-2
```

Evite, salvo quando necessário:

```html
rounded-2xl
rounded-3xl
shadow-xl
p-10
text-3xl
bg-gradient-to-r
```

## Componentização

Antes de repetir classes em várias telas, verifique se existem componentes como:

- botões;
- inputs;
- labels;
- cards;
- tabelas;
- layouts;
- sidebar;
- navbar;
- alerts;
- modais.

Prefira alterar componentes globais quando a mudança deve afetar o sistema inteiro.

---

# 12. Uso de imagens como referência visual

Quando uma imagem for fornecida como referência de design:

1. Analise a imagem antes de alterar o código.
2. Identifique:
   - layout;
   - proporções;
   - espaçamento;
   - bordas;
   - cores;
   - tipografia;
   - tamanho dos botões;
   - aparência dos cards;
   - aparência de tabelas;
   - aparência de formulários;
   - densidade visual.
3. Adapte o projeto para ficar visualmente próximo da referência.
4. Não copie apenas cores; copie também ritmo, hierarquia e espaçamento.
5. Evite criar componentes destoantes da referência.
6. Se houver múltiplas imagens, extraia um sistema visual comum entre elas.

## Regras específicas para referências visuais

- Se a imagem usa cantos pequenos, não usar cantos grandes.
- Se a imagem usa layout compacto, não aumentar demais os espaçamentos.
- Se a imagem usa visual administrativo, não transformar em landing page.
- Se a imagem usa botões discretos, não criar botões exagerados.
- Se a imagem usa cards simples, não criar cards com sombra pesada.
- Se a imagem mostra tabelas densas, manter tabelas densas e legíveis.

---

# 13. Layouts e componentes Blade

Ao mexer no front-end, procure primeiro por:

```text
resources/views/layouts/
resources/views/components/
resources/views/livewire/
resources/views/
```

Se existir layout global, como:

```text
app.blade.php
guest.blade.php
navigation.blade.php
sidebar.blade.php
```

avalie se a mudança deve ser feita nele.

Não faça alterações repetidas em dezenas de telas se um componente global resolver.

---

# 14. Livewire

Se o projeto usa Livewire:

- Não altere propriedades públicas sem verificar a view correspondente.
- Não remova métodos usados pelas views.
- Verifique nomes de eventos.
- Verifique validações.
- Verifique paginação, filtros e busca.
- Mantenha estado e comportamento existentes.

Depois de alterar componente Livewire, verifique:

- classe em `app/Livewire`;
- view em `resources/views/livewire`;
- rotas que usam o componente;
- testes, se existirem.

---

# 15. Segurança

Não introduza vulnerabilidades.

Preste atenção em:

- autorização;
- validação;
- mass assignment;
- upload de arquivos;
- exposição de dados sensíveis;
- SQL injection;
- XSS em Blade;
- CSRF;
- permissões por usuário;
- acesso indevido a recursos de outra equipe/organização/usuário.

## Blade

Evite imprimir conteúdo com `{!! !!}`.

Prefira:

```blade
{{ $valor }}
```

Só use `{!! !!}` quando houver motivo claro e sanitização adequada.

## Eloquent

Evite montar queries inseguras.

Prefira Query Builder/Eloquent com bindings.

---

# 16. Performance

Evite introduzir problemas como:

- N+1 queries;
- carregamento excessivo de dados;
- consultas sem paginação;
- loops pesados em views;
- repetição de queries dentro de Blade.

Use `with()` quando houver relacionamentos necessários.

Use paginação em listagens grandes.

---

# 17. Acessibilidade e usabilidade

Sempre que alterar UI:

- preserve labels em inputs;
- mantenha foco visível;
- use textos claros em botões;
- preserve contraste adequado;
- não dependa apenas de cor para indicar estado;
- mantenha mensagens de erro visíveis;
- mantenha navegação coerente.

---

# 18. Padrão de commits

Quando solicitado a criar mensagem de commit, use mensagens claras.

Formato recomendado:

```text
feat: adiciona checklist às tarefas
fix: corrige validação de formulário de Tarefa
refactor: reorganiza componentes do dashboard
style: ajusta padrão visual do front-end
test: adiciona testes para criação de checklist
```

Evite mensagens vagas como:

```text
ajustes
correções
update
mudanças
```

---

# 19. O que não fazer

Não faça:

- alteração de regra de negócio sem pedido;
- reescrita completa do projeto sem necessidade;
- alteração de migrations antigas sem justificativa;
- exclusão de testes;
- remoção de validações;
- remoção de autorização;
- alteração de nomes de tabelas sem necessidade;
- mudança visual destoante do padrão;
- instalação de pacotes sem justificar;
- comandos destrutivos sem autorização;
- `migrate:fresh` sem autorização;
- `db:wipe` sem autorização;
- exclusão de arquivos importantes;
- alteração em `.env` real com dados sensíveis.

---

# 20. Comandos destrutivos

Nunca execute automaticamente comandos como:

```bash
php artisan migrate:fresh
php artisan db:wipe
php artisan migrate:reset
rm -rf
git reset --hard
git clean -fd
```

Só use comandos destrutivos se o usuário pedir explicitamente.

Se achar necessário, explique o motivo e peça autorização.

---

# 21. Fluxo recomendado para tarefas

Para cada tarefa:

1. Entender o pedido.
2. Ler `AGENTS.md`.
3. Inspecionar arquivos relevantes.
4. Criar plano curto.
5. Implementar.
6. Rodar testes/build quando possível.
7. Corrigir problemas encontrados.
8. Resumir alterações.

---

# 22. Fluxo recomendado para mudanças de front-end

Quando a tarefa for visual:

1. Identificar layout global.
2. Identificar componentes reutilizáveis.
3. Verificar Tailwind/CSS existente.
4. Aplicar padrão global primeiro.
5. Ajustar telas específicas depois.
6. Rodar build do front-end.
7. Verificar se não quebrou Blade/Livewire.
8. Listar arquivos alterados.

Com Sail:

```bash
./vendor/bin/sail npm run build
```

Sem Sail:

```bash
npm run build
```

---

# 23. Fluxo recomendado para mudanças de backend

Quando a tarefa for backend:

1. Identificar model, controller, request, migration e testes relacionados.
2. Verificar regras existentes.
3. Implementar com menor impacto possível.
4. Adicionar ou ajustar testes quando fizer sentido.
5. Rodar testes.

Com Sail:

```bash
./vendor/bin/sail artisan test
```

Sem Sail:

```bash
php artisan test
```

---

# 24. Fluxo recomendado para novas tabelas

Quando precisar criar uma nova tabela:

1. Criar migration nova.
2. Criar model, se necessário.
3. Definir relacionamentos.
4. Definir fillable/casts.
5. Criar controller/action/service, se necessário.
6. Criar validação via Form Request, se fizer sentido.
7. Criar views/componentes.
8. Criar testes básicos.
9. Rodar migrations e testes.

Nunca altere migration antiga sem motivo claro.

---

# 25. Critérios de conclusão

Uma tarefa só deve ser considerada concluída quando:

- o código foi alterado conforme solicitado;
- não há mudança fora do escopo;
- os arquivos alterados foram listados;
- os testes foram executados ou a impossibilidade foi explicada;
- o build foi executado quando houve alteração de front-end;
- os erros encontrados foram relatados;
- a solução respeita este `AGENTS.md`.

---

# 26. Resposta final esperada do Codex

Ao finalizar, responda neste formato:

```text
Resumo:
- ...

Arquivos alterados:
- ...

Comandos executados:
- ...

Resultado dos testes/build:
- ...

Observações:
- ...
```

Se algum comando falhou:

```text
O comando abaixo falhou:

comando

Erro:
...

Possível causa:
...

Próximo passo sugerido:
...
```

---

# 27. Instrução especial sobre Sail

Sempre que precisar testar Laravel neste projeto, tente primeiro:

```bash
./vendor/bin/sail artisan test
```

ou:

```bash
./vendor/bin/sail test
```

Não use:

```bash
./vendor/bin/sail php artisan test
```

Se Sail não estiver disponível, use:

```bash
php artisan test
```

e explique que o teste foi executado fora do Sail.

---

# 28. Instrução especial sobre visual

O usuário prefere uma interface com:

- cantos menos arredondados;
- aparência administrativa;
- layout limpo;
- visual profissional;
- componentes consistentes;
- botões discretos;
- tabelas bem organizadas;
- formulários objetivos;
- baixa “fofura” visual;
- nada de cantos excessivamente arredondados.

Sempre respeite isso em alterações de front-end.
