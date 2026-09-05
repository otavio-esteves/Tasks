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

Tasks usa Laravel 12, Livewire 3/Volt, Blade, Tailwind, Vite e PHPUnit. O ambiente oficial é Sail com PHP 8.2, Node.js 24 LTS, PostgreSQL 18 e Redis.

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

As regras específicas de [docs/architecture-guidelines.md](docs/architecture-guidelines.md) prevalecem sobre recomendações genéricas de Laravel, inclusive neste arquivo. Leia também o README, [a auditoria arquitetural](docs/architecture-audit.md) e [o setup](docs/setup.md) antes de implementar.

A arquitetura atual é suficientemente madura. Preserve casos de uso com `handle(...)`, DTOs, contratos, repositórios e namespaces. Não introduza Actions, novas abstrações ou reorganizações por preferência estética. Refatorações arquiteturais exigem uma necessidade objetiva dentro da tarefa. Priorize desenvolvimento de produto.

Resumo operacional:

- Domain:
  - não depende de Laravel, Eloquent, Livewire, Blade, HTTP, Request, Auth ou container;
  - concentra regras centrais, invariantes, enums, value objects e exceptions de domínio.
- Application:
  - orquestra casos de uso;
  - usa DTOs para input estruturado e pode retornar models ou result objects conforme o padrão existente;
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

Use Sail como referência oficial de validação. A instalação inicial de `vendor/bin/sail` está documentada em [docs/setup.md](docs/setup.md).

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

O formato padronizado neste repositório é `sail artisan ...`.

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
./vendor/bin/sail composer analyze
./vendor/bin/sail pint --test
```

### NPM com Sail

```bash
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
./vendor/bin/sail npm run dev
```

---

## Validação e alternativas

Execute `composer install`, `npm ci`, `npm run build`, `artisan test`, `pint --test` e `composer analyze` via Sail. A suíte completa inclui `tests/Unit/ArchitectureTest.php`; para diagnóstico, execute esse arquivo diretamente. Não reduza o nível do PHPStan nem remova testes/assertions para obter sucesso.

Os guards arquiteturais atuais são scans leves: protegem Domain contra Eloquent/HTTP/Livewire, Application contra UI/HTTP e componentes Livewire críticos contra persistência direta. Não os trate como análise completa de dependências nem amplie seu escopo sem necessidade.

Se Sail estiver indisponível, tente os equivalentes no host (`php artisan test`, `vendor/bin/pint --test`, `composer analyze`, etc.) e informe que a validação ocorreu fora do ambiente oficial. Não sobrescreva `.env`; copie `.env.example` somente na primeira instalação. Não gere novamente a chave de uma instalação existente.

Após mudar backend, valide primeiro o teste relacionado e depois a suíte apropriada. Após mudar frontend, execute o build e confira classe Livewire, view, rotas e testes. Toda regra crítica nova precisa de teste de fluxo, autorização e isolamento por equipe quando aplicável. Use factories e mantenha seeders pequenos, sem depender de dados manuais.

# 4. Se comandos falharem

Se algum comando falhar, não ignore.

Informe claramente:

- o comando executado;
- o erro recebido;
- a possível causa;
- o que ainda precisa ser feito.

Exemplos de falhas comuns:

## `vendor/bin/sail` não existe

Provável causa: dependências PHP ainda não foram instaladas.

Siga o bootstrap via Docker em [docs/setup.md](docs/setup.md); se Docker não estiver disponível, tente `composer install` no host.

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

# 5. Convenções de banco de dados

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

# 6. Convenções de front-end

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

# 7. Uso de imagens como referência visual

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

# 8. Layouts e componentes Blade

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

# 9. Livewire

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

# 10. Segurança

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

# 11. Performance

Evite introduzir problemas como:

- N+1 queries;
- carregamento excessivo de dados;
- consultas sem paginação;
- loops pesados em views;
- repetição de queries dentro de Blade.

Use `with()` quando houver relacionamentos necessários.

Use paginação em listagens grandes.

---

# 12. Acessibilidade e usabilidade

Sempre que alterar UI:

- preserve labels em inputs;
- mantenha foco visível;
- use textos claros em botões;
- preserve contraste adequado;
- não dependa apenas de cor para indicar estado;
- mantenha mensagens de erro visíveis;
- mantenha navegação coerente.

---

# 13. Padrão de commits

Commits devem ser pequenos, sem alterações não relacionadas, com mensagens em inglês no padrão dos commits recentes (`type(scope): description` ou `type: description`).

Formato recomendado:

```text
feat(tasks): add task checklists
fix(tasks): validate task form input
ci: enforce static analysis
style: align shared form components
test(tasks): cover checklist creation
```

Evite mensagens vagas como:

```text
ajustes
correções
update
mudanças
```

---

# 14. Comandos destrutivos

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

# 15. Resposta final esperada do Codex

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
