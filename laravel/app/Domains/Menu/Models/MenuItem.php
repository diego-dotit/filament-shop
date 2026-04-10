<?php

namespace App\Domains\Menu\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'name',
        'type',
        'value',
        'value_id',
        'sort',
        'subcategories',
    ];

    /** @var list<string> */
    public $translatable = ['name', 'value'];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id');
    }

    // -----------------------------------------------------------------------
    // Tree helpers (used by solution-forest/filament-tree)
    // -----------------------------------------------------------------------

    /**
     * Identifies root-level items (no parent).
     * Used by filament-tree's getRootLayerRecords() to detect root nodes.
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Tells filament-tree which column stores the sort order.
     * Overrides the package default ('order') to match our 'sort' column.
     */
    public function determineOrderColumnName(): string
    {
        return 'sort';
    }

    /**
     * The default parent key value for root items.
     * Tells filament-tree to store null for root-level parent_id.
     */
    public static function defaultParentKey(): mixed
    {
        return null;
    }
}
