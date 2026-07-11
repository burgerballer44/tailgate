# Tailgate

Tailgate is a sports prediction platform for groups who want a simple, social way to pick game scores and track results.

## What the application is

Tailgate helps groups run season-long prediction competitions without manual scorekeeping.

- A group is the shared competition space.
- Members join a group and create one or more players.
- Players submit score predictions for games.
- Predictions are evaluated against actual game results.
- Group rules are enforced by the platform so the process stays fair and consistent.

In short, Tailgate turns an informal game-day tradition into a structured, easy-to-run experience.

## How Tailgate was inspired

Tailgate was inspired by a real family-and-friends game built around college football predictions.

In that setup, everyone texted score picks to one organizer, who tracked entries in a spreadsheet, applied group rules manually, and sent updated standings after each game. The competition was fun, but the process depended on one person doing repetitive coordination work.

Tailgate was created to keep the fun parts of that tradition while removing the manual overhead.

## Real-life scenario mapping

Tailgate directly models the same real-world prediction flow in software:

- Family and friends pool their picks in one place as a group.
- The organizer role maps to group admins instead of a single gatekeeper.
- Participants can represent different people with separate players (including household-based submissions).
- Text-message submissions become direct in-app prediction entry.
- Rule enforcement shifts from memory and spreadsheets to automated validation.
- Standings and results are visible to everyone in the group instead of being circulated manually.

This mapping keeps the spirit of the original game intact while making it practical for regular use by many groups.

## Application structure

Tailgate has two distinct experiences that are intentionally separate, while sharing the same domain and service layer.

### User-facing experience (product experience)

This is the core product and the primary design authority for the platform.

- Authentication and account access flows.
- Group membership and group-level prediction rules.
- Player management for each member.
- Following teams, viewing upcoming games, and submitting predictions.
- Results visibility and day-to-day competition experience.

### Developer admin experience (operational backend)

This is an internal operational surface for developers and maintainers.

- Manually creating/updating domain records (teams, seasons, games, groups, etc.).
- Running data imports from external sports sources.
- Inspecting data and validating system behavior during development.

### Shared backend contract

Both experiences call into shared command/query services and domain rules. Controllers, routes, and views are different per experience, but business logic is centralized so behavior remains consistent.

When one experience reveals a gap in service behavior, the fix should happen in shared backend logic rather than duplicating experience-specific workarounds.

## Next feature implementation plan: Leaderboard and raw prediction data

This section defines the implementation plan for a new user-facing results experience with two tabs:

- `Leaderboard`
- `Raw Prediction Data`

The plan below is intentionally detailed and should be used by the development team as the source of implementation scope, sequencing, and dependencies.

### Feature description

Add a season-scoped prediction results experience that allows group members to:

- View standings in a `Leaderboard` tab for a selected season.
- View per-game raw scoring details in a `Raw Prediction Data` tab for the same selected season.
- Switch seasons to view historical or current standings for that group context.
- Only active seasons are selectable in the current group admin interface; historical season browsing is a future read-only archive concern.

This feature must support groups that follow multiple teams and sports over time. The results view is always scoped to one selected season (and therefore one sport context for that season).

### User-facing experience

#### Results navigation

- Add a results area with two tabs:
	- `Leaderboard`
	- `Raw Prediction Data`
- Add a required `Season` selector at the top of the results area.
- Default selected season behavior:
	- Prefer the active/current season for the group if available.
	- Otherwise fall back to the most recent season with eligible games.
- The current season-follow workflow should only expose active seasons for selection; inactive seasons belong in a separate historical view later.

#### Leaderboard tab

For the selected season, display one row per eligible player with:

- Player name
- Total points (lower is better)
- Current rank
- Previous week rank
- Change in rank (`previous rank - current rank` or equivalent directional representation)
- Points behind leader (`player total - leader total`)

Additional behavior:

- Ranking is ascending by total points.
- Tie handling must be deterministic and policy-aware (see policy section).
- Leaderboard updates only from games considered scorable (finalized/completed according to domain status rules).
- Canceled/postponed games are excluded from scoring calculations.

#### Raw prediction data tab

For the selected season, present each game block with:

- Game header metadata:
	- Week/sequence
	- Followed team vs opponent
	- Actual final score (when available)
	- Game status (completed, postponed, canceled, etc.)
- Player rows containing:
	- Player name
	- Predicted followed-team score
	- Predicted opposing-team score
	- Penalty points (if any)
	- Points difference / game points under the selected policy

For the default prediction-difference-from-score policy:

- Base points difference = `abs(pred_followed - actual_followed) + abs(pred_opponent - actual_opponent)`
- Total game points = `base points difference + penalties`
- Lower total game points are better.

Missing prediction behavior for default policy:

- If a player did not submit a prediction for a scorable game:
	- Assign `worst submitted game points among players for that game + 7`.
	- If no submitted predictions exist for that game, use a policy-defined fallback penalty baseline.

### Service interface (shared backend contract)

Create a shared query service in the service layer (used by user-facing controllers and reusable by admin tooling):

- Suggested interface name: `GroupSeasonLeaderboardServiceInterface`
- Suggested implementation name: `GroupSeasonLeaderboardService`

Suggested primary method:

```php
/**
 * Build leaderboard and raw game scoring data for a group-season context.
 */
public function buildSeasonResults(
		int $groupId,
		int $seasonId,
		?int $asOfGameId = null
): SeasonResultsViewData;
```

Suggested response DTO composition:

- `SeasonResultsViewData`
	- `groupId`
	- `seasonId`
	- `pointsPolicy`
	- `generatedAt`
	- `leaderboardRows[]` (`PlayerLeaderboardRowData`)
	- `rawGameRows[]` (`GameRawPredictionData`)
	- `meta` (warnings, excluded games, policy notes)

Leaderboard row DTO fields:

- `playerId`
- `playerName`
- `totalPoints`
- `rank`
- `previousRank`
- `rankChange`
- `pointsBehindLeader`

Raw game DTO fields:

- `gameId`
- `weekLabel`
- `gameStatus`
- `followedTeam`
- `opponentTeam`
- `actualFollowedScore`
- `actualOpponentScore`
- `playerRows[]` with:
	- `playerId`
	- `playerName`
	- `predictedFollowedScore`
	- `predictedOpponentScore`
	- `penaltyPoints`
	- `gamePoints`
	- `calculationNotes`

### Points policy architecture

The scoring mechanism must be pluggable by group configuration.

Define a policy contract (strategy pattern), for example:

```php
interface GroupPointsPolicyInterface
{
		public function key(): string;

		public static function label(): string;

		public static function description(): string;

		public function calculateGamePoints(GamePointsContext $context): PlayerGamePointsResult;

		public function assignMissingPredictionPoints(MissingPredictionContext $context): PlayerGamePointsResult;

		public function compareForRanking(PlayerSeasonTotal $left, PlayerSeasonTotal $right): int;
}
```

Required policies:

- `PredictionDifferenceFromScorePointsPolicy` (default)
	- Uses summed absolute differences between each submitted prediction and the final game score (+ penalties).
	- Missing prediction rule: worst game points + 7.
	- `label()`: `Prediction difference from score (lowest total wins)`
	- `description()`: `Each game result is the sum of absolute differences between submitted predictions and final game scores, plus any penalties.`
- `PlacementPointsPolicy`
	- Per game: best (lowest prediction difference from score) gets 1 point, next gets 2, etc.
	- Seasonal total is sum of placement points.
	- Tie handling uses previous week rank as first tie-breaker (then deterministic fallback).
	- `label()`: `Placement points (1st, 2nd, 3rd...)`
	- `description()`: `Players are ranked each game by prediction difference from score and awarded placement points; lower season totals rank higher.`

Policy option metadata for UI:

- All policy classes must expose static `label()` and `description()` methods for admin-facing radio options.
- Add a policy registry/provider that returns all available policies as UI-ready options (`key`, `label`, `description`, `isDefault`).
- UI should render labels/descriptions directly from policy metadata rather than hard-coded strings.

Group-level policy selection:

- Group admin must choose exactly one points policy.
- UX should present this as mutually exclusive options (radio button behavior).
- A default must always exist. `PredictionDifferenceFromScorePointsPolicy` is the system default for newly created groups until an admin changes it.
- Policy selection is a required first-step configuration before leaderboard calculations are finalized for a group-season context.
- Persist as a group setting and include in service execution context.

### Implementation considerations

#### Season and sport boundaries

- Leaderboard and raw data are always scoped to one season.
- A season belongs to one sport; do not aggregate across sports/seasons in a single leaderboard.
- Group history may include multiple followed teams and seasons; selected season controls inclusion.
- Inactive seasons are excluded from the active season-follow selector and should not be offered for new participation.
- Historical season review, if added later, should be read-only and separate from the active season interface.

#### Eligibility and membership timeline

- Handle players joining mid-season:
	- Include only from effective eligibility date or policy-defined behavior.
- Handle players leaving mid-season:
	- Preserve historical contributions for games while eligible.
	- Exclude from future game calculations after exit effective date.

#### Game status handling

- Completed/final games are scorable.
- Postponed/canceled games are excluded until policy/domain marks them scorable.
- If previously postponed game later becomes final, recalculation must naturally include it.

#### Group context changes mid-season

- If followed team or group season settings change mid-season, scoring inclusion must follow explicit domain rules.
- Document and enforce one canonical rule set for:
	- Which games are included before/after a context change.
	- Whether historical rows are frozen or recomputed.

#### Ranking semantics

- Rank ordering is policy-driven but deterministic.
- Previous week rank requires week-by-week snapshots or deterministic reconstruction from ordered game sequence.
- Rank change should be computed consistently and displayed with clear direction conventions.

#### Performance and query strategy

- Avoid N+1 by eager loading predictions, games, teams, and player membership metadata.
- Prefer one pass aggregation in service memory after optimized query retrieval.
- Consider caching season results per group + policy + as-of marker, with clear invalidation on prediction/game updates.

#### Observability and auditability

- Log policy key, season id, group id, and exclusion reasons for non-scorable games.
- Include machine-readable `calculationNotes` per row for debugging discrepancies.

### Dependencies and prerequisites

Before implementation begins, confirm or add:

- Data model support for group points policy selection.
- A non-null default policy value for groups (default: `PredictionDifferenceFromScorePointsPolicy`).
- Policy registry/provider support that returns labels and descriptions for all policies.
- Data model support for membership effective dates (join/leave) if not already present.
- Canonical game status mapping for scorable vs non-scorable states.
- Canonical season-to-sport relationship in queries.
- Existing prediction penalty rules and where they are currently stored.
- Clear tie-breaker rules per policy (especially placement policy).
- Decision on snapshot persistence vs on-demand rank reconstruction.

### Implementation task list (ordered)

1. Implement admin policy selection first (required bootstrap)
- Add group setting for selected points policy key.
- Set and persist a non-null default (`PredictionDifferenceFromScorePointsPolicy`) for all groups.
- Add group admin UI with radio button options (exactly one selectable policy).
- Populate radio options from policy metadata (`key`, `label`, `description`) for all available policies.
- Add validation to enforce one selected policy at all times.
- Testing: add/extend model, service, request validation, and controller feature tests for defaulting, authorization, and invalid input handling.
- Dependency: none.

2. Finalize product and domain rules
- Confirm acceptance criteria for leaderboard columns and raw data columns.
- Confirm missing prediction behavior for edge cases (no submitted predictions).
- Confirm ranking tie-breakers for each policy.
- Confirm mid-season membership and context-change behavior.
- Testing: add specification-style tests that lock expected behavior for edge-case rules before implementation.
- Dependency: task 1.

3. Define service and DTO contracts
- Create service interface and concrete class skeleton.
- Define output DTOs for leaderboard and raw game data.
- Define points policy interfaces and context/result DTOs.
- Testing: add contract/unit tests that validate DTO shapes, required fields, and type expectations.
- Dependency: tasks 1-2.

4. Implement policy strategies
- Implement default prediction-difference-from-score policy.
- Implement placement policy.
- Add static label/description methods for each policy class.
- Implement shared tie-breaker and deterministic fallback behavior.
- Testing: add unit tests per policy for calculation outcomes, tie-breakers, missing predictions, and metadata (`key`, `label`, `description`).
- Dependency: task 3.

5. Implement season results query orchestration
- Build optimized query pipeline for games, predictions, players, and membership windows.
- Filter to selected season and scorable games.
- Exclude canceled/postponed games according to domain rules.
- Testing: add integration/feature tests covering query scoping, status filtering, and membership-window inclusion rules.
- Dependency: tasks 1, 3.

6. Implement leaderboard aggregation
- Compute per-game points per player via selected policy.
- Aggregate season totals.
- Compute rank, previous rank, rank change, and points behind leader.
- Support `asOfGameId` for progressive rank calculations if needed.
- Testing: add deterministic unit tests for totals, ranks, previous-rank reconstruction, rank change, and points-behind calculations.
- Dependency: tasks 4-5.

7. Implement raw prediction data assembly
- Build per-game blocks with final score metadata and player scoring rows.
- Include penalties and calculation notes.
- Testing: add unit/feature tests verifying row completeness, penalty handling, and calculation note coverage.
- Dependency: tasks 4-5.

8. Implement controller/query endpoint integration
- Add user-facing endpoint(s) to retrieve season-scoped results payload.
- Validate group access and season validity.
- Reuse shared service contract.
- Testing: add feature tests for authorization boundaries, validation failures, and response payload contract.
- Dependency: tasks 6-7.

9. Build user-facing UI tabs and season selector
- Add `Leaderboard` tab UI.
- Add `Raw Prediction Data` tab UI.
- Add season selector and loading/error/empty states.
- Keep tabs synchronized to selected season.
- Testing: add feature/browser-style tests for tab switching, season switching, empty states, and default selection behavior.
- Dependency: task 8.

10. Validate policy-first UX and guardrails
- Ensure results views and services always resolve a policy (selected or default).
- Add fallback/guard behavior if legacy groups are missing policy data.
- Verify radio button default selection is reflected in UI state.
- Testing: add regression tests for fallback/default policy resolution and legacy data guard behavior.
- Dependency: tasks 1, 8-9.

11. Add automated tests
- Unit tests:
	- policy calculations (including missing predictions)
	- ranking and tie-break logic
	- points-behind calculations
- Feature tests:
	- results endpoint authorization and payload shape
	- season scoping behavior
	- tab data behavior for completed vs canceled/postponed games
- Edge-case tests:
	- player joins/leaves mid-season
	- group context changes mid-season
	- no predictions submitted for a game
- Testing: this task executes and stabilizes the complete automated test suite, including newly added tests from tasks 1-10.
- Dependency: tasks 4-10.

12. Add performance checks and observability
- Verify query count and response time for realistic season sizes.
- Add logging for scoring policy execution and game exclusion reasons.
- Optional cache layer with invalidation strategy.
- Testing: add performance assertions/profiling checks and log-side-effect verification where practical.
- Dependency: tasks 6-8.

13. Documentation and rollout readiness
- Update user-facing help text for leaderboard semantics.
- Update admin docs for policy selection.
- Provide migration and deployment notes if schema changes were required.
- Testing: add documentation accuracy checks in PR review and ensure migration + deployment checklist is validated in staging.
- Dependency: tasks 2-12.

### Definition of done checklist

- Leaderboard and raw tabs exist and are season-scoped.
- Required leaderboard columns are populated correctly.
- Raw game data shows required fields and calculations.
- Policy selection is configurable per group and enforced.
- Default and alternate policies both pass tests.
- Mid-season membership and game status edge cases are covered by tests.
- Endpoint and UI performance are acceptable for target group sizes.
