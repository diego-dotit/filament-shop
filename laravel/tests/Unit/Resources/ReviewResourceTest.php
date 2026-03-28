<?php

namespace Tests\Unit\Resources;

use App\Domains\Review\Models\Review;
use App\Domains\Customer\Models\Customer;
use App\Http\Resources\Api\Review\ReviewResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReviewResourceTest extends TestCase
{
    public function test_review_resource_has_expected_keys(): void
    {
        $review = $this->makeReview();

        $resource = new ReviewResource($review);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('rating', $data);
        $this->assertArrayHasKey('comment', $data);
        $this->assertArrayHasKey('customer_name', $data);
        $this->assertArrayHasKey('customer_id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function test_review_resource_maps_values_correctly(): void
    {
        $customer = new Customer();
        $customer->setRawAttributes([
            'id'         => 10,
            'first_name' => 'Alice',
            'last_name'  => 'Wonder',
        ]);

        $review = new Review();
        $review->setRawAttributes([
            'id'         => 5,
            'rating'     => 4,
            'comment'    => 'Great product!',
            'status'     => 'pending',
            'created_at' => '2024-04-01 12:00:00',
        ]);
        $review->setRelation('customer', $customer);

        $resource = new ReviewResource($review);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame(5, $data['id']);
        $this->assertSame(4, $data['rating']);
        $this->assertSame('Great product!', $data['comment']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame('Alice Wonder', $data['customer_name']);
        $this->assertSame(10, $data['customer_id']);
    }

    public function test_review_resource_handles_missing_customer(): void
    {
        $review = $this->makeReview();
        $review->setRelation('customer', null);

        $resource = new ReviewResource($review);
        $data = $resource->toArray(Request::create('/'));

        $this->assertNull($data['customer_name']);
        $this->assertNull($data['customer_id']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeReview(): Review
    {
        $customer = new Customer();
        $customer->setRawAttributes([
            'id'         => 1,
            'first_name' => 'Bob',
            'last_name'  => 'Builder',
        ]);

        $review = new Review();
        $review->setRawAttributes([
            'id'         => 1,
            'rating'     => 5,
            'comment'    => 'Excellent!',
            'created_at' => '2024-01-10 09:00:00',
        ]);
        $review->setRelation('customer', $customer);

        return $review;
    }
}
