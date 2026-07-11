# Hey Brother Secreto

For a project overview, see [docs/project-overview.md](../../../docs/project-overview.md).

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
- API request objects and response resources use Spatie Laravel Data exclusively. Do not expose Eloquent models, use Laravel HTTP API resources, or construct ad-hoc response arrays in controllers.
- Use camelCase for API Data properties and JSON fields. Database columns remain snake_case.
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

## Things this codebase deliberately avoids

- **No meaningless arrays.** Use typed, meaningful Data objects for application and API payloads.
- **No Form Requests.** Input validation happens in Spatie Data objects.
- **No Laravel API Resource classes.** Never create or extend `Illuminate\Http\Resources\Json\JsonResource`, resource collections, or files under `app/Http/Resources` for API output. Output shape must use `Spatie\LaravelData\Data` or `Spatie\LaravelData\Resource`.
- **No inline `authorize()` / ad hoc `abort(403)` ownership checks** in controllers. Use `#[UsePolicy]` on the model and `#[Authorize]` on the controller method.
- **No database `ENUM` types.** Use `VARCHAR` + `CHECK` constraint matching a PHP enum.
- **No PHPStan baselines or inline ignores.** Level 10 errors must be fixed.
- **No multi-line commit messages, no `Co-Authored-By` trailers.**
- **No hardcoded `Storage::disk('name')` or `Storage::fake('name')` in app/test code.** Use the default disk (call `Storage::` facade methods directly, `Storage::fake()` without args). If a non-default disk is genuinely needed, resolve its name from config (`config('filesystems.<key>')`) so environments can swap it — never inline the disk name.
- **Morph maps.** Every model using a polymorphic relationship must be declared in `AppServiceProvider::morphMap()`. Morph maps are enforced globally.

## Guidelines

### Actions
- Encapsulate any piece of business logic into their own dedicated Action classes, inside the `Actions` folder.
- Actions should be implemented using the `handle` method.
- Name actions for what they do, with no `Action` suffix (e.g. `ProcessInsight`, `RegisterBuyerForAuction`).
- Actions should be reusable across contexts (http, artisan, queue, etc.).
- **Boundary between model methods and actions.** The line is *orchestration*, not "does it write the DB". Keep these on the model: typed reads of own state (e.g. `$auction->isAdvertisementEnabledForSlot($slot)`, `$user->hasSavedPaymentMethod()`) and single-statement writes against own columns with no events or transactions (e.g. `$auction->bindAdvertisementToSlot($slot, $ad)` whose body is one `update()` call). Lift to an Action when the work involves: multiple writes coordinated by a transaction, side effects beyond the DB (events, notifications, jobs, integrations), branchy business policy worth unit-testing in isolation, or reuse across HTTP/console/queue. Smell to watch for both ways — an Action whose entire body is `Model::update([...])` is a model method in disguise; a model method that touches other models, dispatches events, or wraps a transaction has outgrown the model and should be extracted into an Action.

### Data Objects
- Data objects should be created in the `Data` folder.
- Data objects should be named like `[DataObjectName]Data`, e.g. `CareerApplicationData`.
- Validated input Data objects should extend `Spatie\LaravelData\Data`; response-only Data objects should extend `Spatie\LaravelData\Resource`.
- DO NOT EVER pass request objects to actions — extract the plain values the action needs and pass those.
- Use `::from($source)` when transforming a request, model, or other source object. Use the constructor when composing explicit application values.

### Others

* Controllers in `app/Http/Controllers/Api/V1/` are thin: validate via Data, delegate to Action, and return Data. GET endpoints use Spatie Query Builder directly with explicit `allowedFilters`, `allowedIncludes`, `allowedSorts`, and `allowedFields`; no Action is needed for reads.

### Pre-commit Checks

- The versioned hook at `.githooks/pre-commit` runs Composer validation, regenerates and verifies the OpenAPI contract, and runs `composer run ci:check` before every commit.
- Composer automatically configures the repository-local hook path after dependency installation. Run `composer run hooks:install` from `backend` to configure it manually.
- Do not bypass the hook. Fix the failing check or stage the regenerated `backend/openapi/v1.json` when the API contract changes.

### Testing

- Prefer full integration tests over isolated unit tests. A feature test should exercise the complete HTTP flow: route, middleware, request Data validation, authorization, controller, database writes, response Data transformation, and OpenAPI contract.
- Unit tests are appropriate for deterministic, framework-independent business rules such as draw algorithms, value objects, and complex transformations.
- API feature tests belong in `tests/Feature/Api` and extend `Tests\\ApiTestCase`. Web feature tests belong in `tests/Feature/Web` and extend `Tests\\TestCase`.
- Do not skip OpenAPI validation in API tests unless the test deliberately crosses a schema boundary, such as submitting an invalid payload to test validation, or exercises an undocumented or in-progress contract. Skip only the necessary direction—request or response—and document why in the test.
- Add a Pest architecture test for each durable structural rule. Architecture tests should protect boundaries such as final API classes, Spatie Data-only payloads, absent Laravel HTTP resources, and future Action or integration conventions.

### External API Integrations
- All external integrations must have a real implementation and a fake implementation.
- All external integrations must implement a common interface for the integration itself.
- Integration implementations must return a common data object for the integration itself.
- Integrations should be created in the `Services` folder, and a subfolder for the integration itself.
- Integration implementations should be named like `[IntegrationName]Integration`, e.g. `LeadsIntegration`.
- In the service provider, map the interface to the real implementation.
- Tests use the fake implementation by default. Add focused contract tests for each real adapter.
