<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Menu\Models\MenuItem;
use App\Domains\Page\Models\Page;
use App\Domains\Product\Models\Product;
use App\Filament\Resources\MenuResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SolutionForest\FilamentTree\Actions\DeleteAction as TreeDeleteAction;
use SolutionForest\FilamentTree\Actions\EditAction as TreeEditAction;
use SolutionForest\FilamentTree\Components\Tree;
use SolutionForest\FilamentTree\Concern\InteractWithTree;
use SolutionForest\FilamentTree\Contract\HasTree;

class EditMenu extends EditRecord implements HasTree
{
    use InteractWithTree;

    protected static string $resource = MenuResource::class;

    // -----------------------------------------------------------------------
    // Tree configuration
    // -----------------------------------------------------------------------

    /**
     * Override getModel() so that the tree component works with MenuItem records.
     * EditRecord's parent getModel() returns Menu::class (the resource model).
     */
    public function getModel(): string
    {
        return MenuItem::class;
    }

    /**
     * Replace the default EditRecord form view with the tree editor.
     */
    public function getView(): string
    {
        return 'filament.resources.menu-resource.pages.edit-menu';
    }

    /**
     * Configure the Tree component (no extra configuration needed here).
     */
    public static function tree(Tree $tree): Tree
    {
        return $tree;
    }

    /**
     * Maximum nesting depth for menu items.
     */
    public static function getMaxDepth(): int
    {
        return 5;
    }

    // -----------------------------------------------------------------------
    // Page metadata
    // -----------------------------------------------------------------------

    public function getTitle(): string
    {
        return 'Edit Menu: '.($this->record?->name ?? '');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successRedirectUrl($this->getResource()::getUrl('index')),

            Action::make('add_menu_item')
                ->label('Add item')
                ->modalHeading('Add Menu Item')
                ->form(fn (): array => $this->getEditFormSchema())
                ->action(function (array $data): void {
                    $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

                    if ($languages->isEmpty()) {
                        $languages = collect([(object) ['code' => 'en', 'name' => 'English', 'is_default' => true]]);
                    }

                    foreach (['name', 'value'] as $field) {
                        $translations = [];

                        foreach ($languages as $lang) {
                            $val = $data["{$field}_{$lang->code}"] ?? null;

                            if ($val !== null && $val !== '') {
                                $translations[$lang->code] = $val;
                            }

                            unset($data["{$field}_{$lang->code}"]);
                        }

                        if ($field === 'value' && ($data['type'] ?? null) !== 'link') {
                            $translations = [];
                        }

                        $data[$field] = $translations;
                    }

                    $data['menu_id'] = $this->record->id;
                    $data['parent_id'] = null;

                    MenuItem::create($data);

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record->id]));
                }),
        ];
    }

    // -----------------------------------------------------------------------
    // Tree data: filter to current Menu's items
    // -----------------------------------------------------------------------

    protected function getTreeQuery(): Builder
    {
        return MenuItem::query()
            ->where('menu_id', $this->record->id);
    }

    protected function getSortedQuery(): Builder
    {
        return $this->getWithRelationQuery()->orderBy('sort');
    }

    /**
     * Root items have parent_id = null (not the package default of -1).
     */
    public function getRootLayerRecords(): Collection
    {
        return collect($this->getRecords() ?? [])
            ->filter(fn (Model $record) => $record->parent_id === null);
    }

    /**
     * Use null as the root-level key so that drag-drop correctly assigns
     * parent_id = null when a node is moved to the root level.
     */
    public function getTreeRootLevelKey(): null|string|int
    {
        return null;
    }

    /**
     * Show the translated name as the tree node label.
     */
    public function getTreeRecordTitle(?Model $record = null): string
    {
        if (! $record) {
            return '';
        }

        return $record->getTranslation('name', app()->getLocale()) ?: '';
    }

    // -----------------------------------------------------------------------
    // Tree node actions (Edit / Delete)
    // -----------------------------------------------------------------------

    /**
     * Tree node actions using the solution-forest/filament-tree action types.
     * Edit opens a translation-aware modal; Delete requires confirmation.
     */
    protected function getTreeActions(): array
    {
        return [
            TreeEditAction::make()
                ->fillForm(function (Model $record): array {
                    $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

                    if ($languages->isEmpty()) {
                        $languages = collect([(object) ['code' => 'en', 'name' => 'English', 'is_default' => true]]);
                    }

                    $data = $record->only(['type', 'value_id', 'subcategories']);

                    foreach (['name', 'value'] as $field) {
                        $translations = $record->getTranslations($field);

                        foreach ($languages as $lang) {
                            $data["{$field}_{$lang->code}"] = $translations[$lang->code] ?? '';
                        }
                    }

                    return $data;
                })
                ->form(fn (): array => $this->getEditFormSchema())
                ->action(function (array $data, Model $record): void {
                    $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

                    if ($languages->isEmpty()) {
                        $languages = collect([(object) ['code' => 'en', 'name' => 'English', 'is_default' => true]]);
                    }

                    foreach (['name', 'value'] as $field) {
                        $translations = [];

                        foreach ($languages as $lang) {
                            $val = $data["{$field}_{$lang->code}"] ?? null;

                            if ($val !== null && $val !== '') {
                                $translations[$lang->code] = $val;
                            }

                            unset($data["{$field}_{$lang->code}"]);
                        }

                        if ($field === 'value' && ($data['type'] ?? null) !== 'link') {
                            $translations = [];
                        }

                        $data[$field] = $translations;
                    }

                    $record->update($data);
                }),

            TreeDeleteAction::make(),
        ];
    }

    /**
     * Form schema for the tree-node edit modal and the "Add item" header action.
     * Mirrors the full EditMenuItemAction form schema.
     */
    protected function getEditFormSchema(): array
    {
        try {
            $languages = Language::orderByDesc('is_default')->orderBy('name')->get();
        } catch (\Throwable) {
            $languages = collect([]);
        }

        if ($languages->isEmpty()) {
            $languages = collect([(object) ['code' => 'en', 'name' => 'English', 'is_default' => true]]);
        }

        $nameTabs = $languages->map(
            fn ($lang) => Tabs\Tab::make($lang->name)
                ->schema([
                    Forms\Components\TextInput::make("name_{$lang->code}")
                        ->label("Name ({$lang->code})")
                        ->maxLength(255)
                        ->required($lang->is_default),
                ])
        )->all();

        $valueTabs = $languages->map(
            fn ($lang) => Tabs\Tab::make($lang->name)
                ->schema([
                    Forms\Components\TextInput::make("value_{$lang->code}")
                        ->label("Value ({$lang->code})")
                        ->maxLength(2048),
                ])
        )->all();

        return [
            Tabs::make('Name Translations')
                ->tabs($nameTabs)
                ->columnSpanFull(),

            Forms\Components\Select::make('type')
                ->label('Type')
                ->options([
                    'link' => 'Link',
                    'page_id' => 'Page',
                    'product_id' => 'Product',
                    'category_id' => 'Category',
                    'blog' => 'Blog',
                    'blog_category_id' => 'Blog Category',
                    'blog_article_id' => 'Blog Article',
                    'manufacturer_id' => 'Manufacturer',
                ])
                ->required()
                ->live(),

            Tabs::make('Value Translations')
                ->tabs($valueTabs)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('type') === 'link'),

            Forms\Components\Select::make('value_id')
                ->label('Target')
                ->options(fn (Get $get): array => match ($get('type')) {
                    'page_id' => Page::select(['id', 'title'])->get()
                        ->mapWithKeys(fn (Page $page): array => [
                            $page->id => $page->getTranslation('title', app()->getLocale()) ?: ($page->title ?? ''),
                        ])
                        ->toArray(),
                    'product_id' => Product::select(['id', 'name'])->get()
                        ->mapWithKeys(fn (Product $product): array => [
                            $product->id => $product->getTranslation('name', app()->getLocale()) ?: ($product->name ?? ''),
                        ])
                        ->toArray(),
                    'category_id' => Category::select(['id', 'name'])->get()
                        ->mapWithKeys(fn (Category $category): array => [
                            $category->id => $category->getTranslation('name', app()->getLocale()) ?: ($category->name ?? ''),
                        ])
                        ->toArray(),
                    'blog_category_id' => BlogCategory::select(['id', 'title'])->get()
                        ->mapWithKeys(fn (BlogCategory $blogCategory): array => [
                            $blogCategory->id => $blogCategory->getTranslation('title', app()->getLocale()) ?: ($blogCategory->title ?? ''),
                        ])
                        ->toArray(),
                    'blog_article_id' => BlogArticle::select(['id', 'title'])->get()
                        ->mapWithKeys(fn (BlogArticle $blogArticle): array => [
                            $blogArticle->id => $blogArticle->getTranslation('title', app()->getLocale()) ?: ($blogArticle->title ?? ''),
                        ])
                        ->toArray(),
                    'manufacturer_id' => Manufacturer::select(['id', 'name'])->get()
                        ->mapWithKeys(fn (Manufacturer $manufacturer): array => [
                            $manufacturer->id => $manufacturer->getTranslation('name', app()->getLocale()) ?: ($manufacturer->name ?? ''),
                        ])
                        ->toArray(),
                    default => [],
                })
                ->searchable()
                ->required(fn (Get $get): bool => $get('type') !== null && $get('type') !== 'link' && $get('type') !== 'blog')
                ->visible(fn (Get $get): bool => $get('type') !== null && $get('type') !== 'link' && $get('type') !== 'blog'),

            Forms\Components\Select::make('subcategories')
                ->label('Show subcategories up until level:')
                ->options(array_combine(range(0, 10), range(0, 10)))
                ->visible(fn (Get $get): bool => $get('type') === 'category_id'),
        ];
    }

    // -----------------------------------------------------------------------
    // Compatibility shims
    // -----------------------------------------------------------------------

    /**
     * EditRecord does not include InteractsWithTable, so mountedTableActionHasForm
     * is not available. The filament-tree package references it in two places:
     *   - HasActions::mountedTreeActionShouldOpenModal (PHP callable syntax)
     *   - actions/modal/actions/button-action.blade.php (Blade call)
     * Delegating to mountedTreeActionHasForm() satisfies both callers.
     */
    public function mountedTableActionHasForm(): bool
    {
        return $this->mountedTreeActionHasForm();
    }

    /**
     * Override to use mountedTreeActionHasForm instead of mountedTableActionHasForm
     * (which is not available on EditRecord pages).
     */
    public function mountedTreeActionShouldOpenModal(): bool
    {
        return $this->getMountedTreeAction()->shouldOpenModal(
            checkForFormUsing: $this->mountedTreeActionHasForm(...),
        );
    }
}
