<?php

namespace App\Domains\Manufacturer\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Manufacturer extends Model implements HasMedia
{
    use HasFactory;
    use HasSlugs;
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /** @var list<string> */
    public $translatable = ['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_manufacturer');
    }
}
