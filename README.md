# SelfBio

**A personal website and blog with a bilingual profile and an Orchid admin panel.**

[Русский](README.ru.md) · [Quick start](#quick-start) · [Administration](#administration) · [License](LICENSE)

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white) ![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white) ![Orchid](https://img.shields.io/badge/Admin-Orchid%2014-6366F1) ![License](https://img.shields.io/badge/License-MIT-blue)

## Overview

**SelfBio** is a Laravel application for publishing a personal profile, contact information, and blog posts. The home, about, and contact pages are available in Russian and English. Site content is managed in Orchid.

[Whodoku](https://github.com/Yatseck-k/whodoku) is a separate Laravel game project.

## Features

| Area | What it provides |
| --- | --- |
| **Profile** | Bilingual home and about pages, with editable skills, technology groups, and section order |
| **Blog** | Post editing and publication scheduling |
| **Contacts** | A public contact page with details managed in Orchid |
| **Administration** | Content, site settings, user accounts, and role management |
| **SEO** | Page metadata, sitemap, and robots.txt |

## Technology

| Layer | Stack |
| --- | --- |
| **Backend** | PHP 8.5, Laravel 13, Livewire 4, Sanctum 4 |
| **Administration** | Orchid 14 |
| **Frontend** | Vue 3, Inertia.js 3, Vite 8, Tailwind CSS 4 |
| **Data and runtime** | PostgreSQL 17, Redis 8, Nginx, PHP-FPM |
| **Local development** | Docker Compose, Make, Node.js 24, Mailpit |

## Quick start

Requires Git, Docker with Compose, and Make.

```bash
git clone https://github.com/Yatseck-k/selfbio.git
cd selfbio
cp .env.example .env
```

On Linux, adjust `PUID` and `PGID` in `.env` if they differ from your user and group IDs (`id -u` and `id -g`).

```bash
make up
make composer CMD=install
make npm CMD=ci
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
docker compose exec php php artisan storage:link
docker compose exec php php artisan db:seed
make build
docker compose exec php php artisan orchid:admin
```

Open [the site](http://localhost:8080/), [the admin panel](http://localhost:8080/admin), or [Mailpit](http://localhost:8025/). Use `make dev` to run Vite with live reload during frontend development.

## Administration

| Section | Settings |
| --- | --- |
| **Site settings** (`/admin/selfbio/settings`) | Set the site name or reset it to `APP_NAME` |
| **Profile content** (`/admin/selfbio/profile`) | Edit Russian and English copy, skills, technology groups, and the order and visibility of sections |
| **Blog** (`/admin/selfbio/blog`) | Edit posts and schedule publication |
| **Contacts** (`/admin/selfbio/contacts`) | Update public contact details |

Site name and profile changes take effect without rebuilding assets. The two screens use the `platform.selfbio.settings.view` and `platform.selfbio.settings.edit` permissions. Blog publication times are entered in `APP_DISPLAY_TIMEZONE` (`Europe/Moscow` by default) and stored in UTC.

Use `docker compose exec php php artisan site:rename --show` to display the site name, replace `--show` with the new name to change it, or use `--reset` to restore `APP_NAME`.

For a database with existing MoonShine administrators, run `php artisan selfbio:import-admins` in the PHP container to preview the import. Back up the database before running the command with `--apply`.

## Development commands

| Command | What it does |
| --- | --- |
| `make up`, `make down`, `make restart` | Manage the containers |
| `make ps`, `make logs`, `make shell` | Check service status, follow logs, or open a shell in the PHP container |
| `make build`, `make dev` | Build assets or run the Vite development server |
| `make test` | Run Laravel tests using the local testing database |
| `make pint`, `make pint-test` | Format changed PHP files or check code style |
| `make rector`, `make rector-fix` | Preview or apply Rector changes |
| `make build-images` | Rebuild the PHP image |

Run Composer and npm commands with `make composer CMD="…"` and `make npm CMD="…"`. Use `docker compose exec php php artisan pail` to follow Laravel logs.

## Docker configuration

The local `.env.example` combines [`docker-compose.yml`](docker-compose.yml) with [`docker-compose.local.yml`](docker-compose.local.yml). The local override adds Mailpit and exposes the database and Redis ports on loopback. The PHP image also has a production target selected with `PHP_TARGET=prod` when using the base Compose file.

## License

[MIT](LICENSE).
