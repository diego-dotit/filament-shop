<?php

namespace Tests\Unit\Models;

use App\Domains\Language\Models\Language;
use App\Domains\Page\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
    }

    // ── scopeActive ───────────────────────────────────────────────────────────

    public function test_active_scope_returns_only_active_pages(): void
    {
        Page::create([
            'title'  => ['en' => 'Active Page'],
            'status' => 'active',
        ]);

        Page::create([
            'title'  => ['en' => 'Inactive Page'],
            'status' => 'inactive',
        ]);

        $result = Page::active()->get();

        $this->assertCount(1, $result);
        $this->assertSame('active', $result->first()->status);
    }

    public function test_active_scope_excludes_inactive_pages(): void
    {
        Page::create([
            'title'  => ['en' => 'Inactive One'],
            'status' => 'inactive',
        ]);

        Page::create([
            'title'  => ['en' => 'Inactive Two'],
            'status' => 'inactive',
        ]);

        $result = Page::active()->get();

        $this->assertCount(0, $result);
    }

    public function test_active_scope_returns_all_active_when_multiple_exist(): void
    {
        Page::create(['title' => ['en' => 'Active One'], 'status' => 'active']);
        Page::create(['title' => ['en' => 'Active Two'], 'status' => 'active']);
        Page::create(['title' => ['en' => 'Active Three'], 'status' => 'active']);
        Page::create(['title' => ['en' => 'Inactive'], 'status' => 'inactive']);

        $result = Page::active()->count();

        $this->assertSame(3, $result);
    }

    public function test_active_scope_can_be_chained_with_query_builder_methods(): void
    {
        Page::create(['title' => ['en' => 'Alpha Active'], 'status' => 'active']);
        Page::create(['title' => ['en' => 'Beta Active'], 'status' => 'active']);
        Page::create(['title' => ['en' => 'Gamma Inactive'], 'status' => 'inactive']);

        $result = Page::active()->orderBy('created_at', 'desc')->get();

        $this->assertCount(2, $result);
        $result->each(fn ($page) => $this->assertSame('active', $page->status));
    }

    public function test_active_scope_returns_empty_collection_when_no_pages_exist(): void
    {
        $result = Page::active()->get();

        $this->assertCount(0, $result);
    }
}
