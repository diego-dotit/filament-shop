<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_article_blog_category', function (Blueprint $table) {
            $table->foreignUuid('blog_article_id')->constrained('blog_articles')->cascadeOnDelete();
            $table->foreignUuid('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blog_article_id', 'blog_category_id'], 'blog_article_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_article_blog_category');
    }
};
