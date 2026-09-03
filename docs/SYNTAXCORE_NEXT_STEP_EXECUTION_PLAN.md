# SyntaxCore — Next Step Execution Plan

## Status

The AI Development Contract has been read and accepted.

Current repository baseline:

```text
Commit: 773b187
```

The project foundation and Bootstrap distribution are already present.

This document defines the **next implementation phase**.

---

# Objective

Do not add new features yet.

The next task is to **stabilize and refactor the existing foundation** so SyntaxCore has a clear architectural direction before further development.

Priority:

```text
Architecture correctness
    ↓
Core boundaries
    ↓
Convention consistency
    ↓
Feature development
```

---

# Execution Rules

Before making changes:

1. Read `SYNTAXCORE_AI_DEVELOPMENT_CONTRACT.md`.
2. Inspect the current repository.
3. Reuse existing components where possible.
4. Do not rewrite unrelated code.
5. Do not add new features outside this plan.
6. Keep changes focused and minimal.

---

# PHASE 01 — Architecture Foundation Refactor

## Task 1 — Audit Current Core Dependencies

Inspect all classes inside:

```text
core/
```

Verify:

```text
Application
    ↓
Core Components
    ↓
PHP / External Dependencies
```

Check specifically that:

- Core does not depend on application business logic.
- Core does not hardcode application-specific domains.
- Core components do not duplicate responsibilities.
- Application structure is not unnecessarily hardcoded inside generic Core components.

### Deliverable

Provide a short report before making unnecessary architectural changes.

---

## Task 2 — Fix Route Topology Ownership

### Problem

The Core must provide routing capability.

The Core should not permanently decide that every application must contain:

```text
web
admin
api
```

with specific hardcoded topology.

### Required Direction

Core responsibility:

```text
Router
Route
Route Groups
Middleware
Dispatching
```

Application responsibility:

```text
Which route files exist
How they are organized
Which prefixes they use
Which middleware they use
```

### Goal

Move application route registration responsibility outside generic Core logic.

Do not remove existing route functionality.

Maintain support for:

```text
routes/web.php
routes/admin.php
routes/api.php
```

but make the organization an application-level decision.

---

## Task 3 — Establish Middleware Source of Truth

Inspect:

```text
config/core.php
core/Application/Kernel.php
```

### Goal

Determine one clear source of truth for middleware configuration.

Avoid this situation:

```text
Config says one thing
Kernel contains another source
```

The final implementation must clearly define:

```text
Global Middleware
Route Middleware
Middleware Execution Order
```

Do not create unused configuration.

---

## Task 4 — Define Application Path Ownership

Inspect all Core classes that manually calculate paths.

### Required Principle

Application paths must have one authoritative owner.

Preferred direction:

```text
Application
    ├── basePath()
    ├── appPath()
    ├── configPath()
    ├── resourcePath()
    ├── storagePath()
    └── routesPath()
```

Core components should use these APIs instead of independently calculating project paths.

Review especially:

```text
View
ExceptionHandler
```

### Goal

Remove duplicated path assumptions where possible.

---

# PHASE 02 — Controller → View Convention

This is a major SyntaxCore identity feature.

## Objective

Controllers should be able to render their conventional view without manually defining the view path.

Preferred usage:

```php
return $this->view();
```

### Convention

Example:

```text
App\Controllers\Web\HomeController
```

Method:

```text
index()
```

Should resolve to:

```text
resources/views/web/home/index.php
```

### Expected Mapping

```text
Controller Namespace
+
Controller Name
+
Method Name
=
View Path
```

Examples:

```text
Web/HomeController::index()
→ web/home/index.php

Admin/DashboardController::index()
→ admin/dashboard/index.php
```

### Requirements

- Preserve the ability to explicitly render a view when necessary.
- Default behavior should use automatic convention.
- Do not hardcode individual controller names.
- Keep the mapping predictable.
- Use existing project architecture.

### Validation

Create or update a minimal example proving:

```text
Route
↓
Controller
↓
$this->view()
↓
Automatically resolved View
```

---

# PHASE 03 — Database Contract Review

Do not expand the Model system yet.

Inspect:

```text
core/Database/Connection.php
core/Database/Model.php
```

## Objective

Determine whether the current Model implementation is:

```text
A. Active Record
```

or:

```text
B. Lightweight database abstraction
```

### Current Instruction

Do not add:

- Relationships
- Query builder expansion
- Eager loading
- Scopes
- Pagination
- Soft deletes
- Events

unless explicitly requested later.

### Security Check

Review dynamic SQL construction.

Identifiers such as:

```text
column names
operators
table names
```

must not be treated as safely parameterized values.

Document any required restrictions or validation.

---

# PHASE 04 — Asset Contract

Bootstrap has already been added to the project.

Now establish the official asset architecture.

## Required Contract

```text
resources/assets/
    ↓
Source / development assets

public/assets/
    ↓
Browser-accessible assets
```

### Define

- Where Bootstrap source/distribution belongs.
- Which files are actually loaded by the browser.
- How project CSS is organized.
- How project JavaScript is organized.
- Where third-party frontend dependencies belong.

### Important

Do not introduce:

- Vite
- Webpack
- npm tooling
- another CSS framework

unless explicitly requested.

Keep the current architecture simple.

---

# PHASE 05 — Composer Identity

Review:

```text
composer.json
```

Correct project identity if necessary.

The package name, description, namespaces, and autoload configuration must accurately represent SyntaxCore.

Do not invent branding.

Preserve compatibility with the existing project structure.

---

# Completion Criteria

This execution phase is complete when:

## Architecture

- [ ] Core boundaries are clear.
- [ ] Route topology belongs to the Application layer.
- [ ] Middleware has one clear source of truth.
- [ ] Application paths have an authoritative owner.

## MVC

- [ ] Controller → View automatic convention works.
- [ ] Explicit view rendering remains possible if needed.

## Database

- [ ] Model responsibility is clearly defined.
- [ ] No unnecessary ORM expansion occurs.
- [ ] Dynamic SQL risks are addressed or documented.

## Assets

- [ ] `resources/assets` responsibility is defined.
- [ ] `public/assets` responsibility is defined.
- [ ] Bootstrap integration follows the official asset contract.

## Identity

- [ ] `composer.json` accurately represents SyntaxCore.

---

# Required Final Report

After completing the work, report only:

```text
Completed:
- ...

Changed:
- ...

Refactored:
- ...

Architecture Decisions:
- ...

Files Affected:
- ...

Not Changed:
- ...
```

Do not continue to the next feature automatically.

Stop after this execution plan is complete and wait for the next instruction.

---

# Final Instruction

This phase is not about making SyntaxCore bigger.

It is about making the existing foundation **stable, predictable, and consistent**.

Do not add features simply because they may be useful.

First establish the DNA of SyntaxCore.
