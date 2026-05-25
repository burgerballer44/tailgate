# Tailgate

Sports group prediction platform built with Laravel.

## Product goal

Tailgate lets users form prediction groups around sports teams. Each member creates one or more "players" and submits score predictions for upcoming games. The core relationship is that a group follows a team, optionally scoped to a single sport for that team; seasons provide time-bound context for games and scores.

If a new season starts, a group should still be following the same team without re-following. Upcoming games for that team in the new season should automatically flow into the prediction experience, filtered by sport when a sport-scoped follow is selected.

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
- Manage players from the group page (create, edit, delete) for their own approved membership.
- Group admin/owner actions: edit group name, approve/reject/remove members, follow or unfollow teams (optionally scoped to a sport).
- Group admin/owner can manage players for any approved member from Manage group.

### What is missing for the MVP

- **Game listing** — users have no view to see upcoming games for their group's followed teams.
- **Score predictions** — users cannot submit predictions from the UI.

These two items are the entire remaining scope of the MVP.

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
- **Follow** — a group's commitment to follow a specific team independent of season boundaries, with an optional sport scope to limit eligible games to a single sport.

## Follow direction (May 2026)

The product direction is now explicit:

- A group follows a team, not a season.
- A follow may be scoped to a sport when a group wants predictions for only one sport.
- Seasons are time windows that organize games and scoring.
- A follow should persist across season boundaries until a group explicitly unfollows.
- New-season games for followed teams should be available automatically for prediction workflows, respecting optional sport scope.

Current implementation behavior:

- A group can have multiple follows, up to its `follow_limit`.
- Sport scope on follow is optional (`null` means all sports for that team).
- New follows are blocked when the group reaches its `follow_limit`.
- Default `follow_limit` is `1`, which preserves current single-team behavior for existing groups.

Recommended domain shape for ongoing implementation:

- `follows` (or `team_follows`): `(group_id, team_id, sport nullable)` for durable follow intent per team/sport scope.
- `groups.follow_limit`: per-group maximum number of follows (defaults to `1`).
- `season_participations`: `(group_id, team_id, season_id)` for season-specific participation state when needed.

This split keeps product behavior clear: "we follow this team" is durable, optional sport scope narrows that intent when needed, and seasonal participation remains a contextual layer for game access and reporting.

## Build order for the MVP

Work through these phases in sequence. Each phase should be treated as a vertical slice — routes, controller, views, and tests together before moving on.

### Phase 1: Player management (completed)

User-facing player management has been implemented with role-aware behavior:

- Regular approved members can manage only their own players from the group show page.
- Group admin/owner can manage players for any approved member from Manage group.
- Regular self-service player creation is capped at `1` player per member.
- Admin-managed creation follows the group's `player_limit` for the selected member.
- Feature coverage exists for create/edit/delete paths, limit enforcement, and authorization boundaries.

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
- Validation: non-negative integer scores, game belongs to a team the group follows (and matches follow sport when scoped), submission within allowed window.
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