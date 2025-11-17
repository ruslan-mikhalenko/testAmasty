# API Справочник

## Аутентификация
- `POST /api/auth/register` — body: `{ email, password }`
- `POST /api/auth/login` — body: `{ email, password }`
- `POST /api/auth/logout`
- `GET /api/auth/me` — вернуть текущего пользователя

## Клиентские задачи
- `GET /api/tickets` — query: `status`, `search`, `dateFrom`, `dateTo`, `sort`, `page`, `perPage`
- `POST /api/tickets` — body: `{ title, description }`
- `GET /api/tickets/{id}`

## Админ
- `PATCH /api/tickets/{id}` — body: `{ status_id, tags[] }`
- `POST /api/tickets/{id}/reply` — body: `{ message }`
- `GET|POST|PUT|DELETE /api/tags`
- `GET|POST|PUT|DELETE /api/statuses`

Все ответы в формате `{ data, meta?, errors? }`.
