<?php

namespace App\Domains\Blog\Models;

use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class BlogArticle extends Model implements HasMedia
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
        'author',
        'status',
        'post_date',
    ];

    /** @var list<string> */
    public $translatable = ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

    protected $casts = [
        'status'    => 'string',
        'post_date' => 'date',
    ];

    public function blogCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            BlogCategory::class,
            'blog_article_blog_category',
            'blog_article_id',
            'blog_category_id',
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

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereDate('post_date', '<=', now());
    }
}
