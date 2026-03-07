# Backend Demo — DDD E-commerce с CQRS

> **[English version](README.md)**

Демо-проект интернет-магазина, построенный по принципам DDD и CQRS без Symfony и Doctrine.
Проект демонстрирует совместную работу четырёх компонентов:

| Компонент | Назначение |
|-----------|------------|
| [**Wirebox**](https://github.com/ascetic-soft/wirebox) | DI-контейнер с autowiring и автоконфигурацией через атрибуты |
| [**Rowcast**](https://github.com/ascetic-soft/rowcast) | DataMapper + QueryBuilder поверх PDO |
| [**Rowcast Schema**](https://github.com/ascetic-soft/RowcastSchema) | Schema-first миграции и diff-инструменты |
| [**Waypoint**](https://github.com/ascetic-soft/waypoint) | PSR-15 роутер с маршрутизацией через атрибуты |

## Архитектура

Проект разделён на два слоя с чёткими границами, контролируемыми через [Deptrac](https://github.com/qossmic/deptrac):

```
core/                          # Домен (namespace Core\)
├── SharedKernel/
│   ├── CQRS/                  # Атрибуты и интерфейсы CQRS
│   └── ValueObject/           # Базовые Value Objects
├── Product/                   # Bounded Context: Продукт
│   ├── Domain/                # Агрегат, Value Objects, интерфейс репозитория
│   └── Application/           # Команды, запросы, обработчики, DTO
└── Order/                     # Bounded Context: Заказ
    ├── Domain/
    └── Application/

src/                           # Инфраструктура (namespace App\)
├── Kernel.php                 # Bootstrap приложения
├── Repository/                # Реализации репозиториев (Rowcast)
├── Http/
│   ├── Controller/            # REST-контроллеры (Waypoint)
│   └── Middleware/            # PSR-15 middleware
├── CQRS/                     # CommandBus и QueryBus

database/                      # Схема и миграции (Rowcast Schema)
├── schema.php                 # Декларативная схема БД
├── migrations/                # Сгенерированные классы миграций
└── rowcast-schema.php         # Конфиг CLI (с env-параметрами)
```

Доменный слой (`core/`) — чистый PHP без внешних зависимостей.
Инфраструктурный слой (`src/`) отвечает за HTTP, базу данных и связывание компонентов.

## Требования

- PHP >= 8.4
- MySQL (или SQLite для тестов)
- Composer

## Установка

```bash
git clone https://github.com/ascetic-soft/backend-demo.git
cd backend-demo
composer install
```

## Настройка базы данных

Создайте базу и примените миграции:

```bash
mysql -u root -p -e "CREATE DATABASE backend_demo"
vendor/bin/rowcast-schema migrate
```

Сгенерировать миграцию из изменений схемы:

```bash
vendor/bin/rowcast-schema diff
```

Проверить статус схемы/миграций:

```bash
vendor/bin/rowcast-schema status
```

Параметры подключения задаются через переменные окружения:

| Переменная | По умолчанию |
|------------|-------------|
| `DB_DRIVER` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `backend_demo` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | *(пусто)* |

## Запуск

```bash
php -S localhost:8000 -t public
```

Docker (Caddy + PHP-FPM + MySQL):

```bash
docker compose up -d --build
```

## API

### Продукты

```
GET    /products          — список продуктов
GET    /products/{id}     — получить продукт
POST   /products          — создать продукт
PUT    /products/{id}     — обновить продукт
```

### Заказы

```
GET    /orders            — список заказов
GET    /orders/{id}       — получить заказ
POST   /orders            — создать заказ
POST   /orders/{id}/cancel — отменить заказ
```

## Тестирование

Интеграционные тесты используют изолированные временные SQLite-базы и поднимают схему через миграции Rowcast Schema.

```bash
# Все тесты
vendor/bin/phpunit

# Только юнит-тесты
vendor/bin/phpunit --testsuite unit

# Только интеграционные тесты
vendor/bin/phpunit --testsuite integration
```

## Статический анализ

```bash
# PHPStan (level 9)
vendor/bin/phpstan analyse

# Deptrac — проверка архитектурных границ
vendor/bin/deptrac analyse

# PHP CS Fixer — стиль кода
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Метрики проекта

| Метрика | Значение |
|---------|----------|
| Файлы в `core/` | 37 |
| Файлы в `src/` | 10 |
| Bounded Contexts | 2 (Product, Order) |
| CQRS-обработчики | 8 (4 команды, 4 запроса) |
| Тесты | 92 (62 unit + 30 integration) |
| Assertions | 200 |
| PHPStan | Level 9, 0 ошибок |
| Deptrac | 0 нарушений |

## Лицензия

MIT
