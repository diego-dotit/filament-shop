<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueSlug implements ValidationRule
{
    public function __construct(
        private readonly ?int $modelId = null,
        private readonly ?string $modelType = null,
    ) {
    }

    /**
     * Fails if the given slug already exists in the slugs table for any entity
     * type or locale, unless it belongs to the current model instance being
     * edited (identified by $modelId and $modelType).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table('slugs')
            ->where('slug', $value)
            ->when(
                $this->modelId !== null && $this->modelType !== null,
                fn ($query) => $query->whereNot(
                    fn ($q) => $q
                        ->where('sluggable_type', $this->modelType)
                        ->where('sluggable_id', $this->modelId)
                )
            )
            ->exists();

        if ($exists) {
            $fail('The slug has already been taken.');
        }
    }
}
