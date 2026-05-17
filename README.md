# Tailgate

Sports group prediction platform built with Laravel.

## Product goal

Tailgate lets users form prediction groups around sports teams. Each member creates one or more "players" and submits score predictions for upcoming games. The core relationship is that a group follows a team; seasons provide time-bound context for games and scores.

If a new season starts, a group should still be following the same team without re-following. Upcoming games for that team in the new season should automatically flow into the prediction experience.

The immediate goal is a working MVP where a regular user can move through the full loop without any developer tooling.

## Application structure

Tailgate has two distinct interfaces that are independent of each other but share the same underlying service layer.

**User-facing interface** — the product experience. Everything a regular user interacts with: authentication, groups, players, and predictions. This is the primary focus of active development.

**Developer admin section** — a separate interface for developers to manually create and manage data outside of the user flow. It exists to support development and testing: creating teams, seasons, and games via import pipelines, seeding groups and members, and inspecting data directly. It is not a staging environment for user features and does not represent the intended user experience.

Both interfaces call into the same command and query services (`PlayerCommandService`, `GameQueryService`, etc.), but they do so independently. The developer admin has its own controllers, routes, and views. The user-facing product has its own. Shared service logic should be written to accommodate both, and if it does not, it should be updated.

When building user-facing features, the right process is:

1. Design the user experience first. What does the user see, do, and expect?
2. Trace back to the route, controller, service, and model that need to support it.
3. Update services or models as needed — the user experience is the design authority, not the current state of the developer admin.
4. Cover the feature with tests that reflect user intent, not just technical wiring.

## MVP definition

The MVP is complete when an approved group member can, without developer-panel access:

1. Create one or more players under their group membership.
2. See the upcoming games for the teams their group is following.
3. Submit a score prediction (home and away score) for each game, per player.
4. See confirmation that their predictions are saved.

Everything else — leaderboards, prediction history, admin dashboards, notifications — is post-MVP.

## Current state (May 2026)

### What works for regular users today

- Register, log in, verify email, reset password, Google social login.
- Dashboard showing all groups the user belongs to or owns.
- Create a group.
- Join a group by invite code (creates a pending membership).
- View group details once approved.
- Group admin/owner actions: edit group name, approve/reject/remove members, follow or unfollow teams.

### What is missing for the MVP

- **Player creation** — users cannot create players from the UI.
- **Game listing** — users have no view to see upcoming games for their group's followed teams.
- **Score predictions** — users cannot submit predictions from the UI.

These three items are the entire remaining scope of the MVP.

### Shared service layer

Both the user-facing product and the developer admin section call into the same command and query services. The following are available for user-facing controllers to use:

- `PlayerCommandService` / `PlayerQueryService` — create, update, delete players; submit and manage scores.
- `GameQueryService` — query games with filters; retrieve upcoming games for a team's active schedule windows.
- `GroupQueryService` — check membership status, follow relationships, and member limits.
- `StorePlayerRequest`, `SubmitScoreRequest` — existing form request classes that may need to be adapted for user-facing validation rules.
- Factories and Pest test helpers for all core models.

These services were initially exercised through the developer admin, so some method signatures or validation assumptions may reflect admin needs rather than user needs. Review and adjust them freely when building user-facing flows.

## Domain model

- **User** — authenticated account.
- **Group** — shared prediction space with invite code and member limit.
- **Member** — join table between user and group; has a role (admin/member) and status (pending/approved).
- **Player** — prediction identity belonging to a member; a member can have multiple players per group.
- **Score** — a player's predicted home/away score for a specific game.
- **Team, Season, Game** — sports schedule entities managed via developer tools and import pipelines. Seasons are contexts for games, not the source of follow relationships.
- **Follow** — a group's commitment to follow a specific team independent of season boundaries.

## Follow direction (May 2026)

The product direction is now explicit:

- A group follows a team, not a season.
- Seasons are time windows that organize games and scoring.
- A follow should persist across season boundaries until a group explicitly unfollows.
- New-season games for followed teams should be available automatically for prediction workflows.

Recommended domain shape for ongoing implementation:

- `follows` (or `team_follows`): `(group_id, team_id)` for durable team follow intent.
- `season_participations`: `(group_id, team_id, season_id)` for season-specific participation state when needed.

This split keeps product behavior clear: "we follow this team" is durable, while seasonal participation remains a contextual layer for game access and reporting.

## Build order for the MVP

Work through these phases in sequence. Each phase should be treated as a vertical slice — routes, controller, views, and tests together before moving on.

### Phase 1: Player creation

The user should be able to create players under their own membership in a group. The relevant questions to answer through UX design before writing code:

- How many players can a member have? Is there a limit per group? How should the UI communicate that limit?
- Where does a user navigate to create a player — from the group page, from their profile, from the dashboard?
- What information does a player need beyond a name?

Once the UX is clear, implement:

- User-facing routes under the existing `group.member` middleware.
- A `PlayerController` handling index, create, and store.
- Views that fit within the existing layout and component patterns.
- Middleware or policy checks ensuring users can only manage their own players.
- Feature tests covering creation, validation errors, and authorization failures.

### Phase 2: Game listing and score prediction

The user should be able to see upcoming games for their group's followed teams and submit a prediction per player. Questions to design before coding:

- Should predictions be per-player or per-membership? Confirm how the Score model ties a player to a game.
- When is a game "open" for predictions? Is there a lock time? What happens after the game starts?
- Can a player change a prediction? Is there a deadline?
- What does the prediction form look like — one game at a time, or a list of games with inline inputs?

Once the UX is clear, implement:

- Routes for listing games and submitting predictions, scoped to group membership.
- A `ScoreController` handling the game list, prediction form, and store/update actions.
- Views for upcoming games and the prediction form.
- Validation: non-negative integer scores, game belongs to a team the group follows, submission within allowed window.
- Feature tests covering the happy path, validation errors, out-of-window submissions, and authorization.

### Phase 3: Dashboard and group surface updates

Connect the completed flows into the existing navigation:

- Update the group show page to surface a member's players and prediction status.
- Add a dashboard prompt when there are games with open predictions.
- Improve empty states throughout so first-time users understand what to do next.

### Phase 4: Cleanup and test coverage

- Review any service or model changes made during phases 1–3 and ensure they are consistent with the rest of the codebase.
- Ensure all new routes, controllers, and service methods have test coverage.
- Remove or reconcile any developer-panel assumptions that were found to conflict with the real user flows.

## Existing route surface

### User-facing routes (current)

- `/` — welcome page (guest only).
- Auth: register, login, logout, password reset, email verification, Google OAuth.
- `/dashboard` — user dashboard.
- `/profile` — profile edit/delete.
- `/groups/create` — create group.
- `/groups` (POST) — store group.
- `/groups/join` — join by invite code form.
- `/groups/join` (POST) — submit invite code.
- `/groups/{group}` — show group (approved members only).
- `/groups/{group}/edit` — manage group (admins only).
- `/groups/{group}` (PATCH) — update group settings.
- `/groups/{group}/approve/{member}` — approve join request.
- `/groups/{group}/reject/{member}` — reject join request.
- `/groups/{group}/remove/{member}` — remove member.
- `/groups/{group}/follow-team` — follow team form/store.
- `/groups/{group}/follow/{follow}` (DELETE) — unfollow team.

### Developer admin routes (role: Developer)

Full CRUD for users, teams, seasons, games, groups, members, players, and scores. Team and game import from CFBD and CBBD APIs. These routes exist under `/developer` and are only accessible to accounts with the Developer role. They are an independent data management interface used during development and testing — separate from the user-facing product, sharing the same service layer underneath.

## Tech stack

- PHP ^8.2, Laravel ^13
- Vite + Tailwind CSS 4 + Alpine.js
- Pest for testing
- SQLite by default (local development)

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # only needed if the file doesn't exist
php artisan migrate
composer run dev                  # starts server, queue, log watcher, and Vite together
```

Seed if your branch includes seeders:

```bash
php artisan db:seed
```

## External service keys (optional)

Only needed when using import tools or Google login:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

CFBD_API_TOKEN=
CFBD_BASE_URL=   # defaults to https://api.collegefootballdata.com

CBBD_API_TOKEN=
CBBD_BASE_URL=   # defaults to https://api.collegebasketballdata.com
```

## Running tests

```bash
./vendor/bin/pest                                               # full suite
./vendor/bin/pest tests/Feature/GroupControllerTest.php        # single file
./vendor/bin/pest --filter "creates a group"                   # single test
```