# Deploy em produção: Cloud Run e Supabase PostgreSQL

## Arquitetura

```text
Usuário
   ↓ HTTPS
Google Cloud Run
   ↓ PostgreSQL com TLS
Laravel Tasks
   ↓
Supabase PostgreSQL
```

O container de produção é independente do Sail. O Sail, PostgreSQL e Redis de
`compose.yaml` continuam sendo somente o ambiente local de desenvolvimento.

## Imagem de produção

O `Dockerfile` usa três estágios:

1. `node:24-bookworm-slim` instala o lockfile com `npm ci` e executa `npm run build`;
2. `php:8.2-cli-bookworm` instala apenas dependências Composer de produção e as extensões PHP necessárias para PostgreSQL, `intl` e `zip`;
3. `php:8.2-apache-bookworm` contém o código, `vendor` e os assets já compilados.

Apache foi escolhido em vez de introduzir outro servidor de aplicação porque é
estável, já traz o módulo PHP oficial, atende HTTP diretamente e mantém a imagem
e a operação simples no Cloud Run. Os workers Apache executam como `www-data`.
O processo mestre usa o modelo padrão do Apache para gerenciar workers e os
descritores stdout/stderr do container; não expõe portas privilegiadas.

O entrypoint recebe `PORT` (padrão `8080`), configura Apache para essa porta e
gera caches de config, rotas e views como `www-data`. Ele **não** executa
migrations, seeders, `migrate:fresh` ou qualquer alteração de banco.

```bash
docker build --tag tasks:local .

docker run --rm --init -p 8080:8080 \
  --env-file .env.production \
  tasks:local
```

Crie `.env.production` fora do Git. O container não inclui `.env` nem aceita
credenciais embutidas na imagem.

## Variáveis de ambiente

Defina pelo Secret Manager/variáveis do Cloud Run, nunca no repositório:

```env
APP_NAME=Tasks
APP_ENV=production
APP_KEY=base64:gere_uma_chave_por_ambiente
APP_DEBUG=false
APP_URL=https://seu-dominio-ou-url-cloud-run

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=host-do-supabase
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=usuario
DB_PASSWORD=segredo
DB_SSLMODE=require
DB_APPLICATION_NAME=Tasks

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=
CACHE_STORE=database
QUEUE_CONNECTION=database

TRUSTED_PROXIES=*
FILESYSTEM_DISK=local
```

Use os valores de host, porta, banco e usuário fornecidos pelo projeto
Supabase. Se for utilizado o pooler, use exatamente host e porta indicados pelo
Supabase para esse pool. `DB_SSLMODE=require` ativa TLS via PDO PostgreSQL. Para
validação de certificado mais estrita, monte uma CA como secret e configure
`DB_SSLMODE=verify-full` e `DB_SSLROOTCERT=/caminho/para/ca.pem`.

`TRUSTED_PROXIES=*` só é apropriado quando a aplicação recebe tráfego apenas do
proxy gerenciado do Cloud Run. Em outro ambiente, informe explicitamente os IPs
de proxy confiáveis. Isso permite reconhecer `X-Forwarded-Proto` e gerar URLs
HTTPS corretamente. Não há Sanctum instalado nem configuração
`SANCTUM_STATEFUL_DOMAINS` usada neste projeto.

## Banco, migrations, sessão, cache e fila

As migrations existentes já criam as tabelas PostgreSQL necessárias:

- `sessions` para `SESSION_DRIVER=database`;
- `cache` e `cache_locks` para `CACHE_STORE=database`;
- `jobs`, `job_batches` e `failed_jobs` para `QUEUE_CONNECTION=database`.

Rode migrations como etapa única do release, depois de backup e antes de mudar
o tráfego. Não as rode no startup do serviço, pois múltiplas instâncias podem
iniciar simultaneamente:

```bash
gcloud run jobs create tasks-migrate \
  --image REGION-docker.pkg.dev/PROJECT/tasks/tasks:TAG \
  --region REGION \
  --set-secrets APP_KEY=tasks-app-key:latest,DB_PASSWORD=tasks-db-password:latest \
  --set-env-vars APP_ENV=production,APP_DEBUG=false,DB_CONNECTION=pgsql,DB_HOST=HOST,DB_PORT=5432,DB_DATABASE=DATABASE,DB_USERNAME=USERNAME,DB_SSLMODE=require \
  --command php \
  --args artisan,migrate,--force

gcloud run jobs execute tasks-migrate --region REGION --wait
```

Substitua os marcadores e adicione os demais secrets necessários sem colocá-los
na linha de comando. O exemplo ilustra a separação operacional; uma conta de
serviço com acesso mínimo ao Secret Manager deve ser usada em produção.

O código atual não despacha jobs assíncronos. Portanto, não há worker Cloud Run
dedicado a criar agora. Se jobs passarem a ser usados, crie um serviço/Job Cloud
Run separado que execute `php artisan queue:work --tries=3`, com observabilidade
e política de reinício definidas antes de habilitar o dispatch.

## Health check, logs e segurança

- `GET /up` é o health check nativo do Laravel. Ele é liveness: confirma que a
  aplicação iniciou e não depende do middleware web nem consulta o banco.
- A disponibilidade do banco deve ser monitorada pelo provedor e por checks
  sintéticos autenticados; não há endpoint público de readiness que exponha
  detalhes de conexão.
- `LOG_CHANNEL=stderr` envia logs para Cloud Logging. Não dependa de
  `storage/logs/laravel.log` no Cloud Run.
- Mantenha `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, CSRF habilitado e
  `SESSION_SAME_SITE=lax`.
- Login já possui rate limit; o cadastro público cria conta sem equipe e sem
  `is_admin`. `is_admin` não é mass assignable, e policies/casos de uso mantêm
  isolamento por equipe. Uma evolução futura recomendada é substituir cadastro
  público por convites aprovados.

## Arquivos enviados

O Cloud Run possui filesystem efêmero. O projeto agora tem anexos de tarefas e
eles usam explicitamente o disco `local`; portanto **não habilite uploads em
produção até definir armazenamento persistente externo**. Sem isso, anexos podem
desaparecer ao reiniciar ou trocar uma instância.

Esta é uma pendência bloqueadora conhecida, documentada para evitar uma falsa
sensação de persistência. Uma próxima etapa deve escolher e implementar um disco
externo (por exemplo, Cloud Storage com integração Flysystem e permissões de
conta de serviço), então atualizar o fluxo de upload/download e seus testes.

## Build e deploy futuro

```bash
gcloud auth configure-docker REGION-docker.pkg.dev
docker build --tag REGION-docker.pkg.dev/PROJECT/tasks/tasks:TAG .
docker push REGION-docker.pkg.dev/PROJECT/tasks/tasks:TAG

gcloud run deploy tasks \
  --image REGION-docker.pkg.dev/PROJECT/tasks/tasks:TAG \
  --region REGION \
  --port 8080 \
  --set-secrets APP_KEY=tasks-app-key:latest,DB_PASSWORD=tasks-db-password:latest \
  --set-env-vars APP_ENV=production,APP_DEBUG=false,APP_URL=https://SEU_DOMINIO,LOG_CHANNEL=stderr,LOG_LEVEL=info,DB_CONNECTION=pgsql,DB_HOST=HOST,DB_PORT=5432,DB_DATABASE=DATABASE,DB_USERNAME=USERNAME,DB_SSLMODE=require,SESSION_DRIVER=database,SESSION_SECURE_COOKIE=true,CACHE_STORE=database,QUEUE_CONNECTION=database,TRUSTED_PROXIES=*
```

O comando não é executado por este repositório nem por CI nesta etapa. Configure
ingress, domínio, conta de serviço e acesso aos secrets conforme o ambiente.

## Atualização, rollback e backup

1. Faça backup/PITR no Supabase antes de migrations.
2. Construa e valide a imagem com a mesma tag imutável que será publicada.
3. Execute o Job de migration e confira o resultado.
4. Faça deploy de uma nova revisão do Cloud Run e valide `/up`, login e fluxo
   principal.
5. Em problema de aplicação, volte o tráfego para a revisão anterior. Não faça
   rollback automático de schema: migrations reversas exigem avaliação de
   compatibilidade e backup.

Backups e retenção são responsabilidade operacional do projeto Supabase; teste
regularmente a restauração em ambiente separado.

## Limites e melhorias futuras

- implementar armazenamento externo antes de uploads em produção;
- avaliar worker somente quando houver jobs reais e métricas de fila;
- considerar convites em substituição ao registro público;
- estabelecer alertas para erros, latência, saturação de conexões PostgreSQL e
  falhas de migration.
