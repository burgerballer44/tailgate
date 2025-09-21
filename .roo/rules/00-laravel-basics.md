# Laravel Conventions (Global)

- Target Laravel ^12 (PHP ^8.4) unless the repo states otherwise.
- Use service classes for complex domain logic; keep Eloquent models skinny.
- Avoid putting business logic in controllers; attempt to use a domain service class so that a console command, api controller, or web controller can reuse the same logic.
- Favor constructor injection over facades; when a facade is idiomatic (e.g., Cache, Log), it’s fine.
- Migrations: generate idempotent, rollback-safe migrations.
- Queues: for long-running tasks use jobs & dispatch with retry/backoff; never block HTTP.
- Events/Listeners for side effects where helpful.
- ENV safety: never hardcode secrets; read from config().
- Logging: structured logs with context; no sensitive PII.
