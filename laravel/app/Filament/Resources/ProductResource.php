<?php

namespace App\Filament\Resources;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\Product\Pages\CreateProduct;
use App\Filament\Resources\Product\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $languages   = Language::orderByDesc('is_default')->orderBy('name')->get();
        $defaultCode = $languages->firstWhere('is_default', true)?->code ?? config('app.locale', 'en');

        $translationTabs = $languages->map(function (Language $language) use ($defaultCode): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->schema([
                    Forms\Components\TextInput::make("name_{$language->code}")
                        ->label('Name')
                        ->required($language->is_default)
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state) use ($language, $defaultCode): void {
                            $oldStr      = is_string($old) ? $old : '';
                            $stateStr    = is_string($state) ? $state : '';
                            $currentSlug = $get("slug_{$language->code}") ?? '';
                            if ($currentSlug === '' || $currentSlug === Str::slug($oldStr)) {
                                $set("slug_{$language->code}", Str::slug($stateStr));
                            }
                            if ($language->code === $defaultCode) {
                                $globalSlug = $get('slug') ?? '';
                                if ($globalSlug === '' || $globalSlug === Str::slug($oldStr)) {
                                    $set('slug', Str::slug($stateStr));
                                }
                            }
                        }),

                    Forms\Components\TextInput::make("slug_{$language->code}")
                        ->label('Slug')
                        ->maxLength(255)
                        ->alphaDash()
                        ->unique(
                            table: 'slugs',
                            column: 'slug',
                            modifyRuleUsing: fn (Unique $rule, ?Model $record): Unique => $rule->ignore(
                                $record?->getSlugForLocale($language->code)?->id
                            ),
                        )
                        ->helperText('Auto-generated from name. You may override manually.'),

                    Forms\Components\Textarea::make("description_{$language->code}")
                        ->label('Description')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                ]);
        })->toArray();

        return $form->schema([
            Forms\Components\Section::make('Translations')
                ->schema([
                    Tabs::make('Translations')
                        ->tabs($translationTabs)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Product Information')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(2),

            Forms\Components\Section::make('Categories & Manufacturers')
                ->schema([
                    Forms\Components\Select::make('categories')
                        ->label('Categories')
                        ->multiple()
                        ->relationship('categories', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getTranslation('name', app()->getLocale()) ?: $record->slug)
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('manufacturers')
                        ->label('Manufacturers')
                        ->multiple()
                        ->relationship('manufacturers', 'name')
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Variants')
                ->description('Each product must have at least one variant.')
                ->schema([
                    Forms\Components\Repeater::make('variants')
                        ->relationship('variants')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('sku')
                                ->label('SKU')
                                ->required()
                                ->maxLength(100)
                                ->unique(
                                    table: 'product_variants',
                                    column: 'sku',
                                    ignoreRecord: true,
                                )
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('regular_price')
                                ->label('Regular Price')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$'),

                            Forms\Components\TextInput::make('special_price')
                                ->label('Special Price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->nullable(),

                            Forms\Components\TextInput::make('stock_quantity')
                                ->label('Stock Quantity')
                                ->required()
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->default(0),

                            Forms\Components\TextInput::make('weight')
                                ->label('Weight (kg)')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->step(0.001),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columns(4)
                        ->addActionLabel('Add Variant')
                        ->reorderable(false)
                        ->collapsible()
                        ->defaultItems(0),
                ]),

            Forms\Components\Section::make('Featured Image')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('thumbnail')
                        ->label('Featured Image')
                        ->collection('thumbnail')
                        ->image()
                        ->imageEditor()
                        ->nullable(),
                ]),

            Forms\Components\Section::make('Image Gallery')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('images')
                        ->label('Gallery Images')
                        ->collection('images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->maxFiles(10)
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhere('name', 'like', "%{$search}%");
                    })
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultPaginationPageOption(10);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
        ];
    }
}
