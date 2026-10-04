# Nagare

Nagare (ながれ) is a project management app for teams: Kanban and Scrum boards with drag-and-drop, sprints, labels, search and live updates. It is built with Laravel 13, Inertia 3, Svelte 5 and Tailwind 4. See [FEATURES.md](FEATURES.md) for the full feature set and [PLAN.md](PLAN.md) for what's built and what's next.

## Features

- **Boards**: pick any mix of stories and sprints, so a board can be plain Kanban, stories only, sprints only, or full Scrum. Custom statuses, with any of them closing an issue.
- **Issues**: tasks and stories with assignees, descriptions and labels. Drag cards between columns and story lanes, with touch and keyboard support.
- **Sprints**: weekly, monthly, quarterly or custom. Fixed-cycle sprints are created for you, and unfinished work carries over when a sprint ends.
- **Comments and activity**: every issue has a timeline of comments (Markdown, editable) mixed with what changed and who changed it.
- **Labels, filters and search**: colour-coded labels, a board filter bar, and search across all your boards with filters for board, label, open or closed, and assigned to you.
- **Teams**: admins and members per board, a user directory, profiles with photos, and a dashboard of your boards, assigned issues and sprint progress.
- **Accounts**: registration with email verification, two-factor authentication, passkeys, and light and dark themes.
- **Live updates**: changes from other people appear on the board without reloading (needs Reverb).

## Two ways to run it

Nagare works fully with nothing but PHP and SQLite. Each extra service in the recommended stack improves one thing:

|                  | Minimal                                                 | Recommended                                    |
| ---------------- | ------------------------------------------------------- | ---------------------------------------------- |
| Database         | SQLite                                                  | PostgreSQL                                     |
| Queue            | none (runs inside the request)                          | Valkey, with a queue worker                    |
| Live updates     | none: other people's changes show on the next page load | Reverb                                         |
| Search           | database engine: substring matching                     | Typesense: typo-tolerant, faster on large data |
| Sprint roll-over | optional cron job                                       | scheduler process                              |
| Processes        | the web server                                          | web server, Reverb, queue worker, scheduler    |

Both setups run the same code, and the test suite runs on SQLite and PostgreSQL in CI. You can start minimal and move to the recommended stack later by changing `.env` (there is no tool for moving existing SQLite data to PostgreSQL).

## Requirements

- PHP 8.3 or newer with the `gd` or `imagick` extension (profile photos; set `IMAGE_DRIVER=imagick` if you use Imagick) and `pdo_sqlite` or `pdo_pgsql`. The `redis` extension is needed for Valkey.
- [Composer](https://getcomposer.org) 2
- Node.js 24 and [pnpm](https://pnpm.io) (the version is pinned in `package.json`; `corepack enable` installs it)

## Minimal setup: PHP and SQLite

```bash
git clone <this repository> nagare && cd nagare
composer setup          # installs dependencies, creates .env and the SQLite database, builds the frontend
php artisan serve       # http://localhost:8000
```

The defaults in `.env.example` need no other services: `QUEUE_CONNECTION=sync` runs queued work during the request and `BROADCAST_CONNECTION=null` turns off live updates.

- **Try it with demo data**: `php artisan db:seed` adds four sample boards. Sign in as `test@example.com` with the password `password`.
- **Email**: new accounts must verify their email address. The default `MAIL_MAILER=log` writes the verification link to `storage/logs/laravel.log`. Configure SMTP in `.env` to send real email.
- **Sprints**: fixed-cycle sprints are created when someone opens a board. To also close ended sprints and carry unfinished issues over each night, add this to cron: `* * * * * cd /path/to/nagare && php artisan schedule:run >> /dev/null 2>&1`
- **Developing**: run `pnpm run dev` next to `php artisan serve` for hot reloading. (`composer dev` also starts Reverb and a queue worker, so use it with the recommended stack.)

## Recommended setup: PostgreSQL, Reverb, Typesense and Valkey

Set these in `.env` (the commented blocks in `.env.example` list them all):

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=nagare
DB_USERNAME=nagare
DB_PASSWORD=choose-a-password

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=nagare
REVERB_APP_KEY=generate-a-key
REVERB_APP_SECRET=generate-a-secret
REVERB_HOST=localhost       # where browsers reach Reverb
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

SCOUT_DRIVER=typesense
SCOUT_QUEUE=true
TYPESENSE_HOST=127.0.0.1
TYPESENSE_API_KEY=choose-a-key
```

Then migrate, rebuild the frontend (the `VITE_` values are read at build time) and index the existing data:

```bash
php artisan migrate
pnpm run build
php artisan scout:import "App\Models\Issue"
php artisan scout:import "App\Models\Board"
```

Run these alongside the web server, each under a process manager such as supervisor or systemd:

```bash
php artisan queue:work      # sends live updates and keeps the search index current
php artisan reverb:start    # the WebSocket server
php artisan schedule:work   # nightly sprint roll-over (or use the cron job above)
```

### With Docker Compose

`compose.yml` defines the whole recommended stack: the app, Reverb, a queue worker, the scheduler, PostgreSQL, Valkey and Typesense. Set the values above in `.env` (the compose file already points the containers at `postgres`, `valkey` and `typesense`), then:

```bash
pnpm install && pnpm run build     # on the host, since the code is mounted into the containers
docker compose up -d --build
docker compose exec app php artisan scout:import "App\Models\Issue"
docker compose exec app php artisan scout:import "App\Models\Board"
```

The app container runs migrations on start. The file doesn't publish any ports, so add them (or a reverse proxy) in a `compose.override.yml`: the app listens on 8080 and Reverb on 8000, and `REVERB_HOST`, `REVERB_PORT` and `REVERB_SCHEME` must be the address browsers use for Reverb. To run the minimal setup in a container, start just the app with `docker compose up -d app`.

### Production

`Containerfile` has a `production` target that bakes in the dependencies, the built frontend and the code: `docker build -f Containerfile --target production .`. Run it as the web server, Reverb, queue worker and scheduler (the same image with different commands), set `APP_ENV=production` and `APP_DEBUG=false`, set `TRUSTED_PROXIES` to your proxy's addresses, and run `php artisan storage:link` once for profile photos.

## Development

```bash
php artisan test               # the test suite (SQLite in memory)
composer ci:check              # what CI runs: formatting, type checks, Larastan and the tests
pnpm run check:fix             # format and lint the frontend
vendor/bin/pint                # format the PHP
```

To run the suite against PostgreSQL, set `DB_CONNECTION=pgsql` and the `DB_*` values in your environment. To also run the search tests against Typesense, set `TYPESENSE_TEST_HOST` (and `TYPESENSE_TEST_PORT`, `TYPESENSE_TEST_API_KEY`). The Svelte pages call the backend through generated [Wayfinder](https://github.com/laravel/wayfinder) helpers, which `pnpm run build` and `pnpm run dev` regenerate.

## License

MIT
