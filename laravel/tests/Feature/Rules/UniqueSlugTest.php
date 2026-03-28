<?php

namespace Tests\Feature\Rules;

use App\Rules\UniqueSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UniqueSlugTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function insertSlug(string $slug, string $type = 'App\\Models\\Product', int $id = 1, string $locale = 'en'): void
    {
        DB::table('slugs')->insert([
            'sluggable_type' => $type,
            'sluggable_id'   => $id,
            'locale'         => $locale,
            'slug'           => $slug,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function runValidation(UniqueSlug $rule, string $slug): ?string
    {
        $failMessage = null;
        $rule->validate('slug', $slug, function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        });

        return $failMessage;
    }

    // -----------------------------------------------------------------------
    // Happy path: slug does not exist
    // -----------------------------------------------------------------------

    public function test_passes_when_slug_does_not_exist_in_slugs_table(): void
    {
        $rule = new UniqueSlug();

        $result = $this->runValidation($rule, 'my-new-slug');

        $this->assertNull($result, 'Rule should pass when slug does not exist');
    }

    // -----------------------------------------------------------------------
    // Failure: slug already taken
    // -----------------------------------------------------------------------

    public function test_fails_when_slug_already_exists_in_slugs_table(): void
    {
        $this->insertSlug('existing-slug');

        $rule   = new UniqueSlug();
        $result = $this->runValidation($rule, 'existing-slug');

        $this->assertSame('The slug has already been taken.', $result);
    }

    // -----------------------------------------------------------------------
    // Edit form: ignore own record
    // -----------------------------------------------------------------------

    public function test_passes_when_slug_belongs_to_the_same_model_instance(): void
    {
        $this->insertSlug('my-slug', 'App\\Models\\Product', 42);

        $rule   = new UniqueSlug(modelId: 42, modelType: 'App\\Models\\Product');
        $result = $this->runValidation($rule, 'my-slug');

        $this->assertNull($result, 'Rule should pass when slug belongs to the current model');
    }

    public function test_fails_when_slug_belongs_to_a_different_model_instance(): void
    {
        $this->insertSlug('shared-slug', 'App\\Models\\Product', 99);

        $rule   = new UniqueSlug(modelId: 42, modelType: 'App\\Models\\Product');
        $result = $this->runValidation($rule, 'shared-slug');

        $this->assertSame('The slug has already been taken.', $result);
    }

    public function test_fails_when_slug_belongs_to_different_model_type_with_same_id(): void
    {
        $this->insertSlug('type-slug', 'App\\Models\\Category', 42);

        $rule   = new UniqueSlug(modelId: 42, modelType: 'App\\Models\\Product');
        $result = $this->runValidation($rule, 'type-slug');

        $this->assertSame('The slug has already been taken.', $result);
    }

    // -----------------------------------------------------------------------
    // Constructor default: both params null (new record)
    // -----------------------------------------------------------------------

    public function test_passes_when_constructed_without_arguments_and_slug_is_free(): void
    {
        $rule   = new UniqueSlug();
        $result = $this->runValidation($rule, 'brand-new-slug');

        $this->assertNull($result);
    }
}
