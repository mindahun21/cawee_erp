#!/bin/bash
set -e

#######################################################################
# Elisoft ERP — First-Time Production Initialization
#
# Prerequisites:
#   1. Clone the repo:  git clone <repo-url> && cd elisoft-erp
#   2. Copy & edit env:  cp .env.example .env
#      - Set APP_ENV=production
#      - Set APP_DEBUG=false
#      - Set APP_URL=https://your-domain.com
#      - Set strong DB passwords
#      - Set GEMINI_API_KEY
#   3. Run:  bash docker/init.sh
#######################################################################

echo "========================================"
echo "  Elisoft ERP — Production Setup"
echo "========================================"

# ── Safety checks ────────────────────────────────────────────────────
if [ ! -f .env ]; then
    echo "❌ ERROR: .env file not found."
    echo "   Copy .env.example → .env and configure it first."
    exit 1
fi

# Warn if APP_ENV is not production
APP_ENV=$(grep -E "^APP_ENV=" .env | cut -d'=' -f2 | tr -d ' "')
if [ "$APP_ENV" != "production" ]; then
    echo "⚠  WARNING: APP_ENV is '$APP_ENV', not 'production'."
    echo "   Set APP_ENV=production in .env for a production deployment."
    read -p "   Continue anyway? (y/N): " confirm
    [ "$confirm" != "y" ] && exit 1
fi

# Warn if APP_DEBUG is true
APP_DEBUG=$(grep -E "^APP_DEBUG=" .env | cut -d'=' -f2 | tr -d ' "')
if [ "$APP_DEBUG" = "true" ]; then
    echo "⚠  WARNING: APP_DEBUG=true is dangerous in production (exposes secrets)."
    echo "   Set APP_DEBUG=false in .env."
    read -p "   Continue anyway? (y/N): " confirm
    [ "$confirm" != "y" ] && exit 1
fi

# ── 1. Build images & start services ────────────────────────────────
echo ""
echo "▶ Step 1/8: Building images and starting containers..."
docker compose up -d --build

# Wait for MySQL to be ready
echo "▶ Waiting for MySQL to accept connections..."
until docker compose exec db mysqladmin ping -h localhost --silent 2>/dev/null; do
    sleep 2
done
echo "  ✔ MySQL is ready."

# ── Fix permissions for www-data (PHP-FPM user) ─────────────────────
echo ""
echo "▶ Fixing file permissions for www-data..."
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache

# ── 2. Install PHP dependencies (production) ────────────────────────
echo ""
echo "▶ Step 2/8: Installing Composer dependencies (production)..."
docker compose exec app composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# ── 3. Install Node dependencies & build frontend ───────────────────
echo ""
echo "▶ Step 3/8: Building frontend assets..."
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app npm prune --omit=dev 2>/dev/null || true

# ── 4. Generate app key (only if not already set) ────────────────────
echo ""
echo "▶ Step 4/8: Checking application key..."
APP_KEY=$(grep -E "^APP_KEY=" .env | cut -d'=' -f2)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    docker compose exec app php artisan key:generate --force
    echo "  ✔ New application key generated."
else
    echo "  ✔ Application key already set, skipping."
fi

# ── 5. Run migrations ───────────────────────────────────────────────
echo ""
echo "▶ Step 5/8: Running database migrations..."
docker compose exec app php artisan migrate --force

# ── 6. Create storage link ──────────────────────────────────────────
echo ""
echo "▶ Step 6/8: Creating storage symlink..."
docker compose exec app php artisan storage:link 2>/dev/null || true

# ── 7. Generate permissions, roles & seed ────────────────────────────
echo ""
echo "▶ Step 7/8: Generating Shield permissions & seeding database..."
docker compose exec app php artisan shield:generate --all --panel=admin --no-interaction
docker compose exec app php artisan db:seed --force

# ── 8. Production caching ───────────────────────────────────────────
echo ""
echo "▶ Step 8/8: Optimizing for production..."
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan event:cache

# ── Optional: Index AI knowledge base ────────────────────────────────
echo ""
echo "▶ Indexing AI knowledge base documents..."
docker compose exec app php artisan ai:index-documents 2>/dev/null \
    || echo "  ⚠ ai:index-documents skipped (pgvector may not be configured)."

echo ""
echo "========================================"
echo "  ✅ Elisoft ERP is running in production!"
echo "========================================"
