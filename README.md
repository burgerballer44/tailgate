# Tailgate

Sports group prediction platform built with Laravel.

## How Tailgate was inspired

Tailgate was inspired by a real life family and friend group who like to make score predictions on the games for their favorite college football team. The goal was to predict the outcome of each game in the season, and then compare predictions against actual results to see who was the most accurate. All scores were tracked on an excel sheet. Family members and friends would submit their scores by texting the organizer, who would then update the master spreadsheet. The process was manual and time-consuming, and there was no easy way for participants to see their predictions or how they were doing compared to others. After each game result, an updated spreadsheet would be shared by email, showing the latest cumulative results. There were rules for what scores could be submitted by participants, such as the first to submit a score would get that score and no duplicates were allowed. The organizer had to enforce these rules manually, which added to the workload. For the point system, it was the absolute value of the difference between the predicted and actual score for each team, and the participant with the lowest total points at the end of the season was declared the winner. Anytime a player correctly guessed the exact score, they would get a special recognition, and a special 7 points would be subtracted from their total score as a bonus. The group had fun with the competition, but the manual process was a barrier to participation and enjoyment. Tailgate was created to automate this process, making it easier for participants to submit their predictions, track their performance, and enjoy the competition without the hassle of manual scorekeeping. There are some nuances to the people who submit scores. Some people are kids who do not have phones, so they submit their predictions through their parents. Some people like to submit their scors early, while others wait until the last minute. You are only allowed to submit one unique score per game but there was discussion about whether to allow duplicate scores that do not earn points. The organizer has to keep track of all of this manually, which can be a lot of work. For example allowing non unique scores woul allow a true prediction to be submitted even if the unique score was already taken, but it would also allow for more gaming of the system and make it less competitive.

## Product goal

Tailgate lets users form prediction groups around their favorite sports teams. Each member of a group creates one or more "players" and submits predictions for upcoming games. The core relationship is that a group follows one or more teams, optionally scoped to a single sport for that team; seasons provide time-bound context for games and predictions.

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
3. Submit a prediction (home and away values) for each game, per player.
4. See confirmation that their predictions are saved.

Everything else — leaderboards, prediction history, admin dashboards, notifications — is post-MVP.

### Implementation status

**Completed:**

- Player management (Phase 1) — approved members can create, edit, and delete their own players; group admins can manage players for all approved members. Full feature and authorization test coverage.
- Group management — create groups, invite members, approve/reject member requests, assign admin roles, manage team follows with optional sport scope.
- Authentication — user registration, email verification, secure login, password reset, and Google OAuth integration.
- Prediction policy framework — four core policies (lock time, season active gate, unique group predictions, minimum lead time) with full service-layer implementation and test coverage. Policies are evaluated and enforced at submission time.
- User-facing upcoming games and prediction submission (Phase 2A/2B) — approved members can view followed-team upcoming games, submit/update predictions per player, and receive validation/policy feedback.
- Dashboard quick predictions (Phase 3) — dashboard now shows upcoming games in the next 2 weeks grouped by team and sport across approved memberships, with per-player submission status and inline quick submit/update actions.
- Data import pipeline — automated imports from College Football Data (CFBD) and College Basketball Data (CBBD) APIs for teams, seasons, and games. Developer admin has UI for triggering imports.
- Developer admin interface — complete CRUD for all entities (users, teams, seasons, games, groups, members, players, predictions) with separate controllers, routes, and views.

## Domain model

- **User** — authenticated account.
- **Group** — shared prediction space with invite code and member limit.
- **Member** — join table between user and group; has a role (admin/member) and status (pending/approved).
- **Player** — prediction identity belonging to a member; a member can have multiple players per group.
- **Prediction** — a player's predicted home/away score for a specific game.
- **Team, Season, Game** — sports schedule entities managed via developer tools and import pipelines. Seasons are contexts for games, not the source of follow relationships.
- **Follow** — a group's commitment to follow a specific team independent of season boundaries, with an optional sport scope to limit eligible games to a single sport.

## Real-life to code mapping

The inspiration story describes one family group and their manual prediction process. Tailgate generalizes this into a platform that supports many independent groups with automated workflows. Here's how the real-life scenario maps to the application code and domain:

**Family and friend group** → `Group`  
One group in the real-life story; Tailgate supports unlimited independent groups, each with its own members, players, and prediction policies.

**Organizer** → Group `Member` with admin role  
In the real-life version, one person managed everything. In Tailgate, the admin role can be held by multiple members and reassigned as needed.

**Participants in the group** → `Member` (under a `Group`)  
Each participant joins a group and has a role (admin or regular member) and status (pending or approved).

**Individual participant creating a "player" name for predictions** → `Player`  
A member can create multiple players in a group; each player represents a distinct prediction identity. A child might submit under their parent's account through their own player.

**Submitting a score by texting the organizer** → Submitting a `Prediction` through the UI  
Instead of texting and having the organizer manually enter data, members submit predictions directly via the web interface for their players. Predictions are created and updated until the game locks.

**The team they follow** → `Follow` (group → team relationship)  
The real-life group followed one team. Tailgate groups can follow multiple teams up to their `follow_limit` (default: 1), and can span multiple seasons automatically.

**Limiting prediction scope to one sport** → `Follow` with sport scope  
A follow can optionally specify a sport (e.g., only college football); `null` means all sports for that team.

**Excel spreadsheet rules (first to submit wins, no duplicates)** → `Prediction` policy framework  
Tailgate enforces rules automatically through the prediction policy system rather than manual enforcement. Policies include lock time, season activity gates, and optional competitive rules.

**Organizer manually checking and enforcing rules** → Prediction policy validation  
The application validates all policies at submission time and provides immediate user feedback, eliminating manual work and human error.

**Tracking predictions and scores across the season** → `Season`, `Prediction`, `Game`  
Seasons provide time windows for games; predictions are stored permanently and linked to games and players. Scoring and leaderboards are computed post-MVP.

**Key scaling differences:**

- **Single group → many groups:** Tailgate is a multi-tenant prediction platform. Each group is independent with its own members, players, teams, and prediction rules.
- **Centralized organizer → distributed admin role:** Any member with admin privileges can manage group settings, members, and policies. The role is not tied to a single person.
- **Manual enforcement → automated policies:** Prediction policies are evaluated automatically at submission time, eliminating manual work and human error.
- **Extensible rule system:** Beyond the MVP, groups can enable optional competitive rules (like enforcing unique predictions per game) without requiring manual tracking.

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

### Phase 1: Player management (completed ✅)

User-facing player management has been implemented with role-aware behavior:

- Regular approved members can manage only their own players from the group show page.
- Group admin/owner can manage players for any approved member from Manage group.
- Regular self-service player creation is capped at `1` player per member.
- Admin-managed creation follows the group's `player_limit` for the selected member.
- Feature coverage exists for create/edit/delete paths, limit enforcement, and authorization boundaries.

### Phase 2A: Upcoming games listing (read-only slice) — IN PROGRESS

**Backend status:** `GameQueryService` exists with methods to fetch games for a group, filtering by team follows and optional sport scope.

**Frontend scope:**

- Add routes and controller actions to list upcoming games for a group membership.
- Show games even when prediction is closed (for example, inactive season), with clear status badges.
- Respect follow filters: team follow and optional sport scope.
- Keep this slice read-only; no prediction entry yet.

**Definition of done:**

- An approved member can see a list of upcoming games for their group's followed teams, with correct open/closed status and filtering by sport scope.

**Testing focus:**

- Authorization: only approved members can access the page.
- Filtering: only games for followed teams and allowed sport scope appear.

### Phase 2B: Core prediction submission — READY FOR INTEGRATION

**Backend status:** Fully complete and tested.

- `PlayerCommandService::submitPrediction()` and `updatePrediction()` exist with full policy evaluation.
- All four core policies are implemented and tested: lock time, season active gate, optional unique group predictions, and optional minimum lead time.
- Form request validation rules are ready: `GameBelongsToFollowedTeam`, `NoPredictionSubmitted`.
- Full feature and unit test coverage exists in `tests/Feature/Service/` and `tests/Feature/PredictionPolicies/`.

**Frontend scope:**

- Add create/store/update prediction actions and form UI from the game listing flow.
- Reuse the existing prediction form structure (home/away score inputs) from the developer admin views.
- Provide user-facing validation and lock-state messages.

**Definition of done:**

- An approved member can submit and edit predictions for open games and see confirmation.
- Submissions outside policy are rejected with clear errors.

**Testing focus:**

- Happy path for create and update before lock.
- Validation failures (invalid predictions, ineligible game, unauthorized player).
- Out-of-window behavior (post-lock and inactive season).
- Authorization boundaries for member/group access.

### Phase 2C: Optional competitive policy (admin opt-in) — COMPLETE

**Status:** Fully complete and tested.

- `UniqueGroupPredictionPolicy` is implemented and enforced during submission.
- Group-level policy toggle is present on the `Group` model as a feature flag.
- Full feature test coverage exists in `tests/Feature/PredictionPolicies/UniqueGroupPredictionPolicyTest.php`.

**Frontend status:** Complete.

- Group-level prediction policy settings are available to group admins in the group edit view.
- Conflict messaging is shown when a submitted prediction is disallowed by policy.

**Definition of done:**

- Group admin can enable/disable uniqueness policy.
- Members receive immediate, understandable feedback when a duplicate prediction is blocked.

**Testing focus:**

- Policy toggle behavior and authorization (admin-only policy changes).
- Uniqueness enforcement across players in the same group/game.
- Regression coverage ensuring baseline rules still work when uniqueness is off.

### Phase 3: Dashboard and group surface updates

Status: Complete.

Completed in this phase:

- Dashboard quick-prediction section for the next 2 weeks, grouped by followed team and sport.
- Per-game per-player prediction status badges (submitted/not submitted) across each approved membership.
- Inline quick submit/update actions from dashboard, including group/player context and policy/validation error display.
- Prompt text that highlights open prediction slots and clearer empty states when setup steps are missing.

### Phase 4: Cleanup and test coverage

- Review any service or model changes made during phases 1–3 and ensure they are consistent with the rest of the codebase.
- Ensure all new routes, controllers, and service methods have test coverage.
- Remove or reconcile any developer-panel assumptions that were found to conflict with the real user flows.

## What is accessible where

### User-facing interface

Available to authenticated, email-verified users:

- Authentication (register, login, logout, password reset, Google OAuth)
- Group management (create, join by invite code, view group details)
- Member management (request to join, view approval status; admins can approve/reject/remove members and assign roles)
- Team follow management (create, remove follows; optional sport scope)
- Player management (create, edit, delete; self-service capped at player limit; admins can manage all members' players)
- Upcoming games listing for followed teams in group and dashboard surfaces
- Prediction submission and editing for member-owned players with policy-aware feedback

### Developer admin interface

Available only to users with the `Developer` role. Provides complete CRUD access to:

- Users and authentication state
- Teams and team imports (trigger CFBD/CBBD API imports)
- Seasons and game imports (trigger external API imports)
- Games and game results
- Groups, members, and member roles
- Players
- Prediction submission and editing
- Prediction policy settings and evaluation

The developer admin is a full-featured backend for testing and manual data management. It is not a staging environment for user features.

## Authentication & OAuth

The application supports two authentication methods:

- **Email/password** via Laravel's standard auth system with email verification.
- **Google OAuth** via `SocialAuthenticationController`, which automatically creates or retrieves users and links social accounts.

Both flows log users in with the same session.
