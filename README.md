# Tailgate - Sports Prediction Platform

## Overview

Tailgate is a Laravel-based sports prediction and tailgating platform where users can form groups, follow professional sports teams and seasons, and submit score predictions for upcoming games. Each group member can have multiple "players" (representing their prediction entries), and these players submit predictions for games in the followed season.

## Current State

### Completed Features

- **Authentication & User Management**: Full registration, login, email verification, password management
- **Group Management**: Users can create groups with invite codes, join existing groups, approve/reject members
- **Team Following**: Group admins can follow specific teams for seasons
- **Developer Admin Panel**: Complete CRUD operations for all entities (users, groups, members, players, teams, seasons, games, scores)
- **Dashboard**: Basic dashboard showing user's groups with links to manage them

### Incomplete User Workflow

The core prediction functionality is missing for normal users:

- Users cannot add "players" to their group membership
- Users cannot submit score predictions for games
- No way to view upcoming games or prediction status

## Application Architecture

### Key Models & Relationships

- **User**: Authenticated users
- **Group**: User-created groups with invite codes, member limits
- **Member**: Junction between users and groups with roles (admin/member) and status (pending/approved)
- **Player**: Prediction entries belonging to members (users can have multiple players per group)
- **Score**: Predictions submitted by players for specific games (home/away team scores)
- **Team**: Sports teams with designation and mascot
- **Season**: Time periods (e.g., "2024 NFL Season")
- **Game**: Matches between teams in seasons
- **Follow**: Groups following teams for specific seasons

### Services

- **GroupService**: Business logic for group operations
- **MemberService**: Member management
- **PlayerService**: Player operations
- **UserService**: User-related logic
- **GameService, SeasonService, TeamService**: Data management services

## Next Steps to Complete MVP

### ✅ 1. Analyze the application's purpose and current state (COMPLETED)

Reviewed models (Group, Player, Score, Game, etc.), controllers (DashboardController, GroupController), views (dashboard, groups/show), and routes to understand the sports prediction platform where groups follow teams and members submit predictions via players.

### ✅ 2. Identify missing user-facing functionality for players and scores (COMPLETED)

Confirmed developer routes exist for players/scores but no user routes; users can create/join groups and follow teams but cannot add players or submit predictions.

## MVP Development Modules

### PLAYER MANAGEMENT MODULE

#### 3. Add routes for user-facing player management

In `routes/web.php` under groups middleware, add resource routes for players (index, create, store) scoped to groups, using middleware to ensure user is approved member; follow pattern of existing group routes.

#### 4. Create PlayerController

Create `app/Http/Controllers/PlayerController.php`; implement index (list user's players in group), create (form), store (validate and create); use PlayerService for business logic; ensure authorization via middleware that user owns the member.

#### 5. Create views for player management

Create `resources/views/groups/players/index.blade.php` (list players with create button), `create.blade.php` (form with player_name field); use existing form components and layouts; validate player limit per member.

### SCORE SUBMISSION MODULE

#### 6. Add routes for user-facing score submission

Add routes for listing upcoming games and submitting predictions; consider routes like `groups/{group}/games` for listing games, and `groups/{group}/games/{game}/predict` for score submission form.

#### 7. Create ScoreController

Create `app/Http/Controllers/ScoreController.php`; implement index (list games needing predictions), create (prediction form for specific game), store (validate and save prediction); use Score model and ensure game belongs to followed season.

#### 8. Create views for score submission

Create `resources/views/groups/games/index.blade.php` (list upcoming games with predict buttons), `predict.blade.php` (form with home_team_prediction and away_team_prediction fields); show team names and game details.

### UI ENHANCEMENTS

#### 9. Update group show view

In `resources/views/groups/show.blade.php`, add section showing members' players and their recent predictions; load players with scores relationship; display prediction counts or recent activity.

#### 10. Update dashboard

In `resources/views/dashboard.blade.php`, add section for upcoming games across user's groups; show games needing predictions with quick links; use Game model filtered by followed seasons and future dates.

### SECURITY & VALIDATION

#### 11. Add middleware to ensure users can only manage their own players/scores

Create middleware like EnsurePlayerBelongsToMember (similar to existing ones); apply to player/score routes; check that player->member->user_id matches auth user.

#### 12. Add validation and error handling for predictions

In ScoreController, validate predictions are integers >=0, game is upcoming; add custom validation rules if needed; handle errors with flash messages and redirects.

### TESTING

#### 13. Test the complete user workflow

Use Pest tests in `tests/Feature/`; create tests for PlayerControllerTest and ScoreControllerTest; test authorization, validation, and successful creation/submission; run tests with `./vendor/bin/pest`.

### FUTURE FEATURES

#### 14. Consider adding features like viewing all predictions, leaderboards (future)

Plan routes for viewing group predictions (`groups/{group}/predictions`), leaderboards (`groups/{group}/leaderboard`); consider score calculation logic for accuracy.

## Development Guidelines

### Laravel Conventions

- Target Laravel ^12 (PHP ^8.4)
- Use service classes for complex domain logic; keep Eloquent models skinny
- Avoid putting business logic in controllers; use domain services
- Favor constructor injection over facades
- Migrations: generate idempotent, rollback-safe migrations
- Queues: use jobs & dispatch with retry/backoff for long-running tasks
- Events/Listeners for side effects where helpful
- ENV safety: never hardcode secrets; read from config()

### PHP Style (PSR-12)

- Use readonly properties, enums, first-class callables, union types, etc.
- Follow CUPID principles for maintainable code
- Apply DRY, KISS, YAGNI principles
- Use meaningful names, PHPDoc blocks, single responsibility

### Testing

- Use PestPHP with clear, human-readable test names
- Follow Arrange/Act/Assert pattern
- Provide negative and edge-case tests
- Use factories for test data
- Feature tests for controller methods, unit tests for services

## Getting Started

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure
4. Run `php artisan migrate`
5. Run `php artisan db:seed` (if seeders exist)
6. Start development server: `php artisan serve`

## Contributing

Follow the established patterns and guidelines. Ensure all new features include appropriate tests and follow the Laravel conventions outlined above.
