<?php

namespace App\Domains\Order\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderItem stores a point-in-time snapshot of the product/variant details.
 * Records are created once when an order is placed and should not be mutated.
 */
class OrderItem extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'variant_sku_snapshot',
        'unit_price_snapshot',
        'quantity',
        'line_total_snapshot',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_snapshot'  => 'decimal:2',
            'line_total_snapshot'  => 'decimal:2',
            'quantity'             => 'integer',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
