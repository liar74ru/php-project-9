PORT ?= 8000

start:
	php -S 0.0.0.0:$(PORT) -t public public/index.php

install:
	composer install

setup: install

compose:
	docker-compose up

compose-bash:
	docker-compose run web bash

compose-setup: compose-build
	docker-compose run web make setup

compose-build:
	docker-compose build

compose-down:
	docker-compose down -v

validate: # проверить код на ошибки
	composer validate

dump: # создание дампа базы данных
	composer dump-autoload

lint: # проверка кода на коректность
	composer exec --verbose phpcs -- --standard=PSR12 src public tests --ignore=coverage-report/

lint-fix: # исправление ошибок в коде
	composer exec --verbose phpcbf -- --standard=PSR12 src public tests --ignore=coverage-report/

test: # запуск тестов
	vendor/bin/phpunit --coverage-html coverage-report

test-xml: # запуск тестов с отчетом в формате XML
	vendor/bin/phpunit --coverage-clover coverage.xml
