<?php

namespace App\Http\Controllers\Api\Blog;

use App\Domains\Blog\Models\BlogArticle;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Blog\BlogArticleResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogArticleController extends Controller
{
    /**
     * GET /blog/articles
     *
     * Returns a paginated list of published active blog articles.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $query = BlogArticle::active()
            ->published()
            ->with(['media', 'blogCategories', 'blogCategories.slugs']);

        if ($categorySlug = $request->input('category_slug')) {
            $query->whereHas('blogCategories', fn ($q) => $q->whereHas('slugs', fn ($sq) => $sq->where('slug', $categorySlug)));
        }

        $articles = $query->paginate($perPage);

        $data = BlogArticleResource::collection($articles)->response()->getData(true);

        return ApiResponse::success($data['data'], extra: ['links' => $data['links'], 'meta' => $data['meta']]);
    }

    /**
     * GET /blog/articles/{slug}
     *
     * Returns a single published active blog article by slug.
     */
    public function show(string $slug): BlogArticleResource
    {
        $article = BlogArticle::active()
            ->published()
            ->with(['media', 'blogCategories', 'blogCategories.slugs'])
            ->whereHas('slugs', fn ($q) => $q->where('slug', $slug))
            ->firstOrFail();

        return new BlogArticleResource($article);
    }
}
