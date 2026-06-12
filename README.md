# Tailgate

Sports group prediction platform built with Laravel.

## Product goal

Tailgate lets users form prediction groups around sports teams. Each member of a group creates one or more "players" and submits score predictions for upcoming games. The core relationship is that a group follows one or more teams, optionally scoped to a single sport for that team; seasons provide time-bound context for games and scores.

The immediate goal is a working MVP where a regular user can move through the full loop without any developer tooling.

## Application structure

Tailgate has two distinct interfaces that are independent of each other but share the same underlying service layer.

**User-facing interface** — the product experience. Everything a regular user interacts with: authentication, groups, players, and predictions. This is the primary focus of active development.

**Developer admin section** — a separate interface for developers to manually create and manage data outside of the user flow. It exists to be an administrative backend. It is not a staging environment for user features and does not represent the intended user experience.

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

### What is missing for the MVP

- **Game listing** — users have no view to see upcoming games for their group's followed teams.
- **Score predictions** — users cannot submit predictions from the UI.

These two items are the entire remaining scope of the MVP.

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

### Phase 2A: Upcoming games listing (read-only slice)

Goal: deliver immediate user value by letting approved members see upcoming games for their group's followed teams before prediction submission is introduced.

Scope:

- Add routes and controller actions to list upcoming games for a group membership.
- Show games even when prediction is closed (for example, inactive season), with clear status badges.
- Respect follow filters: team follow and optional sport scope.
- Keep this slice read-only; no score entry yet.

Definition of done:

- An approved member can open a game list page and understand which games are open or closed for prediction.
- Empty states explain why no games are currently available.

Testing focus:

- Authorization: only approved members can access the page.
- Filtering: only games for followed teams and allowed sport scope appear.
- Status display: open vs closed is correctly derived from season status and game start time.

### Phase 2B: Core prediction submission (required policy rules)

Goal: complete the MVP prediction loop with the required prediction policy rules.

Required rules to implement in this slice:

- Prediction lock time at scheduled game start.
- Season activity gate for submission (inactive seasons visible but closed).
- One prediction per player per game (upsert behavior).
- Edits allowed until lock time.
- Basic validation and eligibility checks:
  non-negative integer scores, eligible followed team/sport, and submission within allowed window.

Scope:

- Add create/store/update prediction actions and form UI from the game listing flow.
- Keep prediction model per-player (not per-membership).
- Provide user-facing validation and lock-state messages.

Definition of done:

- An approved member can submit and edit predictions for open games and see confirmation.
- Submissions outside policy are rejected with clear errors.

Testing focus:

- Happy path for create and update before lock.
- Validation failures (invalid scores, ineligible game, unauthorized player).
- Out-of-window behavior (post-lock and inactive season).
- Authorization boundaries for member/group access.

### Phase 2C: Optional competitive policy (admin opt-in)

Goal: add at least one optional rule to support more competitive groups without blocking MVP launch.

MVP optional rule:

- Group-wide unique score predictions per game (admin toggle).

Deferred optional rules (post-MVP, same policy framework):

- Duplicate handling mode (reject vs allow non-scoring duplicate).
- Blind picks visibility mode.
- Minimum lead time before lock.

Scope:

- Add group-level prediction policy setting for uniqueness.
- Enforce uniqueness during prediction create/update.
- Show clear conflict messaging when a submitted score is disallowed by policy.

Definition of done:

- Group admin can enable/disable uniqueness policy.
- Members receive immediate, understandable feedback when a duplicate score is blocked.

Testing focus:

- Policy toggle behavior and authorization (admin-only policy changes).
- Uniqueness enforcement across players in the same group/game.
- Regression coverage ensuring baseline rules still work when uniqueness is off.

### Phase 3: Dashboard and group surface updates

Connect the completed flows into the existing navigation:

- Update the group show page to surface a member's players and prediction status.
- Add a dashboard prompt when there are games with open predictions.
- Improve empty states throughout so first-time users understand what to do next.

### Phase 4: Cleanup and test coverage

- Review any service or model changes made during phases 1–3 and ensure they are consistent with the rest of the codebase.
- Ensure all new routes, controllers, and service methods have test coverage.
- Remove or reconcile any developer-panel assumptions that were found to conflict with the real user flows.