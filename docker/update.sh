#!/bin/bash
set -e

#######################################################################
# Elisoft ERP — Apply Update (Non-Destructive)
#
# Usage:
#   1. Pull latest code:  git pull origin main
#   2. Run:               bash docker/update.sh
#
# ⚠ This script NEVER drops tables, resets data, or runs migrate:fresh.
#    It only runs NEW migrations on top of existing data.
#######################################################################

echo "========================================"
echo "  Elisoft ERP — Applying Update"
echo "========================================"

# ── 1. Put app in maintenance mode ──────────────────────────────────
echo ""
echo "▶ Step 1/7: Entering maintenance mode..."
docker compose exec app php artisan down --retry=30 || true

# ── 2. Rebuild containers if Dockerfile changed ─────────────────────
echo ""
echo "▶ Step 2/7: Rebuilding containers..."
docker compose up -d --build
docker compose restart web # Restarts Nginx so it can find the new app container IP

# Fix permissions for www-data
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache

# ── 3. Install/update PHP dependencies ──────────────────────────────
echo ""
echo "▶ Step 3/7: Updating Composer dependencies..."
docker compose exec app composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# ── 4. Rebuild frontend assets ──────────────────────────────────────
echo ""
echo "▶ Step 4/7: Rebuilding frontend assets..."
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app npm prune --omit=dev 2>/dev/null || true

# ── 5. Run ONLY new migrations (data is preserved) ──────────────────
echo ""
echo "▶ Step 5/7: Running new migrations (non-destructive)..."
docker compose exec app php artisan migrate --force

# ── 6. Sync Shield permissions (picks up new resources/pages) ───────
echo ""
echo "▶ Step 6/7: Syncing Shield permissions..."
docker compose exec app php artisan shield:generate --all --panel=admin --no-interaction

# ── 7. Refresh caches & restart workers ─────────────────────────────
echo ""
echo "▶ Step 7/7: Optimizing & restarting workers..."
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan event:cache
docker compose exec app php artisan queue:restart

# ── Optional: Re-index AI documents ─────────────────────────────────
echo ""
echo "▶ Re-indexing AI knowledge base..."
docker compose exec app php artisan ai:index-documents 2>/dev/null \
    || echo "  ⚠ ai:index-documents skipped."

# ── Bring app back online ───────────────────────────────────────────
echo ""
echo "▶ Bringing application back online..."
docker compose exec app php artisan up

echo ""
echo "========================================"
echo "  ✅ Update applied successfully!"
echo "  Data: PRESERVED ✔   |   Downtime: minimal"
echo "========================================"
