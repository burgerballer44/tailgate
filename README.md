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

- ~~Data model support for group points policy selection.~~ **Done.** `prediction_scoring_policy` on `group_season_follows` (season-scoped rather than group-level).
- ~~A non-null default policy value for groups (default: `PredictionDifferenceFromScorePointsPolicy`).~~ **Done.** Column default + model observer.
- ~~Policy registry/provider support that returns labels and descriptions for all policies.~~ **Done.** `PredictionScoringPolicyCatalogService` / `PredictionScoringPolicyOptionInterface`.
- Data model support for membership effective dates (join/leave) if not already present.
- Canonical game status mapping for scorable vs non-scorable states.
- Canonical season-to-sport relationship in queries.
- Existing prediction penalty rules and where they are currently stored.
- Clear tie-breaker rules per policy (especially placement policy).
- Decision on snapshot persistence vs on-demand rank reconstruction.

### Implementation task list (ordered)

1. ~~Implement admin policy selection first (required bootstrap)~~ **DONE**
- ~~Add group setting for selected points policy key.~~ Implemented as `prediction_scoring_policy` on `group_season_follows` rather than a flat group column. This is more granular than originally planned: each followed season carries its own scoring policy, allowing different seasons to use different policies within the same group.
- ~~Set and persist a non-null default (`PredictionDifferenceFromScorePointsPolicy`) for all groups.~~ Default is enforced at the `group_season_follows` column level and via a `creating` model observer. `Group::DEFAULT_PREDICTION_SCORING_POLICY = 'prediction-difference-from-score'`.
- ~~Add group admin UI with radio button options (exactly one selectable policy).~~ Implemented as a per-season radio group in the Seasons tab of the group edit page.
- ~~Populate radio options from policy metadata (`key`, `label`, `description`) for all available policies.~~ Driven by `PredictionScoringPolicyCatalogInterface` / `PredictionScoringPolicyCatalogService`; `PredictionDifferenceFromScorePointsPolicy` and `PlacementPointsPolicy` both expose `key`, `label`, `description`, `is_default`.
- ~~Add validation to enforce one selected policy at all times.~~ `UpdateGroupPredictionScoringPolicyRequest` enforces a required, valid policy key via `GroupValidationRulesTrait`.
- ~~Testing: add/extend model, service, request validation, and controller feature tests for defaulting, authorization, and invalid input handling.~~ Covered in `GroupControllerTest` and `GroupCommandServiceTest`.
- Dependency: none.

2. ~~Finalize product and domain rules~~ **DONE**
- ~~Confirm acceptance criteria for leaderboard columns and raw data columns.~~ Finalized in `config/prediction_results.php` (`leaderboard.required_columns`, `raw_prediction_data.required_game_columns`, `raw_prediction_data.required_player_columns`).
- ~~Confirm missing prediction behavior for edge cases (no submitted predictions).~~ Finalized as `submitted_game_points_offset = 7` with deterministic no-submission fallback baseline `14` for `prediction-difference-from-score`; placement policy marks missing predictions as last-place rows.
- ~~Confirm ranking tie-breakers for each policy.~~ Finalized as deterministic tie-break chain: `previous_week_rank_asc` then `player_id_asc` for both currently supported scoring policies.
- ~~Confirm mid-season membership and context-change behavior.~~ Finalized rules: approved members only; membership window is `joined_at` inclusive and `left_at` exclusive; historical rows are recomputed from canonical records (`freeze_historical_rows = false`) with per-game context inclusion evaluation.
- ~~Testing: add tests that lock expected behavior for edge-case rules before implementation.~~ Covered in `tests/Unit/Config/PredictionResultsRulesConfigTest.php`.
- Dependency: task 1.

3. ~~Define service and DTO contracts~~ **DONE**
- ~~Create service interface and concrete class skeleton.~~ Added `GroupSeasonLeaderboardServiceInterface` and `GroupSeasonLeaderboardService` with `buildSeasonResults(int $groupId, int $seasonId, ?int $asOfGameId = null): SeasonResultsViewData`; service is container-bound in `AppServiceProvider` as a task-3 skeleton.
- ~~Define output DTOs for leaderboard and raw game data.~~ Added `SeasonResultsViewData`, `PlayerLeaderboardRowData`, `GameRawPredictionData`, and `GameRawPredictionPlayerRowData` with typed constructors and response-oriented `toArray()` payloads.
- ~~Define points policy interfaces and context/result DTOs.~~ Added `GroupPointsPolicyInterface` plus `GamePointsContext`, `MissingPredictionContext`, `PlayerGamePointsResult`, and `PlayerSeasonTotal`.
- ~~Testing: add tests that validate DTO shapes, required fields, and type expectations.~~ Covered in `GroupSeasonLeaderboardServiceTest`, `SeasonResultsViewDataTest`, and `GroupPointsPolicyInterfaceTest`.
- Dependency: tasks 1-2.

4. ~~Implement policy strategies~~ **DONE**
- ~~Implement default prediction-difference-from-score policy.~~ `PredictionDifferenceFromScorePointsPolicy` now implements scoring calculations (`abs` score deltas + penalties), missing-prediction assignment (worst submitted + configured offset), and ranking comparison.
- ~~Implement placement policy.~~ `PlacementPointsPolicy` now supports rank-based game scoring, deterministic fallback behavior when rank context is unavailable, missing-prediction trailing placement behavior, and ranking comparison.
- ~~Add static label/description methods for each policy class.~~ Retained and validated in policy tests.
- ~~Implement shared tie-breaker and deterministic fallback behavior.~~ Added shared `DeterministicRankingComparison` concern and wired both policies to config-driven tie-breakers with deterministic `player_id` fallback.
- ~~Testing: add tests per policy for calculation outcomes, tie-breakers, missing predictions, and metadata (`key`, `label`, `description`).~~ Covered in `PredictionDifferenceFromScorePointsPolicyTest` and `PlacementPointsPolicyTest`.
- Dependency: task 3.

5. ~~Implement season results query orchestration~~ **DONE**
- ~~Build optimized query pipeline for games, predictions, players, and membership windows.~~ `GroupSeasonLeaderboardService` now orchestrates eager-loaded game/prediction retrieval (`season`, `teams`, `predictions.player.member`) plus approved-membership/player loading and per-game eligibility-window evaluation.
- ~~Filter to selected season and scorable games.~~ Service now scopes by `group_id + season_id + followed teams` and optionally `asOfGameId`, then filters to scorable games.
- ~~Exclude canceled/postponed games according to domain rules.~~ Service excludes non-scorable games (including non-numeric score/status payloads such as postponed/canceled markers in score columns) and records exclusion reasons in `meta.excluded_games`.
- ~~Testing: add tests covering query scoping, status filtering, and membership-window inclusion rules.~~ Added/updated `GroupSeasonLeaderboardServiceTest` coverage for season/follow scoping, non-scorable exclusion, join-window eligibility behavior, and `asOfGameId` filtering.
- Dependency: tasks 1, 3.

6. ~~Implement leaderboard aggregation~~ **DONE**
- ~~Compute per-game points per player via selected policy.~~ `GroupSeasonLeaderboardService` now computes per-game points through the selected `GroupPointsPolicyInterface`, including missing-prediction handling.
- ~~Aggregate season totals.~~ Service now accumulates season totals per eligible player across scorable games.
- ~~Compute rank, previous rank, rank change, and points behind leader.~~ Leaderboard rows now include deterministic rank ordering, prior snapshot rank, derived rank change, and points-behind-leader.
- ~~Use previous-week snapshots for `previousRank` and `rankChange`.~~ Previous-rank reconstruction now rolls up the prior week bucket rather than the immediately prior game.
- ~~Support `asOfGameId` for progressive rank calculations if needed.~~ Existing `asOfGameId` filtering now drives progressive leaderboard snapshots and ranking reconstruction.
- ~~Testing: add deterministic unit tests for totals, ranks, previous-rank reconstruction, rank change, and points-behind calculations.~~ Covered in `GroupSeasonLeaderboardServiceTest` aggregation scenarios for prediction-difference and placement policies.
- Dependency: tasks 4-5.

7. ~~Implement raw prediction data assembly~~ **DONE**
- ~~Build per-game blocks with final score metadata and player scoring rows.~~ `GroupSeasonLeaderboardService` now returns `rawGameRows` as `GameRawPredictionData[]` with followed/opponent team context, actual scores, and per-player rows.
- ~~Include penalties and calculation notes.~~ Player raw rows now include `penalty_points`, `game_points`, and policy-generated `calculation_notes` for both submitted and missing predictions.
- ~~Populate week/sequence and status fields for raw game rows.~~ Raw rows now derive stable week labels from game-date buckets and expose derived statuses for completed, postponed, canceled, scheduled, and pending games.
- Note: penalty points remain `0` until explicit penalty-domain data is introduced; the payload and scoring pipeline already support non-zero values.
- ~~Testing: add unit/feature tests verifying row completeness, penalty handling, and calculation note coverage.~~ Extended `GroupSeasonLeaderboardServiceTest` with raw-row assertions for structure completeness, penalty values, missing-prediction behavior, and calculation note coverage.
- Dependency: tasks 4-5.

8. ~~Implement controller/query endpoint integration~~ **DONE**
- ~~Add user-facing endpoint(s) to retrieve season-scoped results payload.~~ Added member-scoped endpoint `GET /groups/{group}/season-results` (`groups.season-results`) in `web.php`.
- ~~Validate group access and season validity.~~ Access is enforced by existing `user.group.member` middleware; request validation ensures `season_id` belongs to the group's `group_season_follows` and optional `as_of_game_id` belongs to the selected season.
- ~~Reuse shared service contract.~~ `GroupController::seasonResults` now delegates to `GroupSeasonLeaderboardServiceInterface::buildSeasonResults(...)` and returns the DTO payload as JSON.
- ~~Testing: add feature tests for authorization boundaries, validation failures, and response payload contract.~~ Added `seasonResults` feature tests in `GroupControllerTest` for approved-member success, non-member/pending-member 403s, season validation, `as_of_game_id` validation, and payload shape assertions.
- Dependency: tasks 6-7.

9. ~~Build user-facing UI tabs and season selector~~ **DONE**
- ~~Add `Leaderboard` tab UI.~~ Added group-page `Leaderboard` tab and season-results tab view rendering.
- ~~Add `Raw Prediction Data` tab UI.~~ Added group-page `Raw Prediction Data` tab that reuses the season-results UI and renders per-game raw blocks.
- ~~Add season selector and loading/error/empty states.~~ Added season selector (`results-season-selector`) with async loading, explicit error message state, and empty-state messaging when no season/result rows are available.
- ~~Keep tabs synchronized to selected season.~~ Season changes now persist via `season_id` query parameter, and tab links preserve the selected season when switching between results tabs.
- ~~Default the selector to the current active season, with fallback to the most recent followed season that has game data.~~ Group results now order seasons for the selector using active/current status and recent game data instead of simple name sorting.
- Dependency: task 8.

10. Validate policy-first UX and guardrails
- Ensure results views and services always resolve a policy (selected or default).
- Add fallback/guard behavior if legacy groups are missing policy data.
- Verify radio button default selection is reflected in UI state.
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
- ~~Policy selection is configurable per group and enforced.~~ **Done.** Configurable per followed season (more granular than per group); enforced via request validation and service layer.
- Default and alternate policies both pass tests.
- Mid-season membership and game status edge cases are covered by tests.
- Endpoint and UI performance are acceptable for target group sizes.