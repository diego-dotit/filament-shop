<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Domains\Review\Models\Review;
use App\Domains\Product\Models\Product;
use App\Domains\Customer\Models\Customer;

class ReviewTest extends TestCase
{
    public function test_review_model_class_exists(): void
    {
        $this->assertTrue(class_exists(Review::class));
    }

    public function test_review_has_correct_fillable_fields(): void
    {
        $review = new Review();
        $this->assertSame(['product_id', 'customer_id', 'rating', 'author', 'comment', 'status'], $review->getFillable());
    }

    public function test_review_casts_rating_as_integer(): void
    {
        $review = new Review();
        $casts = $review->getCasts();
        $this->assertArrayHasKey('rating', $casts);
        $this->assertSame('integer', $casts['rating']);
    }

    public function test_review_has_product_relationship(): void
    {
        $review = new Review();
        $relation = $review->product();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    public function test_review_product_relationship_points_to_product_model(): void
    {
        $review = new Review();
        $relation = $review->product();
        $this->assertInstanceOf(Product::class, $relation->getRelated());
    }

    public function test_review_has_customer_relationship(): void
    {
        $review = new Review();
        $relation = $review->customer();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    public function test_review_customer_relationship_points_to_customer_model(): void
    {
        $review = new Review();
        $relation = $review->customer();
        $this->assertInstanceOf(Customer::class, $relation->getRelated());
    }

    public function test_review_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass(Review::class);
        $this->assertSame('App\Domains\Review\Models', $reflection->getNamespaceName());
    }
}
