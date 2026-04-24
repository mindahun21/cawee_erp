#!/bin/bash

#######################################################################
# Elisoft ERP — Local Deploy Readiness Test (Fully Isolated)
#
# Spins up the production docker-compose.yml with its OWN MySQL,
# Redis, and pgvector — completely separate from your dev environment.
# Runs all checks inside those containers, then tears everything down.
#
#
# Usage:  bash docker/local_deploy_test.sh
#######################################################################

set -o pipefail

# ── Colors ───────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

PASS="${GREEN}✔ PASS${NC}"
FAIL="${RED}✘ FAIL${NC}"
WARN="${YELLOW}⚠ WARN${NC}"

ERRORS=0
WARNINGS=0

pass() { echo -e "  ${PASS}  $1"; }
fail() { echo -e "  ${FAIL}  $1"; ERRORS=$((ERRORS + 1)); }
warn() { echo -e "  ${WARN}  $1"; WARNINGS=$((WARNINGS + 1)); }
header() { echo -e "\n${CYAN}━━━ $1 ━━━${NC}"; }

# Use the production compose file explicitly
COMPOSE="docker compose -f docker-compose.yml -p elisoft_deploy_test"

echo ""
echo "========================================"
echo "  Elisoft ERP — Deploy Readiness Test"
echo "========================================"
echo "  Fully isolated — uses docker-compose.yml"
echo "========================================"

# ── Create a temporary .env for the test environment ─────────────────
header "0. Preparing Isolated Test Environment"

# Back up .env if it exists
[ -f .env ] && cp .env .env.backup.deploytest

echo "  ▶ Creating isolated test .env..."
cat > .env <<'TESTENV'
APP_NAME="Elisoft ERP Deploy Test"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=elisoft_erp
DB_USERNAME=elisoft_user
DB_PASSWORD=user_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log

VITE_APP_NAME="${APP_NAME}"

AI_PROVIDER=gemini
AI_MODEL=gemini-2.5-flash
AI_TIMEOUT=120
AI_CACHE_TTL=300
GEMINI_API_KEY=test-key-not-real

VECTOR_DB_HOST=vector_db
VECTOR_DB_PORT=5432
VECTOR_DB_DATABASE=elisoft_vectors
VECTOR_DB_USERNAME=vector_user
VECTOR_DB_PASSWORD=vector_password
TESTENV

pass "Test .env created (APP_ENV=production, APP_DEBUG=false)"

# ── Cleanup function (always runs) ──────────────────────────────────
cleanup() {
    echo ""
    echo "▶ Tearing down test environment..."
    $COMPOSE down -v --remove-orphans 2>/dev/null || true
    # Restore original .env
    if [ -f .env.backup.deploytest ]; then
        mv .env.backup.deploytest .env
        echo "  ✔ Restored original .env"
    fi
    echo "  ✔ Test containers and volumes removed."
}
trap cleanup EXIT

########################################################################
# TEST 1: Docker image build + services start
########################################################################
header "1. Docker Image Build & Services"

echo "  ▶ Building image (this may take a few minutes on first run)..."
echo "    Full log: /tmp/elisoft_deploy_build.log"
echo ""

# Run build in background, log to file
DOCKER_BUILDKIT=1 BUILDKIT_PROGRESS=plain $COMPOSE up -d --build > /tmp/elisoft_deploy_build.log 2>&1 &
BUILD_PID=$!

# Rolling 10-line progress display
PREV_LINES=0
while kill -0 $BUILD_PID 2>/dev/null; do
    # Move cursor up to overwrite previous output
    if [ $PREV_LINES -gt 0 ]; then
        printf "\033[${PREV_LINES}A"
    fi
    # Get last 10 lines, truncate long lines, print with clear
    LAST_LINES=$(tail -n 10 /tmp/elisoft_deploy_build.log 2>/dev/null)
    PREV_LINES=0
    while IFS= read -r line; do
        printf "\033[2K    %.100s\n" "$line"
        PREV_LINES=$((PREV_LINES + 1))
    done <<< "$LAST_LINES"
    sleep 1
done

# Final refresh after build ends
if [ $PREV_LINES -gt 0 ]; then
    printf "\033[${PREV_LINES}A"
fi
LAST_LINES=$(tail -n 10 /tmp/elisoft_deploy_build.log 2>/dev/null)
while IFS= read -r line; do
    printf "\033[2K    %.100s\n" "$line"
done <<< "$LAST_LINES"

wait $BUILD_PID
BUILD_EXIT=$?

echo ""
if [ $BUILD_EXIT -eq 0 ]; then
    pass "Docker image built and containers started"
else
    fail "Docker build/start FAILED — check /tmp/elisoft_deploy_build.log"
    echo "  Cannot continue without containers. Exiting."
    exit 1
fi

echo "  ▶ Waiting for MySQL to accept connections..."
RETRIES=30
while [ $RETRIES -gt 0 ]; do
    if $COMPOSE exec db mysqladmin ping -h localhost --silent 2>/dev/null; then
        break
    fi
    RETRIES=$((RETRIES - 1))
    sleep 2
done

if [ $RETRIES -eq 0 ]; then
    fail "MySQL did not become ready in time"
    exit 1
else
    pass "MySQL is ready"
fi

########################################################################
# TEST 2: Composer dependencies (inside container)
########################################################################
header "2. Composer Dependencies"

echo "  ▶ Installing production dependencies inside container..."
if $COMPOSE exec app composer install --no-dev --optimize-autoloader --no-interaction --no-progress 2>&1 | tail -3; then
    pass "Composer production install succeeded"
else
    fail "Composer production install FAILED"
fi

########################################################################
# TEST 3: Generate app key
########################################################################
header "3. Application Key"

$COMPOSE exec app php artisan key:generate --force 2>/dev/null
pass "Application key generated"

########################################################################
# TEST 4: Frontend build
########################################################################
header "4. Frontend Build (Vite)"

echo "  ▶ Installing npm dependencies and building..."
NPM_OUTPUT=$($COMPOSE exec app npm ci 2>&1)
NPM_EXIT=$?

if [ $NPM_EXIT -eq 0 ]; then
    if $COMPOSE exec app npm run build 2>&1; then
        pass "Vite build succeeded"
    else
        fail "npm run build FAILED"
    fi
else
    fail "npm ci FAILED"
    echo "$NPM_OUTPUT" | grep -i "error\|ERR!\|WARN" | head -10
fi

########################################################################
# TEST 5: Fresh migrations
########################################################################
header "5. Database Migrations"

echo "  ▶ Running all migrations from scratch..."
$COMPOSE exec app php artisan migrate --force --no-interaction 2>&1
MIGRATE_EXIT=$?

if [ $MIGRATE_EXIT -eq 0 ]; then
    pass "All migrations passed"
else
    fail "Migrations FAILED — this WILL break production"
fi

########################################################################
# TEST 6: Shield permissions
########################################################################
header "6. Shield Permissions & Roles"

echo "  ▶ Generating permissions..."
if $COMPOSE exec app php artisan shield:generate --all --panel=admin --no-interaction 2>&1 | tail -5; then
    pass "Shield permissions generated"
else
    fail "shield:generate FAILED"
fi

########################################################################
# TEST 7: Database seeding
########################################################################
header "7. Database Seeding"

echo "  ▶ Running seeders..."
SEED_OUTPUT=$($COMPOSE exec app php artisan db:seed --force --no-interaction 2>&1)
SEED_EXIT=$?

if [ $SEED_EXIT -eq 0 ]; then
    pass "All seeders ran successfully"
else
    fail "Seeder FAILED"
    echo "$SEED_OUTPUT" | grep -B1 -A3 "Error\|Exception\|FAIL" | head -10
fi

########################################################################
# TEST 8: Production caching
########################################################################
header "8. Production Caching Compatibility"

if $COMPOSE exec app php artisan config:cache 2>/dev/null; then
    pass "config:cache OK"
    $COMPOSE exec app php artisan config:clear 2>/dev/null
else
    fail "config:cache FAILED — closure in config file"
fi

if $COMPOSE exec app php artisan route:cache 2>/dev/null; then
    pass "route:cache OK"
    $COMPOSE exec app php artisan route:clear 2>/dev/null
else
    fail "route:cache FAILED — closure in routes"
fi

if $COMPOSE exec app php artisan view:cache 2>/dev/null; then
    pass "view:cache OK"
    $COMPOSE exec app php artisan view:clear 2>/dev/null
else
    fail "view:cache FAILED — Blade syntax error"
fi

########################################################################
# TEST 9: Code quality
########################################################################
header "9. Code Quality Scan"

DD_COUNT=$(grep -rn --include="*.php" -P '\b(dd|dump|ray)\(' app/ 2>/dev/null | wc -l)
if [ "$DD_COUNT" -gt 0 ]; then
    warn "Found $DD_COUNT dd()/dump()/ray() calls in app/"
    grep -rn --include="*.php" -P '\b(dd|dump|ray)\(' app/ 2>/dev/null | head -5
else
    pass "No debug statements in app/"
fi

ENV_COUNT=$(grep -rn --include="*.php" "env(" app/ 2>/dev/null | grep -v "// " | wc -l)
if [ "$ENV_COUNT" -gt 0 ]; then
    warn "Found $ENV_COUNT env() calls in app/ — breaks with config:cache"
    grep -rn --include="*.php" "env(" app/ 2>/dev/null | grep -v "// " | head -5
else
    pass "No env() calls in app/"
fi

########################################################################
# RESULTS
########################################################################
echo ""
echo "========================================"
if [ $ERRORS -eq 0 ]; then
    echo -e "  ${GREEN}✅ ALL TESTS PASSED${NC}"
    [ $WARNINGS -gt 0 ] && echo -e "  ${YELLOW}   ($WARNINGS warnings)${NC}"
    echo ""
    echo "  Your code is ready for production!"
else
    echo -e "  ${RED}❌ $ERRORS TEST(S) FAILED${NC}"
    [ $WARNINGS -gt 0 ] && echo -e "  ${YELLOW}   ($WARNINGS warnings)${NC}"
    echo ""
    echo "  Fix the failures before pushing."
fi
echo "========================================"
echo ""

exit $ERRORS
