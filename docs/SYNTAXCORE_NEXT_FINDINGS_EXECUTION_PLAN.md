# SyntaxCore — Next Findings & Execution Plan

## Baseline

This phase continues after the latest architecture audit and recent refactors.

Completed direction:

- Core/Application boundary improved.
- Route topology moved outside generic Core ownership.
- Middleware configuration clarified.
- Application path ownership improved.
- Controller → View automatic convention introduced.
- Dynamic SQL handling improved.
- Bootstrap and asset architecture introduced.
- Native JavaScript API communication direction established.

> Do not add large new capabilities before the findings below are resolved.

---

# Priority 1 — Validate Asset Foundation

## Required Work

- Check CSS for invalid declarations.
- Verify Bootstrap files load correctly.
- Verify `public/assets/css/app.css`.
- Verify `public/assets/js/app.js`.
- Remove unnecessary duplicate asset ownership.

Official contract:

```text
resources/assets/ = source assets
public/assets/    = browser-accessible assets
```

---

# Priority 2 — Finalize Model Contract

## Finding

The Model is becoming powerful enough that its responsibility must be formally defined.

## Recommended Decision

# Lightweight Active Record

Allowed direction:

```text
find
where
create
save
update
delete
```

But intentionally limited.

Do NOT add without explicit approval:

- Relationships
- Eager loading
- Scopes
- Events
- Soft deletes
- Pagination engine
- Complex query builder

---

# Priority 3 — Validate Database Security

Verify:

- Column names are validated.
- Table names are controlled.
- Operators use an allowlist.
- Values use parameter binding.
- `$fillable` protects mass assignment.
- No dynamic SQL bypass exists elsewhere.

Important:

Prepared statements protect values, not dynamic identifiers.

Therefore these must be controlled:

```text
Column
Table
Operator
Sort direction
```

---

# Priority 4 — Validate the View API

Automatic Controller → View resolution is now a major SyntaxCore feature.

Verify:

```text
Web/HomeController::index()
→ resources/views/web/home/index.php
```

Verify:

```text
Admin/DashboardController::index()
→ resources/views/admin/dashboard/index.php
```

Also verify explicit rendering remains possible:

```php
$this->view();
```

Automatic convention.

```php
$this->view('custom.path');
```

Explicit override.

Goal:

> Keep the API predictable and non-ambiguous.

---

# Priority 5 — Audit Composer Identity

Review `composer.json`.

Verify:

- Package name.
- Description.
- PHP version.
- PSR-4 namespaces.
- Autoload paths.

The Composer identity must accurately represent SyntaxCore.

---

# Priority 6 — Create Minimal Runtime Integration Test

Before Authentication or Authorization, prove the foundation works end-to-end.

Required flow:

```text
Browser Request
      ↓
public/index.php
      ↓
Application
      ↓
Kernel
      ↓
Middleware
      ↓
Router
      ↓
Controller
      ↓
Automatic View Resolution
      ↓
HTML Response
```

Also verify:

```text
JavaScript
      ↓
SyntaxCore API Client
      ↓
API Route
      ↓
Controller
      ↓
JSON Response
```

This is foundation validation, not a new feature.

---

# Priority 7 — Establish Minimal Test Strategy

Start with critical architecture tests only.

Recommended coverage:

```text
Routing
Middleware
Controller dispatch
View resolution
Request
Response
Database safety
```

Goal:

> Protect the Core from regression.

Do not build a huge test suite yet.

---

# Execution Order

```text
1. Asset validation
        ↓
2. Model contract
        ↓
3. Database security validation
        ↓
4. View API validation
        ↓
5. Composer identity audit
        ↓
6. Runtime integration test
        ↓
7. Critical architecture tests
```

Do not jump directly to Authentication yet.

---

# Completion Report

After execution, report only:

```text
Completed:
- ...

Fixed:
- ...

Validated:
- ...

Architecture Decision:
- ...

Files Changed:
- ...

Tests Added:
- ...

Remaining Issues:
- ...
```

Stop after this plan is complete.

Do not automatically begin:

- Authentication
- Authorization
- Sessions
- CSRF
- Other major capabilities

---

# Final Principle

The next goal is not to make SyntaxCore bigger.

The next goal is:

> Make the existing SyntaxCore foundation provably stable.

Only after the foundation is validated should the next Core capability be designed.
