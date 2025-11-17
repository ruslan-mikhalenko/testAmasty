# Support Tracker

Мини-проект трекинга обращений клиентов на стеке **PHP 8 + Vue 3**.

## Стек
- PHP 8.2 без фреймворков, FastRoute, PDO, Dotenv
- REST API + JSON, сессии
- Vue 3 (script setup), Vite, Pinia, Vue Router
- MySQL 8
- Docker Compose (php-fpm, nginx, mysql, node)

## Быстрый старт
```bash
cp backend/.env.example backend/.env
# при необходимости скорректируйте доступы к БД

docker compose up --build -d
```

После запуска:
- API: http://localhost:8080/api
- Клиент (Vite dev): http://localhost:5173
- По умолчанию создаётся админ `admin@example.com / admin123` (см. сидер)

Для применения миграций/сидов внутри контейнера PHP:
```bash
docker compose exec php php bin/db.php migrate
docker compose exec php php bin/db.php seed
```

## Структура
- `backend/` — исходники API
- `frontend/` — Vue-приложение
- `docker/` — конфиги для nginx
- `docs/api.md` — описание конечных точек

## Скрипты
- `composer test` — PHPUnit
- `npm run dev|build` — фронтенд

## Возможности
- Клиент: регистрация, логин, создание обращений, просмотр статусов и ответов
- Админ: таблица обращений, обновление статуса, теги, ответы, CRUD для тегов и статусов
