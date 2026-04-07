<?php

namespace App\Http\Controllers\Api\Blog;

use App\Domains\Blog\Models\BlogCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Blog\BlogCategoryResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    /**
     * GET /blog-categories
     *
     * Returns a paginated list of active blog categories.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $categories = BlogCategory::active()
            ->with(['media'])
            ->paginate($perPage);

        $data = BlogCategoryResource::collection($categories)->response()->getData(true);

        return ApiResponse::success($data['data'], extra: ['links' => $data['links'], 'meta' => $data['meta']]);
    }
}
