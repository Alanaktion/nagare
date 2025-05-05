# To-do

## Moving from Jetstream

- [ ] Consolidate stories/tasks into single `issues` record with parent_id like Phproject
    - [ ] Issue should have role, one of:
        - `epic`
        - `story`
        - `task`
- [ ] Add sprints table (by board), change sprint field on issues to FK

- [ ] UI for CRUD boards/statuses

- [#] Use primary color in shadcn-vue. Right now `zinc` is mostly what I want, but primary actions and focus rings could benefit from some color!
- [x] Boards have memberships directly instead of using Teams?
    - This removes a lot of the overhead of teams that we weren't using or didn't have a reason to use (both from the user side and the dev side)
    - [ ] Board creator is connected via membership, not a direct `user_id` relation, so "ownership" can be easily changed?
        - [ ] Could still be useful to know who *created* the board, with a `user_id` value
- [x] Rework card API to be in normal web route, since we don't need `sanctum`.

- [ ] Upgrade to Tailwind 4?
    - [ ] Waiting for shadcn/vue to upgrade, then I'll replace the components manually with the new ones.
    - [ ] Also ideally want support from tailwind eslint plugin but whatevs.
        - [ ] Once this happens, I want to add it to *all* of my TW4 projects because wow is consistency nice!

## Old Jetstream notes

- [x] Rework components to use `shadcn/vue`
    - Replacing Headless UI probably? idk.
    - `npx shadcn-vue@latest init`

- [x] Support adding statuses when creating boards
- [x] Support editing statuses on existing boards
- [ ] Support configuration of sprint cycles when creating Scrum boards
- [ ] Support Scrum/Kanban behavior detail customization:
    - Sprints and Stories can be configured independently -- Non-sprint boards should support stories, non-story boards should support sprints.
    - [ ] Just have "Sprints" and "Stories" settings on each, don't have "Kanban" vs "Scrum"
    - [x] Add `sprint` to cards table, so we can correctly assign non-story tasks to sprints when configured to do so on the board.
- [ ] Add UI for navigating between sprints on sprint boards
- [ ] Prevent editing of personal teams
- [x] Directly add existing users to teams instead of sending invites
