# Promotions Engine

A RESTful API built with Symfony 7 that determines the lowest applicable price for a product by evaluating a set of active promotions. Each promotion type applies a different pricing strategy — the engine picks the one that results in the lowest total price for the customer.

Built as a portfolio project to demonstrate backend API design, design patterns, caching, and testing practices in PHP.

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Symfony 7 / PHP 8 |
| Database | PostgreSQL 16 |
| Cache | Redis |
| Infrastructure | Docker, Nginx, PHP-FPM |
| Testing | PHPUnit (unit + integration) |

## Architecture & Design Decisions

### Strategy + Factory Pattern for Price Modifiers
Each promotion type (`even_items_multiplier`, `date_range_multiplier`, `fixed_price_voucher`) is implemented as a separate class behind a `PriceModifierInterface`. A `PriceModifierFactory` resolves the correct implementation at runtime from the promotion's `type` string — making it easy to add new promotion types without touching existing logic.

### Event-Driven DTO Validation
After deserializing the request body into a DTO, an `AfterDtoCreatedEvent` is dispatched. A `DtoSubscriber` listens to this event and runs Symfony's constraint validator — keeping validation decoupled from the controller.

### Redis Caching
Valid promotions for a product are cached in Redis for 1 hour (`PromotionCache`), avoiding repeated database queries for high-traffic endpoints.

### Separate Test Database
A dedicated PostgreSQL instance runs for tests (`postgres_test` service in Docker), keeping the test environment fully isolated from the development database.

## Project Structure

```
src/
├── Controller/        # HTTP layer
├── DTO/               # Request/response data objects
├── Entity/            # Doctrine ORM entities (Product, Promotion, ProductPromotion)
├── Filter/            # Core pricing logic
│   └── Modifier/      # Strategy implementations per promotion type
├── Cache/             # Redis caching layer
├── Event/             # Custom domain events
├── EventSubscriber/   # DTO validation via events
└── Service/           # Serialization, exception handling
```

## API

### `POST /products/{id}/lowest-price`

Evaluates all active promotions for the given product and returns the lowest achievable price.

**Request:**
```json
{
  "quantity": 3,
  "requestDate": "2024-06-01",
  "requestLocation": "DE",
  "voucherCode": "SUMMER10"
}
```

**Response:**
```json
{
  "price": 1000,
  "discountedPrice": 850,
  "promotionId": 2,
  "promotionName": "Summer Sale"
}
```

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