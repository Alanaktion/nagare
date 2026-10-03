# Nagare — Rebuild Plan

High-level architecture and phased roadmap for rebuilding Nagare (see `FEATURES.md`) on the current starter kit: Laravel 13, Inertia v3, Svelte 5, Tailwind 4, Fortify, Wayfinder, Pest 5. The old Vue/Jetstream WIP lives in `../nagare.old` and is a reference for domain logic only. Nothing is ported verbatim.

## 1. Where we start

Already provided by the starter kit, so **not** rebuilt:

- Auth: login, register, password reset, email verification, password confirmation, 2FA, passkeys (Fortify)
- Settings: profile, security (password + 2FA/passkeys), appearance
- App shell: sidebar layout, breadcrumbs, shadcn-svelte-style `ui/` components (bits-ui), toasts, dark mode
- Tooling: Wayfinder, Pest, Pint, Larastan, `vp` (Vite+) for build/check

Gaps against `FEATURES.md`: profile photos, all domain models, boards UI, drag-and-drop, realtime, user directory, real dashboard.

## 2. Guiding decisions

| Topic          | Decision                                                                                                                                                                                                                | Rationale                                                                                      |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| Issue model    | One `issues` table with `parent_id` and `role` (`epic`/`story`/`task`), per TODO.md                                                                                                                                     | Replaces old separate stories/tasks; allows arbitrary nesting later                            |
| Board behavior | Two independent flags on `boards`: `has_stories`, `has_sprints` (plus `sprint_cycle`). **All four combinations are supported.** "Kanban" (neither) and "Scrum" (both) are presets in the create form that set the flags | TODO.md calls for this; avoids a rigid type enum                                               |
| Membership     | `board_user` pivot with `role` enum (`admin`/`member`). Add `boards.created_by` as a plain FK, kept separate from admin membership                                                                                      | Ownership transfers without losing creator info                                                |
| Authorization  | `BoardPolicy` is the single source of truth. Issue, status and sprint actions delegate to it. Broadcast channels reuse it                                                                                               | Matches FEATURES.md §8–9                                                                       |
| Enums          | PHP backed enums (`BoardRole`, `IssueRole`, `SprintCycle`) cast on models; mirrored as TS types                                                                                                                         | Replaces old string constants                                                                  |
| Ordering       | Fractional `sort` (float/double) per column, computed client-side from neighbors, with a server-side rebalance when gaps get too small                                                                                  | Keeps drags to one write; rebalance prevents float exhaustion, a gap in the old version        |
| Mutations      | Plain Inertia web routes + controllers, with Form Requests. Drag-and-drop uses `router.patch` with optimistic updates and `preserveScroll`/`only`. No separate JSON API, no Sanctum                                     | Per TODO.md; Inertia v3 optimistic updates replace the old axios flow                          |
| Realtime       | Laravel Reverb + Echo on private `boards.{id}` channels. Explicit broadcast events dispatched from an action class, not model-observer `BroadcastsEvents`                                                               | Explicit payloads (including issue deletes and reorders) and easier to test with `Event::fake` |
| Drag-and-drop  | `svelte-dnd-action` (the common Svelte choice) unless the Phase 3 spike shows a problem with nested story lanes or touch                                                                                                | Needs touch support, cross-column and nested-story-lane drops                                  |
| Business logic | Small action classes in `app/Actions/` (`MoveIssue`, `CreateBoard`, `SyncStatuses`, `ResolveCurrentSprint`) called by thin controllers                                                                                  | Same convention as the existing `app/Actions/Fortify`                                          |
| Typing         | Wayfinder for all routes. Shared TS types in `resources/js/types` mirror Eloquent API Resources                                                                                                                         | Resources give a stable contract between PHP and Svelte                                        |
| Soft deletes   | Boards, statuses, issues (per FEATURES.md). Deleting a status that still has issues requires choosing a target status to move them to                                                                                   | Old schema left this undefined                                                                 |

**Dependencies**: `laravel/reverb`, `laravel-echo` and `pusher-js` are approved and installed (Phase 0). Still needing approval when reached: the DnD lib (Phase 3) and an image library (Phase 7). The old plan had a "fallback if image processing is unavailable"; I'd drop that and require GD, which ships with PHP.

## 3. Data model

```
users           (starter kit) + profile_photo_path nullable
boards          id, name, description?, has_stories bool, has_sprints bool,
                sprint_cycle enum? (weekly|monthly|quarterly|custom),
                created_by FK users, timestamps, softDeletes
board_user      board_id, user_id, role (admin|member), unique(board_id,user_id)
statuses        id, board_id, name, sort, is_closed bool, timestamps, softDeletes
sprints         id, board_id, slug, start_date, end_date, closed_at?,
                unique(board_id, slug)
issues          id, board_id, status_id, sprint_id? (nullOnDelete),
                parent_id? (nullOnDelete), role (epic|story|task),
                author_id, assigned_id? (nullOnDelete),
                name, description?, sort (double), closed_at?,
                timestamps, softDeletes
                index(board_id, status_id, sort)
```

Invariants (enforced in actions and covered by tests):

- Moving to a closing status sets `closed_at`. Moving out clears it.
- Create without a status uses the board's first status.
- A parent must be on the same board. Role/parent combinations are validated: task→story/epic, story→epic.
- `has_stories=false` boards only accept `task` issues. `has_sprints=false` boards ignore `sprint_id`.
- A board always keeps at least one status, and at least one admin.

## 4. Backend structure

- **Models**: `Board`, `Status`, `Sprint`, `Issue` (+ factories and seeders), `User` extended.
- **Policies**: `BoardPolicy` (view/update = member; delete/restore/forceDelete = admin). `IssuePolicy` delegates to the board.
- **Controllers** (thin, resourceful): `BoardController`, `BoardStatusController` (or statuses synced via the board form), `SprintController`, `IssueController`, `UserController` (index/show), `DashboardController`, `ProfilePhotoController`.
- **Form Requests** for every write. Support Inertia precognition on board and issue forms for live validation (FEATURES.md §3).
- **Resources**: `BoardResource`, `IssueResource`, `StatusResource`, `SprintResource`, `UserResource` (with avatar URL).
- **Events**: `IssueCreated`, `IssueUpdated`, `IssueDeleted`, each `ShouldBroadcast` on `private-boards.{id}`. `routes/channels.php` authorizes through `BoardPolicy::view`.
- **Sprints**: `SprintCycle::slugFor(CarbonImmutable)` (replaces the old `Sprint::dateSlug`) and `Board::currentSprint()`. A scheduled command (`sprints:roll`) creates the next sprint and optionally closes the old one. The old version had none, so sprints were never created.
- **Photos**: a `HasProfilePhoto` concern: resize, convert to WebP, store on the `public` disk, delete on user deletion. Avatar URL falls back to initials.

## 5. Frontend structure

```
resources/js/pages/
  boards/   Index, Create, Edit, Show (kanban/scrum grid), Sprint (Show variant)
  issues/   Show (detail page)
  users/    Index, Show
  Dashboard.svelte
resources/js/components/
  board/    BoardColumn, IssueCard, StoryLane, IssueDialog, StatusListEditor
  ...       UserAvatar, UserSelect, EmptyState
resources/js/lib/    sort.ts (fractional math), echo.ts, board-store.svelte.ts
```

- **Board view**: reactive board state kept in a Svelte 5 `$state` store seeded from the Inertia props. Local drag updates apply optimistically. Echo events and server responses reconcile into the same store (last-write-wins by `updated_at`, ignoring echoes of our own writes).
- **Scrum layout**: story lanes (rows) × status columns. Kanban: status columns only. Drop target logic lives in one place so both layouts share it.
- **Status editor**: inline reorderable list (drag to reorder, rename, closing toggle) shared between Create and Edit board forms.
- **Sidebar**: lists the user's boards, shared as an Inertia prop (cached or lazy) from the existing `HandleInertiaRequests` middleware.
- Use deferred props with skeletons for slow sections (dashboard widgets, user profile issue lists).
- Primary color theming and a design pass on `zinc` + accent (TODO.md item) happen alongside Phase 3.

## 6. Cross-cutting concerns

- **Testing (Pest)**: feature tests for every policy boundary (member / non-member / admin / guest), the issue invariants above, sprint slug and current-sprint logic, and status-sync behavior. Broadcast tests use `Event::fake`. A small number of browser or manual smoke checks cover drag-and-drop, since that is client-heavy. Run Larastan and Pint each phase.
- **Performance**: eager-load board with statuses/issues/users in one go. Index `issues(board_id, status_id, sort)`. Paginate or window closed issues on large boards (Phase 8).
- **Dev environment**: SQLite by default; Reverb process added to the `composer run dev` concurrently set.
- **Deployment**: not in scope until MVP is done. Laravel Cloud is the likely target; Reverb is available there.

## 7. Phased roadmap

Each phase ends with passing tests, Pint, and a usable vertical slice.

| #   | Phase                      | Delivers                                                                                                                                                                                                                                                                                                                                                                                    |
| --- | -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0   | **Foundations** ✅         | Reverb + Echo installed, enums (`BoardRole`, `IssueRole`, `SprintCycle`), base `UserResource`, starter-kit dashboard placeholders pruned                                                                                                                                                                                                                                                    |
| 1   | **Boards & membership** ✅ | `boards`, `board_user`, `statuses` migrations/models/factories; `BoardPolicy`; board CRUD with inline status editor (reordered with up/down buttons; drag reorder arrives with the Phase 3 DnD library); boards index; sidebar board list; soft delete and restore. Deferred: moving issues to a target status when deleting a status that has issues (Phase 2), last-admin guard (Phase 6) |
| 2   | **Issues (static)**        | `issues` migration/model; create/edit/delete via dialog and detail page; status-change invariants; board view rendering columns (no DnD yet); Kanban + stories lane rendering                                                                                                                                                                                                               |
| 3   | **Drag-and-drop**          | DnD spike and choice; fractional sort and rebalance; optimistic updates and reconcile; touch support; closed-issue styling                                                                                                                                                                                                                                                                  |
| 4   | **Realtime**               | Reverb setup; broadcast events; channel auth; Echo wiring in the board store; presence of conflicts handled                                                                                                                                                                                                                                                                                 |
| 5   | **Sprints**                | `sprints` table, cycle slugs, current-sprint redirect, sprint navigation UI (TODO item), roll/close command, assign issues to sprints, board settings for stories/sprints flags                                                                                                                                                                                                             |
| 6   | **Members & users**        | Add/remove existing board members, roles, last-admin guard; user directory and profile (boards + assigned issues); assignee picker                                                                                                                                                                                                                                                          |
| 7   | **Profile photos**         | Upload/remove, resize to WebP, avatar everywhere, cleanup on account delete                                                                                                                                                                                                                                                                                                                 |
| 8   | **Dashboard & polish**     | Real dashboard (assigned to me, recent boards, current sprint summaries); empty states, skeletons, a11y pass, primary color theme, large-board performance                                                                                                                                                                                                                                  |
| 9   | **Hardening**              | Full-suite review, Larastan level bump, seeders for demo data, deployment config                                                                                                                                                                                                                                                                                                            |

Phases 1 → 3 are the critical path to a usable product. 4 and 5 can swap order. 6–7 are independent of each other.

## 8. Explicitly deferred / out of scope for v1

- Issue attachments and collaborators (placeholders in the old detail page): deferred; keep the layout slots out until built.
- Comments, activity log, notifications, search, labels, and per-board customization beyond the two flags.
- Invites by email: members are added directly from existing users, as in the old TODO.
- Epics UI: the `epic` role exists in the schema and validation, but the board UI treats stories and tasks only until we decide otherwise.

## 9. Decisions (resolved)

1. Flags, not a type enum. All four `has_stories` × `has_sprints` combinations are valid.
2. Reverb for realtime.
3. DnD: `svelte-dnd-action` by default.
4. Sprints are created automatically by the scheduler, with manual override.
5. Epics are schema-only for v1.
6. Deleting a status with issues requires a target status.
