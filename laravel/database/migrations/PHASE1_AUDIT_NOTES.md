# Phase 1 Audit Notes

## T1.1 — FK & Index Audit: slugs.sluggable_id and media.model_id

**Audited:** 2026-01-01  
**DB Engine:** MySQL (InnoDB), charset `utf8mb4_unicode_ci`  
**Purpose:** Document all FK constraints and indexes on morph ID columns before migration.

---

### 1. `slugs` Table — Full DDL (as-built)

```sql
CREATE TABLE `slugs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sluggable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sluggable_id` bigint unsigned NOT NULL,
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slugs_sluggable_type_sluggable_id_locale_unique` (`sluggable_type`,`sluggable_id`,`locale`),
  UNIQUE KEY `slugs_slug_unique` (`slug`),
  KEY `slugs_sluggable_type_sluggable_id_index` (`sluggable_type`,`sluggable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

#### Foreign Key Constraints on `slugs.sluggable_id`

**None.** No FK constraints exist on `slugs.sluggable_id`. Expected for polymorphic morph columns.

#### Indexes on `slugs.sluggable_id`

| Index Name | Type | Columns (in order) | Seq of sluggable_id |
|---|---|---|---|
| `slugs_sluggable_type_sluggable_id_locale_unique` | UNIQUE | `sluggable_type`, `sluggable_id`, `locale` | 2 |
| `slugs_sluggable_type_sluggable_id_index` | INDEX | `sluggable_type`, `sluggable_id` | 2 |

> `sluggable_id` appears as position 2 in both indexes (always paired with `sluggable_type`).  
> **No single-column index** exists on `sluggable_id` alone.

#### Current Column Type

```
`sluggable_id` bigint unsigned NOT NULL
```

---

### 2. `media` Table — Full DDL (as-built)

```sql
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ...
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

#### Foreign Key Constraints on `media.model_id`

**None.** No FK constraints exist on `media.model_id`. Expected for polymorphic morph columns.

#### Indexes on `media.model_id`

| Index Name | Type | Columns (in order) | Seq of model_id |
|---|---|---|---|
| `media_model_type_model_id_index` | INDEX | `model_type`, `model_id` | 2 |

> `model_id` appears as position 2 in the only index (paired with `model_type`).  
> `media_uuid_unique` and `media_order_column_index` do **not** involve `model_id`.  
> **No single-column index** exists on `model_id` alone.

#### Current Column Type

```
`model_id` bigint unsigned NOT NULL
```

---

### 3. Migration Impact Summary

#### Indexes to drop before column type change

**`slugs` table:**
1. `slugs_sluggable_type_sluggable_id_locale_unique` (UNIQUE compound)
2. `slugs_sluggable_type_sluggable_id_index` (compound INDEX)

**`media` table:**
1. `media_model_type_model_id_index` (compound INDEX)

#### FK constraints to manage

**None** — neither column has FK constraints. No FK drops/recreates required.

#### Indexes to recreate after column type change

Recreate the same indexes after altering columns to `varchar(36)`.

#### Recommended migration pattern

```php
// slugs: drop indexes → change column → recreate indexes
Schema::table('slugs', function (Blueprint $table) {
    $table->dropUnique('slugs_sluggable_type_sluggable_id_locale_unique');
    $table->dropIndex('slugs_sluggable_type_sluggable_id_index');
});
Schema::table('slugs', function (Blueprint $table) {
    $table->string('sluggable_id', 36)->change();
});
Schema::table('slugs', function (Blueprint $table) {
    $table->unique(['sluggable_type', 'sluggable_id', 'locale']);
    $table->index(['sluggable_type', 'sluggable_id']);
});

// media: drop index → change column → recreate index
Schema::table('media', function (Blueprint $table) {
    $table->dropIndex('media_model_type_model_id_index');
});
Schema::table('media', function (Blueprint $table) {
    $table->string('model_id', 36)->change();
});
Schema::table('media', function (Blueprint $table) {
    $table->index(['model_type', 'model_id']);
});
```

---

## T1.2 — Seeder Audit: slugs and media table interactions

### Overview

Audited all seeder files in `laravel/database/seeders/` to identify which insert into the
`slugs` or `media` tables, and whether any seeder has a UUID/integer PK mismatch risk.

---

### Seeder-by-Seeder Findings

| Seeder | Inserts into `slugs`? | Inserts into `media`? | Notes |
|---|---|---|---|
| `DatabaseSeeder.php` | No | No | Orchestrator only; calls sub-seeders |
| `SlugPopulationSeeder.php` | **Yes** | No | Reads `$entity->id` via `Slug::firstOrCreate()` |
| `ProductSeeder.php` | No | No | Uses `Product::updateOrCreate()`; slug auto-generated by `HasSlugs` trait on `saved` event |
| `CategorySeeder.php` | No | No | Uses `Category::updateOrCreate()`; slug auto-generated by trait |
| `ManufacturerSeeder.php` | No | No | Uses `Manufacturer::updateOrCreate()`; slug auto-generated by trait |
| `CustomerSeeder.php` | No | No | No slug/media interactions |
| `OrderSeeder.php` | No | No | No slug/media interactions |
| `CurrencySeeder.php` | No | No | No slug/media interactions |
| `LanguageSeeder.php` | No | No | No slug/media interactions |

---

### Model PK Type Audit

| Model | Trait | PK Type | Has HasUuids? |
|---|---|---|---|
| `Product` | `HasSlugs`, `InteractsWithMedia` | Integer (`$table->id()`) | No |
| `Category` | `HasSlugs`, `InteractsWithMedia` | Integer (`$table->id()`) | No |
| `Manufacturer` | `HasSlugs`, `InteractsWithMedia` | Integer (`$table->id()`) | No |
| `BlogArticle` | `HasSlugs`, `InteractsWithMedia`, **`HasUuids`** | UUID (`$table->uuid('id')`) | **Yes** |
| `BlogCategory` | `HasSlugs`, `InteractsWithMedia`, **`HasUuids`** | UUID (`$table->uuid('id')`) | **Yes** |
| `Page` | `HasSlugs`, **`HasUuids`** | UUID (`$table->uuid('id')`) | **Yes** |

---

### slugs Table Schema

The `slugs` table (migration `2026_03_27_000001_create_slugs_table.php`) uses:

```php
$table->morphs('sluggable');
// Generates: sluggable_type VARCHAR, sluggable_id UNSIGNED BIGINT
```

`$table->morphs()` creates `sluggable_id` as **`unsignedBigInteger`** — an integer column.

**Critical finding:** UUID models (`BlogArticle`, `BlogCategory`, `Page`) generate UUIDs as
their primary keys. When `HasSlugs` trait fires on `saved` and calls `$model->slugs()->create()`
with `sluggable_id => $model->id`, the UUID string (e.g. `"550e8400-e29b-41d4-a716-446655440000"`)
cannot be stored in an `unsignedBigInteger` column without data loss or SQL error.

---

### media Table Schema

The `media` table (migration `2026_03_25_190059_create_media_table.php`) uses:

```php
$table->morphs('model');
// Generates: model_type VARCHAR, model_id UNSIGNED BIGINT
```

Same issue: `model_id` is `unsignedBigInteger`. UUID models using `InteractsWithMedia`
(`BlogArticle`, `BlogCategory`) will fail to store media records correctly.

---

### Conclusion: Which Seeders Need Updates?

#### Seeders requiring NO changes post-migration

All existing seeders (`DatabaseSeeder`, `SlugPopulationSeeder`, `ProductSeeder`,
`CategorySeeder`, `ManufacturerSeeder`, `CustomerSeeder`, `OrderSeeder`, `CurrencySeeder`,
`LanguageSeeder`) operate exclusively on **integer-PK models** (Product, Category,
Manufacturer, Customer, Order). These are unaffected.

`SlugPopulationSeeder` reads `$entity->id` dynamically — it will work correctly for
integer models now and will also work for UUID models after the `slugs` table migration
changes `sluggable_id` to `char(36)`.

#### Future seeders that WILL need awareness

When **BlogArticle**, **BlogCategory**, or **Page** seeders are created, they must not
manually insert into `slugs` or `media` with integer assumptions. The `HasSlugs` and
`InteractsWithMedia` traits handle insertion automatically, and they will work correctly
once the `slugs` and `media` tables have their morph columns migrated to `char(36)`.

#### Schema migrations required (out of scope for seeders, but blocking)

Before any BlogArticle/BlogCategory/Page seeders can function, the following migrations
must run to change the morph ID columns from `unsignedBigInteger` to `char(36)`:

1. `slugs.sluggable_id` → `char(36)` (via `uuidMorphs` or explicit column change)
2. `media.model_id` → `char(36)` (via `uuidMorphs` or explicit column change)

These are tasks T1.3 and T1.4 in Phase 1.

---

### Summary for Seeder-Update Implementer

- **No existing seeder files require modification** for the integer-model migration path.
- `SlugPopulationSeeder` is safe as-is; it uses model-driven `$entity->id` (polymorphic).
- New Blog/Page seeders should rely on Eloquent model creation (triggers `HasSlugs` trait)
  rather than direct DB inserts into `slugs` or `media`.
- The blocking issue is the `slugs` and `media` table schema (integer → char(36) for morph
  ID columns), not the seeders themselves.
