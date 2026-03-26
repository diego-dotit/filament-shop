# Domain-Driven Structure

This directory organises the application's business logic into self-contained **domain modules**.
Each domain owns its models, data-transfer objects, repositories, services, and actions.
Cross-cutting concerns that bridge two domains are expressed as **connector domains**.

---

## Namespace Convention

All classes inside `app/Domains/` follow PSR-4 autoloading under the `App\Domains` root:

```
App\Domains\{Domain}\Models\{Model}
App\Domains\{Domain}\Services\{Service}
App\Domains\{Domain}\DTOs\{DTO}
App\Domains\{Domain}\Repositories\{Repository}
App\Domains\{Domain}\Actions\{Action}
```

---

## Core Domains

| Directory | Purpose |
|-----------|---------|
| `Customer/` | Customer profile linked 1:1 to a Laravel `User`. Owns `Customer`, `CustomerAddress` models and the profile/address API logic. |
| `Product/` | Product catalogue. Owns `Product`, `ProductVariant`, `ProductVariantAttribute`, `Attribute`, and `ProductAttribute` models. Handles translatable fields (Spatie Laravel Translatable) and media (Spatie Media Library). |
| `Category/` | Self-referential category tree. Owns `Category` model (translatable name/description) and category–product pivot logic. Media-enabled. |
| `Manufacturer/` | Brand/manufacturer registry. Owns `Manufacturer` model and manufacturer–product pivot logic. |
| `Cart/` | Shopping cart scoped to a customer session. Owns `Cart` and `CartItem` models. |
| `Order/` | Purchase lifecycle. Owns `Order`, `OrderItem`, and `OrderAddress` models. Currency code and exchange-rate snapshots are stored on each order to ensure immutability. |
| `Review/` | Customer product reviews. Owns `Review` model with rating (1–5), comment, and moderation status (`pending` / `approved` / `rejected`). |
| `Language/` | Supported UI languages. Owns `Language` model (`code`, `name`, `is_default`). Drives `Accept-Language` resolution for translatable content. |
| `Currency/` | Supported currencies with exchange rates. Owns `Currency` model (`code`, `name`, `symbol`, `rate`, `is_base`). All prices are stored in the base currency; conversion happens at request time. |

---

## Connector Domains

Connector domains encapsulate logic that sits **between** two core domains. They prevent direct
cross-domain dependencies and keep each core domain cohesive.

| Directory | Bridges | Purpose |
|-----------|---------|---------|
| `CartProduct/` | `Cart` ↔ `Product` | Orchestrates adding/removing product variants to/from a cart; validates stock and resolves pricing. |
| `CustomerOrder/` | `Customer` ↔ `Order` | Handles order placement from a customer's cart, order history retrieval, and customer-facing status tracking. |
| `ProductOrder/` | `Product` ↔ `Order` | Manages inventory adjustments when orders are placed or cancelled; resolves product/variant snapshots stored on `OrderItem`. |

---

## Subdirectory Purposes

| Subdirectory | Convention |
|--------------|-----------|
| `Models/` | Eloquent models. One class per database entity. Traits (`HasTranslations`, `HasMedia`, etc.) are applied here. |
| `Services/` | Business-logic orchestrators. Stateless classes that compose repositories and fire actions. Injected via Laravel's service container. |
| `DTOs/` | Data Transfer Objects (readonly PHP classes / value objects). Used to move validated, typed data between layers without coupling to Request or Model shapes. |
| `Repositories/` | Data-access abstractions. Wrap Eloquent queries so that higher layers are not tightly coupled to the ORM. Interfaces live here alongside concrete implementations. |
| `Actions/` | Single-responsibility command objects. Each Action class does exactly one thing (e.g., `CreateOrderAction`, `ApproveReviewAction`). Keeps `Services` thin and Actions independently testable. |

---

## Design Principles

- **No direct cross-domain model imports** — if Domain A needs data from Domain B, go through a connector domain or a shared interface.
- **All prices in base currency** — conversion is applied at the read layer, never stored.
- **Translatable fields via JSON columns** — Spatie Laravel Translatable stores translations as JSON on the model's own table (no separate `_translations` tables).
- **Media via Spatie Media Library** — `Product` and `Category` use `HasMedia` / `InteractsWithMedia`; no raw file path columns.
- **Auth is separate** — `User` lives in `App\Models` (standard Laravel); `Customer` is a profile linked by `user_id`.
