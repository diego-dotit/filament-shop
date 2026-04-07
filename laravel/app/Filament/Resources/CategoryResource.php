<?php

namespace App\Filament\Resources;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Rules\NoCircularCategoryReference;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $languages = Language::orderBy('is_default', 'desc')->orderBy('name')->get();

        // Fallback: if no languages in DB, use English by default
        if ($languages->isEmpty()) {
            $languages = collect([
                (object) ['code' => 'en', 'name' => 'English'],
            ]);
        }

        $record = $form->getRecord();
        $categoryId = $record?->id;

        $defaultLocale = Language::where('is_default', true)->first()?->code ?? 'en';
        $existingSlugId = $record?->slugs()->where('locale', $defaultLocale)->value('id');

        return $form->schema([
            Forms\Components\Section::make('Category Information')
                ->schema([
                    Forms\Components\Tabs::make('Name Translations')
                        ->tabs(
                            $languages->map(fn ($lang) => Forms\Components\Tabs\Tab::make($lang->name)
                                ->schema([
                                    Forms\Components\TextInput::make("name.{$lang->code}")
                                        ->label("Name ({$lang->code})")
                                        ->required($lang->code === $languages->first()->code)
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state) use ($lang): void {
                                            // Auto-generate slug for each locale from its own name field
                                            $currentSlug = $get("slug_{$lang->code}") ?? '';
                                            $oldStr = is_string($old) ? $old : '';
                                            $stateStr = is_string($state) ? $state : '';
                                            if ($currentSlug === '' || $currentSlug === Str::slug($oldStr)) {
                                                $set("slug_{$lang->code}", Str::slug($stateStr));
                                            }
                                        }),

                                    Forms\Components\TextInput::make("slug_{$lang->code}")
                                        ->label("Slug ({$lang->code})")
                                        ->maxLength(255)
                                        ->alphaDash()
                                        ->unique(
                                            table: 'slugs',
                                            column: 'slug',
                                            modifyRuleUsing: function (Unique $rule, ?Model $record) use ($lang): Unique {
                                                if ($record !== null) {
                                                    // Allow the current record's own slug for this locale
                                                    $rule->where(function ($query) use ($record, $lang): void {
                                                        $query->where('sluggable_type', '!=', Category::class)
                                                            ->orWhere('sluggable_id', '!=', $record->id)
                                                            ->orWhere('locale', '!=', $lang->code);
                                                    });
                                                }

                                                return $rule;
                                            },
                                        )
                                        ->rules(function (Get $get) use ($lang, $languages, $defaultLocale, $categoryId): array {
                                            $rules = [];

                                            // Default locale slug must also be unique in categories.slug column
                                            if ($lang->code === $defaultLocale) {
                                                $rules[] = Rule::unique('categories', 'slug')->ignore($categoryId);
                                            }

                                            // Slug must not duplicate another locale's slug in the same submission
                                            $otherSlugs = $languages
                                                ->filter(fn ($l) => $l->code !== $lang->code)
                                                ->map(fn ($l) => $get("slug_{$l->code}") ?? '')
                                                ->filter(fn ($s) => $s !== '')
                                                ->values()
                                                ->toArray();

                                            if (! empty($otherSlugs)) {
                                                $rules[] = static function (string $attribute, mixed $value, \Closure $fail) use ($otherSlugs): void {
                                                    if (! empty($value) && in_array($value, $otherSlugs, true)) {
                                                        $fail('Slug must be unique across all languages.');
                                                    }
                                                };
                                            }

                                            return $rules;
                                        })
                                        ->helperText('Auto-generated from name. You may override manually.'),

                                    RichEditor::make("description.{$lang->code}")
                                        ->label("Description ({$lang->code})")
                                        ->nullable()
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make("meta_title.{$lang->code}")
                                        ->label("Meta Title ({$lang->code})")
                                        ->maxLength(255)
                                        ->nullable()
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make("meta_description.{$lang->code}")
                                        ->label("Meta Description ({$lang->code})")
                                        ->maxLength(255)
                                        ->nullable()
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make("meta_keywords.{$lang->code}")
                                        ->label("Meta Keywords ({$lang->code})")
                                        ->maxLength(255)
                                        ->nullable()
                                        ->columnSpanFull(),
                                ])
                            )->all()
                        )
                        ->columnSpanFull(),

                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Category')
                        ->relationship('parent', 'name')
                        ->getOptionLabelFromRecordUsing(
                            fn (Category $record): string => $record->getTranslation('name', app()->getLocale()) ?: ($record->name ?? '')
                        )
                        ->searchable()
                        ->nullable()
                        ->placeholder('— None (root category) —')
                        ->rules([
                            new NoCircularCategoryReference($categoryId),
                        ]),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(2),

            Forms\Components\Section::make('Media')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('thumbnail')
                        ->label('Thumbnail Image')
                        ->collection('thumbnail')
                        ->image()
                        ->imageEditor()
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
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->getStateUsing(fn (Category $record): ?string => $record->parent?->getTranslation('name', app()->getLocale()))
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
