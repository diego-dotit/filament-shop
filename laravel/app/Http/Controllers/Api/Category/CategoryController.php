<?php

namespace App\Http\Controllers\Api\Category;

use App\Domains\Category\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Category\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug): CategoryResource
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        return new CategoryResource($category);
    }
}
