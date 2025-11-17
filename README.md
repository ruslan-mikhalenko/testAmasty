# Support Tracker

Мини-проект трекинга обращений клиентов на стеке **PHP 8 + Vue 3**.

## Стек

- PHP 8.2 без фреймворков, FastRoute, PDO, Dotenv
- REST API + JSON, сессии
- Vue 3 (script setup), Vite, Pinia, Vue Router
- MySQL 8
- Docker Compose (php-fpm, nginx, mysql, node)

## Установка

### Требования

- Docker и Docker Compose установлены на вашем компьютере
- Git (для клонирования репозитория)

### Клонирование проекта

1. **Склонируйте репозиторий с GitHub:**

   ```bash
   git clone https://github.com/ruslan-mikhalenko/testAmasty.git
   cd testAmasty
   ```

2. **Создайте файл конфигурации:**

   ```bash
   cp backend/.env.example backend/.env
   ```

3. **Отредактируйте `backend/.env` (опционально):**

   ```env
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=support_tracker
   DB_USERNAME=support
   DB_PASSWORD=secret

   # Опционально: измените данные администратора по умолчанию
   ADMIN_EMAIL=admin@example.com
   ADMIN_PASSWORD=admin123
   ```

## Быстрый старт

1. **Запустите проект через Docker Compose:**

   ```bash
   docker compose up --build -d
   ```

   Эта команда:

   - Соберёт Docker-образы для всех сервисов
   - Запустит MySQL, PHP-FPM, Nginx и фронтенд в фоновом режиме
   - Автоматически установит зависимости (Composer и npm)

2. **Примените миграции базы данных:**

   ```bash
   docker compose exec php php bin/db.php migrate
   ```

3. **Заполните базу данных начальными данными (статусы и администратор):**

   ```bash
   docker compose exec php php bin/db.php seed
   ```

4. **Откройте в браузере:**
   - **Фронтенд:** http://localhost:5173
   - **API:** http://localhost:8080/api

### Данные для входа

После выполнения сида создаётся администратор по умолчанию:

- **Email:** `admin@example.com`
- **Пароль:** `admin123`

Для входа как клиент — зарегистрируйтесь на странице регистрации.

### Остановка проекта

```bash
docker compose down
```

Для полной очистки (включая данные БД):

```bash
docker compose down -v
```

## Документация

- **[ARCHITECTURE.md](docs/ARCHITECTURE.md)** — подробное руководство по архитектуре проекта

  - Объяснение всех слоев и компонентов
  - Принципы работы backend и frontend
  - Поток данных и взаимодействие компонентов
  - Паттерны проектирования и best practices
  - Безопасность и производительность

- **[API.md](docs/api.md)** — описание API эндпоинтов

## Структура проекта

```
support-tracker/
├── backend/              # PHP API (чистый PHP без фреймворков)
│   ├── src/             # Исходный код приложения
│   ├── public/          # Точка входа (index.php)
│   ├── database/        # Миграции и сиды
│   └── bootstrap/       # Инициализация контейнера зависимостей
├── frontend/            # Vue 3 приложение
│   ├── src/             # Исходный код фронтенда
│   │   ├── views/       # Страницы (Client, Admin)
│   │   ├── stores/      # Pinia хранилища
│   │   └── services/    # API клиент
│   └── dist/            # Собранные файлы (генерируется)
├── docker/              # Конфигурация Docker
│   └── nginx/           # Nginx конфиг
├── docs/                # Документация
│   └── api.md           # Описание API эндпоинтов
└── docker-compose.yml   # Оркестрация Docker-контейнеров
```

## Полезные команды

### База данных

```bash
# Применить миграции
docker compose exec php php bin/db.php migrate

# Заполнить начальными данными
docker compose exec php php bin/db.php seed

# Подключиться к MySQL
docker compose exec mysql mysql -u support -psecret support_tracker
```

### Логи

```bash
# Просмотр логов всех сервисов
docker compose logs -f

# Логи конкретного сервиса
docker compose logs -f php
docker compose logs -f nginx
docker compose logs -f frontend
```

### Перезапуск

```bash
# Перезапустить все сервисы
docker compose restart

# Перезапустить конкретный сервис
docker compose restart php
docker compose restart frontend
```

### Разработка

```bash
# Запустить PHPUnit тесты
docker compose exec php composer test

# Пересобрать контейнеры после изменений
docker compose up --build -d
```

## Возможности

- Клиент: регистрация, логин, создание обращений, просмотр статусов и ответов
- Админ: таблица обращений, обновление статуса, теги, ответы, CRUD для тегов и статусов
