# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Xelqun is a Laravel 13 + Inertia v3 + Vue 3 (TypeScript) application built on the Laravel Vue starter kit, extended with **team-based multi-tenancy**. Auth is handled by Laravel Fortify (login, registration, email verification, password reset, 2FA/TOTP, recovery codes). Routes are typed end-to-end via Laravel Wayfinder. Jobs are processed via Redis with **Laravel Horizon** (`php artisan horizon`) — the Horizon dashboard is available at `/horizon`.

The product is an **email operations platform for Amazon SES** — the operational layer (logs, events, webhooks, suppression, retries, domain verification, API keys) around SES, which stays in the user's own AWS account. Each team connects its own AWS credentials; all mail resources are team-scoped and live under the mail routes/controllers. **Amazon SES is the only provider.** The `MailProvider` enum, connection screen, and drivers are SES-only, and there is no provider picker — teams connect SES directly. The provider layer is still driver-based (`app/Services/Mail/`): the `MailProviderDriver` contract and `MailProviderManager` are kept so more providers can be added later, but SES is the sole focus for now. See `.ai/rules/mail.md` for mail-specific conventions.

## Commands

Development (run all services — server, queue, logs, Vite — concurrently):
```bash
composer run dev          # php artisan dev — the primary dev command
npm run dev               # Vite only (if the frontend isn't reflecting changes)
```

Testing (Pest):
```bash
php artisan test --compact                       # full suite
php artisan test --compact --filter=TeamTest     # single file/test by name
```

Linting / static analysis / formatting:
```bash
vendor/bin/pint --dirty --format agent   # format changed PHP (run before finalizing PHP changes)
composer run types:check                 # PHPStan (larastan) at level 7
npm run lint                             # ESLint (--fix); lint:check for CI
npm run format                          # Prettier over resources/; format:check for CI
npm run types:check                     # vue-tsc --noEmit
```

Full CI gate (mirrors `.github`): `composer run ci:check` — runs JS lint/format/type checks, then `composer run test` (which itself runs Pint check, PHPStan, and the Pest suite).

## Architecture

### Team-centric routing (the core concept)
Most authenticated pages live under a `{current_team}` URL prefix (the team **slug**, since `Team::getRouteKeyName()` returns `slug`). Understand this flow before touching routes or middleware:

- **`routes/web.php`** wraps app routes in `Route::prefix('{current_team}')` guarded by `EnsureTeamMembership`. `routes/settings.php` holds profile/security/team-management routes (some also guarded per-route).
- **`EnsureTeamMembership`** (`app/Http/Middleware/`) resolves the team from the `current_team`/`team` route param, aborts 403 if the user isn't a member, optionally enforces a `$minimumRole` (middleware parameter, e.g. `EnsureTeamMembership::class.':admin'`), and auto-switches the user's current team to match the URL.
- **`SetTeamUrlDefaults`** registers `URL::defaults()` for `current_team`/`team` from the user's current team, so `route()` calls and Wayfinder don't need the team passed explicitly.
- **`HandleInertiaRequests`** shares `auth.user`, `currentTeam`, and `teams` (as `UserTeam` DTOs) as props on every page.

### Roles & permissions
Authorization is enum-driven, not Gate/Policy-heavy:
- **`App\Enums\TeamRole`** (Owner/Admin/Member) defines `permissions()`, `hasPermission()`, `level()`/`isAtLeast()` hierarchy, and `assignable()`.
- **`App\Enums\TeamPermission`** enumerates granular actions (`team:update`, `member:remove`, `invitation:create`, …).
- Check permissions via the `User` model, e.g. `$user->hasTeamPermission($team, TeamPermission::UpdateTeam)`.

### Key model/trait layout
- **`App\Concerns\HasTeams`** (used by `User`) holds all team relationships and helpers: `teams()`, `ownedTeams()`, `currentTeam()`, `switchTeam()`, `belongsToTeam()`, `teamRole()`, `toUserTeam()`/`toUserTeams()`, `toTeamPermissions()`, `fallbackTeam()`. Look here first for team logic on the user side.
- **`App\Models\Membership`** is the `team_members` pivot (extends `Pivot`, casts `role` to `TeamRole`). `Team::members()` uses it via `->using(Membership::class)`.
- **`Team`** auto-generates a unique slug on create/name-change (`GeneratesUniqueTeamSlugs`) and uses `SoftDeletes`.
- Other reusable validation lives in `App\Concerns` (`PasswordValidationRules`, `ProfileValidationRules`).

### DTOs, Actions, Requests
- **`App\Data`** holds plain readonly DTOs (`UserTeam`, `TeamPermissions`) passed to the frontend — not Spatie Data.
- **`App\Actions`** — Fortify actions (`CreateNewUser`, `ResetUserPassword`) and team actions (`CreateTeam`).
- Controllers are thin; validation lives in **`App\Http\Requests`** (grouped `Settings/` and `Teams/`).

### Frontend (Inertia + Vue)
- Pages: `resources/js/pages/`; layouts: `resources/js/layouts/`; components: `resources/js/components/` (shadcn-vue via `reka-ui` + Tailwind v4, configured in `components.json`).
- **Wayfinder** generates typed route/controller functions into `resources/js/actions/` and `resources/js/routes/` — import from `@/actions/…` (controllers) and `@/routes/…` (named routes) instead of hardcoding URLs. Regenerated automatically by the Vite plugin; don't hand-edit generated files.
- Vite plugins (`vite.config.ts`): `laravel-vite-plugin`, `@inertiajs/vite` (v3 SSR in dev), `@tailwindcss/vite`, `@vitejs/plugin-vue`, `wayfinder({ formVariants: true })`.

### Testing conventions
- Pest; Feature tests get `RefreshDatabase` automatically (`tests/Pest.php`). Tests are organized by domain under `tests/Feature/` (`Auth/`, `Settings/`, `Teams/`). Use model factories and their states.

## Writing docs content (`resources/docs`)
User-facing docs must be simple and easy to read. Write for someone brand new to the product.
- Keep sentences short and use everyday words. Prefer the plain version: "safe to run again" over "idempotent", "does nothing yet" over "inert", "update/spread" over "propagate", "starting point" over "root of trust".
- Do not use em-dashes (—). Use a period, a comma, or parentheses instead.
- Avoid jargon, filler, and hype. Say the thing directly, then stop.
- Match the plain style of the existing pages when adding or editing docs.

## Git & commits

This is an open-source project — keep history clean and readable. Commits follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<optional scope>): <short imperative summary>

<optional body — what & why, wrapped ~72 cols>

<optional footer — BREAKING CHANGE:, Refs #123>
```

- **Types**: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `perf`, `build`, `ci`, `style`.
- **Scope** (optional) names the area, e.g. `teams`, `auth`, `invitations`, `settings`, `ui`.
- **Summary**: imperative mood, lowercase, no trailing period, ≤ ~50 chars (e.g. `feat(teams): add member role switching`).
- Keep each commit focused on one logical change; split unrelated work into separate commits.
- Reference issues in the footer (`Refs #12`, `Closes #12`) rather than the summary.

Examples:
```
feat(invitations): expire pending invites after 7 days
fix(auth): redirect to intended team after 2FA challenge
refactor(concerns): extract slug generation into HasTeams
test(teams): cover owner-only team deletion
```

**Do not add Claude / AI co-author or attribution trailers** (no `Co-Authored-By: Claude`, no "Generated with" lines). Commits are authored solely by the human contributor.

**Before pushing, always run `composer run ci:check` and make sure it passes** (JS lint/format/type checks, then Pint check, PHPStan, and the Pest suite). If it reports issues, fix them — e.g. `npm run lint` and `npm run format` auto-fix lint/formatting — then re-run until green. Never push with a failing `ci:check`.

## Note on this file
Everything below is the auto-generated Laravel Boost guidelines block (regenerated by `boost:update`). The project-specific content above is maintained separately — edit it here, not in the Boost block.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

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

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

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

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

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

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
