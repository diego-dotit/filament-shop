<?php

namespace App\Domains\Manufacturer\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Manufacturer extends Model
{
    use HasFactory;
    use HasSlugs;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_manufacturer');
    }
}
