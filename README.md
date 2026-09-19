# uDeployIt

A small-scale, self-hosted deployment tool for individuals and small teams — watches GitHub repos, deploys via SFTP/SSH to your own servers, and runs pre/post-deploy scripts. Not a SaaS.

Built with Laravel 13, PHP 8.3, PostgreSQL, and Livewire.

## Local setup

```bash
composer install
npm install && npm run build
cp .env.example .env   # already done for local dev
php artisan key:generate
php artisan migrate
php artisan make:admin   # registration is disabled — this is the only way to create the first user
```

Serve `public/` with Nginx + PHP-FPM. `storage/` and `bootstrap/cache/` must be writable by the PHP-FPM user.

Run the queue worker (required for deployments once that feature lands):

```bash
php artisan queue:work
```

## License

["Commons Clause" License Condition v1.0](LICENSE.md) on top of the GNU Affero General Public License v3.0. Free to self-host and modify; the Commons Clause prohibits selling the software or a service substantially derived from it.
