<?php

namespace App\Domains\Slug\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Slug extends Model
{
    use HasFactory;

    protected $fillable = ['sluggable_type', 'sluggable_id', 'locale', 'slug'];

    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }
}
