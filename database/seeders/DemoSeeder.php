<?php

namespace Database\Seeders;

use App\Enums\BoardRole;
use App\Enums\IssueRole;
use App\Enums\SprintCycle;
use App\Models\Board;
use App\Models\Issue;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * A small team with one board of each kind: kanban, sprints only, stories
 * only, and stories with sprints. Sign in as test@example.com / password.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var Collection<int, User>
     */
    private Collection $team;

    /**
     * Next sort value per status id.
     *
     * @var array<int, int>
     */
    private array $nextSort = [];

    public function run(): void
    {
        $you = User::query()->where('email', 'test@example.com')->first()
            ?? User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

        if ($you->boards()->exists()) {
            $this->command->warn('test@example.com already has boards, so the demo data was not added again.');

            return;
        }

        $teammates = array_map(
            fn (string $name) => User::query()->where('name', $name)->first()
                ?? User::factory()->create([
                    'name' => $name,
                    'email' => strtolower(explode(' ', $name)[0]).'@example.com',
                ]),
            ['Aiko Tanaka', 'Ben Carter', 'Chloé Martin', 'Dev Patel', 'Elena Rossi'],
        );
        $this->team = collect([$you, ...$teammates]);

        $this->kanbanBoard($you);
        $this->scrumBoard($you);
        $this->supportBoard($teammates[1], $you);
        $this->roadmapBoard($you);
    }

    /**
     * Tasks only, with closed work on both sides of the board's closed-issue window.
     */
    private function kanbanBoard(User $owner): void
    {
        [$board, [$todo, $doing, $review, $done]] = $this->board('Website Redesign', $owner, ['To Do', 'In Progress', 'Review', 'Done']);

        foreach (['Audit current page templates', 'Pick a type scale', 'Write copy for the pricing page', 'Compress hero images'] as $name) {
            $this->issue($board, $todo, $name);
        }
        foreach (['Build the new navigation', 'Set up a staging site'] as $name) {
            $this->issue($board, $doing, $name);
        }
        $this->issue($board, $review, 'Accessible colour palette');
        foreach (['Collect analytics baseline', 'Interview five customers', 'Moodboard'] as $index => $name) {
            $this->issue($board, $done, $name, closedDaysAgo: $index + 2);
        }
        foreach (['Kickoff meeting', 'Choose a CMS', 'Domain renewal', 'Brand guidelines review'] as $index => $name) {
            $this->issue($board, $done, $name, closedDaysAgo: 20 + $index * 10);
        }
    }

    /**
     * Stories with weekly sprints: last week closed, this week current, next week planned.
     */
    private function scrumBoard(User $owner): void
    {
        [$board, [$todo, $doing, $review, $done]] = $this->board(
            'Mobile App', $owner, ['To Do', 'In Progress', 'In Review', 'Done'], stories: true, cycle: SprintCycle::Weekly,
        );

        $previous = $this->sprint($board, today()->subWeek(), closed: true);
        $current = $this->sprint($board, today());
        $next = $this->sprint($board, today()->addWeek());

        $onboarding = $this->issue($board, $doing, 'Onboarding flow', role: IssueRole::Story, sprint: $current);
        $this->issue($board, $done, 'Welcome screen design', parent: $onboarding, sprint: $previous, closedDaysAgo: 8);
        $this->issue($board, $done, 'Sign-up form', parent: $onboarding, sprint: $current, closedDaysAgo: 1);
        $this->issue($board, $review, 'Email verification step', parent: $onboarding, sprint: $current);
        $this->issue($board, $doing, 'Permissions prompt copy', parent: $onboarding, sprint: $current);
        $this->issue($board, $todo, 'Skip onboarding for returning users', parent: $onboarding, sprint: $next);

        $notifications = $this->issue($board, $todo, 'Push notifications', role: IssueRole::Story);
        $this->issue($board, $todo, 'Register device tokens', parent: $notifications, sprint: $current);
        $this->issue($board, $todo, 'Notification settings screen', parent: $notifications);
        $this->issue($board, $todo, 'Quiet hours', parent: $notifications);

        $darkMode = $this->issue($board, $done, 'Dark mode', role: IssueRole::Story, sprint: $previous, closedDaysAgo: 7);
        $this->issue($board, $done, 'Theme tokens', parent: $darkMode, sprint: $previous, closedDaysAgo: 9);
        $this->issue($board, $done, 'Settings toggle', parent: $darkMode, sprint: $previous, closedDaysAgo: 7);

        $this->issue($board, $todo, 'Offline mode', role: IssueRole::Story);

        $this->issue($board, $doing, 'Fix crash on Android 12 resume', sprint: $current);
        $this->issue($board, $todo, 'Update app store screenshots', sprint: $next);
        $this->issue($board, $todo, 'Investigate slow cold start');
    }

    /**
     * Tasks with monthly sprints, managed by someone else.
     */
    private function supportBoard(User $admin, User $member): void
    {
        [$board, [$new, $investigating, $waiting, $resolved]] = $this->board(
            'Support Queue', $admin, ['New', 'Investigating', 'Waiting on Customer', 'Resolved'], cycle: SprintCycle::Monthly,
        );

        $previous = $this->sprint($board, today()->subMonthNoOverflow(), closed: true);
        $current = $this->sprint($board, today());

        $this->issue($board, $resolved, 'Password reset email not arriving', sprint: $previous, closedDaysAgo: 35);
        $this->issue($board, $resolved, 'Invoice shows the wrong currency', sprint: $previous, closedDaysAgo: 31);
        $this->issue($board, $resolved, 'Export to CSV times out', sprint: $current, closedDaysAgo: 2);
        $this->issue($board, $investigating, 'Duplicate charges for one customer', sprint: $current, assignee: $member);
        $this->issue($board, $waiting, 'Cannot upload files over 10 MB', sprint: $current);
        $this->issue($board, $new, 'Typo in the welcome email', sprint: $current);
        $this->issue($board, $new, 'Feature request: calendar sync');
    }

    /**
     * Stories without sprints, for longer-term planning.
     */
    private function roadmapBoard(User $owner): void
    {
        [$board, [$later, $next, $now, $shipped]] = $this->board(
            'Product Roadmap', $owner, ['Later', 'Next', 'Now', 'Shipped'], stories: true,
        );

        $teams = $this->issue($board, $now, 'Team workspaces', role: IssueRole::Story);
        $this->issue($board, $shipped, 'Invite by email', parent: $teams, closedDaysAgo: 4);
        $this->issue($board, $now, 'Shared billing', parent: $teams);
        $this->issue($board, $next, 'Workspace roles', parent: $teams);

        $integrations = $this->issue($board, $next, 'Integrations', role: IssueRole::Story);
        $this->issue($board, $next, 'Slack notifications', parent: $integrations);
        $this->issue($board, $later, 'GitHub issue sync', parent: $integrations);

        $this->issue($board, $later, 'Public API', role: IssueRole::Story);
        $this->issue($board, $later, 'Customer survey for Q3 priorities');
    }

    /**
     * Create a board with the given statuses (the last one closes issues),
     * owned by `$owner` with the rest of the team as members.
     *
     * @param  list<string>  $statusNames
     * @return array{0: Board, 1: array<int, Status>}
     */
    private function board(string $name, User $owner, array $statusNames, bool $stories = false, ?SprintCycle $cycle = null): array
    {
        $board = Board::factory()->create([
            'name' => $name,
            'has_stories' => $stories,
            'has_sprints' => $cycle !== null,
            'sprint_cycle' => $cycle,
            'created_by' => $owner->id,
        ]);

        foreach ($this->team as $user) {
            $board->users()->attach($user, ['role' => ($user->is($owner) ? BoardRole::Admin : BoardRole::Member)->value]);
        }

        $statuses = collect($statusNames)->map(fn (string $statusName, int $position) => $board->statuses()->create([
            'name' => $statusName,
            'sort' => $position,
            'is_closed' => $position === count($statusNames) - 1,
        ]));

        return [$board, $statuses->all()];
    }

    /**
     * Create the sprint covering the given date on a board's fixed cycle.
     */
    private function sprint(Board $board, CarbonInterface $date, bool $closed = false): Sprint
    {
        $cycle = $board->sprintCycle();
        [$start, $end] = $cycle->periodFor($date) ?? [$date->toImmutable(), $date->toImmutable()];

        return $board->sprints()->create([
            'slug' => $cycle->slugFor($start),
            'start_date' => $start,
            'end_date' => $end,
            'closed_at' => $closed ? $end->endOfDay() : null,
        ]);
    }

    /**
     * Create an issue at the bottom of its status. Issues in the closing
     * status are closed `$closedDaysAgo` days ago. Tasks get an assignee
     * from the team unless one is given.
     */
    private function issue(
        Board $board,
        Status $status,
        string $name,
        IssueRole $role = IssueRole::Task,
        ?Issue $parent = null,
        ?Sprint $sprint = null,
        ?User $assignee = null,
        int $closedDaysAgo = 0,
    ): Issue {
        $this->nextSort[$status->id] = ($this->nextSort[$status->id] ?? 0) + 1;

        return Issue::factory()->inStatus($status)->create([
            'name' => $name,
            'description' => null,
            'role' => $role,
            'parent_id' => $parent?->id,
            'sprint_id' => $sprint?->id,
            'author_id' => $this->team->random()->id,
            'assigned_id' => $assignee->id ?? ($role === IssueRole::Task ? $this->team->random()->id : null),
            'sort' => $this->nextSort[$status->id],
            'closed_at' => $status->is_closed ? now()->subDays($closedDaysAgo) : null,
            'updated_at' => now()->subDays($closedDaysAgo),
        ]);
    }
}
