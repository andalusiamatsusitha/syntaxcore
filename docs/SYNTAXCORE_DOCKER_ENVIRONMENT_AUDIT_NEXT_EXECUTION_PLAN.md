# SyntaxCore — Docker Environment Audit & Next Execution Plan

## Baseline

Commit:

```text
1c631db
feat(docker): add Docker Compose environment with PHP 8.3, Nginx, MySQL, and seeder
```

This phase adds:

- Dockerfile with PHP 8.3 FPM
- Docker Compose
- Nginx
- MySQL 8.0
- PHP configuration
- Container entrypoint
- Database schema initialization
- Database seeder
- Docker development documentation

Architecture:

```text
Nginx
  ↓
PHP 8.3 FPM
  ↓
SyntaxCore
  ↓
MySQL 8.0
```

# Audit Verdict

```text
🟢 APPROVED FOR DEVELOPMENT
🟡 SECURITY / DEVOPS CLEANUP REQUIRED
```

The Docker layer is infrastructure around SyntaxCore and does not need to enter `core/`.

---

# Priority 1 — Remove / Parameterize Default Credentials

The repository currently exposes:

```text
admin@syntaxcore.com
admin123
```

in the README and seeder.

This is acceptable only for disposable local development.

Required direction:

```text
SEED_ADMIN_EMAIL
SEED_ADMIN_PASSWORD
```

Use environment-driven development credentials.

Never use fixed credentials for production.

---

# Priority 2 — Fix Storage Permissions

`docker/entrypoint.sh` currently uses:

```bash
chmod 777
```

Replace this with least-privilege ownership and permissions.

Preferred direction:

```text
owner/group → www-data
permissions → 775 or stricter
```

Do not use `777` as the default solution.

---

# Priority 3 — Pin Composer Image

The Dockerfile uses:

```dockerfile
COPY --from=composer:latest
```

`latest` is mutable and makes builds less reproducible.

Use a pinned Composer image/version compatible with PHP 8.3.

---

# Priority 4 — Validate Docker Runtime End-to-End

Run and verify:

```bash
docker compose up -d --build
```

Then:

```text
GET /
GET /api/v1/status
GET /admin
GET /admin/login
POST /admin/login
POST /admin/logout
```

Verify:

- Session
- CSRF
- Authentication
- MySQL
- Nginx
- PHP-FPM

This validation is especially important after the OS migration to Ubuntu.

---

# Priority 5 — Validate Database Initialization

MySQL initializes:

```text
database/schema.sql
```

through the container initialization mechanism.

Remember:

> Initialization scripts run when the database volume is initialized, not on every restart.

Reset development database with:

```bash
docker compose down -v
docker compose up -d --build
```

Do not treat `schema.sql` as a migration system.

---

# Priority 6 — Keep Seeder Outside Core

`database/seed.php` is an application development utility.

Correct dependency direction:

```text
Seeder
  ↓
Application Model
  ↓
Core Database
```

Do not move seed logic into `core/`.

---

# Priority 7 — Review Development Bind Mount

Compose mounts:

```text
.:/var/www/html
```

This is appropriate for development.

It overlays files copied during image build, which explains the entrypoint's Composer check.

Keep this as a development strategy and do not assume it is the final production deployment architecture.

---

# Priority 8 — Separate Development and Production

Current development settings include:

```text
APP_ENV=development
APP_DEBUG=true
```

This is acceptable locally.

Production must eventually use:

```text
APP_DEBUG=false
```

and separate:

- Secrets
- Database credentials
- Ports
- File permissions
- Logging
- Docker image strategy

Do not build the complete production architecture yet.

---

# Priority 9 — Nginx Request Architecture

The official Docker development flow is:

```text
Browser
  ↓
Nginx
  ↓
public/index.php
  ↓
PHP-FPM
```

Keep the front-controller architecture unchanged.

The existing `.htaccess` should not become part of the Nginx architecture.

---

# Priority 10 — Environment Configuration

`.env.example` uses:

```text
DB_HOST=127.0.0.1
```

Docker Compose injects:

```text
DB_HOST=db
```

This is valid because they represent different execution contexts.

Document the distinction:

```text
Host PHP
→ 127.0.0.1

Docker app container
→ db
```

Application code must not hardcode either value.

---

# Priority 11 — Test Inside Docker

The documented command:

```bash
docker compose exec app composer test
```

should become a required development check.

Validation flow:

```text
Docker Build
↓
Application Startup
↓
Composer Install
↓
Architecture Tests
↓
HTTP Runtime Tests
↓
Authentication Flow
```

---

# What NOT to Build Yet

Do not add:

- Kubernetes
- CI/CD
- Docker Swarm
- Redis container
- Queue workers
- Production orchestration
- Cloud deployment
- Multi-environment framework

The current goal is only a reliable development environment.

---

# Execution Order

```text
1. Validate Docker runtime on Ubuntu
        ↓
2. Fix storage permissions
        ↓
3. Parameterize default admin credentials
        ↓
4. Pin Composer image
        ↓
5. Validate MySQL initialization
        ↓
6. Validate authentication through Docker
        ↓
7. Review environment consistency
        ↓
8. Confirm architecture tests inside Docker
```

---

# Completion Criteria

- [ ] Docker build works.
- [ ] Nginx serves the application.
- [ ] PHP 8.3-FPM works.
- [ ] MySQL 8.0 works.
- [ ] Schema initializes correctly.
- [ ] Seeder works.
- [ ] Architecture tests pass inside Docker.
- [ ] Login works inside Docker.
- [ ] CSRF works inside Docker.
- [ ] Logout works inside Docker.
- [ ] Storage does not require `777`.
- [ ] Composer image is pinned.
- [ ] Default credentials are configurable and development-only.
- [ ] Host vs Docker database configuration is documented.

---

# Required Final Report

After implementation, report only:

```text
Completed:
- ...

Changed:
- ...

Docker Validation:
- ...

Security Fixes:
- ...

Tests:
- ...

Remaining Issues:
- ...
```

Stop after this phase.

---

# Final Principle

Docker is infrastructure.

It must make SyntaxCore:

> Easy to run, reproducible, and safe to develop.

It must not change the Core architecture or become another abstraction layer inside the application.
