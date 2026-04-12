<?php

namespace App\Domains\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ProductVariant extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['product_id', 'sku', 'name', 'regular_price', 'special_price', 'stock_quantity', 'weight', 'is_active'];

    /** @var list<string> */
    public $translatable = ['name'];

    protected $casts = [
        'is_active' => 'boolean',
        'regular_price' => 'decimal:2',
        'special_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
