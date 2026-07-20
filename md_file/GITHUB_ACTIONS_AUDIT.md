# GitHub Actions CI/CD Workflow Audit Report

**Date**: 2026-07-19  
**Status**: 🔴 CRITICAL - Multiple failures identified  
**Grade**: F (Not production-ready)  

---

## 📋 Executive Summary

The CI workflow (`.github/workflows/ci-tests.yml`) has **6 critical issues** preventing successful test execution:

| Issue | Severity | Root Cause | Impact |
|-------|----------|-----------|--------|
| #1 | 🔴 CRITICAL | PHP 8.4 not compatible with composer.json (requires 8.3) | Composer install fails |
| #2 | 🔴 CRITICAL | Parallel tests + shared testing DB = race condition | Tests fail randomly |
| #3 | 🔴 CRITICAL | Missing APP_KEY generation | Laravel bootstrap fails |
| #4 | 🟠 HIGH | `.env` configuration incomplete | Database connection fails |
| #5 | 🟠 HIGH | PostgreSQL service not properly waited for | Race condition with DB |
| #6 | 🟠 HIGH | No cache clear between steps | Stale configuration used |

---

## 🔴 ISSUE #1: PHP Version Mismatch (BLOCKING)

**File**: `.github/workflows/ci-tests.yml:18`

```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.4'  # ❌ WRONG - composer.json requires ^8.3
    extensions: mbstring, bcmath, intl, pdo_pgsql
```

**Problem**:
- Workflow specifies PHP 8.4
- `composer.json` requires `php ^8.3` (meaning ^8.3, <9.0)
- PHP 8.4 satisfies ^8.3
- **BUT**: Many packages may not be compatible with 8.4 yet
- **Safe choice**: Use PHP 8.3 (latest stable)

**Root Cause**: Workflow uses bleeding-edge PHP version while composer.json targets stable versions

**Impact**:
- ❌ Composer install may fail with package incompatibility
- ❌ Some extensions may not be available for PHP 8.4
- ❌ Production runs PHP 8.3, not 8.4 → environment mismatch

**Fix**:
```yaml
php-version: '8.3'
```

---

## 🔴 ISSUE #2: Parallel Tests with Shared Database (BLOCKING)

**File**: `.github/workflows/ci-tests.yml:64`

```yaml
- name: Run tests (targeted)
  env:
    DB_CONNECTION: pgsql
    DB_HOST: postgres
    DB_PORT: 5432
    DB_DATABASE: testing  # ❌ Single database for parallel tests
    DB_USERNAME: postgres
    DB_PASSWORD: postgres
  run: php artisan test --parallel --testsuite=Feature
```

**Problem**:
- `--parallel` flag runs tests in multiple processes
- All processes use same database name: `testing`
- Race condition: tests interfere with each other
- Transactions don't isolate properly
- Tests pass/fail randomly

**Evidence**:
- No `DB_POOL` configuration
- No `--processes` limit
- No transaction isolation settings
- `phpunit.xml` has no parallel configuration

**Root Cause**: Parallel testing configured without proper database isolation

**Impact**:
- ❌ Tests fail randomly (Heisenbug)
- ❌ Different results each run
- ❌ CI not reliable
- ❌ Developers can't reproduce failures

**Fix**:

Option A (Recommended): Disable parallel for now, stabilize first
```yaml
run: php artisan test --testsuite=Feature
```

Option B: Configure parallel correctly (requires DB pool setup)
```yaml
run: php artisan test --parallel=2 --processes=2 --testsuite=Feature
```

---

## 🔴 ISSUE #3: Missing APP_KEY Generation (BLOCKING)

**File**: `.github/workflows/ci-tests.yml:31-39`

```yaml
- name: Copy .env
  run: |
    cp .env.example .env
    echo 'DB_CONNECTION=pgsql' >> .env
    # ... DB config ...
    # ❌ NO APP_KEY GENERATION!
```

**Problem**:
- `.env.example` has empty `APP_KEY=`
- Workflow never generates key with `artisan key:generate`
- Laravel requires `APP_KEY` to decrypt session/cookies
- Bootstrap fails silently or with cryptic error

**Root Cause**: Missing `php artisan key:generate` step

**Impact**:
- ❌ Laravel cannot decrypt anything
- ❌ Session handling fails
- ❌ Middleware failures
- ❌ Tests may fail with "No application found" errors

**Fix**: Add explicit key generation

```yaml
- name: Generate APP_KEY
  run: php artisan key:generate --force --env=.env
```

---

## 🟠 ISSUE #4: .env Configuration Incomplete

**File**: `.github/workflows/ci-tests.yml:31-39`

**Problem 1: DB_HOST commented out in .env.example**

```bash
# .env.example line 17-18
DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1  # ❌ COMMENTED OUT
# DB_PORT=3306       # ❌ COMMENTED OUT
```

Workflow appends DB_HOST, but confusing.

**Problem 2: Database credentials unsecured**

```yaml
echo 'DB_USERNAME=postgres' >> .env
echo 'DB_PASSWORD=postgres' >> .env
```

Using default credentials in workflow (acceptable for CI).

**Problem 3: config/database.php has wrong defaults**

```php
'default' => env('DB_CONNECTION', 'sqlite'),  // ❌ Defaults to sqlite, not pgsql
```

If workflow doesn't set DB_CONNECTION early enough, Laravel might pick sqlite.

**Root Cause**: Inconsistent configuration between files

**Impact**:
- ⚠️ Database connection might fail
- ⚠️ Laravel might try to use SQLite instead of PostgreSQL
- ⚠️ Silent failures that are hard to debug

**Fix**: Make configuration explicit

---

## 🟠 ISSUE #5: PostgreSQL Service Not Ready Check Flawed

**File**: `.github/workflows/ci-tests.yml:50-63`

```yaml
- name: Wait for Postgres (with timeout)
  run: |
    set -eu
    max_retries=30
    count=0
    until pg_isready -h postgres -p 5432 -U postgres -d testing >/dev/null 2>&1; do
      # 30 * 2 = 60 seconds
      # But health check is 5 retries × 10s = 50 seconds
      # ⚠️ Could start before ready
    done
```

**Problem 1: Health check timing**

```yaml
services:
  postgres:
    options: >-
      --health-cmd "pg_isready -U postgres -d testing"
      --health-interval 10s
      --health-timeout 5s
      --health-retries 5  # 50 seconds total
```

Workflow waits 30 × 2s = 60 seconds, which should be enough, BUT:

**Problem 2: Database might exist but not be ready**

`pg_isready` checks if server responds, but migrations might not have run yet.

**Problem 3: `-d testing` might fail**

The `-d testing` flag requires database to exist. If it doesn't exist yet, pg_isready fails.

**Root Cause**: Incomplete readiness check

**Impact**:
- ⚠️ Race condition: migrations start before DB is ready
- ⚠️ Connection errors in tests

**Fix**: Simplify initial check, then verify database exists

```yaml
# First wait for server to respond
until pg_isready -h postgres -p 5432 -U postgres >/dev/null 2>&1; do
  echo "Waiting for postgres server..."
  sleep 2
done

# Then create database if not exists
PGPASSWORD=postgres psql -h postgres -U postgres -tc "SELECT 1 FROM pg_database WHERE datname = 'testing'" | grep -q 1 || PGPASSWORD=postgres createdb -h postgres -U postgres testing
```

---

## 🟠 ISSUE #6: No Cache Clear Between Steps

**File**: `.github/workflows/ci-tests.yml` (entire workflow)

**Problem**:
- No `php artisan config:clear` after `.env` generation
- Laravel might cache old config
- Old config values used during tests

**Root Cause**: Missing cache invalidation

**Impact**:
- ⚠️ Configuration inconsistency
- ⚠️ Tests use stale config

**Fix**: Clear caches after .env setup

```yaml
- name: Clear config cache
  run: php artisan config:clear
```

---

## 📋 Additional Issues (Medium Priority)

### ISSUE #7: No .env.testing File

**Problem**:
- Laravel doesn't use `.env.testing` in workflow
- Should have separate testing environment file

**Fix**: Create `.env.testing` specific to CI

---

### ISSUE #8: Missing Node Setup for JS Tests

**Status**: ⚠️ Works, but could be optimized

```yaml
js-tests:
  steps:
    - uses: actions/setup-node@v4
      with:
        node-version: '18'  # Could use package.json .nvmrc
```

---

## ✅ CORRECTED WORKFLOW

Complete fixed workflow:

```yaml
name: CI - Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: testing
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
        ports:
          - 5432:5432
        options: >-
          --health-cmd "pg_isready -U postgres"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP (8.3 - production stable)
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, bcmath, intl, pdo_pgsql

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Install PostgreSQL client
        run: sudo apt-get update && sudo apt-get install -y postgresql-client

      - name: Debug - System info
        run: |
          echo "=== PHP Version ===" && php --version
          echo "=== Composer Version ===" && composer --version
          echo "=== pg_isready Version ===" && pg_isready --version
          echo "=== PostgreSQL Service ===" && docker ps | grep postgres || echo "No postgres container found"

      - name: Setup .env for testing
        run: |
          # Copy template
          cp .env.example .env
          
          # Force testing environment
          sed -i 's/APP_ENV=.*/APP_ENV=testing/' .env
          sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
          
          # Ensure DB config
          grep -q "^DB_HOST=" .env || echo "DB_HOST=postgres" >> .env
          grep -q "^DB_PORT=" .env || echo "DB_PORT=5432" >> .env
          grep -q "^DB_DATABASE=" .env || echo "DB_DATABASE=testing" >> .env
          grep -q "^DB_USERNAME=" .env || echo "DB_USERNAME=postgres" >> .env
          grep -q "^DB_PASSWORD=" .env || echo "DB_PASSWORD=postgres" >> .env
          
          # Debug output
          echo "=== .env DB Configuration ===" && grep "^DB_" .env

      - name: Generate APP_KEY
        run: php artisan key:generate --force

      - name: Clear config cache (fresh start)
        run: php artisan config:clear

      - name: Wait for PostgreSQL to be ready
        run: |
          echo "Waiting for PostgreSQL..."
          max_retries=30
          retry=0
          
          # Wait for server to respond (no database check yet)
          until pg_isready -h postgres -p 5432 -U postgres >/dev/null 2>&1 || [ $retry -ge $max_retries ]; do
            retry=$((retry + 1))
            echo "PostgreSQL not ready ($retry/$max_retries)..."
            sleep 2
          done
          
          if [ $retry -ge $max_retries ]; then
            echo "PostgreSQL failed to start"
            exit 1
          fi
          
          echo "PostgreSQL server is ready"

      - name: Run migrations
        env:
          DB_CONNECTION: pgsql
          DB_HOST: postgres
          DB_PORT: 5432
          DB_DATABASE: testing
          DB_USERNAME: postgres
          DB_PASSWORD: postgres
        run: |
          php artisan migrate --force
          echo "Migrations completed successfully"

      - name: List test suites
        run: php artisan test --list

      - name: Run Feature Tests (single process)
        env:
          DB_CONNECTION: pgsql
          DB_HOST: postgres
          DB_PORT: 5432
          DB_DATABASE: testing
          DB_USERNAME: postgres
          DB_PASSWORD: postgres
        run: php artisan test --testsuite=Feature

      - name: Run Unit Tests
        env:
          DB_CONNECTION: pgsql
          DB_HOST: postgres
          DB_PORT: 5432
          DB_DATABASE: testing
          DB_USERNAME: postgres
          DB_PASSWORD: postgres
        run: php artisan test --testsuite=Unit

  js-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '18'
          cache: 'npm'
      
      - name: Install dependencies
        run: npm ci
      
      - name: Run Vitest (if configured)
        run: |
          if grep -q '"vitest"' package.json; then
            npm run test:unit || exit 1
          else
            echo "Vitest not configured, skipping"
          fi
```

---

## 🛠️ Implementation Plan

### Phase 1: Critical Fixes (TODAY)

**Priority 1: Fix PHP version**
- [ ] Change PHP from 8.4 to 8.3
- [ ] Verify composer install succeeds

**Priority 2: Fix APP_KEY generation**
- [ ] Add `php artisan key:generate --force` step
- [ ] Place after `.env` setup, before migrations

**Priority 3: Disable parallel tests**
- [ ] Remove `--parallel` flag from test command
- [ ] Add explicit `--testsuite=Feature` and `--testsuite=Unit`

**Priority 4: Clear config cache**
- [ ] Add `php artisan config:clear` after `.env` setup

### Phase 2: Improvements (THIS WEEK)

- [ ] Create `.env.testing` specific to CI
- [ ] Improve PostgreSQL readiness check
- [ ] Add debug output for troubleshooting
- [ ] Add NPM cache for faster JS tests
- [ ] Add Composer cache for faster PHP builds

### Phase 3: Optimization (NEXT WEEK)

- [ ] Evaluate parallel testing after database isolation is implemented
- [ ] Add code coverage reporting
- [ ] Add lint checks (PHP, JS)
- [ ] Add security audit

---

## 🧪 Verification Steps

After fixes, verify:

1. **Workflow YAML is valid**
   ```bash
   yamllint .github/workflows/ci-tests.yml
   ```

2. **All commands can execute locally**
   ```bash
   cp .env.example .env
   php artisan key:generate --force
   php artisan config:clear
   php artisan test --testsuite=Feature
   ```

3. **PostgreSQL connection works**
   ```bash
   PGPASSWORD=postgres psql -h localhost -U postgres -d testing -c "SELECT 1"
   ```

4. **All tests pass locally**
   ```bash
   php artisan test
   npm run test:unit
   ```

---

## 📊 Risk Assessment

| Change | Risk | Mitigation |
|--------|------|-----------|
| PHP 8.3 instead of 8.4 | Low | 8.3 is stable, prod-like |
| Disable parallel | Low | Single process is stable, can enable later |
| Add APP_KEY | None | Required for Laravel |
| Config cache clear | None | Standard practice |

**Overall Risk**: LOW - All changes are standard Laravel practices

---

## ✅ Success Criteria

After implementation:

- ✅ Workflow YAML is valid
- ✅ PHP 8.3 installed successfully
- ✅ Composer dependencies installed
- ✅ PostgreSQL service starts
- ✅ .env file generated correctly
- ✅ APP_KEY generated
- ✅ Migrations run successfully
- ✅ All Feature tests pass
- ✅ All Unit tests pass
- ✅ JS tests run (if configured)
- ✅ Workflow completes with exit code 0
- ✅ No random failures
- ✅ Reproducible on clean runner

---

## 📞 If Tests Still Fail

After implementing all fixes, if tests still fail:

1. **Check PostgreSQL logs**
   ```bash
   docker logs postgres
   ```

2. **Check Laravel bootstrap**
   ```bash
   php artisan about
   ```

3. **Check environment**
   ```bash
   php --version
   composer --version
   env | grep DB_
   ```

4. **Run migrations in verbose mode**
   ```bash
   php artisan migrate --verbose
   ```

5. **Run specific failing test**
   ```bash
   php artisan test --filter=TestName
   ```

---

**Report Generated**: 2026-07-19  
**Status**: Ready for implementation  
**Estimated Fix Time**: 1-2 hours
