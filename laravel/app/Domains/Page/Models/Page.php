<?php

namespace App\Domains\Page\Models;

use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasUuids;
    use HasSlugs;
    use HasTranslations;

    protected string $slugSourceField = 'title';

    protected $fillable = [
        'title',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
    ];

    /** @var list<string> */
    public $translatable = ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

    protected $casts = [
        'status' => 'string',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
