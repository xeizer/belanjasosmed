<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

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

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

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

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== xeizer/aistarter/core rules ===

# Laravel AI Development Standards

These rules come from the xeizer/aistarter package and are mandatory for AI coding agents on this project. They are regenerated by boost:install / boost:update, so never edit them inside CLAUDE.md or AGENTS.md; project-specific rules belong in .ai/rules.

Precedence: these rules take precedence over inferred conventions and optional recommendations. If Laravel Boost guidelines or any other package guidelines or skills conflict with this section on scope, approval, safety, or security, this section wins. Higher-priority system instructions still apply.

## 1. Communication

- Always reply in Bahasa Indonesia. Keep code identifiers and framework terms in their conventional form.
- Clearly separate verified facts, assumptions, recommendations, and open questions.
- Never claim success, or that a command ran, without actual evidence.

## 2. Workflow: Inspect → Propose → Approve → Implement → Test → Report

- Read-only inspection and non-mutating diagnostic commands are allowed at any time.
- Before any mutating action (creating, editing, deleting, or renaming files; migrations; package installs; git commit or push; any state-changing command), present a plan and STOP:
  problem and evidence, root cause, proposed solution, affected files, database/config/dependency changes, risks, verification approach.
- A request to explain, analyze, review, debug, or propose is NOT approval. Silence or an ambiguous reply is NOT approval. Approval covers only the proposed scope.
- Implement only the approved scope. If new requirements or out-of-scope issues appear, report them and ask again. No silent fixes, no unrelated refactoring, no restyling of untouched code.
- Extra artifacts suggested by other guidelines (factories, seeders, extra tests, documentation, additional files) are created only if they are part of the approved plan.
- Fast path: if the user explicitly writes "langsung kerjakan" for a small task, a short plan in the same reply is enough. Section 3 still applies.

## 3. Always requires separate, explicit approval

Even after general implementation approval:
- migrate:fresh, migrate:refresh, migrate:reset, migrate:rollback, db:wipe, seeding a non-test database, deleting data or files.
- git push, force push, reset --hard, or rewriting history.
- Adding Composer/npm packages, or introducing Vite, build tools, or UI frameworks.
- Editing .env or production-affecting configuration.
- Breaking changes to existing behavior or public interfaces.
- Any action touching production data or schema.

## 4. Evidence and anti-hallucination

- Never invent files, routes, models, relationships, methods, package features, command output, or test results.
- Check installed versions with composer show (or package.json for JS) before using version-sensitive APIs. Use Boost search-docs for version-specific documentation.
- Do not assume a package or feature exists because it is common. If something cannot be verified, say so.
- Before proposing a new package, verify it exists on Packagist/npm, is the intended package (not a look-alike name), and is actively maintained. Prefer fewer dependencies.
- Diagnose the root cause from evidence before recommending a fix.

## 5. Project conventions and architecture

- Follow existing architecture, naming, and module boundaries.
- Prefer the simplest solution that reliably meets requirements. Keep simple CRUD simple.
- Do not introduce Repository patterns, interfaces, traits, or extra layers unless justified in the plan and approved. Use Services/Actions only for complex or reused logic.
- Use the infer-conventions skill only on request or for initial project analysis. Present findings before recording rules. Never overwrite user-defined rules.
- If an inferred convention conflicts with this section, report the conflict and follow this section.
- Call env() only inside config files; everywhere else read values through config(). Calls outside config files return null once configuration is cached.

## 6. Database

- Identify the database engine first (php artisan config:show database.default and the matching connection). Also check the testing connection; it may differ from development.
- Eloquent first, then the Query Builder. Raw expressions (DB::raw, whereRaw, selectRaw, orderByRaw, ...) only with a justification stated in the plan. Always bind untrusted values.
- Column names cannot be bound. Validate user-supplied sort, filter, and grouping columns against an explicit allowlist.
- Prefer portable, engine-agnostic queries and migrations. If engine-specific behavior is needed, state it in the plan.
- Prevent N+1 with eager loading. Use exists(), withCount(), and aggregates instead of loading full datasets. Paginate or chunk large datasets. Avoid queries inside loops when batching is possible.
- Wrap logically atomic multi-step writes in transactions. A single insert or update does not need one. Add constraints and indexes through migrations when justified.
- Case sensitivity of LIKE differs by engine (case-sensitive on PostgreSQL; usually case-insensitive on MariaDB/MySQL depending on collation; case-insensitive only for ASCII on SQLite). For search, use whereLike with an explicit caseSensitive argument if the installed Laravel version supports it.
- If tests run on a different engine than development (e.g. SQLite in tests, PostgreSQL/MariaDB in development), do not treat passing tests as proof of engine-specific behavior; report this limitation.

## 7. Security

Authorization and data access:
- Authorize every request or action that reads or modifies data, on the server (Policies, Gates, or the existing permission system), in controllers, form requests, and Livewire components. Hiding a button is not authorization.
- Never load a record by a client-supplied ID without checking ownership: scope the query to the current user or authorize the loaded model with a policy.
- Use scoped route bindings for nested resources so child records must belong to their parent.
- When adding or changing protected features, add tests proving unauthorized users are denied and cannot change data.

Input and output:
- Persist only validated, explicitly allowed data. Never pass raw request input to create, update, or fill.
- State-changing routes use POST, PUT, PATCH, or DELETE, never GET, so CSRF protection applies. Do not exempt routes from CSRF unless another verification exists (e.g. webhook signatures).
- Rely on Blade's default escaped output. Use unescaped (raw) output only for fully trusted content, and apply the same rule to raw-HTML bindings in Alpine or Vue.
- Treat LLM or other external generated output as untrusted: escape it, never execute it, and authorize any action it triggers.

Files:
- Validate type and size of every upload. Never trust the client filename or extension; generate the stored name on the server.
- Store private files on a non-public disk and serve them through authorized routes or short-lived signed URLs.
- Reject SVG uploads unless they are sanitized.

Secrets and sensitive data:
- Never print, copy, or log the contents of .env, credentials, tokens, or keys. Never log passwords or full request payloads.
- Hash passwords with the model's hashed cast, not manually. Hide sensitive model attributes from serialization. Store secrets in the database with encrypted casts.
- Never show exceptions, SQL, or stack traces to end users. Do not silently swallow exceptions.

Abuse protection:
- When building or changing login, registration, password reset, contact forms, or API endpoints, apply rate limiting using named limiters, following the project's existing setup.

## 8. UX for user-triggered actions

- Every asynchronous create, update, delete, upload, import, or export must have: scoped loading feedback, a disabled trigger while processing, success and failure feedback, field-level validation errors, and confirmation before destructive actions. Handle empty, loading, and error states.
- Livewire alone does not guarantee idempotency. When duplicates matter, enforce it server-side (unique constraints, transactions, locks).
- Detailed patterns: livewire-crud-ux skill.

## 9. Frontend

- Follow the existing frontend stack and styling. If the project uses Tailwind CDN, keep it.
- No new JavaScript dependencies. Prefer native Livewire/Alpine features.
- Do not run npm run build or npm run dev yourself; if assets need rebuilding, tell the user which command to run.

## 10. Testing and verification

- Before running tests, check phpunit.xml and .env.testing. Tests using RefreshDatabase or DatabaseMigrations must target a dedicated testing database. If they would hit the development database, STOP and report.
- Add or update tests for important behavior and bug fixes using the existing test framework.
- After approved implementation, run the relevant tests.
- Format only changed files: run Pint with --dirty. Never run Pint without --dirty on the whole project unless the user explicitly asks.
- Never claim tests passed unless they ran and passed. Report what could not run and why. Do not change unrelated code to make tests pass.

## 11. Completion report

1. Summary of changes.
2. Files created, modified, or deleted.
3. Database, configuration, or dependency changes.
4. Tests and checks actually run, with results.
5. Known limitations, risks, or unresolved issues.
6. Further recommendations, clearly separated from approved work.

</laravel-boost-guidelines>
