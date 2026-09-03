# SyntaxCore — Admin Routes & Authentication Module Execution Plan

## Objective

Implement the first complete **Admin Authentication Module** for SyntaxCore.

This phase must use the existing Core architecture and must not redesign the foundation.

Primary flow:

```text
Guest
  ↓
Admin Login Page
  ↓
Authentication
  ↓
Authenticated Admin
  ↓
Admin Dashboard
```

---

# 1. Scope

This phase includes:

- Admin routes
- Login page
- Login processing
- Logout
- Authentication middleware
- Session-based authentication
- Basic authenticated admin dashboard
- User model integration
- Password verification

This phase does NOT include:

- Registration
- Password reset
- Email verification
- OAuth/social login
- Remember me
- Role management UI
- Permission management UI
- Multi-factor authentication

Keep the module focused.

---

# 2. Architecture Rule

Authentication must respect:

```text
Route
  ↓
Middleware
  ↓
Controller
  ↓
Service
  ↓
Model
  ↓
Database
```

Do not put authentication business logic directly inside routes.

Do not put authentication business logic inside Core unless it is generic infrastructure intentionally approved as a Core capability.

---

# 3. Route Architecture

Use the existing:

```text
routes/admin.php
```

Recommended routes:

```text
GET  /admin/login
POST /admin/login

POST /admin/logout

GET  /admin
```

Recommended naming:

```text
/admin/login
/admin/logout
/admin
```

The admin dashboard should be the default authenticated admin destination.

---

# 4. Route Access Rules

## Guest-only routes

```text
GET  /admin/login
POST /admin/login
```

Authenticated users must not see the login page again.

Recommended behavior:

```text
Authenticated Admin
        ↓
Visits /admin/login
        ↓
Redirect /admin
```

---

## Authenticated routes

```text
GET /admin
POST /admin/logout
```

Unauthenticated users attempting to access protected admin routes must be redirected to:

```text
/admin/login
```

---

# 5. Authentication Components

Create application-level authentication components where appropriate.

Recommended structure:

```text
app/
├── Controllers/
│   └── Admin/
│       ├── AuthController.php
│       └── DashboardController.php
│
├── Middleware/
│   ├── Authenticate.php
│   └── RedirectIfAuthenticated.php
│
├── Models/
│   └── User.php
│
└── Services/
    └── AuthService.php
```

Use existing naming conventions where possible.

Do not create duplicate abstractions if the current Core already provides a suitable capability.

---

# 6. Authentication Service

Authentication business logic should live in:

```text
app/Services/AuthService.php
```

Responsibilities:

```text
Attempt login
Verify user credentials
Create authenticated session
Retrieve authenticated user
Logout
```

Controller responsibility:

```text
Read Request
Call AuthService
Return Response
```

Controller must remain thin.

---

# 7. User Model

Use:

```text
app/Models/User.php
```

The model must integrate with the existing lightweight Active Record contract.

Expected user fields:

```text
id
name
email
password
created_at
updated_at
```

Do not add role/permission architecture yet unless required by the existing schema.

---

# 8. Password Security

Passwords must never be stored or compared as plain text.

Use native PHP:

```php
password_hash()
password_verify()
```

Rules:

- Never compare passwords directly.
- Never return password hashes to views or API responses.
- Never store plain passwords in sessions.
- Never log passwords.

---

# 9. Session Authentication

Authentication state should be session-based.

Recommended session data:

```text
auth.user_id
```

Do not store the entire User model or sensitive user data in the session unless there is a strong architectural reason.

Each authenticated request should be able to resolve the current user safely.

---

# 10. Middleware

## Authenticate Middleware

Purpose:

Protect routes requiring authentication.

Behavior:

```text
Guest
  ↓
Protected Route
  ↓
Redirect /admin/login
```

Authenticated user:

```text
Authenticated User
  ↓
Protected Route
  ↓
Continue Request
```

---

## RedirectIfAuthenticated Middleware

Purpose:

Prevent authenticated users from revisiting guest-only pages.

Behavior:

```text
Authenticated User
  ↓
/admin/login
  ↓
Redirect /admin
```

---

# 11. Middleware Registration

Use the existing SyntaxCore middleware architecture.

Do not create a second middleware registration system.

Register middleware aliases using the current project convention.

Example conceptual aliases:

```text
auth
guest
```

These aliases should be reusable by route definitions.

---

# 12. Controller Design

## AuthController

Recommended responsibilities:

```text
showLogin()
login()
logout()
```

Expected flow:

```text
GET /admin/login
    ↓
showLogin()
    ↓
Login View
```

```text
POST /admin/login
    ↓
login()
    ↓
AuthService
    ↓
Success → Redirect /admin
Failure → Return login response with error
```

```text
POST /admin/logout
    ↓
logout()
    ↓
Destroy authentication state
    ↓
Redirect /admin/login
```

---

## DashboardController

Recommended responsibility:

```text
index()
```

Protected by authentication middleware.

Expected view convention:

```text
App\Controllers\Admin\DashboardController::index()

↓ automatically resolves

resources/views/admin/dashboard/index.php
```

Use the existing Controller → View convention.

Do not manually invent a competing view convention.

---

# 13. View Structure

Recommended structure:

```text
resources/views/
└── admin/
    ├── auth/
    │   └── login.php
    │
    └── dashboard/
        └── index.php
```

The login page may use an explicit view if necessary, but it should follow the same predictable convention.

Preferred automatic mapping:

```text
Admin/AuthController::showLogin()
```

If the current automatic view resolver cannot map this cleanly, improve the convention carefully rather than creating random exceptions.

---

# 14. Bootstrap UI

Use Bootstrap as the UI foundation.

Required login page elements:

- Email field
- Password field
- Login button
- Error feedback

Required dashboard:

- Authenticated user indicator
- Logout action

Keep the first version minimal.

Do not build a complete admin template yet.

---

# 15. Validation

Validate:

```text
email
password
```

Minimum requirements:

- Email must exist.
- Email format must be valid.
- Password must exist.

Do not build a large validation framework during this phase.

Use the simplest approach compatible with the existing architecture.

If validation infrastructure is missing, implement only what this module requires and keep it outside Core unless it is explicitly promoted to a generic Core capability.

---

# 16. Database Requirement

The authentication module requires a `users` table.

Minimum schema:

```text
id
name
email
password
created_at
updated_at
```

Requirements:

- `email` must be unique.
- Password must contain a password hash.
- Never insert default production credentials.

If the project currently has no migration system, do not automatically build a migration framework.

Use a simple documented schema/setup approach for this phase.

---

# 17. Security Requirements

The implementation must consider:

- Password hashing
- Password verification
- Session fixation protection
- Session cleanup during logout
- Authentication middleware
- Input validation
- Sensitive data exposure

Required behavior after successful login:

```text
Regenerate session ID
```

Required behavior during logout:

```text
Clear authentication state
Destroy/regenerate session safely
```

Do not claim CSRF protection is implemented unless it actually exists.

If POST forms currently lack CSRF infrastructure, document this as a remaining security requirement rather than pretending the authentication module is fully production-ready.

---

# 18. Error Handling

Login failure must not reveal whether:

- The email does not exist, or
- The password is incorrect.

Use a generic message such as:

```text
Invalid credentials.
```

Avoid user enumeration.

---

# 19. Required Integration Tests

Add focused tests for:

## Authentication

```text
✓ Valid credentials authenticate successfully.
✓ Invalid password fails.
✓ Unknown user fails.
✓ Password is verified using password_verify().
✓ Session stores only required authentication identity.
```

## Middleware

```text
✓ Guest cannot access /admin.
✓ Authenticated user can access /admin.
✓ Authenticated user cannot access login page.
```

## Logout

```text
✓ Logout removes authentication state.
✓ Logged out user cannot access protected routes.
```

Keep tests focused.

---

# 20. Completion Criteria

The module is complete when:

- [ ] `/admin/login` works.
- [ ] Valid login creates an authenticated session.
- [ ] Invalid login is rejected safely.
- [ ] Session ID regeneration occurs after login.
- [ ] `/admin` requires authentication.
- [ ] Guest is redirected to `/admin/login`.
- [ ] Authenticated user is redirected away from login.
- [ ] Dashboard loads successfully.
- [ ] Logout works.
- [ ] Authentication tests pass.
- [ ] Existing foundation tests still pass.
- [ ] No unrelated Core rewrite occurred.

---

# 21. Required Final Report

After implementation, report only:

```text
Completed:
- ...

Created:
- ...

Changed:
- ...

Core Reused:
- ...

Security Implemented:
- ...

Tests:
- ...

Remaining Limitations:
- ...
```

---

# Final Instruction

This phase creates the first usable Admin module.

Do not turn this into a complete identity and access management system.

The goal is:

> A minimal, secure, consistent session-based authentication module built on the existing SyntaxCore foundation.

Stop after this module is complete and tested.
