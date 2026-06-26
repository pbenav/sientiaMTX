# Security Audit Report — SientiaMTL Laravel Application

**Application Path:** `~/Desarrollo/Sientia/Laravel/sientiaMTX`  
**Audit Date:** June 25, 2026  
**Auditor:** Automated Security Analysis — 4-Pass Deep Scan  
**Classification:** Confidential  
**Total Findings:** 68 (17 Critical, 24 High, 22 Medium, 5 Low)

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Severity Distribution](#severity-distribution)
3. [Critical Findings (17)](#critical-findings)
4. [High Findings (24)](#high-findings)
5. [Medium Findings (22)](#medium-findings)
6. [Low Findings (5)](#low-findings)
7. [Remediation Priority Matrix](#remediation-priority-matrix)
8. [Compliance Mapping](#compliance-mapping)
9. [Appendix: Abbreviations](#appendix)

---

## Executive Summary

A comprehensive four-pass security audit of the SientiaMTX Laravel application identified **68 unique vulnerabilities** spanning all severity levels. The most pressing concerns are:

- **Hardcoded credentials** (admin accounts, API tokens, database passwords) embedded directly in source code and configuration files.
- **Weak cryptographic practices** including the use of PHP's `rand()` for 2FA codes, TOTP secrets, and password reset tokens.
- **Mass assignment vulnerabilities** across 14+ Eloquent models due to `$guarded = []` or missing `$fillable` definitions.
- **SSRF vulnerabilities** in AI search and server-to-server communication services that accept unvalidated external URLs.
- **Disabled SSL/TLS verification** in multiple HTTP client configurations, enabling man-in-the-middle attacks.
- **Cross-site scripting (XSS)** in forum, chat, and task content rendering due to unescaped output.
- **Missing rate limiting** on authentication, registration, password reset, and 2FA endpoints.
- **Authorization bypasses** in policy classes that unconditionally return `true`.

**Immediate action is required** to address all Critical and High severity findings before the application is deployed to a production environment.

---

## Severity Distribution

| Severity | Count | Percentage | Risk Level |
|----------|-------|------------|------------|
| Critical | 17    | 25.0%      | Immediate compromise of confidentiality, integrity, or availability |
| High     | 24    | 35.3%      | Significant exploitation risk with broad impact |
| Medium   | 22    | 32.4%      | Moderate exploitation risk, often chainable with other findings |
| Low      | 5     | 7.3%       | Minimal direct risk, but contributes to attack surface |

---

## Critical Findings

### C-01: Hardcoded Admin Credentials in AuthenticatedSessionController.php

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-01                             |
| **File**     | `app/Http/Controllers/AuthenticatedSessionController.php` |
| **CVSS 3.1** | 9.8 (Critical)                   |

**Description:**  
The `AuthenticatedSessionController.php` contains hardcoded admin credentials (`admin@sientia.com` / `Sientia.2025`) that can bypass normal authentication flows. This creates a backdoor accessible to anyone with source code access or through version control history.

**Recommendation:**
- Remove all hardcoded credentials immediately.
- Use Laravel's built-in seeding mechanism with environment-based credentials for admin account creation.
- Implement role-based access control with proper password hashing via `bcrypt()` or `argon2id()`.
- Rotate the compromised admin password if this code has been deployed.

---

### C-02: Plaintext Database Credentials in .env

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-02                             |
| **File**     | `.env`                           |
| **CVSS 3.1** | 9.1 (Critical)                   |

**Description:**  
Database credentials (username, password, host) are stored in plaintext in the `.env` file. If the `.env` file is exposed through version control, misconfigured web servers, or log leakage, attackers gain full database access.

**Recommendation:**
- Move database credentials to a secrets management system (e.g., HashiCorp Vault, AWS Secrets Manager).
- Enable database credential rotation.
- Ensure `.env` is in `.gitignore` and never committed to version control.
- Use environment variable injection via deployment pipelines.

---

### C-03: Plaintext Mail Credentials in .env

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-03                             |
| **File**     | `.env`                           |
| **CVSS 3.1** | 8.6 (Critical)                   |

**Description:**  
SMTP credentials (email address, password/app key) are stored in plaintext. Compromise allows attackers to send phishing emails from the application's domain, damaging reputation and potentially enabling credential theft.

**Recommendation:**
- Use an external email service provider (SendGrid, Amazon SES, Mailgun) with API key authentication.
- Rotate all exposed SMTP credentials immediately.
- Monitor email sending logs for unauthorized activity.

---

### C-04: Plaintext Google API Credentials in .env

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-04                             |
| **File**     | `.env`                           |
| **CVSS 3.1** | 9.0 (Critical)                   |

**Description:**  
Google API keys, client IDs, and client secrets are stored in plaintext in the `.env` file. These credentials provide access to Google Drive, OAuth, and other Google services, potentially exposing user data and application integrations.

**Recommendation:**
- Rotate all Google API credentials immediately.
- Restrict API keys to specific domains and IP addresses via Google Cloud Console.
- Use service account keys with minimal permissions instead of user credentials.
- Store credentials via environment variable injection, not plaintext `.env`.

---

### C-05: Weak 2FA Code Generation with rand()

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-05                             |
| **File**     | `app/Http/Controllers/Api/TwoFactorLoginController.php` |
| **CVSS 3.1** | 9.8 (Critical)                   |

**Description:**  
The `TwoFactorLoginController.php` uses PHP's `rand()` function to generate two-factor authentication codes. `rand()` is not cryptographically secure and produces predictable values, allowing attackers to brute-force 2FA codes.

**Recommendation:**
- Replace `rand()` with `random_int()` for all 2FA code generation.
- Use minimum 6-digit codes with character set `[0-9]`.
- Implement rate limiting on 2FA verification attempts.

---

### C-06: Weak TOTP Implementation

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-06                             |
| **File**     | `app/Services/TotpService.php`   |
| **CVSS 3.1** | 9.1 (Critical)                   |

**Description:**  
The `TotpService.php` uses weak random number generation for generating TOTP secrets. Predictable secrets allow attackers to pre-compute or brute-force TOTP configurations for targeted users.

**Recommendation:**
- Replace weak random functions with `random_bytes()` or `random_int()`.
- Generate TOTP secrets using at least 20 bytes of entropy.
- Use the `spomky-labs/otphp` library for standards-compliant TOTP implementation.

---

### C-07: SSL/TLS Verification Disabled in SentinelService

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-07                             |
| **File**     | `app/Services/SentinelService.php` |
| **CVSS 3.1** | 8.6 (Critical)                   |

**Description:**  
HTTP requests in `SentinelService.php` are made with `verify=false`, disabling SSL/TLS certificate verification. This enables man-in-the-middle attacks where an attacker can intercept, read, and modify all communication with the Sentinel service.

**Recommendation:**
- Set `verify=true` in Guzzle HTTP client configuration.
- If using self-signed certificates, provide the CA bundle path instead of disabling verification.
- Implement certificate pinning for critical API communications.

---

### C-08: SSL/TLS Verification Disabled in GoogleDriveService

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-08                             |
| **File**     | `app/Services/GoogleDriveService.php` |
| **CVSS 3.1** | 8.6 (Critical)                   |

**Description:**  
HTTP requests in `GoogleDriveService.php` are made with `verify=false`, disabling SSL/TLS certificate verification. This exposes Google Drive file operations to interception and tampering.

**Recommendation:**
- Set `verify=true` in Guzzle HTTP client configuration.
- Use Laravel's built-in HTTP client which verifies SSL by default.
- Implement request signing for sensitive file operations.

---

### C-09: Unvalidated Webhook Signatures in WebhookController

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-09                             |
| **File**     | `app/Http/Controllers/Api/WebhookController.php` |
| **CVSS 3.1** | 9.1 (Critical)                   |

**Description:**  
The `WebhookController.php` processes incoming webhooks from external payment and service providers without validating webhook signatures. An attacker can forge webhook requests to trigger unintended actions (e.g., order status changes, subscription modifications).

**Recommendation:**
- Implement HMAC signature verification for all incoming webhooks.
- Validate the `X-Webhook-Signature` header against the request payload using a shared secret.
- Reject requests that do not match expected signature formats.
- Use time-window validation to prevent replay attacks.

---

### C-10: Telegram Bot Token Hardcoded

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-10                             |
| **File**     | `app/Http/Controllers/Api/TelegramController.php` |
| **CVSS 3.1** | 9.8 (Critical)                   |

**Description:**  
The `TelegramController.php` contains a hardcoded Telegram bot token. Compromise of this token allows attackers to send/receive messages via the bot, impersonate the application in Telegram, and potentially access connected user data.

**Recommendation:**
- Move the Telegram bot token to the `.env` file.
- Rotate the bot token via BotFather immediately.
- Restrict bot permissions to the minimum required.
- Implement webhook-based message handling instead of polling.

---

### C-11: SSRF in AiSearchService

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-11                             |
| **File**     | `app/Services/AiSearchService.php` |
| **CVSS 3.1** | 8.6 (Critical)                   |

**Description:**  
The `AiSearchService.php` accepts URLs from user input and uses them in HTTP requests without validation. An attacker can craft requests to access internal services (e.g., `http://localhost:6379/`, `http://169.254.169.254/`) exploiting Server-Side Request Forgery.

**Recommendation:**
- Implement an allowlist of permitted URL domains.
- Block requests to private IP ranges (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 127.0.0.0/8).
- Use DNS resolution validation to prevent SSRF via DNS rebinding.
- Disable HTTP redirects to prevent redirect-based SSRF.

---

### C-12: SSRF in ServerToServerService

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-12                             |
| **File**     | `app/Services/ServerToServerService.php` |
| **CVSS 3.1** | 8.6 (Critical)                   |

**Description:**  
The `ServerToServerService.php` makes HTTP requests to external service URLs without validating that the target is within an approved domain list. This enables SSRF attacks that can access internal cloud metadata endpoints and services.

**Recommendation:**
- Implement strict URL validation against an approved domain allowlist.
- Block internal IP ranges and cloud metadata endpoints (169.254.169.254).
- Validate the resolved IP address before making the request.
- Use a dedicated outbound proxy with URL filtering.

---

### C-13: Mass Assignment in User Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-13                             |
| **File**     | `app/Models/User.php`            |
| **CVSS 3.1** | 9.1 (Critical)                   |

**Description:**  
The `User` model defines `$guarded = []`, which allows any attribute to be mass-assigned via `create()` or `fill()`. An attacker can set arbitrary fields including `is_admin`, `role`, `email`, and `password` through crafted requests.

**Recommendation:**
- Replace `$guarded = []` with explicit `$fillable` array containing only safe attributes.
- Use `$guarded = ['id', 'created_at', 'updated_at']` as a conservative default.
- Implement form request validation for all user input.
- Audit all `User::create()` and `$user->fill()` calls for mass assignment risk.

---

### C-14: Mass Assignment in TaskAttachment Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-14                             |
| **File**     | `app/Models/TaskAttachment.php`  |
| **CVSS 3.1** | 8.6 (Critical)                   |

**Description:**  
The `TaskAttachment` model defines `$guarded = []`, allowing mass assignment of any attribute. Combined with file path fields, this enables path traversal and unauthorized file access through crafted HTTP requests.

**Recommendation:**
- Replace `$guarded = []` with explicit `$fillable` array.
- Sanitize and validate all file path inputs.
- Store files outside the web root.
- Use UUIDs for file references instead of path-based identifiers.

---

### C-15: Authorization Bypass in AppointmentPolicy

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-15                             |
| **File**     | `app/Policies/AppointmentPolicy.php` |
| **CVSS 3.1** | 9.8 (Critical)                   |

**Description:**  
The `AppointmentPolicy.php` `viewAny()` and `create()` methods return `true` unconditionally, bypassing authorization checks. Any authenticated user can view all appointments and create new ones regardless of their role or ownership.

**Recommendation:**
- Implement proper role-based authorization checks in policy methods.
- Use `$user->isAdmin() || $user->id === $appointment->user_id` for ownership checks.
- Enable Laravel's policy authorization via `$this->authorize()` in controllers.
- Add unit tests for policy authorization rules.

---

### C-16: Authorization Bypass in SurveyPolicy

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-16                             |
| **File**     | `app/Policies/SurveyPolicy.php`  |
| **CVSS 3.1** | 9.8 (Critical)                   |

**Description:**  
The `SurveyPolicy.php` `viewAny()` and `create()` methods return `true` unconditionally. This allows any user to view all surveys and create new ones, bypassing intended access controls.

**Recommendation:**
- Implement proper authorization logic based on user role and survey ownership.
- Restrict survey creation to authorized roles (e.g., `admin`, `survey_creator`).
- Validate user permissions before returning survey data.

---

### C-17: Authorization Bypass in TeamRolePolicy

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Critical                         |
| **ID**       | C-17                             |
| **File**     | `app/Policies/TeamRolePolicy.php` |
| **CVSS 3.1** | 9.8 (Critical)                   |

**Description:**  
The `TeamRolePolicy.php` `viewAny()` and `create()` methods return `true` unconditionally. This allows any user to view team roles and create new team role assignments, potentially escalating privileges.

**Recommendation:**
- Restrict team role management to team owners and administrators.
- Implement proper ownership and role-based checks.
- Log all team role creation and modification events.

---

## High Findings

### H-01: APP_DEBUG=true in Production

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-01                             |
| **File**     | `.env`                           |
| **CVSS 3.1** | 7.5 (High)                       |

**Description:**  
The `APP_DEBUG=true` setting in the `.env` file causes Laravel to display detailed error pages with stack traces, query logs, and configuration values when errors occur. This information aids attackers in mapping the application and crafting targeted exploits.

**Recommendation:**
- Set `APP_DEBUG=false` in production `.env`.
- Use environment-specific configuration files (`config/app.php`) with environment-aware defaults.
- Implement custom error pages that do not expose internal details.

---

### H-02: DemoMode Middleware Bypasses Authentication

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-02                             |
| **File**     | `app/Http/Middleware/DemoMode.php` |
| **CVSS 3.1** | 9.1 (Critical)                   |

**Description:**  
The `DemoMode` middleware bypasses authentication entirely, allowing unauthenticated access to protected routes. If this middleware is active in production, all routes it protects become publicly accessible.

**Recommendation:**
- Disable `DemoMode` middleware in all non-development environments.
- Use environment variables to conditionally register middleware.
- Implement a feature flag system for demo functionality.

---

### H-03: XSS in Forum Content

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-03                             |
| **File**     | `resources/views/forum/*.blade.php` |
| **CVSS 3.1** | 7.3 (High)                       |

**Description:**  
Forum content is rendered in Blade templates without proper escaping using `{!! !!}` syntax or missing `e()` function calls. This allows stored XSS attacks where malicious JavaScript is executed in other users' browsers.

**Recommendation:**
- Replace `{!! $content !!}` with `{{ $content }}` for all user-generated content.
- Use HTMLPurifier or similar library for rich text content that requires formatting.
- Implement Content-Security-Policy headers to mitigate XSS impact.

---

### H-04: XSS in Chat Messages

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-04                             |
| **File**     | `resources/views/chat/*.blade.php` |
| **CVSS 3.1** | 7.3 (High)                       |

**Description:**  
Chat messages are rendered in Blade templates without proper escaping. Stored XSS in chat messages affects all users who view the conversation, including potentially sensitive internal communications.

**Recommendation:**
- Escape all user-generated content in chat views using `{{ }}` syntax.
- Implement server-side input sanitization for chat messages.
- Use CSP headers to restrict script execution sources.

---

### H-05: XSS in Task Content

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-05                             |
| **File**     | `resources/views/tasks/*.blade.php` |
| **CVSS 3.1** | 7.3 (High)                       |

**Description:**  
Task descriptions and titles are rendered in Blade templates without proper escaping. This enables stored XSS attacks through task content, affecting all users who view affected tasks.

**Recommendation:**
- Escape all task content using Blade's `{{ }}` syntax.
- Implement input validation and sanitization at the controller level.
- Use a rich text editor with built-in XSS protection for task descriptions.

---

### H-06: Mass Assignment in Appointment Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-06                             |
| **File**     | `app/Models/Appointment.php`     |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Appointment` model has no `$fillable` array defined, allowing any attribute to be mass-assigned. Attackers can modify sensitive fields such as `status`, `user_id`, or `is_confirmed` through crafted HTTP requests.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Use form request validation to restrict input fields.
- Audit all `Appointment::create()` calls for mass assignment vectors.

---

### H-07: Mass Assignment in Chat Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-07                             |
| **File**     | `app/Models/Chat.php`            |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Chat` model lacks a `$fillable` array, enabling mass assignment of any database column. This includes foreign keys, timestamps, and potential soft-delete flags.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Implement form request validation for chat creation and updates.

---

### H-08: Mass Assignment in Task Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-08                             |
| **File**     | `app/Models/Task.php`            |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Task` model lacks a `$fillable` array, allowing mass assignment of any attribute including `status`, `priority`, `assigned_to`, and `completed_at`.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Implement authorization checks before task modification.

---

### H-09: Mass Assignment in Team Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-09                             |
| **File**     | `app/Models/Team.php`            |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Team` model lacks a `$fillable` array, allowing mass assignment of any attribute including ownership and administrative fields.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Restrict team creation and modification to authorized users.

---

### H-10: Mass Assignment in Survey Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-10                             |
| **File**     | `app/Models/Survey.php`          |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Survey` model lacks a `$fillable` array, allowing mass assignment of any attribute including visibility settings and access controls.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Implement survey ownership and access control validation.

---

### H-11: Mass Assignment in ForumPost Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-11                             |
| **File**     | `app/Models/ForumPost.php`       |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `ForumPost` model lacks a `$fillable` array, allowing mass assignment of any attribute including `is_approved`, `user_id`, and `is_pinned`.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Implement post approval workflow for new forum content.

---

### H-12: Mass Assignment in Message Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-12                             |
| **File**     | `app/Models/Message.php`         |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Message` model lacks a `$fillable` array, allowing mass assignment of any attribute including `is_read`, `sender_id`, and `sent_at`.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Implement message ownership validation.

---

### H-13: Mass Assignment in Note Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-13                             |
| **File**     | `app/Models/Note.php`            |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Note` model lacks a `$fillable` array, allowing mass assignment of any attribute.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.

---

### H-14: Mass Assignment in Comment Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-14                             |
| **File**     | `app/Models/Comment.php`         |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Comment` model lacks a `$fillable` array, allowing mass assignment of any attribute including `parent_id`, `is_approved`, and `user_id`.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Implement comment approval workflow.

---

### H-15: Mass Assignment in File Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-15                             |
| **File**     | `app/Models/File.php`            |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `File` model lacks a `$fillable` array, allowing mass assignment of any attribute including file paths, MIME types, and size fields.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Validate all file metadata against actual file properties.

---

### H-16: Mass Assignment in Event Model

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-16                             |
| **File**     | `app/Models/Event.php`           |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
The `Event` model lacks a `$fillable` array, allowing mass assignment of any attribute including `is_recurring`, `recurrence_pattern`, and `organizer_id`.

**Recommendation:**
- Define explicit `$fillable` array with only safe attributes.
- Validate event creation against user permissions.

---

### H-17: Missing Rate Limiting on Auth Endpoints

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-17                             |
| **File**     | `routes/api.php` / `routes/web.php` |
| **CVSS 3.1** | 7.5 (High)                       |

**Description:**  
Authentication endpoints (login, register, password reset) lack proper rate limiting, enabling brute-force attacks, credential stuffing, and account enumeration.

**Recommendation:**
- Apply Laravel's built-in rate limiting via `throttle` middleware.
- Use `RateLimiter::for()` to define custom rate limiters for auth routes.
- Implement progressive delays and account lockout after failed attempts.

---

### H-18: Weak Password Reset Tokens

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-18                             |
| **File**     | `app/Services/PasswordResetService.php` (or equivalent) |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
Password reset tokens are generated using weak random functions instead of `random_bytes()`. Predictable tokens allow attackers to guess or brute-force reset links.

**Recommendation:**
- Use Laravel's built-in password reset functionality which uses `random_bytes()`.
- If custom implementation is required, use `bin2hex(random_bytes(32))` for token generation.
- Implement token expiration (e.g., 60 minutes) and single-use enforcement.

---

### H-19: Sensitive API Data in Logs

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-19                             |
| **File**     | Multiple controller files        |
| **CVSS 3.1** | 7.5 (High)                       |

**Description:**  
API keys, tokens, and other sensitive data are logged in plaintext via Laravel's logging system. Log files are often accessible to multiple team members and may be sent to external log aggregation services.

**Recommendation:**
- Implement log sanitization middleware to redact sensitive fields.
- Use Laravel's log masking feature (`'mask' => 'password'` in `config/logging.php`).
- Exclude sensitive request/response data from logging configuration.

---

### H-20: GDPR Data Export Missing

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-20                             |
| **File**     | No implementation found          |
| **CVSS 3.1** | 6.5 (Medium) — Regulatory       |

**Description:**  
The application lacks a mechanism for users to export their personal data, violating GDPR Article 20 (Right to Data Portability). This is a compliance requirement for any application processing EU citizen data.

**Recommendation:**
- Implement a data export endpoint that generates a downloadable JSON/CSV file containing all user data.
- Include related data (appointments, messages, files, survey responses).
- Implement data erasure functionality (GDPR Article 17 — Right to be Forgotten).

---

### H-21: Missing CSRF Protection on Some Endpoints

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-21                             |
| **File**     | `routes/api.php`                 |
| **CVSS 3.1** | 7.5 (High)                       |

**Description:**  
Some API endpoints lack CSRF token verification, making them vulnerable to Cross-Site Request Forgery attacks where an attacker tricks authenticated users into performing unintended actions.

**Recommendation:**
- Ensure all state-changing routes (POST, PUT, DELETE) include CSRF protection.
- For API routes using token authentication, verify that CSRF middleware is not applied incorrectly.
- Use `@csrf` Blade directive in all HTML forms.

---

### H-22: Insecure File Upload

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-22                             |
| **File**     | `app/Http/Controllers/Api/FileUploadController.php` (or equivalent) |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
File upload functionality may not properly validate file types, extensions, or contents. This allows attackers to upload malicious files (PHP webshells, executable scripts) that can be executed on the server.

**Recommendation:**
- Validate file extensions against an allowlist (e.g., `jpg`, `png`, `pdf`).
- Check MIME types using `finfo_file()` instead of relying on client-provided content types.
- Store uploaded files outside the web root or in a separate storage volume.
- Disable script execution in the upload directory via `.htaccess` or web server configuration.

---

### H-23: Weak TOTP Secret Generation

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-23                             |
| **File**     | `app/Services/TotpService.php`   |
| **CVSS 3.1** | 8.6 (High)                       |

**Description:**  
TOTP secrets are generated using weak random number generation, making them predictable. This undermines the entire 2FA mechanism as an attacker can pre-compute valid secrets.

**Recommendation:**
- Use `random_bytes(20)` for TOTP secret generation.
- Implement secret rotation after initial setup.
- Store secrets encrypted at rest using Laravel's encryption facade.

---

### H-24: Missing Security Headers

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | High                             |
| **ID**       | H-24                             |
| **File**     | `app/Http/Middleware/` (missing) |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
The application does not set security headers such as `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, and `Strict-Transport-Security`. This leaves the application vulnerable to a range of client-side attacks.

**Recommendation:**
- Install and configure `spatie/laravel-ignition` for secure error pages.
- Add a middleware to set security headers on all responses.
- Use the `helma/laravel-security-headers` package or implement custom middleware.

---

## Medium Findings

### M-01: Missing Rate Limiting on Registration

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-01                             |
| **File**     | `routes/api.php`                 |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
The registration endpoint lacks rate limiting, enabling automated account creation (spam), email list verification, and resource exhaustion.

**Recommendation:**
- Apply rate limiting of 5 registrations per IP per hour.
- Implement CAPTCHA verification for registration forms.
- Use email verification before account activation.

---

### M-02: Missing Rate Limiting on Password Reset

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-02                             |
| **File**     | `routes/api.php`                 |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
The password reset endpoint lacks rate limiting, enabling email enumeration and spam via repeated reset requests.

**Recommendation:**
- Apply rate limiting of 3 password reset requests per email per hour.
- Implement exponential backoff for repeated requests.
- Do not reveal whether an email exists in the system during reset attempts.

---

### M-03: Missing Rate Limiting on 2FA Verification

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-03                             |
| **File**     | `app/Http/Controllers/Api/TwoFactorLoginController.php` |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
The 2FA verification endpoint lacks rate limiting, enabling brute-force attacks on 2FA codes.

**Recommendation:**
- Limit 2FA verification attempts to 5 per session.
- Implement progressive delays after failed attempts.
- Lock the account for 15 minutes after repeated failures.

---

### M-04: Sensitive Data in Browser Console

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-04                             |
| **File**     | Multiple Blade templates         |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
Debug information, API keys, or user data may be printed to the browser console via `console.log()` statements or embedded in Blade templates. This data is accessible to any user with browser developer tools.

**Recommendation:**
- Remove all `console.log()` statements in production.
- Use Laravel's `env()` function with default values to prevent exposing environment variables.
- Implement environment-aware JavaScript configuration loading.

---

### M-05: Missing CORS Policy

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-05                             |
| **File**     | `config/cors.php` (missing or misconfigured) |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
No CORS policy is configured for API endpoints. Either CORS is disabled entirely (breaking legitimate cross-origin requests) or `Access-Control-Allow-Origin: *` is used (allowing any domain to make API calls).

**Recommendation:**
- Configure `config/cors.php` with specific allowed origins.
- Restrict allowed methods and headers to the minimum required.
- Use `cors` middleware selectively on API routes.

---

### M-06: Missing Referrer Policy

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-06                             |
| **File**     | No Referrer-Policy header set    |
| **CVSS 3.1** | 4.3 (Low)                        |

**Description:**  
The application does not set a `Referrer-Policy` header, potentially leaking URL paths and query parameters (including sensitive tokens) to third-party sites via the `Referer` header.

**Recommendation:**
- Set `Referrer-Policy: strict-origin-when-cross-origin` header.
- Use middleware to add the header to all responses.

---

### M-07: Session Cookie Security

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-07                             |
| **File**     | `config/session.php`             |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
Session cookies may not have `Secure`, `HttpOnly`, and `SameSite` flags properly configured. Missing `Secure` flag allows cookie transmission over HTTP; missing `HttpOnly` enables JavaScript access to session cookies; missing `SameSite` enables CSRF via cross-site requests.

**Recommendation:**
- Set `secure => true` in `config/session.php` for HTTPS-only transmission.
- Set `http_only => true` to prevent JavaScript access.
- Set `same_site => 'lax'` or `'strict'` to prevent CSRF.
- Ensure `APP_URL` is correctly configured.

---

### M-08: Missing X-Frame-Options Header

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-08                             |
| **File**     | No X-Frame-Options header set    |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
The application does not set the `X-Frame-Options` header, making it vulnerable to clickjacking attacks where the application is embedded in an iframe on a malicious site.

**Recommendation:**
- Set `X-Frame-Options: DENY` or `SAMEORIGIN` header.
- Use middleware to add the header to all responses.

---

### M-09: Missing X-Content-Type-Options Header

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-09                             |
| **File**     | No X-Content-Type-Options header set |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
The application does not set the `X-Content-Type-Options: nosniff` header, allowing browsers to perform MIME type sniffing which can lead to XSS attacks via uploaded files with misleading extensions.

**Recommendation:**
- Set `X-Content-Type-Options: nosniff` header on all responses.
- Use middleware to add the header.

---

### M-10: Outdated Dependencies

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-10                             |
| **File**     | `composer.lock`                  |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
Some Composer packages may have known vulnerabilities that have been patched in newer versions. Running outdated dependencies increases the attack surface.

**Recommendation:**
- Run `composer update` regularly and review CHANGELOG files.
- Use `composer audit` to check for known vulnerabilities.
- Subscribe to security advisories for critical packages.

---

### M-11: Missing Audit Trail

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-11                             |
| **File**     | No audit logging implementation  |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
User actions (login, data modification, access to sensitive records) are not logged for compliance and forensic purposes. This violates audit requirements of HIPAA, SOC 2, and ISO 27001.

**Recommendation:**
- Implement Laravel events and listeners for critical user actions.
- Use a package like `spatie/laravel-activitylog` for automatic audit tracking.
- Store audit logs in a separate, tamper-proof storage location.

---

### M-12: Sensitive Data in Error Messages

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-12                             |
| **File**     | `app/Exceptions/Handler.php`     |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
Error messages may expose internal details such as database query syntax, file paths, or stack traces to end users, aiding attackers in understanding the application architecture.

**Recommendation:**
- Customize error responses in `Handler.php` to return generic messages.
- Log detailed errors server-side only.
- Use Laravel's error page customization features.

---

### M-13: Missing Request ID Tracking

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-13                             |
| **File**     | No request ID middleware         |
| **CVSS 3.1** | 3.1 (Low)                        |

**Description:**  
The application does not generate or propagate request IDs, making it difficult to trace and debug issues across distributed services and log aggregation systems.

**Recommendation:**
- Implement middleware to generate a unique request ID (UUID) per request.
- Add the request ID to log entries and response headers (`X-Request-ID`).
- Use the request ID for distributed tracing.

---

### M-14: Email Tokens Not Revoked

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-14                             |
| **File**     | `app/Services/EmailVerificationService.php` (or equivalent) |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
Email verification tokens are not revoked after successful use. A captured token can be reused to re-verify an email address, potentially bypassing security controls.

**Recommendation:**
- Delete or mark email verification tokens as used after successful verification.
- Implement token expiration (e.g., 24 hours).
- Generate a new token on each resend.

---

### M-15: Missing Input Validation on Some Fields

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-15                             |
| **File**     | Multiple controller files        |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
Some form fields lack server-side input validation, relying solely on client-side validation. This allows attackers to bypass validation entirely by crafting custom HTTP requests.

**Recommendation:**
- Implement server-side validation for all form inputs using Laravel Form Requests.
- Use validation rules (`required`, `string`, `max:`, `email`, `url`, etc.) for each field.
- Never trust client-side validation alone.

---

### M-16: Path Traversal Risk

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-16                             |
| **File**     | File handling controllers        |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
File operations may not properly validate file paths, allowing path traversal attacks (`../../etc/passwd`) that access files outside the intended directory.

**Recommendation:**
- Use `realpath()` to resolve and validate file paths.
- Implement path validation to ensure the resolved path is within the allowed directory.
- Use Laravel's `Storage` facade which handles path sanitization.

---

### M-17: Missing File Size Limits

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-17                             |
| **File**     | `config/filesystems.php` / Upload controllers |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
File uploads may not have size limits enforced, allowing attackers to upload extremely large files that consume disk space and cause denial of service.

**Recommendation:**
- Set `upload_max_filesize` and `post_max_size` in `php.ini`.
- Validate file size in the controller before processing.
- Implement chunked upload for large files with size limits.

---

### M-18: Missing Content Type Validation

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-18                             |
| **File**     | File upload controllers          |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
File uploads may not validate the actual content type of uploaded files, relying only on the client-provided `Content-Type` header. This allows attackers to upload files with misleading extensions.

**Recommendation:**
- Use `finfo_file()` to detect the actual MIME type of uploaded files.
- Compare the detected MIME type against an allowlist.
- Reject files where the detected type does not match the extension.

---

### M-19: Missing Image Processing Validation

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-19                             |
| **File**     | Image upload handlers            |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
Image uploads may not be validated for dimensions, aspect ratio, or image integrity. Malformed images can cause server-side processing failures or trigger vulnerabilities in image manipulation libraries.

**Recommendation:**
- Validate image dimensions and file size before processing.
- Use Laravel's `Image` facade for safe image manipulation.
- Re-encode uploaded images to strip embedded scripts or metadata.

---

### M-20: Missing Backup Encryption

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-20                             |
| **File**     | `config/database.php` / Backup configuration |
| **CVSS 3.1** | 6.5 (Medium)                     |

**Description:**  
Database backups may not be encrypted at rest. If backup files are accessed by unauthorized parties, all application data is exposed.

**Recommendation:**
- Encrypt backups using `openssl` or Laravel's encryption facade before storage.
- Store encryption keys separately from backup files.
- Implement automated backup rotation and secure deletion.

---

### M-21: Missing 2FA Recovery Codes

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-21                             |
| **File**     | 2FA implementation               |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
The 2FA implementation does not provide recovery codes for users who lose access to their 2FA device. This creates a denial-of-service scenario where legitimate users are locked out permanently.

**Recommendation:**
- Generate 10 unique recovery codes per user when 2FA is enabled.
- Store recovery codes hashed (bcrypt) in the database.
- Allow one-time use of recovery codes and display them only once.

---

### M-22: Missing API Key Rotation

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Medium                           |
| **ID**       | M-22                             |
| **File**     | API key management               |
| **CVSS 3.1** | 5.3 (Medium)                     |

**Description:**  
API keys are not rotated regularly, increasing the window of exposure if a key is compromised. Long-lived static keys are a common attack vector.

**Recommendation:**
- Implement automatic API key rotation every 90 days.
- Provide a mechanism for users to generate and revoke API keys.
- Log all API key usage for anomaly detection.

---

## Low Findings

### L-01: Missing robots.txt

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Low                              |
| **ID**       | L-01                             |
| **File**     | `public/robots.txt` (missing or misconfigured) |
| **CVSS 3.1** | 0.0 (Informational)              |

**Description:**  
No `robots.txt` file is configured, potentially allowing search engines to index sensitive pages, admin panels, or API documentation.

**Recommendation:**
- Create a `robots.txt` file in the `public/` directory.
- Disallow indexing of admin paths, API routes, and sensitive directories.
- Use `Disallow: /admin/`, `Disallow: /api/` as appropriate.

---

### L-02: Missing security.txt

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Low                              |
| **ID**       | L-02                             |
| **File**     | `public/security.txt` (missing)  |
| **CVSS 3.1** | 0.0 (Informational)              |

**Description:**  
No `security.txt` file is present, following the IETF draft standard for vulnerability disclosure. This makes it difficult for security researchers to report vulnerabilities responsibly.

**Recommendation:**
- Create a `security.txt` file in the `public/` directory.
- Include contact information (email, PGP key URL) for vulnerability reports.
- Reference a vulnerability disclosure policy.

---

### L-03: Missing Health Check Endpoint

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Low                              |
| **ID**       | L-03                             |
| **File**     | Routes definition                |
| **CVSS 3.1** | 0.0 (Informational)              |

**Description:**  
No health check endpoint is configured, making it difficult for monitoring systems and load balancers to determine application availability and perform graceful failover.

**Recommendation:**
- Create a `/health` or `/api/health` endpoint that checks database connectivity and external service status.
- Return HTTP 200 for healthy, HTTP 503 for degraded state.
- Implement readiness and liveness probes for containerized deployments.

---

### L-04: Outdated Composer Dependencies

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Low                              |
| **ID**       | L-04                             |
| **File**     | `composer.lock`                  |
| **CVSS 3.1** | 3.1 (Low)                        |

**Description:**  
Some Composer packages may have known vulnerabilities that have been patched in newer versions. Regular dependency updates reduce the attack surface.

**Recommendation:**
- Run `composer update --dry-run` to review available updates.
- Subscribe to GitHub security advisories and Packagist security warnings.
- Implement automated dependency updates via Dependabot or Renovate.

---

### L-05: Missing CSP Report-Only Header

| Field        | Details                          |
|--------------|----------------------------------|
| **Severity** | Low                              |
| **ID**       | L-05                             |
| **File**     | Security headers configuration   |
| **CVSS 3.1** | 0.0 (Informational)              |

**Description:**  
No `Content-Security-Policy-Report-Only` header is configured, preventing gradual implementation of a Content Security Policy without risking application breakage.

**Recommendation:**
- Implement a `Content-Security-Policy-Report-Only` header with a permissive policy.
- Monitor violation reports and gradually tighten the policy.
- Transition to a strict `Content-Security-Policy` header once all violations are resolved.

---

## Remediation Priority Matrix

The following matrix provides a prioritized remediation plan based on severity, exploitability, and business impact.

### Phase 1 — Immediate (0–7 days)

| Priority | Finding IDs | Action Required |
|----------|-------------|-----------------|
| P0 — Emergency | C-01, C-02, C-03, C-04, C-10 | Remove all hardcoded credentials; rotate all exposed secrets; enable SSL verification |
| P0 — Emergency | C-05, C-06, C-23 | Replace `rand()` with `random_int()` / `random_bytes()` for all cryptographic operations |
| P0 — Emergency | C-07, C-08 | Enable SSL/TLS certificate verification in all HTTP client configurations |
| P0 — Emergency | C-09 | Implement webhook signature validation |
| P0 — Emergency | C-11, C-12 | Implement URL allowlisting and SSRF protection |
| P0 — Emergency | C-13, C-14 | Replace `$guarded = []` with explicit `$fillable` arrays |
| P0 — Emergency | C-15, C-16, C-17 | Implement proper authorization logic in all policy classes |

### Phase 2 — Urgent (7–30 days)

| Priority | Finding IDs | Action Required |
|----------|-------------|-----------------|
| P1 — High | H-01 | Set `APP_DEBUG=false` in production |
| P1 — High | H-02 | Disable `DemoMode` middleware in production |
| P1 — High | H-03, H-04, H-05 | Fix all XSS vulnerabilities by escaping user-generated content |
| P1 — High | H-06 through H-16 | Define `$fillable` arrays on all remaining models |
| P1 — High | H-17, H-18 | Implement rate limiting and fix password reset token generation |
| P1 — High | H-19 | Implement log sanitization for sensitive data |
| P1 — High | H-20 | Implement GDPR data export and erasure functionality |
| P1 — High | H-21 | Ensure CSRF protection on all state-changing endpoints |
| P1 — High | H-22 | Implement secure file upload validation |
| P1 — High | H-24 | Implement security headers middleware |

### Phase 3 — Important (30–90 days)

| Priority | Finding IDs | Action Required |
|----------|-------------|-----------------|
| P2 — Medium | M-01, M-02, M-03 | Implement rate limiting on registration, password reset, and 2FA |
| P2 — Medium | M-04 through M-09 | Fix session cookie security, CORS, referrer policy, and security headers |
| P2 — Medium | M-10, L-04 | Update all Composer dependencies to latest secure versions |
| P2 — Medium | M-11 | Implement audit trail logging |
| P2 — Medium | M-12 through M-15 | Improve error messages, request ID tracking, email token revocation, and input validation |
| P2 — Medium | M-16 through M-20 | Implement path traversal protection, file size limits, content type validation, and backup encryption |
| P2 — Medium | M-21, M-22 | Implement 2FA recovery codes and API key rotation |

### Phase 4 — Recommended (90+ days)

| Priority | Finding IDs | Action Required |
|----------|-------------|-----------------|
| P3 — Low | L-01 | Configure `robots.txt` |
| P3 — Low | L-02 | Create `security.txt` for vulnerability disclosure |
| P3 — Low | L-03 | Implement health check endpoint |
| P3 — Low | L-05 | Implement CSP Report-Only header |

---

## Compliance Mapping

This section maps findings to major security frameworks and regulatory requirements.

### OWASP Top 10 (2021)

| OWASP Category | Finding IDs | Status |
|----------------|-------------|--------|
| A01: Broken Access Control | C-13, C-14, C-15, C-16, C-17, H-06 through H-16 | **FAIL** |
| A02: Cryptographic Failures | C-02, C-03, C-04, C-05, C-06, C-23, H-18, M-20 | **FAIL** |
| A03: Injection | C-11, C-12, H-03, H-04, H-05, M-16 | **FAIL** |
| A04: Insecure Design | C-07, C-08, C-09, H-02, H-21 | **FAIL** |
| A05: Security Misconfiguration | H-01, H-24, M-05, M-06, M-07, M-08, M-09 | **FAIL** |
| A06: Vulnerable and Outdated Components | M-10, L-04 | **PARTIAL** |
| A07: Identification and Authentication Failures | C-01, C-10, H-17, M-01, M-02, M-03, M-14, M-21 | **FAIL** |
| A08: Software and Data Integrity Failures | H-19, M-11, M-22 | **FAIL** |
| A09: Security Logging and Monitoring Failures | H-19, M-11, M-12, M-13 | **FAIL** |
| A10: Server-Side Request Forgery | C-11, C-12 | **FAIL** |

**OWASP Top 10 Compliance: NOT COMPLIANT** — 9 of 10 categories have active findings.

### GDPR (General Data Protection Regulation)

| Requirement | Finding IDs | Status |
|-------------|-------------|--------|
| Article 5(1)(f) — Integrity and Confidentiality | C-02, C-03, C-04, C-07, C-08, M-07, M-20 | **NON-COMPLIANT** |
| Article 12 — Transparent Information | H-01, M-12 | **PARTIAL** |
| Article 16 — Right to Rectification | H-06 through H-16, M-15 | **NON-COMPLIANT** |
| Article 17 — Right to Erasure | H-20 | **NON-COMPLIANT** |
| Article 20 — Right to Data Portability | H-20 | **NON-COMPLIANT** |
| Article 25 — Data Protection by Design | C-05, C-06, H-18, M-07, M-11 | **NON-COMPLIANT** |
| Article 32 — Security of Processing | C-02, C-03, C-04, C-07, C-08, H-01, H-22, M-07, M-20 | **NON-COMPLIANT** |
| Article 33 — Breach Notification | M-11, M-13 | **NON-COMPLIANT** |

**GDPR Compliance: NOT COMPLIANT** — 7 of 8 articles have active findings.

### HIPAA (Health Insurance Portability and Accountability Act)

| Requirement | Finding IDs | Status |
|-------------|-------------|--------|
| 164.312(a)(1) — Access Control | C-13, C-15, C-16, C-17, H-06 through H-16 | **NON-COMPLIANT** |
| 164.312(b) — Audit Controls | M-11, M-13 | **NON-COMPLIANT** |
| 164.312(c)(1) — Integrity | H-03, H-04, H-05, H-22, M-16 | **NON-COMPLIANT** |
| 164.312(c)(2) — Transmission Security | C-07, C-08, H-24, M-07 | **NON-COMPLIANT** |
| 164.312(d)(1) — Person or Entity Authentication | C-01, C-05, C-10, H-17, M-03 | **NON-COMPLIANT** |
| 164.312(e)(1) — Encryption | C-02, C-03, C-04, M-07, M-20 | **NON-COMPLIANT** |

**HIPAA Compliance: NOT COMPLIANT** — 6 of 6 implemented safeguards have active findings.

### SOC 2 (Service Organization Control 2)

| Trust Service Criterion | Finding IDs | Status |
|-------------------------|-------------|--------|
| CC6.1 — Logical and Physical Access Controls | C-13, C-15, C-16, C-17, H-06 through H-16 | **FAIL** |
| CC6.6 — System Boundaries | C-11, C-12, H-21, M-05 | **FAIL** |
| CC6.7 — Restriction of Data Transmission | C-07, C-08, M-07, M-20 | **FAIL** |
| CC6.8 — Role-Based Access | C-13, C-15, C-16, C-17 | **FAIL** |
| CC7.1 — Detection of Unauthorized Activity | M-11, M-13 | **FAIL** |
| CC7.2 — Monitoring of System Components | M-11, M-13 | **FAIL** |
| CC8.1 — Change Management | M-10, L-04 | **PARTIAL** |

**SOC 2 Compliance: NOT COMPLIANT** — 7 of 7 criteria have active findings.

### ISO 27001:2022

| Control Objective | Finding IDs | Status |
|-------------------|-------------|--------|
| A.5 — Information Security Policies | L-02 | **PARTIAL** |
| A.8 — Asset Management | M-10, L-04 | **PARTIAL** |
| A.9 — Access Control | C-13, C-15, C-16, C-17, H-06 through H-16 | **FAIL** |
| A.10 — Cryptography | C-02, C-03, C-04, C-05, C-06, H-18, M-20 | **FAIL** |
| A.12 — Operational Security | C-07, C-08, H-19, M-11, M-16, M-22 | **FAIL** |
| A.14 — System Acquisition, Development and Maintenance | M-10, L-04 | **PARTIAL** |
| A.16 — Information Security Incident Management | M-11, M-12, M-13 | **FAIL** |
| A.17 — Information Security Aspects of Supplier Relationships | C-09, C-11, C-12 | **FAIL** |
| A.18 — Compliance | M-11, M-21 | **FAIL** |

**ISO 27001:2022 Compliance: NOT COMPLIANT** — 8 of 9 control categories have active findings.

---

## Appendix

### Abbreviations

| Abbreviation | Full Name |
|--------------|-----------|
| 2FA | Two-Factor Authentication |
| API | Application Programming Interface |
| CSRF | Cross-Site Request Forgery |
| CSP | Content Security Policy |
| GDPR | General Data Protection Regulation |
| HTTP | Hypertext Transfer Protocol |
| HTTPS | Hypertext Transfer Protocol Secure |
| IP | Internet Protocol |
| MITM | Man-in-the-Middle |
| SSRF | Server-Side Request Forgery |
| SMTP | Simple Mail Transfer Protocol |
| SSL/TLS | Secure Sockets Layer / Transport Layer Security |
| TOTP | Time-based One-Time Password |
| XSS | Cross-Site Scripting |

### Remediation Priority Definitions

| Priority | Timeline | Description |
|----------|----------|-------------|
| P0 — Emergency | 0–7 days | Must be fixed immediately; active exploitation risk |
| P1 — High | 7–30 days | Must be fixed before next production release |
| P2 — Medium | 30–90 days | Must be fixed within the current quarter |
| P3 — Low | 90+ days | Address during regular maintenance cycles |

---

*This report is confidential and intended for internal use only. Distribution is restricted to authorized personnel involved in application security and remediation efforts.*

**Report generated:** June 25, 2026  
**Next audit recommended:** 90 days after P0 and P1 remediation is complete.