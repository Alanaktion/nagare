# Nagare — Feature Set

Nagare (ながれ) is a project management and task board web application for teams. It supports both Kanban-style boards (a flat collection of tasks) and Scrum-style boards (tasks organized under stories, with sprint cycles). This document describes the application's features from a user and product perspective.

## Core Features

### 1. Authentication & Account Lifecycle

- Users can register, log in, and log out.
- Password reset is available via an emailed link.
- Email verification is supported, including a notice, a signed verification URL, and the ability to resend the verification email with throttling.
- Sensitive actions require password confirmation.
- Access to the main application areas—boards, issues, users, and the dashboard—is restricted to authenticated users.

### 2. User Accounts & Profiles

- Users have a name, email, hashed password, and an optional profile photo.
- Profile photos are automatically resized and converted to an efficient web format, with a fallback if image processing is unavailable.
- Photos can be uploaded or removed; deleting a user also deletes their photo.
- An avatar representation is included when user data is displayed in the interface.

### 3. Boards

- Two board types are supported:
    - **Kanban** — "Tasks only", a free-form collection of tasks.
    - **Scrum** — "Stories + Tasks", tasks organized within stories.
- Boards support full create, read, update, and soft-delete operations.
- Creating a board involves providing a name, type, and an initial ordered set of statuses; the creator is attached as a board **admin**.
- Deleting a board is restricted to board admins.
- Board create and update forms provide client-side validation feedback.

### 4. Board Statuses (Columns)

- Statuses act as the columns of a board and are ordered by a sort value.
- Each status can be marked as a closing status. Moving an issue into a closed status sets a closed timestamp; moving it out clears that timestamp.
- Statuses can be added, renamed, reordered by drag-and-drop, and marked as closing; the list is managed inline when creating or editing a board.

### 5. Issues / Cards

- An issue belongs to a board and a status, and has an author, an optional assignee, a name, a description, and a sort value used for ordering within a column.
- Issues are hierarchical: stories can contain tasks. The system also defines epic, story, and task roles.
- Status transitions automatically maintain the closed timestamp, and closed issues render struck-through on the board.
- When no status is provided during creation, the issue defaults to the board's first status.
- Issue creation and update endpoints support both the drag-and-drop client and standard form submissions.
- A dedicated issue detail page shows the issue's name, board, status, assignee, and description, with placeholder sections for collaborators and attachments.

### 6. Drag-and-Drop Kanban

- The board view renders one column per status, plus a "Story" column for Scrum boards.
- Cards can be dragged between and within columns. Dropping a card computes a new fractional sort value from the neighboring cards and updates the issue.
- Updates are applied optimistically to the local view and then reconciled with the server response.

### 7. Sprints (Scrum)

- Sprints belong to a board and have a slug, start date, end date, and optional closed timestamp.
- Sprints are slugged from their date according to a board sprint cycle: weekly, monthly, quarterly, or custom (falling back to an ISO date).
- A board can resolve its current sprint—the sprint covering today that is not closed. Boards with a sprint cycle redirect to that sprint view.
- Issues can be associated with a sprint.

### 8. Board Membership & Roles

- Membership is modeled directly on boards rather than through a separate teams concept, with a role of **admin** or **user**.
- Any member can view and update a board.
- Only board admins can delete, restore, or force-delete a board.
- Non-members cannot view or modify a board.
- The same board-level update permission guards issue updates.

### 9. Realtime Collaboration

- Issue creation and update events are broadcast on private channels for both the issue and its board.
- Channel authorization reuses the board view permission, so only board members receive events.
- The board view subscribes to its board channel and applies issue updates and creations to the local state, so changes from other users appear live.
- A WebSocket server provides the realtime transport; broadcasting must be enabled and the server process running.

### 10. Dashboard

- The landing page after login renders a dashboard shell.
- It currently serves as a component showcase (buttons, calendar, radio group, number field) and a placeholder for future widgets.

### 11. User Directory & Profiles

- Authenticated users can browse a list of all users and view an individual profile.
- A user profile loads their boards and issues assigned to them, ready for display.

### 12. Settings

- **Profile** — update name, email, and photo; unverified email changes trigger re-verification; delete account (password-confirmed) and profile photo.
- **Password** — change password with current-password confirmation and standard password rules.
- **Appearance** — light, dark, or system theme selection.

## Data Model

The application's data model includes the following relationships:

- A user can own many boards and be a member of many boards (with a role).
- A board has many statuses, sprints, and issues.
- A status classifies many issues.
- A sprint contains many issues.
- A user can author many issues and be assigned many issues.
- An issue can have child issues, forming a story/task hierarchy.

Additional schema notes:

- Boards hold a type and an optional sprint cycle; they are soft-deleted.
- Statuses and issues track a sort value and are soft-deleted; a status's closing flag drives the issue's closed timestamp.
- An issue's parent, sprint, and assignee references are nullable and are cleared if the referenced record is deleted.
- Database migrations manage the schema.

## Frontend Architecture

- Inertia pages are grouped by domain: board, user, settings, and auth.
- Reusable components are available, including shadcn-vue primitives.
- Layouts exist for the app, auth, and settings areas.
- Shared TypeScript types cover boards, issues, statuses, sprints, and users.
- The app layout provides a collapsible sidebar whose navigation lists the user's accessible boards.

## Authorization Summary

- Board access is membership-based, enforced through policies and authorization checks.
- Broadcast channels reuse the board view permission.
- Guests are redirected to login for all authenticated areas.
