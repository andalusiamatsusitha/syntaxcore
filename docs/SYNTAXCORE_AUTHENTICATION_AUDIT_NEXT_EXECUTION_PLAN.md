# SyntaxCore — Authentication Audit Findings & Next Execution Plan

## Baseline

This document continues after implementation of:

```text
Admin Routes
+
Session-Based Authentication
```

Current capabilities:

- Admin login
- Login processing
- Logout
- Auth middleware
- Guest middleware
- AuthService
- User model
- Session authentication
- Password verification
- Session ID regeneration
- Protected admin dashboard

The module is functionally approved, but the following findings must be resolved before Role and Permission.

---

# Priority 1 — Implement CSRF Protection

## Finding

Authentication uses state-changing POST requests.

Authentication must not be considered production-hardened until CSRF protection exists.

Required flow:

```text
Session
   ↓
CSRF Token
   ↓
Form / Request
   ↓
CSRF Middleware
   ↓
Validate
   ↓
Continue / Reject
```

Minimum protected routes:

```text
POST /admin/login
POST /admin/logout
```

The design should support future:

```text
POST
PUT
PATCH
DELETE
```

## Architecture Rule

Do not put CSRF logic inside `AuthController`.

Preferred flow:

```text
Request
   ↓
CSRF Middleware
   ↓
Route
   ↓
Controller
```

If CSRF enters Core, it must be generic, reusable, independent from Authentication, and tested.

---

# Priority 2 — Formalize Session Strategy

## Finding

Authentication currently uses native PHP sessions directly.

This is acceptable for the current phase.

For now:

```text
PHP Native Session
```

remains the chosen implementation.

Do not immediately build a large Session Manager.

## Required Review

Audit all usage of:

```php
$_SESSION
session_start()
session_destroy()
session_regenerate_id()
```

Keep usage limited and consistent.

## Decision Principle

Promote Session into Core only when multiple independent capabilities genuinely require a shared abstraction.

Do not build Session Core merely because other frameworks have one.

---

# Priority 3 — Improve Session Runtime Handling

## Finding

Do not suppress session errors with:

```php
@session_start();
```

Use explicit state checking:

```php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
```

Requirements:

- Avoid duplicate session startup.
- Avoid `@` suppression.
- Preserve existing architecture.

---

# Priority 4 — Add forceFill() Regression Tests

## Finding

`forceFill()` is now part of the Model capability and must be protected.

Required tests:

```text
✓ fill() respects $fillable.
✓ forceFill() bypasses $fillable intentionally.
✓ Database hydration can populate protected fields.
✓ Untrusted user input cannot accidentally bypass $fillable.
```

Required distinction:

```text
User Input
    ↓
fill()
    ↓
$fillable protection
```

```text
Trusted Database Hydration
    ↓
forceFill()
    ↓
Internal Model Population
```

Never use `forceFill()` with untrusted request input.

---

# Priority 5 — Authentication Security Tests

Required coverage:

## Login

```text
✓ Valid credentials succeed.
✓ Invalid password fails.
✓ Unknown email fails.
✓ Generic error message is returned.
✓ Password is never exposed.
```

## Session

```text
✓ Session ID regenerates after successful login.
✓ Only required identity data is stored.
✓ Logout removes authentication state.
✓ Logged-out user loses protected access.
```

## Middleware

```text
✓ Guest cannot access protected admin routes.
✓ Authenticated user can access protected routes.
✓ Authenticated user cannot revisit login routes.
```

---

# Priority 6 — Validate End-to-End Authentication

Required runtime flow:

```text
Guest
  ↓
GET /admin
  ↓
Redirect /admin/login
  ↓
Submit Login
  ↓
CSRF Validation
  ↓
AuthService
  ↓
Password Verification
  ↓
Session Regeneration
  ↓
Authenticated Session
  ↓
Redirect /admin
  ↓
Dashboard
  ↓
Logout
  ↓
Session Cleanup
  ↓
Redirect /admin/login
```

This must work as one complete system.

---

# Priority 7 — Do Not Build Authorization Yet

Do NOT begin:

- Roles
- Permissions
- RBAC
- Admin user management

until this phase is complete.

The separation remains:

```text
Authentication
=
Who are you?

Authorization
=
What are you allowed to do?
```

---

# Execution Order

```text
1. CSRF architecture and implementation
        ↓
2. Session usage audit
        ↓
3. Remove session error suppression
        ↓
4. forceFill() regression tests
        ↓
5. Authentication security tests
        ↓
6. End-to-end runtime validation
```

Stop after this phase.

---

# Completion Criteria

- [ ] CSRF protects state-changing forms.
- [ ] Login POST is protected.
- [ ] Logout POST is protected.
- [ ] Session startup does not suppress errors.
- [ ] Session behavior is documented and consistent.
- [ ] forceFill() has regression tests.
- [ ] Authentication security tests pass.
- [ ] Existing architecture tests still pass.
- [ ] Full authentication flow works.

---

# Required Final Report

After implementation, report only:

```text
Completed:
- ...

Created:
- ...

Changed:
- ...

Core Changes:
- ...

Security Implemented:
- ...

Tests Added:
- ...

Session Decision:
- ...

Remaining Limitations:
- ...
```

---

# Final Principle

Do not make Authentication bigger.

Make it trustworthy.

The goal is:

> Stable, tested, CSRF-protected session authentication.

Only after this phase is complete should SyntaxCore move to Authorization.
