#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
for tool in docker node npm python3; do
    command -v "$tool" >/dev/null || { echo "Missing prerequisite: $tool" >&2; exit 1; }
done
docker compose version >/dev/null
docker info >/dev/null
# Preserve existing configuration; generate secrets only in a new environment.
python3 - <<'PY'
from pathlib import Path
import secrets
path = Path('.env')
if not path.exists():
    content = Path('.env.example').read_text()
    for key in ('DB_PASSWORD', 'DB_ROOT_PASSWORD', 'EVOLUTION_API_KEY', 'EVOLUTION_DB_PASSWORD'):
        content = content.replace(f'{key}=\n', f'{key}={secrets.token_hex(32)}\n')
    path.write_text(content)
    path.chmod(0o600)
PY
npm ci
npm run build
docker compose up -d --build --wait --wait-timeout 180 app db web
docker compose exec -T app composer install --no-interaction --prefer-dist
docker compose exec -T app composer check-platform-reqs
docker compose exec -T app php artisan config:clear
docker compose exec -T app php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); if (!config("app.key")) { Illuminate\Support\Facades\Artisan::call("key:generate"); }'
docker compose exec -T app php artisan migrate --no-interaction
docker compose exec -T app php artisan about
echo 'Setup complete: http://localhost:8000/admin (demo seeder was not run).'
