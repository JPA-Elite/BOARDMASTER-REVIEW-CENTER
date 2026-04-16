# BoardMasters Review Center

BoardMasters Review Center is a Laravel + Inertia + React application for managing board exam review content, subscriptions, and admin operations.

## Tech Stack
- Laravel
- Inertia.js
- React
- Vite
- Tailwind CSS
- Docker + Nginx + MySQL + phpMyAdmin

## Requirements
- PHP 8.2+, Composer, Node.js 18+, and MySQL (for non-Docker setup)
- Docker Desktop (or Docker Engine with Compose v2) for containerized setup

## Local Setup (Without Docker)
1. Install PHP dependencies.
   - `composer install`
2. Install JavaScript dependencies.
   - `npm install`
3. Create your environment file.
   - `copy .env.example .env`
4. Generate an app key.
   - `php artisan key:generate`
5. Configure your database in `.env`, then run migrations.
   - `php artisan migrate`
6. Create an admin user and set `role` to `admin`.
7. Start backend and frontend.
   - `php artisan serve`
   - `npm run dev`

## Docker Setup (Development)
This uses `docker-compose.yml` with Nginx, PHP-FPM, MySQL, and phpMyAdmin.

1. Copy Docker env template.
   - `copy .env.docker.example .env.docker`
2. Ensure your Laravel app env exists.
   - `copy .env.example .env`
3. Add a local host entry (run editor as Administrator):
   - `127.0.0.1 boardmasters.local`
4. Start containers.
   - `docker compose --env-file .env.docker -f docker-compose.yml up -d --build`

URLs:
- App: `http://boardmasters.local:8080`
- phpMyAdmin: `http://localhost:8081`
- MySQL Host Port: `3306`

Optional workers (queue + scheduler):
- `docker compose --env-file .env.docker -f docker-compose.yml --profile workers up -d`

Make shortcuts:
- `make dev-build`
- `make dev-up`
- `make dev-down`
- `make dev-migrate`
- `make dev-seed`
- `make dev-deploy`

## Docker Setup (Production)
This uses `docker-compose.prod.yml` with HTTPS-ready Nginx config and optional phpMyAdmin profile.

1. Copy and configure production Docker env.
   - `copy .env.docker.prod.example .env.docker.prod`
2. Create your Laravel production env file.
   - `copy .env.example .env.production`
3. Ensure `APP_ENV_FILE` in `.env.docker.prod` points to your production env file with a valid `APP_KEY` and secrets.
4. Ensure Let's Encrypt certs exist on host:
   - `/etc/letsencrypt/live/<CERT_DOMAIN>/fullchain.pem`
   - `/etc/letsencrypt/live/<CERT_DOMAIN>/privkey.pem`
5. Start production stack.
   - `docker compose --env-file .env.docker.prod -f docker-compose.prod.yml up -d --build`

Optional phpMyAdmin in production:
- `docker compose --env-file .env.docker.prod -f docker-compose.prod.yml --profile tools up -d phpmyadmin`

Make shortcuts:
- `make prod-build`
- `make prod-up`
- `make prod-down`
- `make prod-migrate`
- `make prod-seed`
- `make prod-deploy`

## Notes
- Admin routes are under `/admin` and require an authenticated user with `role = admin`.
- Public marketing pages are served through Webflow HTML in `resources/js/webflow`.
- Mobile Google sign-in endpoint uses `POST /api/mobile/login/google`.
- Set `GOOGLE_AUTH_AUDIENCES` in `.env` with allowed Google OAuth client IDs (comma-separated).
