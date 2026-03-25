-- Description
Ecommerce application inspired by OpenCart 3, but redesigned for high modularity and AI-readable structure.

Key principles:
- Clear domain separation (Customer, Product, Order, etc.)
- No legacy OpenCart patterns (no options tables, no extensions/modules tables)
- Use product variants instead of options
- Optimized for generative AI interaction and code understanding

Functional scope:
- Single store only (no multi-store support)
- Multi-language support (all customer-facing content must be translatable)
- Multi-currency support (prices stored in base currency, converted per request)

Reference (for conceptual inspiration only, not structure):
https://github.com/opencart/opencart/tree/3.0.x.x


-- Main stack
Backend:
- Laravel 12
- Laravel Sail (local development with MySQL)
- Laravel Sanctum (API authentication)
- Laravel Boost (MCP server + generative AI support)

Admin:
- Filament 5
- Spatie Media Library (image and media management)

Frontend:
- Nuxt (Vue framework)
- Vite (default build tool)

Architecture:
- Backend exposes API only
- Frontend consumes API
- Multiple frontends can be supported


-- Folder structure (root)

/
- /laravel → full Laravel backend (API + admin)
- /web → Nuxt frontend


-- Core system concepts (before database)

Language system:
- System supports multiple languages
- All translatable fields must be stored separately (no inline translations)
- Example: product name, description, category name
- API responses must return data based on selected language

Currency system:
- System supports multiple currencies
- One base currency stored in database
- All product prices stored in base currency
- Conversion applied dynamically using exchange rates
- Orders store currency snapshot at time of purchase

Product system:
- No option/option_value tables
- Each sellable item is a product_variant
- Product = parent entity (grouping)
- ProductVariant = actual purchasable unit (price, stock, SKU)
- Variants can have attributes (size, color, etc.)

Modularity:
- Each domain is isolated (Customer, Product, Order, Cart, etc.)
- No direct cross-domain logic
- Cross-domain communication handled via connector services

Media:
- All images/files handled via Spatie Media Library
- Attached to models (products, categories, etc.)

API-first:
- No server-rendered frontend
- Everything exposed via REST API
- Nuxt consumes API

Admin:
- Built entirely with Filament
- CRUD per domain
- No plugin/module system

-- Database FOR INSPIRATION, adapt as needed for laravel (inspired by opencart3, adapted)

Tables:

users
- id BIGINT
- name VARCHAR
- email VARCHAR
- password VARCHAR
- created_at TIMESTAMP
- updated_at TIMESTAMP

customers
- id BIGINT
- user_id BIGINT
- first_name VARCHAR
- last_name VARCHAR
- email VARCHAR
- phone VARCHAR
- created_at TIMESTAMP
- updated_at TIMESTAMP

customer_addresses
- id BIGINT
- customer_id BIGINT
- country VARCHAR
- city VARCHAR
- address_line_1 VARCHAR
- address_line_2 VARCHAR
- postcode VARCHAR
- created_at TIMESTAMP
- updated_at TIMESTAMP

products
- id BIGINT
- name VARCHAR
- slug VARCHAR
- description TEXT
- status BOOLEAN
- created_at TIMESTAMP
- updated_at TIMESTAMP

product_variants
- id BIGINT
- product_id BIGINT
- sku VARCHAR
- price DECIMAL
- special_price DECIMAL
- quantity INT
- weight DECIMAL
- status BOOLEAN
- created_at TIMESTAMP
- updated_at TIMESTAMP

product_variant_attributes
- id BIGINT
- product_variant_id BIGINT
- attribute_name VARCHAR
- attribute_value VARCHAR

categories
- id BIGINT
- parent_id BIGINT
- name VARCHAR
- slug VARCHAR
- status BOOLEAN
- created_at TIMESTAMP
- updated_at TIMESTAMP

category_product
- id BIGINT
- category_id BIGINT
- product_id BIGINT

manufacturers
- id BIGINT
- name VARCHAR
- slug VARCHAR
- created_at TIMESTAMP
- updated_at TIMESTAMP

product_manufacturer
- id BIGINT
- product_id BIGINT
- manufacturer_id BIGINT

orders
- id BIGINT
- customer_id BIGINT
- status VARCHAR
- total DECIMAL
- currency VARCHAR
- created_at TIMESTAMP
- updated_at TIMESTAMP

order_items
- id BIGINT
- order_id BIGINT
- product_id BIGINT
- product_variant_id BIGINT
- name VARCHAR
- price DECIMAL
- quantity INT
- total DECIMAL

order_addresses
- id BIGINT
- order_id BIGINT
- type VARCHAR
- country VARCHAR
- city VARCHAR
- address_line_1 VARCHAR
- address_line_2 VARCHAR
- postcode VARCHAR

carts
- id BIGINT
- customer_id BIGINT
- created_at TIMESTAMP
- updated_at TIMESTAMP

cart_items
- id BIGINT
- cart_id BIGINT
- product_id BIGINT
- product_variant_id BIGINT
- quantity INT

reviews
- id BIGINT
- product_id BIGINT
- customer_id BIGINT
- rating INT
- comment TEXT
- created_at TIMESTAMP

attributes
- id BIGINT
- name VARCHAR

product_attributes
- id BIGINT
- product_id BIGINT
- attribute_id BIGINT
- value VARCHAR

media
- id BIGINT
- model_type VARCHAR
- model_id BIGINT
- collection_name VARCHAR
- file_name VARCHAR
- mime_type VARCHAR
- size INT
- created_at TIMESTAMP

-- Domains structure AS INSPIRATION, adapt as needed (Laravel)

app/Domains/

Customer/
- Models/
  - Customer.php
  - CustomerAddress.php
- Actions/
- DTOs/
- Repositories/
- Services/

Product/
- Models/
  - Product.php
  - ProductVariant.php
- Actions/
- DTOs/
- Repositories/
- Services/

Category/
- Models/
  - Category.php
- Actions/
- DTOs/
- Repositories/
- Services/

Order/
- Models/
  - Order.php
  - OrderItem.php
  - OrderAddress.php
- Actions/
- DTOs/
- Repositories/
- Services/

Cart/
- Models/
  - Cart.php
  - CartItem.php
- Actions/
- DTOs/
- Repositories/
- Services/

Shared/
- ValueObjects/
- Enums/
- Traits/

-- Inter-domain connectors

CustomerOrder/
- Models/
  - CustomerOrder.php
- Services/
  - AttachCustomerToOrder.php
  - GetCustomerOrders.php

ProductOrder/
- Services/
  - AttachProductToOrder.php

CartProduct/
- Services/
  - AddProductToCart.php
  - RemoveProductFromCart.php

-- API structure (Laravel routes/api.php)

- POST /auth/login
- POST /auth/register
- GET /auth/me

- GET /products
- GET /products/{slug}
- GET /categories
- GET /categories/{slug}

- POST /cart
- GET /cart
- PUT /cart/items
- DELETE /cart/items/{id}

- POST /orders
- GET /orders
- GET /orders/{id}

-- Nuxt frontend structure (/web)

pages/
- index.vue
- product/[slug].vue
- category/[slug].vue
- cart.vue
- checkout.vue
- account/index.vue
- account/orders.vue
- login.vue
- register.vue

components/
- Header.vue
- Footer.vue
- ProductCard.vue
- CartItem.vue

layouts/
- default.vue

composables/
- useAuth.js
- useCart.js
- useProducts.js

plugins/
- api.js

assets/
- css/

-- Nuxt routes behavior

/ → homepage (list products)
/product/:slug → product details with variants
/category/:slug → product listing by category
/cart → cart overview
/checkout → order placement
/account → customer dashboard
/account/orders → order history
/login → login page
/register → register page