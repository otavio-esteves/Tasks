# Segurança e provisionamento de acesso

## Privilégio administrativo

`users.is_admin` é falso por padrão e não pode ser preenchido via mass assignment. A ausência de equipe, a criação de uma conta ou a verificação do e-mail não tornam ninguém administrador.

Uma conta sem equipe e sem privilégio administrativo é encaminhada para `/aguardando-acesso`. Ela pode gerenciar o próprio perfil, mas não visualizar equipes, categorias ou tarefas da organização. O vínculo com uma equipe permite somente o acesso previsto nas policies existentes.

## Atualização de instalações existentes

O modelo anterior considerava qualquer conta sem equipe como administradora, inclusive contas criadas pelo cadastro público. Não é possível distinguir administradores legítimos apenas por esse atributo.

A migration `2026_09_06_000000_add_explicit_admin_privilege_to_users` não copia esse privilégio implícito. Todos os usuários começam com `is_admin = false`, mantendo seus registros e vínculos com equipes. Os administradores legítimos precisam ser reautorizados pelo operador do servidor.

Antes do deploy, identifique as contas administrativas aprovadas e confirme que seus e-mails estão verificados. Execute as migrations e faça a concessão explícita:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan users:admin admin@example.com
```

O comando atua somente sobre uma conta existente e com e-mail verificado. Não cria usuários, não redefine senhas e não altera a equipe. Não há endpoint HTTP para executá-lo. Em instalações novas, cadastre a conta por `/register`, verifique o e-mail e então execute o comando no servidor.

Para revogar o privilégio:

```bash
./vendor/bin/sail artisan users:admin admin@example.com --revoke
```

A revogação funciona mesmo se o e-mail não estiver mais verificado. Se a conta tiver uma equipe, continua sujeita às permissões dessa equipe.

## Vínculo com uma equipe

O cadastro público não escolhe equipe. O operador pode atribuir a equipe aprovada pelo console existente, sem conceder acesso administrativo:

```bash
./vendor/bin/sail artisan tinker
```

```php
$user = App\Models\User::where('email', 'usuario@example.com')->firstOrFail();
$team = App\Models\Team::findOrFail(1); // ID da equipe aprovada
$user->team()->associate($team);
$user->save();
```

O ID deve ser confirmado pelo operador. Remover o vínculo ou excluir a equipe não promove o usuário a administrador.

## Dependências

As correções de segurança são aplicadas dentro das versões principais suportadas pelo projeto, com revisão do diff dos lockfiles e validação via Sail:

```bash
./vendor/bin/sail composer audit
./vendor/bin/sail npm audit
./vendor/bin/sail composer install
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test
./vendor/bin/sail pint --test
./vendor/bin/sail composer analyze
```

Não use `npm audit fix --force` como atualização automática. Auditorias sem alertas significam que não há vulnerabilidades conhecidas reportadas para os pacotes verificados naquele momento; não substituem a revisão de autorização nem a revisão de contas antigas.

O CI executa `composer audit --locked --no-interaction` e `npm audit --audit-level=low`. Alertas conhecidos, inclusive em dependências de desenvolvimento, impedem o job de passar.
