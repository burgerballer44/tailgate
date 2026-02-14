The application, named "Tailgate," is a Laravel-based sports prediction platform. Users create or join groups to predict game outcomes, with groups following specific sports, teams, or seasons (set up via developer/admin tools). Each group member can have multiple "players" (prediction entries), submit scores (predictions) for upcoming games, and compete in group standings based on prediction accuracy. The developer section provides admin tools to manage sports data (teams, seasons, games), while the GroupController handles user-facing group operations like creation, joining, and member management. The core prediction flow (follows, players, scores, results, standings) is unfinished for normal users.

The DashboardController displays a user's accessible groups (owned or joined), with basic navigation to create/join groups. The GroupController is partially complete for normal users, supporting group creation (with invite codes), joining (via invite codes, with pending approval), viewing groups, editing/managing members (approve/reject/remove for owners/admins), but lacks prediction-related features like following teams/seasons, managing players, submitting scores, viewing standings, and displaying upcoming games/predictions.

To complete the MVP for normal users, focusing on core functionality first while planning major additions, here is the prioritized list of next steps in order of completion:

1. **Add Group Follow/Unfollow for Admins (High Priority - Core Functionality)**
    - Add routes/views in GroupController (reuse developer logic: groups/{group}/follow-team create/post/delete).
    - Why: Prerequisite for games/predictions. Services/DTO ready; unblocks flow.

2. **Implement Player CRUD for Members (High Priority - Core Functionality)**
    - Routes: groups/{group}/players (scoped to own member; authorize).
    - Reuse PlayerService, developer views/forms.
    - Why: Players hold predictions; required for score submission.

3. **Add Prediction (Score) Submission (High Priority - Core Functionality)**
    - Routes: groups/{group}/players/{player}/scores create/update (eligible games from follows/seasons).
    - Reuse ScoreService, rules (e.g., NoScoreSubmitted).
    - Why: Core user action (submit before game time).

4. **Add Game Results Input for Admins (High Priority - Core Functionality)**
    - Extend groups/{group}/games/{game}/results or developer (add home_final_score, away_final_score to games if needed).
    - Why: Enables accuracy calculation.

5. **Implement Basic Standings Computation/View (High Priority - User Engagement)**
    - Group show section: rank by points (e.g., exact=10/winner=5 via service/job).
    - Why: Feedback loop; makes competing fun.

6. **Update Views (Group Show/Dashboard) (Medium Priority - UX Improvement)**
    - Add sections/tables: follows, players/games lists, upcoming/standings (components like row-actions-dropdown).
    - Why: Exposes features; improves UX/discoverability.

7. **Write Feature/Unit Tests (Medium Priority - Quality Assurance)**
    - New Pest files (e.g., UserFollowTest, PredictionTest); follow AAA, factories.
    - Why: Regression-proof; high coverage per rules.

8. **UI/UX Polish (Low Priority - Refinement)**
    - Filters/search, errors/loading, responsive; structured logs.
    - Why: Professional MVP.
