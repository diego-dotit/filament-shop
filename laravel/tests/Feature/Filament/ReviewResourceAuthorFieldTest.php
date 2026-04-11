<?php

namespace Tests\Feature\Filament;

use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use App\Filament\Resources\ReviewResource\Pages\CreateReview;
use App\Filament\Resources\ReviewResource\Pages\EditReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewResourceAuthorFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // Field existence
    // -----------------------------------------------------------------------

    public function test_edit_review_form_has_author_field(): void
    {
        $this->actingAs($this->admin);

        $review = Review::factory()->create();

        Livewire::test(EditReview::class, ['record' => $review->getRouteKey()])
            ->assertFormFieldExists('author');
    }

    public function test_create_review_form_has_author_field(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateReview::class)
            ->assertFormFieldExists('author');
    }

    // -----------------------------------------------------------------------
    // Author field is nullable (no required constraint)
    // -----------------------------------------------------------------------

    public function test_create_review_succeeds_with_author_and_no_customer(): void
    {
        $this->actingAs($this->admin);

        $product = Product::factory()->create();

        Livewire::test(CreateReview::class)
            ->fillForm([
                'product_id'  => $product->id,
                'customer_id' => null,
                'author'      => 'Guest Reviewer',
                'rating'      => 4,
                'status'      => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('reviews', [
            'author'      => 'Guest Reviewer',
            'customer_id' => null,
        ]);
    }

    public function test_edit_review_author_field_persists_changes(): void
    {
        $this->actingAs($this->admin);

        $review = Review::factory()->create(['author' => null]);

        Livewire::test(EditReview::class, ['record' => $review->getRouteKey()])
            ->fillForm(['author' => 'Updated Author'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Author', $review->fresh()->author);
    }

    public function test_edit_review_author_field_allows_null(): void
    {
        $this->actingAs($this->admin);

        $review = Review::factory()->create(['author' => 'Some Author']);

        Livewire::test(EditReview::class, ['record' => $review->getRouteKey()])
            ->fillForm(['author' => null])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // -----------------------------------------------------------------------
    // Form-level validation: at least one of customer_id or author required
    // -----------------------------------------------------------------------

    public function test_create_review_fails_when_both_customer_and_author_are_empty(): void
    {
        $this->actingAs($this->admin);

        $product = Product::factory()->create();

        Livewire::test(CreateReview::class)
            ->fillForm([
                'product_id'  => $product->id,
                'customer_id' => null,
                'author'      => null,
                'rating'      => 3,
                'status'      => 'pending',
            ])
            ->call('create')
            ->assertHasFormErrors(['customer_id']);
    }

    public function test_create_review_succeeds_with_customer_and_no_author(): void
    {
        $this->actingAs($this->admin);

        $product  = Product::factory()->create();
        $customer = \App\Domains\Customer\Models\Customer::factory()->create();

        Livewire::test(CreateReview::class)
            ->fillForm([
                'product_id'  => $product->id,
                'customer_id' => $customer->id,
                'author'      => null,
                'rating'      => 5,
                'status'      => 'pending',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_edit_review_fails_when_both_customer_and_author_are_cleared(): void
    {
        $this->actingAs($this->admin);

        $review = Review::factory()->create(['author' => 'Some Author']);

        Livewire::test(EditReview::class, ['record' => $review->getRouteKey()])
            ->fillForm([
                'customer_id' => null,
                'author'      => null,
            ])
            ->call('save')
            ->assertHasFormErrors(['customer_id']);
    }
}
