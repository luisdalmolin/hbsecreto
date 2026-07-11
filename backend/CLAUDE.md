<laravel-boost-guidelines>
=== .ai/general rules ===

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

- **No meaningless arrays** We should always shoot for using typed and meaningfull data objects instead.
- **No Form Requests.** Input validation happens in Spatie Data objects.
- **No Laravel API Resource classes.** Never create or extend `Illuminate\Http\Resources\Json\JsonResource`, resource collections, or files under `app/Http/Resources` for API output. Output shape must use `Spatie\LaravelData\Data` or `Spatie\LaravelData\Resource`.
- **No inline `authorize()` / ad hoc `abort(403)` ownership checks** in controllers. Use `#[UsePolicy]` on the model + `#[Authorize]` on the controller method; existing student self-service actor guards are allowlisted in arch tests.
- **No database `ENUM` types.** Use `VARCHAR` + `CHECK` constraint matching a PHP enum.
- **No PHPStan baselines or inline ignores.** Level 10 errors must be fixed.
- **No multi-line commit messages, no `Co-Authored-By` trailers.**
- **No hardcoded `Storage::disk('name')` or `Storage::fake('name')` in app/test code.** Use the default disk (call `Storage::` facade methods directly, `Storage::fake()` without args). If a non-default disk is genuinely needed, resolve its name from config (`config('filesystems.<key>')`) so environments can swap it — never inline the disk name.
- **Morph-maps**: Every model using polymorphic relationships, needs to be declared in a morph map so we detach the code from the data as much as we can.

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
- Data objects should extend the `Spatie\LaravelData\Data` class.
- Data objects should be serializable.
- Use `snake_case` for data object property names (e.g. `first_name`, `client_secret`, `stripe_publishable_key`) to match the convention used throughout the project (`ContactData`, `BidData`, etc.) and to line up with the underlying column/API names.
- DO NOT EVER pass request objects to actions — extract the plain values the action needs and pass those.
- Generate TypeScript definitions from the data objects by running the `php artisan typescript:transform` command.
- Use the generated types in the front-end.
- **Call sites use `::from($source)` uniformly.** Spatie routes `Data::from($source)` to a typed factory like `from{ClassName}` based on the parameter type, so define the factory inside the Data class — don't expose it as a separate public name to callers.

### Others

* Controllers in `app/Http/Controllers/Api/V1/` are thin: validate via Data, delegate to Action, return Data. GET endpoints use Spatie Query Builder directly — no Action needed for reads.

### External API Integrations

- All external integrations must be done by having a read implementation, and a fake implementation.
- All external integrations must implement a common interface for the integration itself.
- Integration implementations must return a common data object for the integration itself.
- Integrations should be created in the `Services` folder, and a subfolder for the integration itself.
- Integration implementations should be named like `[IntegrationName]Integration`, e.g. `LeadsIntegration`.
- In the service provider, map the interface to the real implementation.
- When running tests, always use the fake implementation.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
