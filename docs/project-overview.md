# Project Overview — CPX Secreto

CPX Secreto is a mobile-first Secret Santa (`Amigo Secreto`) platform for organizing groups, running recurring gift-exchange editions, and keeping the draw experience private until reveal time.

The project is organized as a monorepo. The Laravel backend is the system of record and exposes a versioned HTTP API intended for the Expo React Native application.

## Product Direction

The platform centers on groups that can run multiple Secret Santa editions over time. It preserves a person's membership history within a group, including people invited before they create an account. This makes historical draw constraints, repeat avoidance, and later account claiming reliable across years.

An edition moves through a clear lifecycle:

```text
draft → open → drawn → revealed → archived
```

The approved domain model covers:

- group membership and invitation claiming;
- edition rosters and pluggable draw algorithms;
- exclusions and paid forced-pick constraints;
- assignments with historical repeat avoidance;
- per-edition wishes with optional affiliate products;
- anonymous assignment chat until reveal; and
- provider-agnostic payment orders.

## Notification Platform

Notifications use Laravel's notification system as the single delivery pipeline. Every application notification is stored through the database channel for the in-app inbox and sent through a custom Expo Push channel for registered mobile devices. The Expo integration is isolated behind a transport contract with real and fake implementations, so tests never make network requests and another push provider can be introduced without changing notification producers.

Push device registrations belong to both the user and the active Sanctum access token. Revoking that token removes its push route, which prevents a signed-out or shared device from receiving notifications for the previous account. Chat messages, completed draws, and edition reveals are the first notification producers; future producers can extend the same base notification payload and channels.

The Expo client registers granted devices, exposes a durable notification inbox, updates unread badges when the app returns to the foreground, and handles internal deep links from foreground, background, and cold-start notification responses. Remote push delivery requires a development or production build and an EAS project ID, supplied by EAS automatically or through `EXPO_PUBLIC_EAS_PROJECT_ID`.

The full proposed domain design is documented in [data-model.md](data-model.md).

## Current Backend Foundation

The backend is API-first and starts at `/api/v1`. The initial authenticated-user contract includes:

| Endpoint | Purpose | Authentication |
| --- | --- | --- |
| `POST /api/v1/auth/login` | Exchanges credentials and a device name for a Sanctum token. | Public |
| `POST /api/v1/auth/logout` | Revokes the current token. | Bearer token |
| `GET /api/v1/me` | Returns the current user profile. | Bearer token |

Responses use typed Spatie Laravel Data classes, never Eloquent models, Laravel HTTP resource classes, or ad-hoc arrays. Request validation and API errors use Brazilian Portuguese by default; source code, comments, schema descriptions, and developer documentation remain in English.

The API contract is documented in OpenAPI 3.1 and committed at `backend/openapi/v1.json`. The specification is generated from PHP attributes, keeping endpoint metadata and response schemas close to their controller and Data classes.

Regenerate the contract after API changes:

```sh
cd backend
composer run openapi:generate
```

## Technology Stack

### Backend

| Area | Technology |
| --- | --- |
| Language | PHP 8.5 |
| Framework | Laravel 13 |
| API authentication | Laravel Sanctum personal access tokens |
| API data objects | Spatie Laravel Data 4 |
| API specification | OpenAPI 3.1 generated with swagger-php 6 |
| Application database | PostgreSQL |
| Queues and cache | Laravel database drivers by default |
| Localization | Laravel translations, with `pt_BR` as the default locale |

### Quality and Tooling

| Area | Technology |
| --- | --- |
| Tests | Pest 4 |
| Static analysis | Larastan / PHPStan |
| Formatting | Laravel Pint |
| Contract verification | OpenAPI generation test plus the committed OpenAPI artifact |
| Test database | SQLite in memory |

### Client and Existing Web Scaffold

| Area | Technology |
| --- | --- |
| Mobile client target | Expo with React Native |
| Included Laravel web scaffold | Inertia.js 3 with React 19 |
| Frontend build tooling | Vite |
| Styling | Tailwind CSS 4 |

## Engineering Conventions

- Version all public API routes under `/api/v1`.
- Protect authenticated API routes with `auth:sanctum` and issue named tokens per device.
- Use typed Spatie Data objects for every API request and response; do not use Laravel HTTP API resource classes.
- Add OpenAPI attributes to every operation and API schema; regenerate `backend/openapi/v1.json` whenever the contract changes.
- Keep user-facing backend messages localized and Brazilian Portuguese by default.
- Cover API behavior with Pest feature tests and run Pint and PHPStan before merging.
