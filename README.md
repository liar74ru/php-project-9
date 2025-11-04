# PHP Project 9 — Url Checker (Slim + PDO + PostgreSQL)

Учебное веб‑приложение на PHP с использованием Slim Framework и PostgreSQL.  
Приложение позволяет добавлять URL, запускать проверку страницы (получить HTTP‑код, title, h1, meta description) и просматривать историю проверок.

## Демо

[https://php-project-9-gfi1.onrender.com](https://php-project-9-gfi1.onrender.com)

## Стек:
- PHP 8+
- Slim Framework
- PDO (PostgreSQL)
- GuzzleHttp (HTTP клиент)
- PHPUnit (тесты)
- PHPCS (статический анализ / PSR-12)
- Docker / docker-compose (опционально)

## Быстрый старт

Клонирование и установка зависимостей:

```bash
git clone <репозиторий>
cd php-project-9
make install
# или
composer install
```

Запуск встроенного PHP‑сервера (локально):

```bash
# по умолчанию PORT=8000
make start
# или
php -S 0.0.0.0:8000 -t public public/index.php
```

Откройте в браузере: http://localhost:8000

## Команды Makefile

- `make start` — запустить встроенный PHP сервер.
- `make install` — `composer install`.
- `make lint` — проверка кода PHPCS (PSR‑12).
- `make lint-fix` — автопочинка через phpcbf.
- `make test` — прогон тестов (phpunit), генерирует `coverage-report`.

## Настройка базы данных

В проекте используется PostgreSQL (файл инициализации `database.sql` в корне). Для разработки:

Для локального использования создайте .env файл:

```bash
env

DATABASE_URL=pgsql://user:pass@host:5432/dbname
```

Импортируйте схему:

```bash
# Нужное значение берем из External Database Url
# Экспортируем переменную окружения, чтобы командная оболочка видела эту переменную
export DATABASE_URL=postgresql://janedoe:mypassword@localhost:5432/mydb
# В такой команде выполнятся все инструкции из файла
psql -a -d $DATABASE_URL -f database.sql
```

## Описание основных маршрутов (API)

- GET `/` — главная страница с формой добавления URL.
- POST `/urls` — добавить URL (параметр `url[name]`).
- GET `/urls` — список добавленных URL.
- GET `/urls/{id}` — страница URL с историей проверок.
- POST `/urls/{id}/checks` — выполнить проверку страницы (создаёт запись проверки).

Пример добавления URL (curl):

```bash
curl -X POST http://localhost:8000/urls \
  -d "url[name]=https://example.com"
```

Пример запуска проверки:

```bash
curl -X POST http://localhost:8000/urls/1/checks
```

## Структура проекта

```bash
php-project-9/
├── src/
│   ├── Controllers/     # Контроллеры приложения
│   ├── Models/          # Модели данных (Url, UrlCheck)
│   ├── Services/        # Бизнес-логика
│   └── Database/        # Работа с базой данных
├── tests/               # PHPUnit тесты
├── public/              # Публичная директория
├── templates/           # PHTML шаблоны
└── database.sql         # Схема базы данных
```

## Структура проекта (важные директории)

- `public/` — точка входа (index.php) и статические файлы.
- `src/` — исходный код (Controllers, Models, Services, Database).
- `templates/` — PHTML шаблоны.
- `tests/` — PHPUnit тесты.
- `database.sql` — SQL схема / начальные миграции.
- `.vscode/settings.json` — настройки workspace (phpValidate, inline suggestions и т.д.)

## Тесты

Запуск тестов:

```bash
make test
# или
vendor/bin/phpunit --coverage-html coverage-report
```

Отчёт покрытия появится в `coverage-report/index.html`.

## Кодстайл / Линтинг

Проверка стиля и PSR‑12:

```bash
make lint
```

Автоматическая фиксация некоторых ошибок:

```bash
make lint-fix
```

## Полезные советы по разработке

- VS Code + Remote‑WSL:
  - Если вы работаете в WSL, откройте проект через Remote‑WSL, чтобы `php.validate.executablePath` мог указывать на `/usr/bin/php`.
  - Если VS Code запущен в Windows, укажите абсолютный путь к `php.exe` в `settings.json`.

- Flash‑сообщения:
  - Приложение использует `Slim\Flash\Messages()` и `session_start()` — убедитесь, что сессия инициализирована и ваш шаблон выводит flash‑сообщения (ключи: `success`, `danger`/`error` и т.д.).

- Ошибки 500/404:
  - В `public/index.php` зарегистрированы кастомные обработчики ошибок (ErrorController). При отладке включите `displayErrorDetails: true` только локально.

## Отладка и распространённые проблемы

- Ошибка "не является допустимым исполняемым PHP‑файлом" в VS Code:
  - Укажите корректный путь в `php.validate.executablePath` или откройте проект в WSL.

- Flash‑сообщения не показываются:
  - Проверьте, вызывается ли `session_start()`, вызов `addMessage()` и что шаблон реально выводит сообщения из `$flash`.

- Slim показывает свою отладочную страницу вместо кастомной 500:
  - Проверьте регистрацию обработчиков ошибок (`$errorMiddleware->setErrorHandler(...)`) — лучше регистрировать замыкания, которые безопасно получают контроллер из контейнера.

- PHPCS/Makefile: при ошибках линтера убедитесь, что имя файлов совпадает с классами (`Url.php` vs `url.php`) и удалён BOM.

## Зависимости

Список зависимостей указан в `composer.json`. Чтобы установить:

```bash
composer install
```

## Лицензия

Проект — MIT (при необходимости укажите файл LICENSE).

## Контакты

Если нужно помочь с настройкой окружения, тестами или шаблонами — присылайте ошибки/логи (вывод `php -S` / содержимое `storage/logs` / консольные сообщения), и я помогу детально.
