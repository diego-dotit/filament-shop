<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentSeoBlogMigrationsTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Products table — SEO meta columns (T1.1)
    // -------------------------------------------------------------------------

    public function test_products_table_has_meta_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'meta_title'));
    }

    public function test_products_table_has_meta_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'meta_description'));
    }

    public function test_products_table_has_meta_keywords_column(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'meta_keywords'));
    }

    // -------------------------------------------------------------------------
    // Categories table — description + SEO meta columns (T1.2)
    // -------------------------------------------------------------------------

    public function test_categories_table_has_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'description'));
    }

    public function test_categories_table_has_meta_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'meta_title'));
    }

    public function test_categories_table_has_meta_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'meta_description'));
    }

    public function test_categories_table_has_meta_keywords_column(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'meta_keywords'));
    }

    // -------------------------------------------------------------------------
    // Manufacturers table — description + SEO meta columns (T1.3)
    // -------------------------------------------------------------------------

    public function test_manufacturers_table_has_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn('manufacturers', 'description'));
    }

    public function test_manufacturers_table_has_meta_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn('manufacturers', 'meta_title'));
    }

    public function test_manufacturers_table_has_meta_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn('manufacturers', 'meta_description'));
    }

    public function test_manufacturers_table_has_meta_keywords_column(): void
    {
        $this->assertTrue(Schema::hasColumn('manufacturers', 'meta_keywords'));
    }

    // -------------------------------------------------------------------------
    // blog_categories table (T1.4)
    // -------------------------------------------------------------------------

    public function test_blog_categories_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('blog_categories'));
    }

    public function test_blog_categories_table_has_uuid_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('blog_categories', 'id'));

        // Insert a row and confirm the id is a UUID-formatted string
        DB::table('blog_categories')->insert([
            'id'     => \Illuminate\Support\Str::uuid()->toString(),
            'status' => 'active',
        ]);

        $row = DB::table('blog_categories')->first();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $row->id,
        );
    }

    public function test_blog_categories_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('blog_categories', [
            'id',
            'title',
            'description',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'slug',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    // -------------------------------------------------------------------------
    // blog_articles table (T1.5)
    // -------------------------------------------------------------------------

    public function test_blog_articles_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('blog_articles'));
    }

    public function test_blog_articles_table_has_uuid_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('blog_articles', 'id'));

        DB::table('blog_articles')->insert([
            'id'        => \Illuminate\Support\Str::uuid()->toString(),
            'author'    => 'Test Author',
            'post_date' => now()->toDateString(),
            'status'    => 'active',
        ]);

        $row = DB::table('blog_articles')->first();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $row->id,
        );
    }

    public function test_blog_articles_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('blog_articles', [
            'id',
            'title',
            'description',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'slug',
            'author',
            'status',
            'post_date',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_blog_articles_table_has_author_string_column(): void
    {
        $this->assertTrue(Schema::hasColumn('blog_articles', 'author'));
    }

    public function test_blog_articles_table_has_post_date_column(): void
    {
        $this->assertTrue(Schema::hasColumn('blog_articles', 'post_date'));
    }

    public function test_blog_articles_table_has_status_column(): void
    {
        $this->assertTrue(Schema::hasColumn('blog_articles', 'status'));
    }

    // -------------------------------------------------------------------------
    // blog_article_blog_category pivot table (T1.6)
    // -------------------------------------------------------------------------

    public function test_blog_article_blog_category_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('blog_article_blog_category'));
    }

    public function test_blog_article_blog_category_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('blog_article_blog_category', [
            'blog_article_id',
            'blog_category_id',
        ]));
    }

    public function test_blog_article_blog_category_enforces_unique_constraint(): void
    {
        $articleId  = \Illuminate\Support\Str::uuid()->toString();
        $categoryId = \Illuminate\Support\Str::uuid()->toString();

        DB::table('blog_articles')->insert([
            'id'        => $articleId,
            'author'    => 'Author',
            'post_date' => now()->toDateString(),
            'status'    => 'active',
        ]);

        DB::table('blog_categories')->insert([
            'id'     => $categoryId,
            'status' => 'active',
        ]);

        DB::table('blog_article_blog_category')->insert([
            'blog_article_id'  => $articleId,
            'blog_category_id' => $categoryId,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('blog_article_blog_category')->insert([
            'blog_article_id'  => $articleId,
            'blog_category_id' => $categoryId,
        ]);
    }

    public function test_blog_article_blog_category_rejects_invalid_article_fk(): void
    {
        $categoryId = \Illuminate\Support\Str::uuid()->toString();

        DB::table('blog_categories')->insert([
            'id'     => $categoryId,
            'status' => 'active',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('blog_article_blog_category')->insert([
            'blog_article_id'  => \Illuminate\Support\Str::uuid()->toString(),
            'blog_category_id' => $categoryId,
        ]);
    }

    // -------------------------------------------------------------------------
    // pages table (T1.7)
    // -------------------------------------------------------------------------

    public function test_pages_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('pages'));
    }

    public function test_pages_table_has_uuid_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('pages', 'id'));

        DB::table('pages')->insert([
            'id'     => \Illuminate\Support\Str::uuid()->toString(),
            'status' => 'active',
        ]);

        $row = DB::table('pages')->first();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $row->id,
        );
    }

    public function test_pages_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('pages', [
            'id',
            'title',
            'description',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'slug',
            'status',
            'created_at',
            'updated_at',
        ]));
    }
}
