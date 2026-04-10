<?php

namespace Tests\Feature\Filament;

use App\Domains\Review\Models\Review;
use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewListRecordsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // Default pending filter
    // -----------------------------------------------------------------------

    public function test_list_reviews_shows_pending_reviews_by_default(): void
    {
        $pending  = Review::factory()->pending()->create();
        $approved = Review::factory()->approved()->create();
        $rejected = Review::factory()->rejected()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved, $rejected]);
    }

    // -----------------------------------------------------------------------
    // Status filter
    // -----------------------------------------------------------------------

    public function test_list_reviews_can_filter_by_approved_status(): void
    {
        $pending  = Review::factory()->pending()->create();
        $approved = Review::factory()->approved()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->filterTable('status', 'approved')
            ->assertCanSeeTableRecords([$approved])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_list_reviews_can_filter_by_rejected_status(): void
    {
        $pending  = Review::factory()->pending()->create();
        $rejected = Review::factory()->rejected()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->filterTable('status', 'rejected')
            ->assertCanSeeTableRecords([$rejected])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_list_reviews_can_filter_by_pending_status_explicitly(): void
    {
        $pending  = Review::factory()->pending()->create();
        $approved = Review::factory()->approved()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->filterTable('status', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    }

    // -----------------------------------------------------------------------
    // Filter existence
    // -----------------------------------------------------------------------

    public function test_list_reviews_has_status_select_filter(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->assertTableFilterExists('status');
    }

    // -----------------------------------------------------------------------
    // Resource navigation
    // -----------------------------------------------------------------------

    public function test_review_resource_uses_review_model(): void
    {
        $this->assertSame(Review::class, ReviewResource::getModel());
    }

    // -----------------------------------------------------------------------
    // Pagination
    // -----------------------------------------------------------------------

    public function test_list_reviews_does_not_show_eleventh_record_on_first_page(): void
    {
        // Create 10 pending reviews that should appear on page 1
        Review::factory()->pending()->count(10)->create();

        // The 11th record should be on page 2 (page size = 10)
        $overflow = Review::factory()->pending()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->assertCanNotSeeTableRecords([$overflow]);
    }

    // -----------------------------------------------------------------------
    // Approve action
    // -----------------------------------------------------------------------

    public function test_approve_action_transitions_pending_review_to_approved(): void
    {
        $review = Review::factory()->pending()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->callTableAction('approve', $review);

        $this->assertSame('approved', $review->fresh()->status);
    }

    public function test_approve_action_is_visible_for_pending_reviews(): void
    {
        $review = Review::factory()->pending()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->assertTableActionVisible('approve', $review);
    }

    public function test_approve_action_is_hidden_for_approved_reviews(): void
    {
        $review = Review::factory()->approved()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->filterTable('status', 'approved')
            ->assertTableActionHidden('approve', $review);
    }

    public function test_approve_action_is_hidden_for_rejected_reviews(): void
    {
        $review = Review::factory()->rejected()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->filterTable('status', 'rejected')
            ->assertTableActionHidden('approve', $review);
    }

    // -----------------------------------------------------------------------
    // Reject action
    // -----------------------------------------------------------------------

    public function test_reject_action_transitions_pending_review_to_rejected(): void
    {
        $review = Review::factory()->pending()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->callTableAction('reject', $review);

        $this->assertSame('rejected', $review->fresh()->status);
    }

    public function test_reject_action_is_visible_for_pending_reviews(): void
    {
        $review = Review::factory()->pending()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->assertTableActionVisible('reject', $review);
    }

    public function test_reject_action_is_hidden_for_approved_reviews(): void
    {
        $review = Review::factory()->approved()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->filterTable('status', 'approved')
            ->assertTableActionHidden('reject', $review);
    }

    public function test_reject_action_is_hidden_for_rejected_reviews(): void
    {
        $review = Review::factory()->rejected()->create();

        $this->actingAs($this->admin);

        Livewire::test(ListReviews::class)
            ->filterTable('status', 'rejected')
            ->assertTableActionHidden('reject', $review);
    }

    // -----------------------------------------------------------------------
    // Header actions
    // -----------------------------------------------------------------------

    public function test_list_reviews_get_header_actions_returns_create_action(): void
    {
        $reflection = new \ReflectionClass(ListReviews::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(CreateAction::class, $actions[0]);
    }

    public function test_list_reviews_create_action_has_label(): void
    {
        $reflection = new \ReflectionClass(ListReviews::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $createAction = $actions[0];
        $this->assertSame('New review', $createAction->getLabel());
    }
}
