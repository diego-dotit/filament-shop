<?php

namespace Tests\Feature\Filament;

use App\Domains\Product\Models\Product;
use App\Filament\Resources\Product\Pages\CreateProduct;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Filament\Resources\ProductResource;
use App\Models\User;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Form Schema ────────────────────────────────────────────────────────

    public function test_product_form_contains_media_upload_field(): void
    {
        $component = Livewire::test(CreateProduct::class);

        $component->assertFormFieldExists('media');
    }

    public function test_media_upload_field_is_spatie_media_library_component(): void
    {
        $component = Livewire::test(CreateProduct::class);

        // Get the form fields from the Livewire component
        $livewireInstance = $component->instance();
        $formFields       = $livewireInstance->form->getFlatFields(withHidden: true);
        $mediaField       = $formFields['media'] ?? null;

        $this->assertNotNull($mediaField, 'Media field should exist in the form');
        $this->assertInstanceOf(SpatieMediaLibraryFileUpload::class, $mediaField);
    }

    // ── Media Upload on Create ─────────────────────────────────────────────

    public function test_uploading_image_on_create_stores_file_in_media_library(): void
    {
        $image = UploadedFile::fake()->image('product.jpg', 200, 200);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name'      => 'Media Product',
                'slug'      => 'media-product',
                'is_active' => true,
                'media'     => [$image],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'media-product')->first();

        $this->assertNotNull($product);
        $this->assertCount(1, $product->getMedia('images'));
    }

    public function test_primary_image_is_first_media_in_images_collection(): void
    {
        $image = UploadedFile::fake()->image('first.jpg', 300, 300);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name'      => 'Primary Image Product',
                'slug'      => 'primary-image-product',
                'is_active' => true,
                'media'     => [$image],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'primary-image-product')->first();

        $this->assertNotNull($product->getFirstMedia('images'));
    }

    // ── Media Persists on Edit ─────────────────────────────────────────────

    public function test_existing_media_displayed_on_edit_page(): void
    {
        $product = Product::factory()->create(['slug' => 'edit-media-product']);

        // Attach media directly via the model
        $image = UploadedFile::fake()->image('existing.jpg');
        $product->addMedia($image)->toMediaCollection('images');

        $component = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);

        $component->assertSuccessful();

        // The product should still have its media after loading the edit page
        $this->assertCount(1, $product->fresh()->getMedia('images'));
    }

    public function test_media_persists_after_saving_edit_page(): void
    {
        $product = Product::factory()->create(['slug' => 'persist-media-product']);
        $image   = UploadedFile::fake()->image('existing.jpg');
        $product->addMedia($image)->toMediaCollection('images');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'slug'      => 'persist-media-product',
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(1, $product->fresh()->getMedia('images'));
    }

    // ── Media Validation ───────────────────────────────────────────────────

    public function test_media_upload_accepts_jpg_png_webp(): void
    {
        $jpg  = UploadedFile::fake()->image('photo.jpg');
        $png  = UploadedFile::fake()->image('photo.png');
        $webp = UploadedFile::fake()->image('photo.webp');

        // Each should not cause form-level errors when submitted individually
        foreach ([$jpg, $png, $webp] as $file) {
            Livewire::test(CreateProduct::class)
                ->fillForm([
                    'name'      => 'Accept Test',
                    'slug'      => 'accept-test-' . uniqid(),
                    'is_active' => true,
                    'media'     => [$file],
                ])
                ->call('create')
                ->assertHasNoFormErrors(['media']);
        }
    }
}
