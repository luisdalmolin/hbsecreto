# Hey Brother Secreto

For a project overview, see the ../docs/project-overview.md.
# Backend Guidelines — Technology Stack and Decisions

## Technology Stack

| Area | Choice |
| --- | --- |
| Language | PHP 8.5 |
| Framework | Laravel 13 |
| Database | Postgres |
| Authentication | Laravel Sanctum personal access tokens |
| API data objects | Spatie Laravel Data |
| API contract | OpenAPI 3.1 generated with swagger-php 6 |
| Tests | Pest 4 |
| Static analysis | Larastan / PHPStan |
| Formatting | Laravel Pint |
| Current web scaffold | Inertia.js 3, React 19, Vite, and Tailwind CSS 4 |
| Mobile client target | Expo with React Native |

## API Decisions

- The backend is API-first. Public HTTP endpoints are versioned under `/api/v1`.
- The OpenAPI document is generated from PHP attributes and committed at `backend/openapi/v1.json`. Regenerate it with `composer run openapi:generate` after changing an API contract.
- Every API endpoint must have a controller action, a Pest feature test, and OpenAPI metadata for its path, operation ID, tag, request body, and success/error responses.
- API request objects and response resources use Spatie Laravel Data. Do not expose Eloquent models or construct ad-hoc response arrays in controllers.
- Use `Resource` Data classes for response-only transformations and `Data` classes for validated request input.
- API JSON uses camelCase property names. Database columns remain Laravel-standard snake_case.
- API authentication uses Sanctum bearer tokens, issued per named device. Protected routes use `auth:sanctum`; the current token is revoked on logout.
- Login is throttled to five attempts per minute per IP address.

## Localization Decisions

- All user-facing content must use Laravel's translation system.
- Brazilian Portuguese (`pt_BR`) is the default and fallback locale.
- Source code, comments, OpenAPI descriptions, and developer documentation are written in English.

## Domain Decisions

- The domain model is membership-anchored: `group_members` is the durable identity within a group, while `edition_participants` scopes a member to one edition.
- Editions follow the lifecycle `draft → open → drawn → revealed → archived`.
- Draw algorithms are pluggable through an edition-type registry. The launch type is `classic` and honors constraints while minimizing repeat giver-to-receiver pairs.
- Draw constraints are edition-scoped. `must_not_pair` is symmetric; `must_pair` is directional and supports paid pick-to-draw features.
- Wishes belong to an edition participant and may optionally reference an affiliate product.
- Assignment-chat anonymity is a presentation rule: the true sender is stored, but the giver's identity is not exposed before reveal.
- Payment orders are provider-agnostic and single-purpose. A paid pick-to-draw order activates its linked draw constraint.

See the complete approved model at [docs/data-model.md](../../../docs/data-model.md).
