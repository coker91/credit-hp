---
name: cloud-environment-onboarding-setup
description: Set up and verify a local cloud development environment for the credit-hp Laravel application. Use when asked to onboard or prepare this repository.
---

# Cloud environment setup for credit-hp

This is a repository-local replacement guide, not the original unavailable
`cloud-environment-onboarding:setup` plugin. It can be read directly from this
file; automatic skill discovery depends on the client loading repository skills.

## Scope and inspection

1. Work from the repository root. Read applicable `AGENTS.md` files first.
2. Inspect `README.md`, `composer.json`, `composer.lock`, `package.json`,
   `package-lock.json`, `.env.example`, `Dockerfile`, and `docker-compose.yml`.
3. Check the working tree and available PHP, Composer, Node.js, npm, Docker,
   and Docker Compose versions. Preserve existing user changes.
4. This application requires PHP 8.3 or a compatible newer version, Laravel 12,
   Filament 5, and Node.js compatible with the locked Vite version. Inspect the
   lockfile platform requirements and run Composer's platform check; do not
   bypass missing PHP extensions with `--ignore-platform-reqs`.

## Dependencies

- Use `composer install --no-interaction --prefer-dist`, not `composer update`.
- Use `npm ci`, not an install that rewrites the lockfile.
- For Docker, run Composer through `docker compose exec app` after starting
  the required services. Build frontend assets on the host with Node.js.
- If a runtime is unavailable, install it only through permitted environment
  mechanisms, or report the exact blocker. Never claim installation succeeded
  without verifying the command result.

## Environment and services

1. Copy `.env.example` to `.env` only if `.env` does not already exist.
   Never print secrets or commit `.env`.
2. For native execution, configure a reachable development MySQL database.
   The example uses `credit-hp`, while Docker Compose creates `credit_hp`.
3. For the Compose application container, use `DB_CONNECTION=mysql`,
   `DB_HOST=db`, `DB_PORT=3306`, `DB_DATABASE=credit_hp`, and
   `DB_USERNAME=credit_hp_user`. Obtain the development password from the
   current Compose configuration without echoing it in output.
4. Start the core Docker services with `docker compose up -d --build app web db
   evolution-redis` when Docker is available. Wait for database readiness before
   migrations. Host access is `http://localhost:8000/admin`.
5. Generate an application key only when `APP_KEY` is empty. Ensure `storage`
   and `bootstrap/cache` are writable by the application user; avoid blanket
   world-writable permissions.
6. Run `php artisan migrate` against the configured development database.
   Do not use `migrate:fresh`, reset databases, or remove volumes during routine
   onboarding. Inspect seeders before executing them and seed only a new,
   isolated development database when needed.
7. Evolution API is optional for core application setup. Inside Compose, its
   URL is `http://evolution-api:8080`; native host access uses port 8080.
   Configure its key privately. Validate service compatibility and health if
   enabling it. Connecting a WhatsApp account or sending messages requires
   explicit user instructions; onboarding alone does not authorize messages.

## Verification

1. Run `composer check-platform-reqs` and `npm run build`.
2. Run `php artisan about` and `php artisan migrate:status`.
3. Read the tests and `phpunit.xml` before running `php artisan test`.
   Configure a separate disposable test database: the current PHPUnit config
   does not select an isolated database automatically. Never run database
   mutation tests against an existing application database.
4. Start the native application with `php artisan serve --host=0.0.0.0
   --port=8000`, or verify the Compose web service. Check that `/admin` responds
   successfully or redirects to the login page. Report the actual reachable
   URL and whether the service remains running.
5. Review `git status --short`. Keep dependency lockfiles unchanged unless a
   dependency change was requested. Report completed setup, verification
   results, and any remaining runtime or service blockers accurately.
