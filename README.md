# Promotions Engine

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?logo=symfony&logoColor=white)](https://symfony.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-8-DC382D?logo=redis&logoColor=white)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-compose-2496ED?logo=docker&logoColor=white)](docker-compose.yml)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.0-85EA2D?logo=swagger&logoColor=white)](http://localhost:8080/api/doc)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-12-366488?logo=php&logoColor=white)](https://phpunit.de)
![CI](https://github.com/utkucatal/promotions-engine/actions/workflows/ci.yml/badge.svg)

A RESTful API built with Symfony 7 that determines the lowest applicable price for a product by evaluating a set of active promotions. Each promotion type applies a different pricing strategy — the engine picks the one that results in the lowest total price for the customer.

Built as a portfolio project to demonstrate backend API design, design patterns, caching, rate limiting, and testing practices in PHP.

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Symfony 7 / PHP 8 |
| Database | PostgreSQL 16 |
| Cache / Rate Limiting | Redis |
| Infrastructure | Docker, Nginx, PHP-FPM |
| API Docs | OpenAPI 3.0 (NelmioApiDocBundle + Swagger UI) |
| Testing | PHPUnit 12 (unit + integration) |

## Architecture & Design Decisions

### Strategy + Factory Pattern for Price Modifiers
Each promotion type (`even_items_multiplier`, `date_range_multiplier`, `fixed_price_voucher`) is implemented as a separate class behind a `PriceModifierInterface`. A `PriceModifierFactory` resolves the correct implementation at runtime from the promotion's `type` string — making it easy to add new promotion types without touching existing logic.

### Event-Driven DTO Validation
After deserializing the request body into a DTO, an `AfterDtoCreatedEvent` is dispatched. A `DtoSubscriber` listens to this event and runs Symfony's constraint validator — keeping validation decoupled from the controller.

### Rate Limiting
Rate limiting is enforced at the `kernel.request` level via a `RateLimitListener`. Each controller action is annotated with a `#[RateLimit(limit: N, intervalSeconds: M)]` PHP attribute — the listener reads this via reflection and applies a per-IP, per-endpoint sliding window limiter backed by Redis. Limits can be tuned per endpoint by changing the attribute value; no listener or config changes needed. Endpoints without the attribute are not rate limited. Exceeding the limit returns `429 Too Many Requests`.

### Redis Caching
Valid promotions for a product are cached in Redis for 1 hour (`PromotionCache`), avoiding repeated database queries for high-traffic endpoints.

### Separate Test Database
A dedicated PostgreSQL instance runs for tests (`postgres_test` service in Docker), keeping the test environment fully isolated from the development database.

## Project Structure

```
src/
├── Attribute/         # Custom PHP attributes (RateLimit)
├── Cache/             # Redis caching layer
├── Controller/        # HTTP layer
├── DTO/               # Request/response data objects
├── Entity/            # Doctrine ORM entities (Product, Promotion, ProductPromotion)
├── Enum/              # Backed enums (PromotionType)
├── Event/             # Custom domain events
├── EventListener/     # kernel.request listeners (RateLimitListener, ExceptionListener)
├── Filter/            # Core pricing logic
│   └── Modifier/      # Strategy implementations per promotion type
└── Service/           # Serialization, exception handling
```

## API

### `POST /products/{id}/lowest-price`

Evaluates all active promotions for the given product and returns the lowest achievable price.

**Request:**
```json
{
  "quantity": 3,
  "request_date": "2024-06-01",
  "voucher_code": "SUMMER10"
}
```

**Response:**
```json
{
  "quantity": 3,
  "voucher_code": "SUMMER10",
  "request_date": "2024-06-01",
  "price": 1000,
  "discounted_price": 850,
  "promotion_id": 2,
  "promotion_name": "Summer Sale"
}
```

Rate limit: **60 req / 60s** per IP.

---

### `GET /products/{id}/promotions`

Returns all currently valid promotions for a product.

**Response:**
```json
[
  {
    "id": 1,
    "name": "Summer Sale",
    "type": "date_range_multiplier",
    "adjustment": 0.85,
    "criteria": { "start": "2024-06-01", "end": "2024-08-31" }
  }
]
```

Rate limit: **120 req / 60s** per IP.

---

### Promotions CRUD

| Method | Path | Description | Rate limit |
|--------|------|-------------|------------|
| `GET` | `/promotions` | List all promotions | 120 req / 60s |
| `GET` | `/promotions/{id}` | Get a promotion by ID | 120 req / 60s |
| `POST` | `/promotions` | Create a new promotion | 30 req / 60s |
| `PUT` | `/promotions/{id}` | Update a promotion | 30 req / 60s |
| `DELETE` | `/promotions/{id}` | Delete a promotion | 10 req / 60s |

**Promotion types:** `date_range_multiplier`, `fixed_price_voucher`, `even_items_multiplier`

**Create request example:**
```json
{
  "name": "Black Friday",
  "type": "date_range_multiplier",
  "adjustment": 0.5,
  "criteria": { "start": "2024-11-29", "end": "2024-11-29" }
}
```

---

## API Documentation

Interactive Swagger UI is available at:

```
http://localhost:8080/api/doc
```

Raw OpenAPI JSON spec:

```
http://localhost:8080/api/doc.json
```

Powered by [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle). All endpoints are annotated with OpenAPI attributes (`#[OA\...]`) directly in the controllers. The spec covers both the `/products` and `/promotions` route groups.

## Setup

```bash
cp .env.example .env
docker-compose up -d
docker exec symfony_php php bin/console doctrine:migrations:migrate
```

API available at `http://localhost:8080`.

## Tests

Unit and integration tests are included. Integration tests run against the real test database.

```bash
docker exec symfony_php php bin/phpunit
```

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
