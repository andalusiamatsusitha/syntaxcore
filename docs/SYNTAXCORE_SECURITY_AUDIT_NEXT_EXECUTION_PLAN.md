# SyntaxCore — Security Audit Findings & Next Execution Plan

## Baseline

This document continues after commit:

```text
ad4b80b
feat(security): implement CSRF protection, clean session lifecycle and forceFill regression tests
```

Completed improvements:

- CSRF protection capability added.
- CSRF middleware added.
- Protected HTTP methods defined.
- CSRF token supports form input and `X-CSRF-TOKEN` header.
- CSRF validation uses timing-safe comparison.
- Session error suppression was removed.
- Middleware priority was updated.
- Authentication views/routes were updated for CSRF.
- Regression coverage was expanded.

This is a strong security milestone.

---

# Audit Verdict

## Overall

```text
APPROVED WITH NEXT-PHASE FINDINGS
```

The implementation follows the intended architecture:

```text
Core\Security\Csrf
        ↓
Application CSRF Middleware
        ↓
Route Middleware
        ↓
Controller
```

This separation is correct because:

- Token generation and validation are generic capabilities.
- HTTP request enforcement belongs in middleware.
- Authentication controllers do not contain CSRF logic.

---

# Priority 1 — Centralize Session Lifecycle

## Finding

Session lifecycle logic now exists in multiple places.

Examples include:

```text
Core\Security\Csrf
App\Services\AuthService
```

Both currently need to ensure that a PHP session is active.

This creates duplicated infrastructure logic.

## Required Direction

Do not build a large Session framework.

Instead, evaluate creating one minimal generic session lifecycle capability.

Possible responsibility:

```text
Ensure session is started
Read session state
Write session values
Remove session values
Regenerate session ID
Invalidate session safely
```

However, implementation must remain minimal.

## Important Rule

Do not immediately create a Laravel-style Session Manager.

The purpose is only:

> One consistent owner for native PHP session lifecycle.

---

# Priority 2 — Define CSRF Token Lifecycle

## Finding

CSRF can:

```text
Generate Token
Validate Token
Regenerate Token
```

The next step is to define exactly when regeneration occurs.

## Required Decision

Document the token lifecycle.

Recommended questions:

```text
When is the first token generated?
When should it be regenerated?
Should login regenerate the token?
Should logout regenerate the token?
Does session regeneration affect CSRF token behavior?
```

## Security Goal

Authentication state transitions should not leave an unclear token lifecycle.

Recommended direction:

```text
Anonymous Session
    ↓
CSRF Token A
    ↓
Successful Login
    ↓
Session ID Regeneration
    ↓
CSRF Token Regeneration
    ↓
Authenticated Session
```

And after logout:

```text
Authentication Cleanup
    ↓
Session Cleanup
    ↓
New Anonymous Session
    ↓
New CSRF Token
```

Do not implement this blindly.

First ensure it fits the current session architecture.

---

# Priority 3 — CSRF Middleware Scope Review

## Finding

The CSRF middleware protects:

```text
POST
PUT
PATCH
DELETE
```

This is correct as a default.

However, the next audit must define which application routes should receive CSRF middleware.

## Required Rule

CSRF protection is primarily relevant to browser requests authenticated through cookies/sessions.

Future API authentication may use different security mechanisms.

Therefore:

```text
Admin/Web Session Routes
    → CSRF Protected
```

Future:

```text
Token-Based API
    → Do not automatically assume the same CSRF requirement
```

Do not globally apply CSRF to every future API endpoint without considering the authentication mechanism.

---

# Priority 4 — Standardize CSRF Access in Views

## Finding

Views now require access to a CSRF token.

The project must establish one consistent convention.

## Goal

Avoid scattered direct calls throughout application views if a cleaner convention can be provided.

Choose one official pattern.

Example direction:

```php
Csrf::token()
```

or a future small helper:

```php
csrf_token()
```

Do not introduce multiple competing patterns.

## Required Outcome

Document the official view usage for:

```text
HTML Forms
AJAX Requests
JavaScript API Requests
```

---

# Priority 5 — Standardize JavaScript CSRF Support

## Finding

The middleware supports:

```text
X-CSRF-TOKEN
```

This creates an opportunity to integrate CSRF into the existing JavaScript API client.

## Required Direction

Update the official JavaScript request abstraction so browser requests can consistently include:

```text
X-CSRF-TOKEN
```

Example flow:

```text
SyntaxCore JavaScript Client
        ↓
Read CSRF Token
        ↓
Attach X-CSRF-TOKEN
        ↓
fetch()
```

## Important

Do not create another HTTP client.

Reuse:

```text
window.SyntaxCore
```

or the current official JavaScript API abstraction.

---

# Priority 6 — Review Request API Consistency

## Finding

`Request::getMethod()` was added while a method-based API already exists.

This may be a compatibility alias, but the Request contract should remain consistent.

## Required Review

Determine the official naming convention.

Avoid long-term duplication such as:

```text
method()
getMethod()
```

unless there is a clear compatibility reason.

## Preferred Principle

SyntaxCore APIs should have:

```text
One primary convention
Optional compatibility aliases only when justified
```

Do not perform a broad rewrite.

---

# Priority 7 — Expand Security Regression Tests

The security layer is now important enough to require focused regression coverage.

Required tests:

## CSRF

```text
✓ Token is generated.
✓ Generated token is stored consistently.
✓ Valid token passes.
✓ Invalid token fails.
✓ Missing token fails.
✓ Header token passes.
✓ POST is protected.
✓ PUT is protected.
✓ PATCH is protected.
✓ DELETE is protected.
✓ Safe methods are not rejected unnecessarily.
```

## Token Lifecycle

After finalizing the lifecycle:

```text
✓ Login creates correct token state.
✓ Session regeneration preserves intended security behavior.
✓ Logout clears or regenerates token according to the final decision.
```

## Session

```text
✓ Session starts only once.
✓ No error suppression is used.
✓ Authentication and CSRF use the same session lifecycle convention.
```

---

# Priority 8 — Full Browser Security Flow Validation

Perform an end-to-end validation.

Required flow:

```text
Guest
  ↓
GET /admin/login
  ↓
Session Started
  ↓
CSRF Token Generated
  ↓
Login Form
  ↓
POST /admin/login
  ↓
CSRF Middleware
  ↓
AuthService
  ↓
Password Verification
  ↓
Session Regeneration
  ↓
CSRF Lifecycle Update
  ↓
Authenticated Dashboard
```

Then:

```text
POST /admin/logout
  ↓
CSRF Middleware
  ↓
Logout
  ↓
Session Cleanup
  ↓
Anonymous State
```

---

# What NOT to Build Yet

Do NOT begin:

- Roles
- Permissions
- RBAC
- User management
- OAuth
- Password reset
- Registration
- API authentication

The current goal is to finish and stabilize:

```text
Session
+
CSRF
+
Authentication Security Lifecycle
```

---

# Execution Order

```text
1. Audit duplicated session lifecycle logic
        ↓
2. Define minimal session ownership
        ↓
3. Define CSRF token lifecycle
        ↓
4. Define CSRF middleware scope
        ↓
5. Standardize CSRF usage in Views
        ↓
6. Integrate CSRF into JavaScript client
        ↓
7. Review Request API consistency
        ↓
8. Add security regression tests
        ↓
9. Run complete browser authentication flow
```

---

# Completion Criteria

This phase is complete when:

- [ ] Session lifecycle has one clear owner.
- [ ] CSRF token lifecycle is documented.
- [ ] Login/session regeneration behavior is defined.
- [ ] Logout/token cleanup behavior is defined.
- [ ] CSRF scope is clear for Web/Admin versus future APIs.
- [ ] Views have one official CSRF usage convention.
- [ ] JavaScript requests can use the official CSRF mechanism.
- [ ] Request API duplication has been reviewed.
- [ ] Security regression tests pass.
- [ ] Full browser authentication flow works.
- [ ] Existing tests still pass.

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

Core Decisions:
- ...

Security Decisions:
- ...

Tests Added:
- ...

Remaining Limitations:
- ...
```

Stop after completing this plan.

---

# Final Principle

SyntaxCore now has Authentication and CSRF.

The next objective is not to add more security features.

The objective is:

> Make session-based security behavior consistent, predictable, and testable.

Only after the complete session and CSRF lifecycle is stable should SyntaxCore move to Authorization.
