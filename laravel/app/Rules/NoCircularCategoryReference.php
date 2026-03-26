<?php

namespace App\Rules;

use App\Domains\Category\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoCircularCategoryReference implements ValidationRule
{
    public function __construct(private readonly ?int $categoryId)
    {
    }

    /**
     * Fails if the given parent_id is the category itself or one of its descendants.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->categoryId === null) {
            return;
        }

        $parentId = (int) $value;

        if ($parentId === $this->categoryId) {
            $fail('A category cannot be its own parent.');

            return;
        }

        if ($this->isDescendant($parentId)) {
            $fail('A category cannot have one of its descendants as its parent.');
        }
    }

    private function isDescendant(int $parentId): bool
    {
        $descendants = $this->getDescendantIds($this->categoryId);

        return in_array($parentId, $descendants, strict: true);
    }

    private function getDescendantIds(int $categoryId): array
    {
        $ids      = [];
        $children = Category::where('parent_id', $categoryId)->pluck('id')->all();

        foreach ($children as $childId) {
            $ids[]  = $childId;
            $ids    = array_merge($ids, $this->getDescendantIds($childId));
        }

        return $ids;
    }
}
