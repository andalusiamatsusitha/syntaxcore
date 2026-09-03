# SyntaxCore AI Development Contract

## Purpose

This document is an execution contract for any AI assisting the development of **SyntaxCore**.

AI may help implement, debug, refactor, review, and extend the project, but it must preserve the architecture, conventions, and boundaries defined here.

---

# 1. Core Identity

SyntaxCore is a stable application development foundation for building:

- Public websites
- Admin applications
- APIs

Primary goals:

- Consistency
- Reusability
- Predictable architecture
- AI-friendly development
- Programmer-friendly development
- Stable Core
- Clear separation between Core and Application

SyntaxCore must not become a clone of Laravel or a generic feature-heavy framework.

---

# 2. Mandatory Core Principle

## Core is passive and stable

The `core/` directory must be treated similarly to a vendor dependency.

Rules:

- Do not modify Core to implement application-specific features.
- Do not place business logic inside Core.
- Do not add Core features merely because one application needs them.
- Core changes only through intentional Core updates.
- Generic capabilities should be validated before entering Core.

Dependency direction:

```text
Application
    ↓ uses
Core
    ↓ uses
PHP / External Dependencies
```

Never reverse this dependency.

---

# 3. Core First Development Rule

Before creating a new implementation:

```text
1. Check whether Core already provides the capability.
2. Reuse the Core capability if available.
3. If unavailable, use native PHP when appropriate.
4. If necessary, use an external dependency.
5. Do not recreate a Core capability under another name.
```

Rule:

> Reuse before create.

Examples:

- Core has routing → do not create another router.
- Core has Request → do not create another request abstraction.
- Core has database access → do not create random database helpers.
- Core has middleware → do not bypass middleware architecture.

---

# 4. Project Structure

```text
syntaxcore/
├── app/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Api/
│   │   └── Web/
│   ├── Middleware/
│   ├── Models/
│   ├── Services/
│   └── Support/
│
├── bootstrap/
├── config/
├── core/
├── public/
├── resources/
│   ├── assets/
│   └── views/
├── routes/
├── storage/
│
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

Do not restructure the project without a clear architectural reason.

---

# 5. Application Responsibilities

The `app/` directory contains application-level code.

Allowed:

- Controllers
- Models
- Services
- Application middleware
- Application support utilities
- Business logic

Preferred flow:

```text
Controller
    ↓
Service
    ↓
Model
    ↓
Database
```

Rules:

- Controllers coordinate requests.
- Services contain complex business logic.
- Models communicate with databases.
- Views must not query databases.
- Controllers should not contain large business workflows.

---

# 6. Core Responsibilities

The Core contains reusable infrastructure.

Architectural areas include:

```text
core/
├── Application/
├── Controller/
├── Database/
├── Exceptions/
├── Http/
├── Middleware/
├── Routing/
├── Support/
└── View/
```

Core components must remain:

- Generic
- Reusable
- Stable
- Independent from business domains

Never put application concepts such as School, Student, Product, Order, or customer-specific workflows inside Core.

---

# 7. Supported Contexts

SyntaxCore supports:

```text
Web
Admin
API
```

All contexts share the same Core.

Do not create separate frameworks for each context.

---

# 8. Routing and Middleware

Required request flow:

```text
Request
    ↓
Kernel
    ↓
Global Middleware
    ↓
Router
    ↓
Route Middleware
    ↓
Controller
    ↓
Response
```

Rules:

- Routes define endpoints.
- Middleware handles cross-cutting concerns.
- Controllers coordinate requests.
- Do not bypass middleware architecture.
- Core provides routing capability but should not hardcode application topology.

---

# 9. MVC Rules

## Controller

Controllers may:

- Read Request data.
- Call Services.
- Call Models for simple workflows.
- Return Views.
- Return API Responses.

Controllers should not:

- Contain large business workflows.
- Contain raw SQL.
- Render complex HTML directly.

## Model

Models are the database communication layer:

```text
Controller / Service
        ↓
      Model
        ↓
    Database
```

Do not build a large ORM unless explicitly planned and approved.

## View

Views are presentation files.

Views must not:

- Query databases.
- Contain complex business logic.
- Handle architecture-level authorization.
- Process requests.

---

# 10. Controller → View Convention

SyntaxCore uses convention over configuration.

Long-term direction:

```text
Controller namespace
+
Controller name
+
Method name
=
Automatic view resolution
```

Example:

```text
App\Controllers\Web\HomeController
index()
```

Conceptually resolves to:

```text
resources/views/web/home/index.php
```

Preferred controller usage:

```php
return $this->view();
```

Do not introduce competing view path conventions.

---

# 11. Frontend Foundation

Client-side technologies:

- HTML
- CSS
- JavaScript

Bootstrap is the primary HTML/UI foundation.

Do not introduce React, Vue, or another frontend framework unless explicitly requested.

---

# 12. Asset Contract

```text
resources/assets/
    = source assets

public/assets/
    = browser-accessible assets
```

Do not randomly create multiple competing asset locations.

---

# 13. Bootstrap Rule

Bootstrap is the official frontend foundation of SyntaxCore.

Use it consistently for:

- Layout
- Grid
- Forms
- Components
- Responsive behavior

Custom CSS should extend Bootstrap rather than unnecessarily replace it.

Do not introduce another CSS framework without explicit approval.

---

# 14. JavaScript and API Communication

JavaScript is the standard client communication layer.

Client communication with APIs must happen through JavaScript.

Typical operations:

- Create
- Read
- Update
- Delete
- Search
- Filter
- Dynamic loading

Long-term direction:

```text
Standard API Client
        ↓
fetch()
        ↓
SyntaxCore API
```

Do not introduce Axios or another HTTP client unless explicitly approved.

Do not create multiple inconsistent API communication patterns.

---

# 15. Admin Authorization

Admin access should move toward:

```text
User
    ↓
Role
    ↓
Permission
```

Do not hardcode application-specific role names inside Core.

---

# 16. Configuration Rules

Every configuration value must have a real runtime purpose.

Do not create unused configuration.

Avoid duplicate sources of truth.

---

# 17. Path Ownership

Application paths should have one authoritative owner.

Core components should use the application's path APIs instead of independently guessing project paths.

---

# 18. Dependency Rules

Before adding a dependency, evaluate:

1. Is this already provided by Core?
2. Can native PHP solve it cleanly?
3. Is the dependency genuinely necessary?
4. What is the maintenance cost?
5. Does it conflict with the stable Core philosophy?

Do not install dependencies merely for convenience.

---

# 19. AI Prohibited Behaviors

AI must NOT:

- Rewrite the entire architecture without instruction.
- Replace Core components with competing versions.
- Create duplicate routers.
- Create duplicate Request or Response systems.
- Create duplicate database abstractions.
- Modify Core for application-specific requirements.
- Introduce another framework without approval.
- Add unnecessary abstraction layers.
- Build unrequested features.
- Refactor unrelated code during a focused task.
- Hardcode business domains into Core.

---

# 20. AI Working Procedure

For every task:

## Step 1 — Inspect

Understand relevant existing architecture and dependencies.

## Step 2 — Reuse

Check Core capabilities before creating anything new.

## Step 3 — Implement

Make the smallest consistent change required.

## Step 4 — Validate

Check:

- Architecture consistency
- Dependency direction
- Naming consistency
- Existing conventions
- Security implications
- Backward compatibility

## Step 5 — Report

Report:

```text
Changed:
- ...

Created:
- ...

Reused from Core:
- ...

Architecture impact:
- ...

Known limitations:
- ...
```

---

# 21. Refactoring Rule

Refactoring is allowed when necessary.

However:

```text
Requested Feature
≠
Permission to rewrite the project
```

Keep refactoring scoped.

Before a large architectural refactor, explain:

- Why it is necessary.
- Which components are affected.
- Compatibility risks.
- Whether a smaller solution exists.

---

# 22. Current Known Architecture Tasks

The following items are planned architecture work:

- Implement Controller → View automatic convention.
- Remove hardcoded application route topology from Core where applicable.
- Establish one source of truth for middleware configuration.
- Define the final Model/database contract.
- Centralize application path ownership.
- Finalize the Asset Contract.
- Create a standardized JavaScript API/AJAX layer.
- Finalize Composer/package identity.

Do not assume these are already solved.

---

# 23. Definition of Done

A task is complete only when:

- It follows this contract.
- It does not unnecessarily modify Core.
- It reuses existing Core capabilities.
- It does not create duplicate architecture.
- It maintains project consistency.
- The implementation is scoped to the requested task.

---

# Final Instruction to AI

You are assisting the development of SyntaxCore.

Your role is not to redesign SyntaxCore according to your preferred framework architecture.

Your role is to:

1. Understand the existing system.
2. Respect Core boundaries.
3. Reuse Core capabilities.
4. Extend the Application safely.
5. Preserve consistency.
6. Make minimal, maintainable changes.

When uncertain:

> Prefer existing conventions over creating a new pattern.

When Core does not provide a capability:

> Extend outside Core first.

When considering a Core modification:

> Treat it as an intentional Core version decision, not a quick application fix.
