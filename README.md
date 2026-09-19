# uDeployIt

A small-scale, self-hosted deployment tool for individuals and small teams. Watches your GitHub repositories, deploys via SFTP/SSH to your own servers (LAN or internet-reachable), and runs pre/post-deploy shell scripts. Not a SaaS — there's no multi-tenancy, no billing, and it's meant to run on infrastructure you already control.

Built with Laravel 13, PHP 8.3, PostgreSQL, and Livewire.

## What it does

- Connects to GitHub over SSH (a global default deploy key, with an optional per-project override) and tracks every branch's latest commit.
- Deploys are **incremental by default**: it diffs the last-deployed commit against the target commit and uploads only what changed, falling back to a full upload when there's no prior deploy or the history isn't reachable (e.g. a force-push).
- Runs an optional before/after SSH script per project, with a timeout and a configurable abort-or-continue-on-failure policy.
- Polls GitHub for branch updates **only while a dashboard is open**, at an admin-configurable interval — no webhooks, no cron. If a branch you've marked for auto-deploy updates, it deploys automatically.
- Username/password login only — no email-based login, no self-registration, no self-service password reset (an admin creates and resets accounts). Two roles: admin and staff.

See `LICENSE.md` for the license terms — this affects what you're allowed to do with the code.

## Local setup

```bash
composer install
npm install && npm run build      # or `npm run dev` while actively working on frontend assets
cp .env.example .env              # already done for local dev — see below for the expected values
php artisan key:generate
php artisan migrate
php artisan make:admin            # registration is disabled — this is the only way to create the first user
```

Local dev expects PostgreSQL reachable with the credentials in `.env` (`DB_DATABASE=udeployit`, `DB_USERNAME=postgres`, `DB_PASSWORD=postgres` by default), and `APP_URL` pointing at whatever host your Nginx vhost serves.

## Serving it

Point Nginx (or any PHP-FPM-fronting web server) at `public/` as the docroot. `storage/` and `bootstrap/cache/` must be writable by whichever user PHP-FPM runs as (commonly `www-data`) — if you're also running `artisan`/tests as a different local user, both need write access to those directories (a shared group with `g+w`, or equivalent, is the usual fix).

Deployments run as queued jobs, so a worker must always be running. Run it as `www-data` (the same user as PHP-FPM) — repo mirrors under `storage/app/repos/` get created by whichever process touches a project first, and a worker running as a different user won't be able to write into a mirror the web UI already created (or vice versa).

The `--timeout` matters: deploy scripts are capped at 3600s each, `DeployProjectJob` itself times out at 3660s, so the worker's `--timeout` must stay above that (3700s here). `DB_QUEUE_RETRY_AFTER` in `.env` is already set to 3800s for the same reason — Laravel's default (90s) would let the database queue driver treat a still-running deploy as crashed and hand it to another worker, running it twice.

### Running the worker persistently (systemd)

```bash
sudo tee /etc/systemd/system/udeployit-queue.service > /dev/null <<'UNIT'
[Unit]
Description=uDeployIt queue worker
After=network.target postgresql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/smgithub/udeployit
ExecStart=/usr/bin/php artisan queue:work --timeout=3700 --sleep=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
UNIT

sudo systemctl daemon-reload
sudo systemctl enable --now udeployit-queue
```

`enable --now` starts it immediately and makes it start on every boot from then on.

- Check it's running: `sudo systemctl status udeployit-queue`
- Tail its output live: `journalctl -u udeployit-queue -f`
- **Restart it after updating uDeployIt's own code** (not needed for deployments it *performs* — those are just queued jobs picked up by the already-running worker, no restart required): `sudo systemctl restart udeployit-queue`. The worker is a long-running process that keeps the app's code loaded in memory, so pulling a new version of uDeployIt itself won't take effect until it's restarted.

## Testing

```bash
php artisan test        # Pest — uses an in-memory SQLite DB, safe to run anytime
./vendor/bin/pint       # code style
./vendor/bin/phpstan analyse   # static analysis (Larastan)
```

Git-backed features (`GitRepositoryService`, `GitDiffService`, the branch poller) are tested against real local `git` repositories created in a temp directory — no network access or real GitHub credentials needed. The SSH/SFTP deploy path (`SftpDeployerService`) is tested with mocks; it hasn't been exercised against a real SSH server, so it's worth a manual end-to-end test against an actual target server before relying on it.

## License

["Commons Clause" License Condition v1.0](LICENSE.md) on top of the GNU Affero General Public License v3.0. Free to self-host and modify; the Commons Clause prohibits selling the software or a service substantially derived from it.
