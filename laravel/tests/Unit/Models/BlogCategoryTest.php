<?php

namespace Tests\Unit\Models;

use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
    }

    // ── scopeActive ───────────────────────────────────────────────────────────

    public function test_active_scope_returns_only_active_categories(): void
    {
        BlogCategory::create([
            'title'  => ['en' => 'Active Category'],
            'status' => 'active',
        ]);

        BlogCategory::create([
            'title'  => ['en' => 'Inactive Category'],
            'status' => 'inactive',
        ]);

        $result = BlogCategory::active()->get();

        $this->assertCount(1, $result);
        $this->assertSame('active', $result->first()->status);
    }

    public function test_active_scope_excludes_inactive_categories(): void
    {
        BlogCategory::create([
            'title'  => ['en' => 'Inactive One'],
            'status' => 'inactive',
        ]);

        BlogCategory::create([
            'title'  => ['en' => 'Inactive Two'],
            'status' => 'inactive',
        ]);

        $result = BlogCategory::active()->get();

        $this->assertCount(0, $result);
    }

    public function test_active_scope_returns_all_active_when_multiple_exist(): void
    {
        BlogCategory::create(['title' => ['en' => 'Active One'], 'status' => 'active']);
        BlogCategory::create(['title' => ['en' => 'Active Two'], 'status' => 'active']);
        BlogCategory::create(['title' => ['en' => 'Active Three'], 'status' => 'active']);
        BlogCategory::create(['title' => ['en' => 'Inactive'], 'status' => 'inactive']);

        $result = BlogCategory::active()->count();

        $this->assertSame(3, $result);
    }

    public function test_active_scope_can_be_chained_with_query_builder_methods(): void
    {
        BlogCategory::create(['title' => ['en' => 'Alpha Active'], 'status' => 'active']);
        BlogCategory::create(['title' => ['en' => 'Beta Active'], 'status' => 'active']);
        BlogCategory::create(['title' => ['en' => 'Gamma Inactive'], 'status' => 'inactive']);

        $result = BlogCategory::active()->orderBy('created_at', 'desc')->get();

        $this->assertCount(2, $result);
        $result->each(fn ($cat) => $this->assertSame('active', $cat->status));
    }

    public function test_active_scope_returns_empty_collection_when_no_categories_exist(): void
    {
        $result = BlogCategory::active()->get();

        $this->assertCount(0, $result);
    }
}
