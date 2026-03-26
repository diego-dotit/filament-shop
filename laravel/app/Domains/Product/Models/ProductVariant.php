<?php

namespace App\Domains\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'sku', 'regular_price', 'special_price', 'stock_quantity', 'weight', 'is_active'];

    protected $casts = [
        'is_active'     => 'boolean',
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
