# Nagare — Plan

Nagare is a team task board (see `FEATURES.md`) built on Laravel 13, Inertia v3, Svelte 5, Tailwind 4, Fortify, Wayfinder and Pest 5. v1 is complete. This document summarizes it and lays out the next phases.

## v1 summary

- **Boards**: two independent flags, `has_stories` and `has_sprints`, with all four combinations supported. Soft delete and restore by admins. Ordered statuses, any of which can close issues, edited inline with drag reorder. Deleting a status that still has issues requires choosing where they go.
- **Issues**: one `issues` table with `role` (`epic`/`story`/`task`) and `parent_id`. Created, edited and deleted from a dialog and a detail page. The closed timestamp follows the status. Epics exist in the schema only.
- **Drag-and-drop**: `svelte-dnd-action` across columns and story lanes, with touch and keyboard support. Fractional sort with server-side rebalancing. Inertia optimistic updates with rollback.
- **Realtime**: Reverb + Echo on private `boards.{id}` channels authorized by `BoardPolicy::view`. Issue and board events are applied to page props without a reload. The sender's tab is skipped.
- **Sprints**: weekly, monthly, quarterly or custom cycles. Fixed-cycle sprints are created on demand and by the daily `sprints:roll`, which also closes ended sprints and carries unfinished issues over. Sprints can be created and closed by hand. Stories appear in any sprint that holds one of their tasks.
- **Members & users**: admin and member roles, with at least one admin per board (also when an account is deleted). Admins add existing users. Members can leave. User directory and profiles showing shared boards and assigned issues.
- **Profile photos**: 256×256 WebP via Laravel's `Image` API, with an initials fallback.
- **Dashboard & polish**: recent boards, assigned issues, current-sprint progress, empty states and skeletons, indigo theme, an accessibility pass. Kanban boards and backlogs hide issues closed more than 14 days ago unless asked.
- **Hardening**: audit fixes, Larastan level 8, lazy-loading guard outside production, `DemoSeeder`, production container target, scheduler service, CI with pnpm.

## Conventions

- Thin controllers call action classes in `app/Actions/*`. Every write goes through a Form Request. Responses use Eloquent API Resources. Pages call routes through Wayfinder.
- `BoardPolicy` is the single source of authorization. Issue, sprint and channel checks delegate to it.
- Realtime events are dispatched explicitly from actions (`ShouldBroadcast`, `ShouldDispatchAfterCommit`, skipping the sender). Pages apply event payloads to their props and only refetch when the payload can't be applied safely.
- Slow page sections are deferred props with skeletons. Lists have empty states.
- Every phase ends with passing Pest tests, Pint, Larastan, `vp check` and `svelte-check`.

## v1 follow-ups

Small items worth doing alongside v2:

- Live validation on the board and issue forms with Inertia precognition (`FEATURES.md` §3).
- Send a removed member away from an open board page right away, instead of on their next request.
- Show on a story card when its other tasks are in other sprints (for example "Also in 2026W42").
- Optional board description.
- Larastan level 9, once actions take typed input objects instead of validated arrays.

## v2 roadmap

Ordered by dependency: activity recording underpins comments, notifications and invites, so it comes early. Each phase is a usable vertical slice.

| #   | Phase                           | Delivers                                                                                                                                                                                                                                                                                                                                                                                      | Decide first                                                                                                  |
| --- | ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| 1   | **Labels, search & filters** ✅ | Per-board labels (name and colour) on issues, edited in board settings. A board filter bar (assignee, label, text, "only mine") applied client-side to the loaded issues, with the state kept in the query string. A global search across the user's boards with a database `LIKE` query, scoped through membership                                                                           | Whether search needs Scout (a database driver is enough at this scale)                                        |
| 2   | **Activity log & comments**     | An `issue_activities` table recorded by the existing actions (created, moved, assigned, sprint changed, closed, renamed). A timeline on the issue page that mixes activity with comments. Comments can be added, edited and deleted (by their author or a board admin), in Markdown rendered safely. New comments and activity arrive in realtime over the board channel                      | Markdown renderer and sanitizer; whether board-level actions (status edits, membership) are logged too        |
| 3   | **Watchers & notifications**    | Issue watchers replace the "collaborators" placeholder. Authors, assignees and commenters watch automatically, and anyone can watch or unwatch. Laravel database notifications for assignment, comments, mentions (`@name`) and status changes on watched issues. A notification bell with unread count over a private user channel. Optional email, per user preference, sent from the queue | Email immediately or as a digest; which events notify by default                                              |
| 4   | **Attachments**                 | Files on issues, stored on a configurable disk (local or S3-compatible) with size and type limits. Image thumbnails via the `Image` API. Served through an authorized controller rather than public URLs. Deleting an issue soft-deletes its attachments, and a scheduled prune removes the files later                                                                                       | Per-file and per-board limits; whether attachments can be added to comments                                   |
| 5   | **Email invites**               | Admins invite an email address that has no account, with a role. The invite is a signed, expiring link that joins the board after registering or logging in. Pending invites are listed in board settings and can be revoked or resent. Throttled and recorded in the activity log                                                                                                            | Invite lifetime; whether existing users can also be invited (instead of added) so they get a chance to accept |
| 6   | **Epics**                       | Turn on the `epic` role in the UI. An epic picker on stories, an epics view per board listing stories with progress (done/total tasks rolled up), and an epic badge on story cards. Applies to boards with stories                                                                                                                                                                            | Whether epics span boards (current schema says no) and whether to gate them behind a board flag               |
| 7   | **Board customization**         | Status colours, per-status WIP limits (warning only), a default status for new issues, and per-board choice of which card fields show (assignee, labels, story). Saved board views (filters from phase 1) shared with members                                                                                                                                                                 | Which options earn their complexity; agree the list before building                                           |

Phase 1 is done: ten fixed label colours (a `LabelColor` enum, so every colour has tested contrast), labels managed by any member in board settings and chosen in the issue dialog, a board filter bar whose state lives in the query string, and a `/search` page over the user's boards, issues and label names (a database `LIKE`, no Scout). Phases 1 and 2 are independent and can swap. Phase 3 needs 2. Phases 4–7 are independent of each other.

## Out of scope

- Teams or organizations above boards: membership stays per board.
- Time tracking, estimates and burndown charts. Revisit after epics, since roll-ups would share the same queries.
- Public or guest boards.
- A JSON API: the app stays Inertia-only until there is a client that needs one.
