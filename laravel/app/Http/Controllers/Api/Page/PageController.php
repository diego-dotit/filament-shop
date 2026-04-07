<?php

namespace App\Http\Controllers\Api\Page;

use App\Domains\Page\Models\Page;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Page\PageResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * GET /api/pages
     *
     * Returns a paginated list of active pages.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $pages = Page::active()->paginate($perPage);

        $data = PageResource::collection($pages)->response()->getData(true);

        return ApiResponse::success($data['data'], extra: ['links' => $data['links'], 'meta' => $data['meta']]);
    }

    /**
     * GET /api/pages/{slug}
     *
     * Returns a single active page by slug.
     */
    public function show(string $slug): PageResource
    {
        $page = Page::active()
            ->whereHas('slugs', fn ($q) => $q->where('slug', $slug))
            ->firstOrFail();

        return new PageResource($page);
    }
}
