# Backend Demo — DDD E-commerce with CQRS

> **[Русская версия / Russian version](README.ru.md)**

A demo e-commerce project built with DDD and CQRS principles — without Symfony or Doctrine.
The project showcases three lightweight components working together:

| Component | Purpose |
|-----------|---------|
| [**Wirebox**](https://github.com/ascetic-soft/wirebox) | DI container with autowiring and attribute-based autoconfiguration |
| [**Rowcast**](https://github.com/ascetic-soft/rowcast) | DataMapper + QueryBuilder over PDO |
| [**Waypoint**](https://github.com/ascetic-soft/waypoint) | PSR-15 router with attribute-based routing |

## Architecture

The project is split into two layers with clear boundaries enforced by [Deptrac](https://github.com/qossmic/deptrac):

```
core/                          # Domain layer (namespace Core\)
├── SharedKernel/
│   ├── CQRS/                  # CQRS attributes and interfaces
│   └── ValueObject/           # Base Value Objects
├── Product/                   # Bounded Context: Product
│   ├── Domain/                # Aggregate, Value Objects, repository interface
│   └── Application/           # Commands, queries, handlers, DTOs
└── Order/                     # Bounded Context: Order
    ├── Domain/
    └── Application/

src/                           # Infrastructure layer (namespace App\)
├── Kernel.php                 # Application bootstrap
├── Repository/                # Repository implementations (Rowcast)
├── Http/
│   ├── Controller/            # REST controllers (Waypoint)
│   └── Middleware/            # PSR-15 middleware
├── CQRS/                     # CommandBus and QueryBus
└── Database/
    └── schema.sql             # DB schema
```

The domain layer (`core/`) is pure PHP with no external dependencies.
The infrastructure layer (`src/`) handles HTTP, database access, and wiring.

## Requirements

- PHP >= 8.4
- MySQL (or SQLite for tests)
- Composer

## Installation

```bash
git clone https://github.com/ascetic-soft/backend-demo.git
cd backend-demo
composer install
```

## Database Setup

Create the database and apply the schema:

```bash
mysql -u root -p -e "CREATE DATABASE backend_demo"
mysql -u root -p backend_demo < src/Database/schema.sql
```

Connection parameters are configured via environment variables:

| Variable | Default |
|----------|---------|
| `DB_DRIVER` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `backend_demo` |
| `DB_USER` | `root` |
| `DB_PASSWORD` | *(empty)* |

## Running

```bash
php -S localhost:8000 -t public
```

## API

### Products

```
GET    /products          — list products
GET    /products/{id}     — get a product
POST   /products          — create a product
PUT    /products/{id}     — update a product
```

### Orders

```
GET    /orders            — list orders
GET    /orders/{id}       — get an order
POST   /orders            — place an order
POST   /orders/{id}/cancel — cancel an order
```

## Testing

Integration tests use SQLite in-memory — no external database required.

```bash
# All tests
vendor/bin/phpunit

# Unit tests only
vendor/bin/phpunit --testsuite unit

# Integration tests only
vendor/bin/phpunit --testsuite integration
```

## Static Analysis

```bash
# PHPStan (level 9)
vendor/bin/phpstan analyse

# Deptrac — architecture boundary checks
vendor/bin/deptrac analyse

# PHP CS Fixer — code style
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Project Metrics

| Metric | Value |
|--------|-------|
| Files in `core/` | 37 |
| Files in `src/` | 10 |
| Bounded Contexts | 2 (Product, Order) |
| CQRS handlers | 8 (4 commands, 4 queries) |
| Tests | 92 (62 unit + 30 integration) |
| Assertions | 200 |
| PHPStan | Level 9, 0 errors |
| Deptrac | 0 violations |

## License

MIT
