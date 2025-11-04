# PHP Project 9 — Url Checker (Slim + PDO + PostgreSQL)

Учебное веб‑приложение на PHP с использованием Slim Framework и PostgreSQL.  
Приложение позволяет добавлять URL, запускать проверку страницы (получить HTTP‑код, title, h1, meta description) и просматривать историю проверок.

## Демо

https://php-project-9-gfi1.onrender.com

## Стек
- PHP 8+
- Slim Framework
- PDO (PostgreSQL)
- GuzzleHttp (HTTP клиент)
- PHPUnit (тесты)
- PHPCS (статический анализ / PSR‑12)
- Docker / docker‑compose (опционально)

---

## Быстрый старт

Клонируйте репозиторий и установите зависимости:

```bash
git clone https://github.com/liar74ru/php-project-9.git
cd php-project-9
make install
# или
composer install
```

Запуск встроенного PHP‑сервера для локальной разработки:

```bash
# По умолчанию PORT=8000
make start
# или
php -S 0.0.0.0:8000 -t public public/index.php
```

Откройте http://localhost:8000

---

## Переменные окружения и `.env`

Создайте файл `.env` в корне проекта (не коммитить в репозиторий). Пример содержимого:

```dotenv
# .env.example
DATABASE_URL=postgresql://postgres:postgres@127.0.0.1:5432/app
APP_ENV=development
```

Чтобы экспортировать переменные для текущей сессии:

```bash
export DATABASE_URL="postgresql://postgres:postgres@127.0.0.1:5432/app"
export APP_ENV=development
```

---

## Настройка и импорт схемы базы данных (PostgreSQL)

В репозитории есть `database.sql` — схема/миграция. Чтобы  импортировать:

1. Убедитесь, что PostgreSQL запущен и `DATABASE_URL` корректен.

2. Импорт (если `DATABASE_URL` экспортирован):

```bash
psql "$DATABASE_URL" -f database.sql
```

Или явно:

```bash
psql "postgresql://user:password@host:5432/dbname" -f database.sql
```

## Описание основных маршрутов (API)

- GET `/` — главная страница с формой добавления URL  
- POST `/urls` — добавить URL (параметр `url[name]`)  
- GET `/urls` — список добавленных URL  
- GET `/urls/{id}` — страница URL с историей проверок  
- POST `/urls/{id}/checks` — выполнить проверку страницы (создаёт запись проверки)

Пример добавления URL:

```bash
curl -X POST http://localhost:8000/urls -d "url[name]=https://example.com"
```

Пример запуска проверки:

```bash
curl -X POST http://localhost:8000/urls/1/checks
```

---

## Команды (Makefile)

- `make start` — запустить встроенный PHP‑сервер  
- `make install` — `composer install`  
- `make lint` — запуск PHPCS (PSR‑12)  
- `make lint-fix` — автопочинка через phpcbf  
- `make test` — прогон тестов (phpunit) и генерация покрытия  

---

## Тесты

Запуск тестов:

```bash
make test
# или
vendor/bin/phpunit --coverage-html coverage-report
```

Отчёт покрытия появится в `coverage-report/index.html`.

> В тестах `tests/Database/ConnectionTest.php` проверяется поведение `Connection::get()` при наличии/отсутствии `DATABASE_URL`. Для CI убедитесь, что `DATABASE_URL` доступна в окружении.

---

## Кодстайл / Линтинг

Проверка стиля и PSR‑12:

```bash
make lint
```

Автоматическое исправление:

```bash
make lint-fix
```

---

## Структура проекта

```
php-project-9/
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Database/
├── tests/
├── public/
├── templates/
└── database.sql
```

---

## Частые проблемы и отладка

- Flash‑сообщения не появляются:
  - Убедитесь, что `session_start()` вызывается и шаблон выводит `$flash`.

- Кастомная 500 страница не отображается:
  - Проверьте регистрацию обработчиков ошибок (`$errorMiddleware->setErrorHandler(...)`). Для надёжности используйте замыкания, которые получают контроллер из контейнера и оборачивают вызов в `try/catch`.

- PHPCS: проверьте регистр имён файлов и BOM.

- Проблемы с импортом `database.sql`:
  - Убедитесь, что SQL совместим с PostgreSQL. При необходимости адаптируйте автокомплимент/типы.

---

## Зависимости

Список в `composer.json`. Установка:

```bash
composer install
```

---

## Лицензия

MIT

---

## Контакты и помощь

Если нужно помочь с настройкой PostgreSQL, адаптацией `database.sql`, правками шаблонов или CI/CD (Render), пришлите:
- содержимое `database.sql` (если нужна адаптация),
- логи ошибок,
- вывод `php -S` или логи контейнера.

С радостью помогу детально.
