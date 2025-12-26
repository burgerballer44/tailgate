The user system in Tar Heel Tailgate is functional but basic, relying on Laravel Breeze for authentication with email verification, password resets, and simple profile management. Users have roles (Regular/Admin) and statuses (Active/Pending/Deleted), with admins able to manage users via CRUD interfaces. However, several enhancements could improve engagement, security, and functionality:

Missing or Improvable Features

1. Automatic Scoring System
   Current State: Predictions are stored, but no logic calculates points based on accuracy (e.g., exact score match, correct winner, within point margin).
   Improvement: Implement scoring rules (e.g., 10 points for exact score, 5 for winner, 1 for close margin). Add a ScoreService to calculate and update user/group leaderboards after games end.
2. Leaderboards and Statistics
   Current State: No rankings or stats visible to users.
   Improvement: Add group-level and season-level leaderboards. Track user stats like total predictions, win rate, average points. Display on dashboard and group pages.
3. Notifications and Reminders
   Current State: Basic email verification; no in-app or email notifications.
   Improvement: Notify users of group invites, upcoming games, prediction deadlines, and score results via email or in-app alerts. Use Laravel's notification system.
4. Enhanced Profiles
   Current State: Basic name/email; no avatars or additional details.
   Improvement: Add profile pictures, bios, favorite teams, prediction history. Allow public profiles for social comparison.
5. Social Features
   Current State: Isolated predictions.
   Improvement: Enable comments/likes on predictions, direct messaging between users, prediction challenges ("beat my score").
6. Group Invitation System
   Current State: Invite codes for joining; no email invites.
   Improvement: Send email invites with accept/decline. Add join requests for private groups. Better group discovery.
7. User Activity and History
   Current State: No tracking of user actions.
   Improvement: Log prediction history, login activity, group joins. Provide user dashboards with stats and recent activity.
8. Achievements and Gamification
   Current State: None.
   Improvement: Badges for milestones (e.g., "First Prediction", "Perfect Score", "100 Wins"). Display on profiles.
9. Security Enhancements
   Current State: Basic auth; no 2FA.
   Improvement: Add two-factor authentication (TOTP/SMS). Implement password confirmation for sensitive actions. Audit logs for admin changes.
10. User Preferences and Settings

- **Current State**: None.
- **Improvement**: Allow users to set timezone, notification preferences, privacy settings (public/private profiles).

11. Real-time Features

- **Current State**: Static views.
- **Improvement**: Live score updates, real-time notifications using WebSockets (Laravel Broadcasting). Show live game progress.

12. API and Mobile Support

- **Current State**: Web-only.
- **Improvement**: Build a REST API for mobile apps, allowing predictions on-the-go.

13. Data Management

- **Current State**: Hard deletes.
- **Improvement**: Soft deletes for users/groups with retention policies. Data export for users.

14. Onboarding and Tutorials

- **Current State**: None.
- **Improvement**: Welcome emails, in-app tutorials for new users, guided group creation.

15. Admin Tools

- **Current State**: Basic CRUD.
- **Improvement**: Bulk actions, user analytics, moderation tools for inappropriate content (if social features added).

Implementing these would transform the app from a basic prediction tool into a engaging, competitive platform. Priority features: scoring system, leaderboards, and notifications for core value; social features and gamification for retention.

The team system in Tar Heel Tailgate is straightforward, allowing admins to manage real sports teams with basic attributes (organization, designation, mascot, type) and associate them with sports (basketball/football). Teams can be followed by groups in seasons for prediction purposes. However, it's quite basic and could be enhanced for better user experience and functionality:

Missing or Improvable Features

1. Team Logos and Visuals
   Current State: Text-only; no images or branding.
   Improvement: Add logo upload/storage (e.g., via Laravel MediaLibrary or S3). Display team logos in lists, predictions, and game views for visual appeal.
2. Extended Team Information
   Current State: Basic name/mascot; no additional details.
   Improvement: Add fields like stadium, coach, website, founded year, conference/division. Create a public team profile page with stats and history.
3. Real-time Data Integration
   Current State: Manual game entry and score updates.
   Improvement: Integrate with sports APIs (e.g., ESPN, SportsData.io) for automatic game schedules, live scores, and team stats. Update predictions in real-time.
4. Team Hierarchies and Organization
   Current State: Flat team list.
   Improvement: Add conferences, divisions, leagues (e.g., ACC for UNC). Allow filtering by conference. Support team rivalries or groupings.
5. User Team Preferences
   Current State: Teams followed via groups only.
   Improvement: Allow users to favorite teams personally. Show personalized feeds of followed teams' games and news.
6. Team News and Updates
   Current State: None.
   Improvement: Integrate news feeds or RSS for team updates, injuries, trades. Display on team pages or user dashboards.
7. Automated Team Management
   Current State: Manual creation.
   Improvement: Scripts to import teams from external sources. Bulk import for new seasons. Duplicate prevention.
8. Team Verification and Status
   Current State: All teams equal.
   Improvement: Mark teams as "Official" vs. "User-Added". Add verification status. Prevent duplicates.
9. Team Statistics and History
   Current State: No historical data.
   Improvement: Store past seasons' performance, win/loss records. Show on team pages. Use for prediction insights.
10. Team Following Limits and Rules

- **Current State**: Unlimited follows per group.
- **Improvement**: Set limits (e.g., max teams per group/season). Rules like "must follow home team" for relevance.

11. Team Search and Discovery

- **Current State**: Basic text search.
- **Improvement**: Advanced search by sport, type, conference. Autocomplete suggestions. Popular teams recommendations.

12. Team Social Features

- **Current State**: Isolated.
- **Improvement**: Team-specific forums or comments. User polls ("Who will win?").

13. Mobile and API Support

- **Current State**: Web-only.
- **Improvement**: Ensure team data is API-ready for mobile apps, with images optimized for small screens.

14. Team Analytics for Admins

- **Current State**: Basic CRUD.
- **Improvement**: Track which teams are most followed, prediction volumes. Insights for adding/removing teams.

15. Internationalization

- **Current State**: US-focused (College/Professional).
- **Improvement**: Support international teams/sports if expanding beyond UNC focus.

These enhancements would make teams more engaging, provide richer context for predictions, and reduce manual maintenance. Priority: logos/visuals and real-time data integration for immediate impact; extended info and hierarchies for depth.

The season system in Tar Heel Tailgate manages sports seasons with types (preseason/regular/postseason), dates, and active status. Seasons contain games and are followed by groups for team predictions. It's functional for basic organization but could be enhanced for automation, user engagement, and scalability:

Missing or Improvable Features

1. Automated Season and Game Creation
   Current State: Manual season/game setup.
   Improvement: Templates for common seasons (e.g., NCAA Basketball Regular Season). Auto-generate games based on team schedules or APIs. Bulk import from CSV/external sources.
2. Season Progress Tracking
   Current State: Basic active/inactive status.
   Improvement: Track current phase (e.g., Week 5 of 15), progress percentage, upcoming milestones. Visual progress bars on season pages.
3. Season Lifecycle Management
   Current State: Simple active toggle.
   Improvement: Automated activation/deactivation based on dates. Lock predictions after games start. Archive ended seasons with final stats.
4. Season Statistics and Analytics
   Current State: No aggregated data.
   Improvement: Total predictions, average accuracy, top performers per season. Trend analysis (e.g., improving predictions over time).
5. Multi-Season Support
   Current State: Seasons are independent.
   Improvement: Handle overlapping seasons (e.g., football and basketball simultaneously). Allow groups to follow multiple seasons.
6. Season Notifications and Deadlines
   Current State: None.
   Improvement: Alerts for season start, prediction deadlines, game reminders. Customizable per user/group.
7. Season Customization
   Current State: Fixed structure.
   Improvement: Custom scoring rules per season, prediction windows, allowed sports. Theme seasons (e.g., "March Madness Special").
8. Season Visibility and Access Control
   Current State: All seasons visible to admins.
   Improvement: Public seasons for browsing, private seasons for specific groups. Draft seasons before activation.
9. Season Archiving and History
   Current State: No archiving.
   Improvement: Soft-delete ended seasons, maintain historical data. Allow users to view past seasons' results and their predictions.
10. Season Integration with External Data

- **Current State**: Manual.
- **Improvement**: Sync with sports APIs for real-time schedules, results. Auto-update game scores and trigger scoring calculations.

11. Season-Based Leaderboards

- **Current State**: None.
- **Improvement**: Season-specific rankings, streaks, achievements. Compare performance across seasons.

12. Season Planning Tools

- **Current State**: Basic CRUD.
- **Improvement**: Calendar views for games, conflict detection, scheduling assistants. Preview season before activation.

13. Season User Engagement

- **Current State**: Passive following.
- **Improvement**: Season challenges, polls, discussion threads. User-generated content tied to seasons.

14. Season Analytics for Admins

- **Current State**: Basic management.
- **Improvement**: Engagement metrics (predictions per season), popular seasons, user retention analysis.

15. International and Flexible Season Support

- **Current State**: US-centric types.
- **Improvement**: Support for international sports calendars, custom season types (e.g., World Cup cycles).

These enhancements would make seasons more dynamic and user-friendly, reducing admin workload through automation while increasing engagement with progress tracking and analytics. Priority: automated creation/integration and progress tracking for immediate usability; lifecycle management and archiving for long-term sustainability.

The group system in Tar Heel Tailgate allows users to create leagues with members, players, and followed teams in seasons. Groups have owners, invite codes, and limits on members/players. However, it's basic and could be enhanced for better community building, management, and engagement:

Missing or Improvable Features

1. Group Discovery and Browsing
   Current State: Groups accessed via invite codes or admin views.
   Improvement: Public group directory with search, categories (e.g., "NCAA Fans", "Friends League"). Featured/popular groups.
2. Enhanced Invitation System
   Current State: Invite codes only.
   Improvement: Email invitations with accept/decline. Bulk invites. Join requests for private groups. Social sharing of invite links.
3. Group Roles and Permissions
   Current State: Admin/Member roles.
   Improvement: More granular permissions (e.g., Moderator, Score Manager). Custom roles. Transfer ownership.
4. Group Settings and Customization
   Current State: Basic limits.
   Improvement: Group avatars, descriptions, rules. Privacy settings (public/private). Custom scoring rules. Themes.
5. Group Communication
   Current State: None.
   Improvement: In-app messaging, announcements, group chat. Email notifications for group activity.
6. Multiple Team Following
   Current State: Service limits to one follow per group (bug).
   Improvement: Allow groups to follow multiple teams/seasons. Fix service to support hasMany follows.
7. Group Analytics and Insights
   Current State: None.
   Improvement: Member activity, prediction stats, engagement metrics. Admin dashboards for group health.
8. Group Templates and Quick Setup
   Current State: Manual creation.
   Improvement: Pre-made templates (e.g., "March Madness Pool", "Season Long League"). Auto-follow popular teams.
9. Group Moderation Tools
   Current State: Basic admin controls.
   Improvement: Member management (ban/kick), content moderation, activity logs. Dispute resolution.
10. Group Events and Challenges

- **Current State**: Static groups.
- **Improvement**: Time-limited events, prediction challenges, tournaments. Group milestones and celebrations.

11. Group Social Features

- **Current State**: Isolated.
- **Improvement**: Group forums, polls, shared predictions. User profiles within groups. Group badges/achievements.

12. Group Archiving and History

- **Current State**: No archiving.
- **Improvement**: Archive ended groups, maintain historical leaderboards. Allow reactivation.

13. Group Integration with External Platforms

- **Current State**: Standalone.
- **Improvement**: Discord/Slack integration, social media sharing of results.

14. Group Monetization (Future)

- **Current State**: None.
- **Improvement**: Premium groups, sponsored leagues, entry fees with prizes.

15. Group Mobile Experience

- **Current State**: Web-only.
- **Improvement**: Ensure responsive design, push notifications for group activity.

These enhancements would transform groups from simple containers into vibrant communities, increasing user retention and engagement. Priority: fix multiple follows bug, add discovery/invitations for growth; communication and social features for engagement.

Based on the application's purpose as a sports prediction league system, implementing regular user functionality involves creating a user-friendly interface for joining groups, managing players, submitting predictions, and viewing results. Since the admin backend is complete, focus on the user journey from registration to active participation.

### What to Do First

Start by designing the user flow and dashboard, as these define the core experience. Prioritize the dashboard since it's already mentioned in routes and serves as the entry point after login. This will help identify required controllers, views, and features iteratively.

### Adjusted Detailed List of Steps to Implement Regular User Functionality

Prioritized for private groups, with confirmation flows.

1. **Design User Dashboard (High Priority - Foundation)**
    - Update `resources/views/dashboard.blade.php` for personalized content.
    - Show user's groups (owned and joined), with actions like "Create Group" or "Join Group".
    - Display upcoming games in followed seasons (if user has groups), with prediction status.
    - Include prompts: "No groups yet? Create one or enter an invite code to join!"
    - Add a form for entering invite codes directly on the dashboard.

2. **Implement Group Creation (Core User Onboarding)**
    - Create routes for group creation (e.g., `/groups/create`).
    - Add `GroupController` methods for users to create groups (auto-add as owner/admin).
    - Display invite code prominently after creation for sharing.
    - Include group settings: name, member/player limits.

3. **Build Invite-Based Group Joining (Private Access)**
    - Add routes for joining via invite code (e.g., `/groups/join`).
    - Create a form for users to enter invite code and submit a join request.
    - Validate code, check group limits, and create a pending member request (new status or flag).
    - Notify group owner of join request (flash message or email).
    - Prevent duplicate requests or joins.

4. **Develop Owner Confirmation for Joins (Group Management)**
    - Extend `GroupController` with owner views for pending join requests.
    - Allow owners to approve/reject requests, adding approved users as members.
    - Handle rejections gracefully, with user notifications.
    - Enforce group limits during confirmation.

5. **Build Group Management for Users (User Ownership/Admin)**
    - Add views for owners/admins to manage members: view list, promote/demote roles, remove members.
    - Display group details: invite code, current members, limits.
    - Allow updating group settings (name, limits) for owners.

6. **Develop Player Management (Prediction Setup)**
    - Create routes and controller methods for users to add/edit/delete players in their groups.
    - Enforce limits per member (from group settings).
    - Display players per group, with links to submit predictions.

7. **Implement Prediction Submission (Core Gameplay)**
    - Add routes for viewing available games and submitting predictions.
    - Filter games by group follows and season activity.
    - Validate predictions: game not started, user has player in group, no duplicates.
    - Use existing score submission logic adapted for user interface.

8. **Create Standings and Results Views (Engagement)**
    - Build views for group standings: rank players by prediction accuracy.
    - Show individual player stats: predictions made, correct/incorrect counts.
    - Display game results with user predictions highlighted.

9. **Add Notifications and Activity Feeds (Retention)**
    - Implement flash messages for actions (e.g., "Join request sent!", "Prediction submitted!").
    - Add a user activity feed on dashboard: recent predictions, group updates, join request status.
    - Email notifications for join requests, confirmations, or game results.

10. **Polish UI/UX and Testing (Quality Assurance)**
    - Ensure responsive design.
    - Add form validation, error handling, and loading states.
    - Write feature tests for user flows (e.g., creating group, requesting join, submitting prediction).
    - Test edge cases: invalid codes, full groups, unconfirmed joins.

11. **Advanced Features (Post-MVP)**
    - Leaderboards within groups.
    - Social features: share predictions within group.
    - Mobile app or API endpoints.

### Dashboard Content and User Prompts

- **Welcome Message**: Personalized greeting.
- **Quick Stats**: Groups joined, predictions made, accuracy.
- **Active Groups Section**: List groups with roles, member counts, upcoming games/predictions.
- **Call-to-Actions**:
    - If no groups: "Create a group to start!" or "Have an invite code? Join now!" with inline form.
    - If groups exist: "Submit predictions" or "Manage your groups".
- **Pending Actions**: Show join requests sent (status: pending/approved/rejected).
- **Recent Activity**: Last predictions, group events.

This flow emphasizes privacy and owner control, guiding users to create or be invited into groups for predictions.
