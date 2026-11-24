# SelfBio

**Персональный сайт и блог с двуязычным профилем и админ-панелью Orchid.**

[English](README.md) · [Быстрый старт](#быстрый-старт) · [Администрирование](#администрирование) · [Лицензия](LICENSE)

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white) ![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white) ![Orchid](https://img.shields.io/badge/Admin-Orchid%2014-6366F1) ![License](https://img.shields.io/badge/License-MIT-blue)

## О проекте

**SelfBio** — Laravel-приложение для публикации персонального профиля, контактов и записей блога. Главная, «Обо мне» и контакты доступны на русском и английском. Контент сайта редактируется в Orchid.

[Whodoku](https://github.com/Yatseck-k/whodoku) — отдельный игровой проект на Laravel.

## Возможности

| Раздел | Назначение |
| --- | --- |
| **Профиль** | Двуязычные главная и «Обо мне», редактирование компетенций, групп технологий и порядка блоков |
| **Блог** | Редактирование записей и настройка времени публикации |
| **Контакты** | Публичная страница с контактами, которые можно обновлять через Orchid |
| **Администрирование** | Управление контентом, настройками сайта, учётными записями и ролями |
| **SEO** | Метаданные страниц, карта сайта и robots.txt |

## Технологии

| Уровень | Технологии |
| --- | --- |
| **Backend** | PHP 8.5, Laravel 13, Livewire 4, Sanctum 4 |
| **Админ-панель** | Orchid 14 |
| **Frontend** | Vue 3, Inertia.js 3, Vite 8, Tailwind CSS 4 |
| **Данные и окружение** | PostgreSQL 17, Redis 8, Nginx, PHP-FPM |
| **Локальная разработка** | Docker Compose, Make, Node.js 24, Mailpit |

## Быстрый старт

Нужны Git, Docker с Compose и Make.

```bash
git clone https://github.com/Yatseck-k/selfbio.git
cd selfbio
cp .env.example .env
```

В Linux при необходимости укажите в `.env` идентификаторы пользователя и группы в `PUID` и `PGID`. Их можно узнать командами `id -u` и `id -g`.

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

Откройте [сайт](http://localhost:8080/), [админ-панель](http://localhost:8080/admin) или [Mailpit](http://localhost:8025/). Для разработки фронтенда запустите `make dev`, чтобы Vite обновлял страницу при изменениях.

## Администрирование

| Раздел | Настройки |
| --- | --- |
| **Настройки сайта** (`/admin/selfbio/settings`) | Название сайта и сброс к значению `APP_NAME` |
| **Главная и «Обо мне»** (`/admin/selfbio/profile`) | Тексты на русском и английском, компетенции, группы технологий, порядок и видимость блоков |
| **Блог** (`/admin/selfbio/blog`) | Редактирование записей и настройка времени публикации |
| **Контакты** (`/admin/selfbio/contacts`) | Контактные данные на сайте |

Изменения названия и профиля вступают в силу без пересборки фронтенда. Для этих экранов используются права `platform.selfbio.settings.view` и `platform.selfbio.settings.edit`. Время публикации вводится в часовом поясе `APP_DISPLAY_TIMEZONE` (`Europe/Moscow` по умолчанию), а хранится в UTC.

Чтобы посмотреть текущее название, выполните `docker compose exec php php artisan site:rename --show`. Для переименования укажите новое название вместо `--show`. Флаг `--reset` вернёт значение `APP_NAME`.

Если в базе есть администраторы MoonShine, выполните `php artisan selfbio:import-admins` в PHP-контейнере для предварительной проверки. Перед запуском с флагом `--apply` сделайте резервную копию базы.

## Команды разработки

| Команда | Назначение |
| --- | --- |
| `make up`, `make down`, `make restart` | Управлять контейнерами |
| `make ps`, `make logs`, `make shell` | Проверить состояние сервисов, посмотреть логи или открыть оболочку PHP-контейнера |
| `make build`, `make dev` | Собрать фронтенд или запустить Vite в режиме разработки |
| `make test` | Запустить тесты Laravel с локальной тестовой базой |
| `make pint`, `make pint-test` | Отформатировать изменённые PHP-файлы или проверить стиль |
| `make rector`, `make rector-fix` | Просмотреть или применить изменения Rector |
| `make build-images` | Пересобрать образ PHP |

Для Composer и npm используйте `make composer CMD="…"` и `make npm CMD="…"`. Команда `docker compose exec php php artisan pail` показывает логи Laravel в реальном времени.

## Конфигурация Docker

Локальный `.env.example` объединяет [`docker-compose.yml`](docker-compose.yml) и [`docker-compose.local.yml`](docker-compose.local.yml). Локальная конфигурация добавляет Mailpit и открывает порты базы и Redis на loopback-интерфейсе. Для production-образа PHP в базовой конфигурации используется `PHP_TARGET=prod`.

## Лицензия

[MIT](LICENSE).
