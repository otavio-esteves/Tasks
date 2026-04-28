# Setup e Validação

## Ambiente oficial

O ambiente oficial do projeto é o Laravel Sail.

Para instalar dependências, gerar assets e validar o projeto, use Sail em vez de PHP, Composer ou npm instalados diretamente no host.

## Sequência oficial de validação

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail php artisan test
./vendor/bin/sail composer analyze
./vendor/bin/sail composer lint
```

## Observações importantes

- Falhas no host local por ausência de extensões PHP como `dom`, `xml` ou `xmlwriter` não invalidam o projeto por si só.
- Falhas no host local por ausência de `npm` também não invalidam o projeto por si só.
- A referência correta é o resultado dos comandos executados via Sail.

## Comandos complementares

```bash
./vendor/bin/sail php artisan route:list
./vendor/bin/sail php artisan migrate
./vendor/bin/sail npm run dev
```
