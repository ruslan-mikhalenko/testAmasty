# Архитектура проекта Support Tracker

Подробное руководство по структуре, архитектуре и принципам работы проекта.

## Содержание

1. [Общая архитектура](#общая-архитектура)
2. [Backend (PHP)](#backend-php)
3. [Frontend (Vue 3)](#frontend-vue-3)
4. [База данных](#база-данных)
5. [Docker и развертывание](#docker-и-развертывание)
6. [Поток данных](#поток-данных)
7. [Аутентификация и авторизация](#аутентификация-и-авторизация)

---

## Общая архитектура

### Что такое архитектура проекта?

Архитектура проекта — это способ организации кода и взаимодействия его частей. Наш проект использует **клиент-серверную архитектуру** с разделением на:

- **Backend (серверная часть)** — PHP API, обрабатывает запросы, работает с базой данных
- **Frontend (клиентская часть)** — Vue.js приложение в браузере пользователя
- **База данных** — MySQL, хранит все данные

```
┌─────────────┐         HTTP/JSON         ┌─────────────┐
│   Браузер   │ ────────────────────────> │   Nginx     │
│  (Frontend) │ <──────────────────────── │  (Backend)  │
└─────────────┘                           └──────┬──────┘
                                                  │
                                                  │ SQL
                                                  ▼
                                          ┌─────────────┐
                                          │   MySQL     │
                                          │  (Database) │
                                          └─────────────┘
```

### Почему такая архитектура?

1. **Разделение ответственности** — каждый слой решает свою задачу
2. **Масштабируемость** — можно менять frontend без изменения backend
3. **Безопасность** — логика на сервере, данные не видны в браузере
4. **Переиспользование** — один API может использоваться разными клиентами

---

## Backend (PHP)

### Структура папок

```
backend/
├── src/                      # Исходный код приложения
│   ├── Core/                # Ядро системы (DI контейнер)
│   ├── Http/                # HTTP слой (роутинг, контроллеры, запросы)
│   ├── Services/            # Бизнес-логика
│   ├── Repositories/        # Работа с базой данных
│   └── Exceptions/          # Обработка ошибок
├── public/                  # Точка входа (публичная папка)
│   └── index.php           # Главный файл, принимает все запросы
├── database/
│   ├── migrations/          # SQL скрипты для создания таблиц
│   └── seeders/            # Начальные данные
└── bootstrap/              # Инициализация приложения
    └── container.php       # Настройка DI контейнера
```

### Принцип работы Backend

#### 1. Точка входа: `public/index.php`

Этот файл — первая точка входа для всех запросов. Он:

```php
<?php
// 1. Подключает автозагрузку классов (PSR-4)
require dirname(__DIR__) . '/vendor/autoload.php';

// 2. Загружает конфигурацию из .env
Dotenv::createImmutable($basePath)->safeLoad();

// 3. Запускает сессию для аутентификации
session_start();

// 4. Создает контейнер зависимостей
$container = require $basePath . '/bootstrap/container.php';

// 5. Загружает пользователя из сессии (если авторизован)
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $currentUser = $container->get(UserRepository::class)->findById(...);
}

// 6. Создает объект запроса
$request = Request::fromGlobals($currentUser);

// 7. Обрабатывает запрос через роутер
$response = Router::handle($request, $container);

// 8. Отправляет ответ клиенту
$response->send();
```

**Почему `public/index.php`?**

- Безопасность: файлы вне `public/` недоступны из браузера
- Nginx/FPM настраиваются на работу с этой папкой

#### 2. Роутинг: `src/Http/Router.php`

Роутер определяет, какой контроллер вызвать для каждого URL.

```php
// Пример роута:
GET /api/tickets → TicketController::index()
POST /api/tickets → TicketController::store()
GET /api/tickets/123 → TicketController::show(['id' => 123])
```

**Как это работает?**

1. **FastRoute** парсит URL и метод (GET/POST/PATCH/DELETE)
2. Находит соответствующий роут в `Routes.php`
3. Создает экземпляр контроллера через DI контейнер
4. Вызывает нужный метод контроллера
5. Перехватывает исключения и преобразует их в HTTP ошибки

**Пример обработки ошибок:**

```php
try {
    $result = $handler($request, $vars);
} catch (HttpException $exception) {
    return Response::json(['errors' => [$exception->getMessage()]], $exception->status());
} catch (InvalidArgumentException $e) {
    return Response::json(['errors' => [$e->getMessage()]], 422);
}
```

#### 3. Контроллеры: `src/Http/Controllers/`

Контроллеры обрабатывают HTTP запросы и возвращают ответы.

**Пример: `TicketController.php`**

```php
class TicketController
{
    // Конструктор получает сервис через DI
    public function __construct(private readonly TicketService $tickets) {}

    // GET /api/tickets - список задач
    public function index(Request $request): Response
    {
        // 1. Проверяет авторизацию
        $user = $request->requireAuth();

        // 2. Извлекает параметры из запроса (фильтры, сортировка, пагинация)
        $filters = [
            'status' => $request->query['status'] ?? null,
            'search' => $request->query['search'] ?? null,
        ];

        // 3. Вызывает сервис для получения данных
        $result = $this->tickets->list($user, $filters, ...);

        // 4. Возвращает JSON ответ
        return Response::json(['data' => $result['data'], 'meta' => $result['meta']]);
    }
}
```

**Принципы контроллеров:**

- Тонкие: только принимают запрос и возвращают ответ
- Делегируют логику сервисам
- Не работают напрямую с базой данных

#### 4. Сервисы: `src/Services/`

Сервисы содержат бизнес-логику приложения.

**Пример: `TicketService.php`**

```php
class TicketService
{
    // Получает репозитории через конструктор (DI)
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly StatusRepository $statuses,
        ...
    ) {}

    // Создает новую задачу
    public function create(int $userId, string $title, string $description): array
    {
        // 1. Валидация
        $title = trim($title);
        if ($title === '' || $description === '') {
            throw new InvalidArgumentException('Заполните название и описание');
        }

        // 2. Получает статус по умолчанию
        $status = $this->statuses->getDefaultStatus();

        // 3. Делегирует сохранение репозиторию
        return $this->tickets->create($userId, $title, $description, $status['id']);
    }
}
```

**Принципы сервисов:**

- Содержат бизнес-логику и валидацию
- Не знают про HTTP (не работают с Request/Response)
- Используют репозитории для работы с данными

#### 5. Репозитории: `src/Repositories/`

Репозитории отвечают за работу с базой данных.

**Пример: `TicketRepository.php`**

```php
class TicketRepository
{
    // PDO подключение к БД через конструктор
    public function __construct(private readonly PDO $pdo) {}

    // Создает новую задачу в БД
    public function create(int $userId, string $title, string $description, int $statusId): array
    {
        // 1. Подготовленный запрос (защита от SQL инъекций)
        $stmt = $this->pdo->prepare('
            INSERT INTO tickets (user_id, title, description, status_id)
            VALUES (:user_id, :title, :description, :status_id)
        ');

        // 2. Выполнение с параметрами
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'status_id' => $statusId,
        ]);

        // 3. Возвращает созданную запись
        return $this->findWithRelations((int) $this->pdo->lastInsertId());
    }

    // Пагинация с фильтрами и сортировкой
    public function paginate(...): array
    {
        // Строит динамический SQL запрос на основе фильтров
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 't.status_id = :status_id';
            $params['status_id'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(t.title LIKE :search_title OR t.description LIKE :search_desc)';
            $params['search_title'] = '%' . $filters['search'] . '%';
            $params['search_desc'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT ... FROM tickets t WHERE " . implode(' AND ', $where);
        // Выполняет запрос и возвращает результаты
    }
}
```

**Принципы репозиториев:**

- Только работа с БД (SELECT, INSERT, UPDATE, DELETE)
- Используют подготовленные запросы (безопасность)
- Возвращают массивы данных
- Не содержат бизнес-логику

#### 6. Dependency Injection (DI): `src/Core/Container.php`

**Что такое DI?**
Вместо создания объектов вручную, контейнер автоматически создает и внедряет зависимости.

**Проблема без DI:**

```php
// Плохо: жесткая связанность
class TicketController {
    public function __construct() {
        $this->tickets = new TicketService(
            new TicketRepository(new PDO(...)),
            new StatusRepository(new PDO(...)),
            // ... много зависимостей
        );
    }
}
```

**Решение с DI:**

```php
// Хорошо: зависимости создаются автоматически
$container->set(PDO::class, function() {
    return new PDO($dsn, $user, $pass);
});

$container->set(TicketRepository::class, function(Container $c) {
    return new TicketRepository($c->get(PDO::class)); // PDO создается автоматически
});

$container->set(TicketService::class, function(Container $c) {
    return new TicketService(
        $c->get(TicketRepository::class), // Автоматически создаст все зависимости
        $c->get(StatusRepository::class),
        ...
    );
});

// Использование:
$controller = $container->get(TicketController::class); // Все зависимости созданы автоматически!
```

**Преимущества DI:**

- Легко тестировать (можно подменять зависимости)
- Меньше связанности между классами
- Централизованная конфигурация

### Схема взаимодействия Backend

```
HTTP Request
    │
    ▼
index.php (точка входа)
    │
    ▼
Router (определяет маршрут)
    │
    ▼
Controller (принимает запрос)
    │
    ▼
Service (бизнес-логика, валидация)
    │
    ▼
Repository (работа с БД)
    │
    ▼
PDO → MySQL
    │
    ▼ (данные возвращаются обратно)
Response → JSON → Frontend
```

---

## Frontend (Vue 3)

### Структура папок

```
frontend/
├── src/
│   ├── views/              # Страницы приложения
│   │   ├── LoginView.vue
│   │   ├── ClientDashboard.vue
│   │   └── AdminDashboard.vue
│   ├── stores/             # Pinia хранилища (глобальное состояние)
│   │   ├── auth.js         # Данные пользователя
│   │   ├── tickets.js      # Задачи клиента
│   │   └── admin.js        # Админские данные
│   ├── services/           # API клиент
│   │   └── api.js          # Axios настройка
│   ├── router/             # Vue Router (маршрутизация)
│   │   └── index.js
│   ├── App.vue             # Корневой компонент
│   └── main.js             # Точка входа
├── public/                 # Статические файлы
└── vite.config.js          # Конфигурация Vite
```

### Принцип работы Frontend

#### 1. Точка входа: `main.js`

```javascript
import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import App from "./App.vue";

// Создает Vue приложение
const app = createApp(App);

// Подключает Pinia для управления состоянием
app.use(createPinia());

// Подключает роутер для навигации
app.use(router);

// Монтирует приложение в #app элемент HTML
app.mount("#app");
```

#### 2. Маршрутизация: `router/index.js`

Определяет, какой компонент показать для каждого URL.

```javascript
const router = createRouter({
  routes: [
    { path: "/login", component: LoginView },
    { path: "/client", component: ClientDashboard, meta: { requiresAuth: true } },
    { path: "/admin", component: AdminDashboard, meta: { requiresAuth: true, role: "admin" } },
  ],
});

// Навигационная защита (guard)
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

  // Проверяет авторизацию
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next("/login"); // Перенаправляет на логин
  }

  // Проверяет роль
  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return next(auth.user?.role === "admin" ? "/admin" : "/client");
  }

  next(); // Разрешает переход
});
```

#### 3. State Management: Pinia Stores

**Что такое Pinia?**
Глобальное хранилище состояния приложения. Все компоненты могут читать и изменять данные.

**Пример: `stores/auth.js`**

```javascript
export const useAuthStore = defineStore("auth", {
  // Состояние (данные)
  state: () => ({
    user: null, // Текущий пользователь
    loading: false, // Индикатор загрузки
    error: null, // Ошибки
  }),

  // Геттеры (вычисляемые значения)
  getters: {
    isAuthenticated: (state) => Boolean(state.user),
  },

  // Действия (методы для изменения состояния)
  actions: {
    async login(payload) {
      this.loading = true;
      try {
        const { data } = await api.post("/auth/login", payload);
        this.user = data.data; // Сохраняет пользователя
      } catch (error) {
        this.error = error.response?.data?.errors?.[0];
      } finally {
        this.loading = false;
      }
    },
  },
});
```

**Зачем нужны stores?**

- Централизованное хранение данных (не нужно передавать props через много компонентов)
- Единый источник правды
- Переиспользование логики

#### 4. API Клиент: `services/api.js`

Настройка Axios для работы с backend API.

```javascript
const api = axios.create({
  baseURL: "/api", // Базовый URL для всех запросов
  withCredentials: true, // Отправляет cookies (для сессий)
  headers: {
    "Content-Type": "application/json",
  },
});

// Интерцептор для обработки ошибок
api.interceptors.response.use(
  (response) => response, // Успешный ответ проходит как есть
  (error) => {
    // Обрабатывает ошибки
    const message = error.response?.data?.errors?.[0];
    if (message) {
      console.error(message);
    }
    return Promise.reject(error);
  }
);
```

#### 5. Компоненты: Vue 3 Composition API

**Пример: `ClientDashboard.vue`**

```vue
<script setup>
// Импорты
import { ref, reactive, onMounted } from "vue";
import { useTicketsStore } from "@/stores/tickets";
import api from "@/services/api";

// Store (глобальное состояние)
const tickets = useTicketsStore();

// Локальное состояние компонента
const showForm = ref(false);
const filters = reactive({ status: "", search: "" });

// Функции
const loadTickets = async () => {
  tickets.setFilter("status", filters.status);
  await tickets.fetch({ page: 1 });
};

// Жизненный цикл: выполняется при монтировании компонента
onMounted(() => {
  loadTickets();
});
</script>

<template>
  <!-- HTML шаблон -->
  <div class="filters">
    <select v-model="filters.status" @change="loadTickets">
      <option value="">Все статусы</option>
    </select>
  </div>

  <table>
    <tr v-for="item in tickets.items" :key="item.id">
      <td>{{ item.title }}</td>
    </tr>
  </table>
</template>

<style scoped>
/* Стили только для этого компонента */
</style>
```

**Ключевые концепции Vue:**

1. **Reactivity (Реактивность)**

   ```javascript
   const count = ref(0); // Реактивная переменная
   count.value++; // Автоматически обновит все места, где используется
   ```

2. **v-model (Двустороннее связывание)**

   ```vue
   <input v-model="filters.search" />
   <!-- Автоматически синхронизирует значение input с переменной -->
   ```

3. **v-for (Циклы)**

   ```vue
   <tr v-for="item in tickets.items" :key="item.id">
   <!-- Создает элемент для каждого элемента массива -->
   ```

4. **Computed (Вычисляемые свойства)**
   ```javascript
   const currentSort = computed(() => {
     return tickets.sort.split(":");
   }); // Пересчитывается автоматически при изменении tickets.sort
   ```

#### 6. Схема взаимодействия Frontend

```
Пользователь (клик, ввод)
    │
    ▼
Vue Component (обработчик события)
    │
    ▼
Pinia Store (action метод)
    │
    ▼
API Client (Axios)
    │
    ▼ (HTTP запрос)
Backend API
    │
    ▼ (HTTP ответ)
Store обновляет состояние
    │
    ▼
Vue автоматически обновляет UI (реактивность)
```

---

## База данных

### Структура таблиц

```
users                    # Пользователи
├── id (PK)
├── email (UNIQUE)
├── password (hashed)
├── role (enum: client, admin)
└── created_at

statuses                 # Статусы задач
├── id (PK)
└── name

tickets                  # Задачи/обращения
├── id (PK)
├── user_id (FK → users.id)
├── title
├── description
├── status_id (FK → statuses.id)
├── created_at
└── updated_at

tags                     # Теги
├── id (PK)
├── name
└── color

ticket_tags              # Связь многие-ко-многим (tickets ↔ tags)
├── ticket_id (FK → tickets.id)
└── tag_id (FK → tags.id)

responses                # Ответы админов
├── id (PK)
├── ticket_id (FK → tickets.id)
├── admin_id (FK → users.id)
├── body
└── created_at
```

### Типы связей

1. **Один-ко-многим (1:N)**

   - `users` → `tickets` (один пользователь может иметь много задач)
   - `statuses` → `tickets` (один статус используется многими задачами)

2. **Многие-ко-многим (N:M)**

   - `tickets` ↔ `tags` (задача может иметь много тегов, тег может быть у многих задач)
   - Реализуется через промежуточную таблицу `ticket_tags`

3. **Внешние ключи (Foreign Keys)**
   - Обеспечивают целостность данных
   - При удалении пользователя автоматически удаляются его задачи (ON DELETE CASCADE)

### Миграции

Миграции — это SQL скрипты для создания и изменения структуры БД.

**Пример: `001_create_tables.sql`**

```sql
-- Создает таблицу пользователей
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('client','admin') NOT NULL DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создает таблицу статусов
CREATE TABLE statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Создает таблицу задач с внешними ключами
CREATE TABLE tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Внешние ключи
    CONSTRAINT fk_ticket_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_status FOREIGN KEY (status_id)
        REFERENCES statuses(id)
);

-- Индексы для ускорения поиска
CREATE INDEX idx_tickets_status ON tickets(status_id);
CREATE INDEX idx_tickets_updated_at ON tickets(updated_at);
```

**Зачем нужны миграции?**

- Версионность структуры БД
- Легко воспроизвести БД на новом сервере
- История изменений

### Сиды (Seeds)

Сиды заполняют БД начальными данными.

**Пример: `DatabaseSeeder.php`**

```php
// Создает базовые статусы
$statuses = ['ToDo', 'InProgress', 'Ready For Review', 'Done'];
foreach ($statuses as $status) {
    $stmt = $pdo->prepare('INSERT INTO statuses (name) VALUES (:name)');
    $stmt->execute(['name' => $status]);
}

// Создает администратора по умолчанию
$password = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (:email, :password, :role)');
$stmt->execute([
    'email' => 'admin@example.com',
    'password' => $password,
    'role' => 'admin',
]);
```

---

## Docker и развертывание

### Что такое Docker?

Docker — это платформа для контейнеризации приложений. Контейнеры содержат приложение и все его зависимости, что позволяет запускать его на любой машине одинаково.

### Структура Docker Compose

```yaml
services:
  mysql: # База данных
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: support_tracker
      MYSQL_USER: support
      MYSQL_PASSWORD: secret
    volumes:
      - db-data:/var/lib/mysql # Персистентное хранилище

  php: # PHP-FPM для обработки PHP
    build: ./backend
    volumes:
      - ./backend:/var/www/html # Монтирует код (live reload)
    environment:
      DB_HOST: mysql # Подключается к контейнеру mysql

  nginx: # Web сервер
    image: nginx:alpine
    ports:
      - "8080:80" # Пробрасывает порт 80 контейнера на 8080 хоста
    volumes:
      - ./backend:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php # Запускается после php

  frontend: # Vite dev сервер
    build: ./frontend
    ports:
      - "5173:5173" # Порт для разработки
    volumes:
      - ./frontend:/app # Live reload при изменениях
```

### Как это работает?

1. **MySQL контейнер** — запускает MySQL сервер
2. **PHP контейнер** — обрабатывает PHP код через FastCGI
3. **Nginx контейнер** — принимает HTTP запросы и передает PHP запросы в PHP-FPM
4. **Frontend контейнер** — Vite dev сервер для разработки

### Сеть Docker

Все контейнеры находятся в одной Docker сети и могут обращаться друг к другу по имени сервиса:

```
Frontend → http://nginx/api → Nginx → php:9000 (FastCGI)
PHP → mysql:3306 (PDO)
```

### Volumes (тома)

- **db-data** — персистентное хранилище для MySQL (данные сохраняются при перезапуске)
- **Монтирование кода** — позволяет редактировать код на хосте, изменения сразу видны в контейнере

---

## Поток данных

### Полный цикл: создание задачи

```
1. Пользователь заполняет форму в браузере
   ClientDashboard.vue:
   - Вводит title и description
   - Нажимает "Создать"

2. Vue компонент обрабатывает событие
   ClientDashboard.vue:
   const submitTicket = async () => {
     await tickets.create({ title, description });
   }

3. Pinia Store отправляет запрос
   stores/tickets.js:
   async create(payload) {
     const { data } = await api.post('/tickets', payload);
     // Axios отправляет POST /api/tickets с JSON телом
   }

4. Nginx получает запрос
   - Проксирует /api/* на PHP-FPM

5. index.php обрабатывает запрос
   - Загружает сессию
   - Проверяет авторизацию
   - Создает Request объект

6. Router определяет маршрут
   Router.php:
   - Находит POST /api/tickets → TicketController::store()

7. Controller обрабатывает запрос
   TicketController.php:
   public function store(Request $request) {
     $user = $request->requireAuth('client');
     $data = $request->json();
     $ticket = $this->tickets->create(...);
     return Response::json(['data' => $ticket], 201);
   }

8. Service выполняет бизнес-логику
   TicketService.php:
   public function create(...) {
     // Валидация
     if ($title === '') throw new InvalidArgumentException(...);
     // Получает статус по умолчанию
     $status = $this->statuses->getDefaultStatus();
     // Создает задачу
     return $this->tickets->create(...);
   }

9. Repository сохраняет в БД
   TicketRepository.php:
   public function create(...) {
     $stmt = $this->pdo->prepare('INSERT INTO tickets ...');
     $stmt->execute([...]);
     return $this->findWithRelations($id);
   }

10. Данные возвращаются обратно
    Repository → Service → Controller → Router → Response → Nginx → Frontend

11. Store обновляет состояние
    stores/tickets.js:
    this.items = [...this.items, data.data]; // Добавляет новую задачу

12. Vue обновляет UI
    ClientDashboard.vue:
    - Автоматически показывает новую задачу в таблице (реактивность)
```

---

## Аутентификация и авторизация

### Как работает авторизация?

#### 1. Регистрация / Вход

```
1. Пользователь вводит email и password
2. Frontend отправляет POST /api/auth/login
3. Backend проверяет credentials:
   - Находит пользователя по email
   - Проверяет password через password_verify()
   - Сохраняет user_id в сессию: $_SESSION['user_id'] = $user['id']
4. Возвращает данные пользователя (без пароля)
5. Frontend сохраняет пользователя в Pinia store
```

#### 2. Проверка авторизации

```php
// В index.php при каждом запросе:
if (!empty($_SESSION['user_id'])) {
    $currentUser = $userRepository->findById($_SESSION['user_id']);
}

// В контроллере:
public function index(Request $request) {
    $user = $request->requireAuth(); // Бросает исключение, если нет пользователя
    // ...
}
```

#### 3. Проверка ролей

```php
// Только для клиентов:
$user = $request->requireAuth('client');

// В Vue Router:
router.beforeEach((to, from, next) => {
  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return next('/login'); // Перенаправляет, если роль не подходит
  }
});
```

#### 4. Сессии

- **PHP сессии** — хранят `user_id` на сервере
- **Cookies** — хранят `PHPSESSID` в браузере
- **withCredentials: true** в Axios — отправляет cookies с каждым запросом

---

## Ключевые концепции и паттерны

### 1. MVC (Model-View-Controller)

- **Model** = Repository (работа с данными)
- **View** = Vue компоненты (отображение)
- **Controller** = Controller классы (обработка запросов)

### 2. Dependency Injection

Внедрение зависимостей вместо их создания внутри класса.

**Без DI (плохо):**

```php
class TicketService {
    public function __construct() {
        $this->repo = new TicketRepository(new PDO(...)); // Жесткая связанность
    }
}
```

**С DI (хорошо):**

```php
class TicketService {
    public function __construct(private TicketRepository $repo) {} // Зависимость внедряется
}
```

### 3. Repository Pattern

Инкапсуляция логики работы с БД в отдельные классы.

**Преимущества:**

- Легко заменить источник данных (MySQL → PostgreSQL)
- Легко тестировать (mock репозиторий)
- Чистая бизнес-логика в сервисах

### 4. Service Layer

Слой бизнес-логики между контроллерами и репозиториями.

**Зачем?**

- Контроллеры не знают про бизнес-правила
- Репозитории не содержат бизнес-логику
- Переиспользование логики

### 5. Single Responsibility Principle

Каждый класс отвечает за одну вещь:

- **Controller** — обработка HTTP
- **Service** — бизнес-логика
- **Repository** — работа с БД

---

## Безопасность

### Защита от SQL инъекций

```php
// Плохо:
$sql = "SELECT * FROM users WHERE email = '$email'"; // Уязвимо!

// Хорошо:
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]); // Безопасно
```

### Защита паролей

```php
// Хеширование при сохранении:
$hash = password_hash($password, PASSWORD_BCRYPT);

// Проверка при входе:
if (password_verify($password, $user['password'])) {
    // Пароль верный
}
```

### Валидация данных

- **Frontend** — удобство для пользователя (быстрая обратная связь)
- **Backend** — безопасность (всегда проверяй на сервере!)

---

## Тестирование

### Зачем тестировать?

- Уверенность в работоспособности кода
- Легче рефакторить
- Документация поведения

### Как тестировать?

```php
// Пример теста репозитория:
class TicketRepositoryTest extends TestCase {
    public function testCreateTicket() {
        $pdo = new PDO('sqlite::memory:'); // In-memory база для тестов
        $repo = new TicketRepository($pdo);

        $ticket = $repo->create(1, 'Title', 'Description', 1);

        $this->assertEquals('Title', $ticket['title']);
    }
}
```

---

## Производительность

### Индексы в БД

```sql
CREATE INDEX idx_tickets_status ON tickets(status_id);
-- Ускоряет фильтрацию по статусу
```

### Кеширование

- Можно добавить Redis для кеширования часто запрашиваемых данных
- Кеширование запросов на уровне PHP

### Оптимизация запросов

- Используй JOIN вместо множественных запросов
- Пагинация для больших списков
- Lazy loading для связанных данных

---

## Развертывание в production

### Отличия от разработки

1. **Frontend** — собирается в статические файлы (`npm run build`)
2. **PHP** — использует `opcache` для ускорения
3. **Nginx** — настроен на статические файлы и кеширование
4. **Безопасность** — `.env` с реальными credentials
5. **Логи** — настроены на файловую систему

### Шаги развертывания

```bash
# 1. Склонировать репозиторий
git clone https://github.com/...

# 2. Настроить .env
cp backend/.env.example backend/.env
# Отредактировать .env

# 3. Запустить Docker
docker compose up -d

# 4. Применить миграции
docker compose exec php php bin/db.php migrate
docker compose exec php php bin/db.php seed

# 5. Собрать frontend
docker compose exec frontend npm run build
```

---

## Заключение

Этот проект демонстрирует:

1. **Чистую архитектуру** — разделение слоев и ответственности
2. **Современные технологии** — PHP 8, Vue 3, Docker
3. **Best practices** — DI, Repository Pattern, валидация
4. **Безопасность** — подготовленные запросы, хеширование паролей
5. **Масштабируемость** — легко добавлять новые функции

### Следующие шаги для изучения

1. Изучи каждый слой подробнее
2. Попробуй добавить новую функцию (например, комментарии к задачам)
3. Напиши тесты
4. Изучи оптимизацию запросов
5. Попробуй развернуть на сервере

---

**Автор:** Подробное руководство по архитектуре проекта Support Tracker
**Дата:** 2025
