<?php

namespace App\Http\Controllers\Api\Category;

use App\Domains\Category\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Category\CategoryResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $data = CategoryResource::collection($categories)->response()->getData(true);

        return ApiResponse::success($data['data']);
    }

    public function show(string $slug): CategoryResource
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('is_active', true),
                'parent',
            ])
            ->firstOrFail();

        return new CategoryResource($category);
    }
}
