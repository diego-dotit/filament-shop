<?php

namespace App\Domains\Blog\Models;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class BlogCategory extends Model implements HasMedia
{
    use HasFactory;
    use HasUuids;
    use HasSlugs;
    use HasTranslations;
    use InteractsWithMedia;

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

    public function blogArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            BlogArticle::class,
            'blog_article_blog_category',
            'blog_category_id',
            'blog_article_id',
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
