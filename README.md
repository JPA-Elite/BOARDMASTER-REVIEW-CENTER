# Boardmaster Review Center

Boardmaster Review Center is a Laravel + Inertia + React application for managing board exam review content, subscriptions, and admin operations.

## Tech Stack
- Laravel
- Inertia.js
- React
- Vite
- Tailwind CSS

## Requirements
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL or compatible database

## Setup
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

## Development
1. Start the backend server.
   - `php artisan serve`
2. Start the Vite dev server.
   - `npm run dev`

## Build
- `npm run build`

## Notes
- Admin routes are under `/admin` and require an authenticated user with `role = admin`.
- Public marketing pages are served through Webflow HTML in `resources/js/webflow`.
