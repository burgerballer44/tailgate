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