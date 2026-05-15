# SSLMon
### SSL Certificate Monitoring Platform
**Product Requirements Document** · v0.2 — MVP Focus

---

| Field | Detail |
|---|---|
| **Version** | 0.2 — MVP Focus |
| **Status** | In Review |
| **Author** | Founder |
| **Stack** | Laravel 12 (API) · Vue.js (Options API) · Vuex |
| **Audience** | Development Team, Stakeholders |

---

## 1. Overview

### 1.1 Problem Statement

SSL certificates are a critical part of website security, yet they expire on fixed schedules. Website owners frequently miss expiry deadlines due to lack of visibility, leading to browser warnings, broken trust indicators, and potential downtime. The process of manually tracking multiple certificates across domains is error-prone and time-consuming.

### 1.2 Product Vision

SSLMon is a web-based SaaS platform that continuously monitors SSL certificates for user-registered domains and sends proactive notifications before expiry.

### 1.3 Goals

- Provide users with real-time visibility into the SSL health of all their domains.
- Deliver timely, configurable expiry notifications via email.
- Build a trustworthy, reliable MVP with a clean developer-grade UI.

### 1.4 Non-Goals (MVP)

- Mobile native apps (iOS/Android).
- Multi-user team accounts / organisation workspaces.
- Support for internal/private CA certificates.
- Automatic certificate renewal.

---

## 2. Pricing Plans

| | **Free** | **Pro** |
|---|---|---|
| **Price** | $0 / mo | $10 / mo |
| **Domains** | 3 | 25 |
| **Monitoring** | Hourly | Hourly |
| **Notifications** | 30, 15, 7, 1 day (fixed) | Fixed + custom intervals |

The Free plan lets individuals try the product and cover personal projects. The Pro plan targets developers and small agencies managing client domains who need more domains and notification flexibility.

---

## 3. Target Users

SSLMon targets technically minded individuals and small businesses who manage one or more public-facing websites.

- Freelance developers managing client websites.
- Small agency teams running multiple domains.
- Indie hackers / startup founders with several side projects.
- Solo webmasters who have previously missed an SSL renewal.

---

## 4. Authentication

### 4.1 Supported Methods

- **Email / Password** — Standard credential-based registration and login.
- **GitHub OAuth** — One-click sign-in for developer users.

### 4.2 Requirements

- Passwords hashed using bcrypt (Laravel default).
- Authentication handled via cookie-based sessions (no token headers).
- Email verification required before a user can add domains.
- Password reset via emailed token link.
- GitHub OAuth via Laravel Socialite package.
- Sessions protected with CSRF tokens.
- Remember-me functionality (30-day persistent session option).

---

## 5. Feature Specifications

### 5.1 Feature Overview

| Feature | Description | Priority |
|---|---|---|
| Domain Management | Add, edit, and remove domains to monitor | High |
| SSL Monitoring | Hourly background check of certificate status | High |
| Expiry Dashboard | Visual overview of all domains & cert health | High |
| Email Notifications | Configurable alerts before expiry, per plan | High |
| Auth — Email/Password | Standard registration, login, password reset | High |
| Auth — GitHub OAuth | Sign in with GitHub via Socialite | High |

### 5.2 Domain Management

- Users can add a domain by entering a hostname (e.g. `example.com`).
- The system immediately performs an initial SSL check on add.
- Users can add a friendly label/nickname to each domain.
- Users can pause monitoring for a domain without deleting it.
- Users can remove a domain; all associated notification history is deleted.
- Domain limits enforced by plan: **Free** — 3 domains, **Pro** — 25 domains.
- Free plan users are shown a clear upgrade prompt when they hit the limit.

### 5.3 SSL Monitoring Engine

The monitoring engine is a Laravel scheduled job that runs every hour via the built-in task scheduler (`app/Console/Kernel.php`). It inspects each active domain's SSL certificate via a TLS handshake and stores the raw expiry date. Days remaining is derived at query/display time — not stored.

**Data captured per check:**
- Certificate expiry date
- Certificate issuer / authority
- Whether the certificate is currently valid

**Additional behaviour:**
- Check results are stored with a timestamp in a `certificate_checks` table.
- If a check fails (connection refused, DNS error), the domain is flagged with a `check_failed` status and the user is notified by email.

### 5.4 Notification System

Each domain has a single active notification threshold. When the days until expiry (derived from the stored expiry date) falls at or below that threshold, an email is sent.

- **Free plan:** User selects one fixed threshold — 30, 15, 7, or 1 day before expiry.
- **Pro plan:** User selects from fixed options or enters a custom number of days.
- One notification per domain per expiry cycle — no repeat emails for the same threshold.
- Notification log stored and visible in the dashboard.
- Emails sent via Laravel Mail + Resend.

> **Email design note:** Templates should be clean, branded, and mobile-friendly. Include domain name, expiry date, days remaining, and a direct link to the dashboard entry for that domain.

---

## 6. Technical Stack

| Layer | Technology / Notes |
|---|---|
| **Backend API** | Laravel 12 — REST API with cookie-based session auth. |
| **Frontend** | Vue.js (Options API) — SPA. Vue Router, Vuex for state management. |
| **Database** | MySQL — primary data store and queue driver. |
| **Job Queue** | Laravel Queues with database driver — monitoring jobs and email dispatch. |
| **Scheduler** | Laravel Task Scheduler (cron) — hourly monitoring sweep. |
| **Email** | Laravel Mail + Resend. |
| **SSL Checks** | PHP `stream_socket_client()` or `openssl_x509_parse()` to read certificate metadata. |
| **Auth** | Cookie-based sessions + GitHub OAuth via Laravel Socialite. |

---

## 7. Data Model (Core Tables)

### `users`
`id`, `name`, `email`, `password`, `github_id`, `email_verified_at`, `remember_token`, `timestamps`

### `domains`
`id`, `user_id` (FK), `hostname`, `label`, `is_active`, `last_checked_at`, `last_status`, `timestamps`

### `certificate_checks`
`id`, `domain_id` (FK), `checked_at`, `issuer`, `expiry_date`, `is_valid`, `error_message`

> `days_remaining` is not stored — derived at query time from `expiry_date`.

### `notification_settings`
`id`, `domain_id` (FK), `threshold_days` (integer), `timestamps`

> One row per domain. Presence of the row means active — no `is_active` flag needed.

### `notification_logs`
`id`, `domain_id` (FK), `threshold_days`, `sent_at`, `email_address`

---
erDiagram
  users {
    bigint id PK
    string name
    string email
    string password
    string github_id
    timestamp email_verified_at
    string remember_token
    timestamps timestamps
  }
  domains {
    bigint id PK
    bigint user_id FK
    string hostname
    string label
    boolean is_active
    timestamp last_checked_at
    string last_status
    timestamps timestamps
  }
  certificate_checks {
    bigint id PK
    bigint domain_id FK
    timestamp checked_at
    string issuer
    date expiry_date
    boolean is_valid
    text error_message
  }
  notification_settings {
    bigint id PK
    bigint domain_id FK
    integer threshold_days
    timestamps timestamps
  }
  users ||--o{ domains : "owns"
  domains ||--o{ certificate_checks : "has"
  domains ||--|| notification_settings : "has"


## 8. MVP Scope

Everything below ships together as the initial release.

- User authentication (email/password + GitHub OAuth).
- Domain add / edit / remove with plan-based limits.
- Hourly SSL monitoring engine (DB queue driver).
- Expiry dashboard with per-domain status.
- Single-threshold email notifications via Resend.
- Notification log per domain.
- Free and Pro plan enforcement.

---

## 9. Open Questions & Risks

- **Plan enforcement on downgrade** — How do we handle accounts that exceed the free limit after downgrading (e.g. had 10 domains on Pro, downgraded)? Pause excess domains or block new checks?
- **Rate limiting at scale** — Hourly checks across many domains need careful queue sizing to avoid delays.
- **Monitoring frequency** — Is hourly the right default? Worth revisiting once real usage patterns emerge.