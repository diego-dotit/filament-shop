<?php

namespace App\Domains\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantAttribute extends Model
{
    use HasFactory;

    protected $fillable = ['product_variant_id', 'name', 'value'];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
