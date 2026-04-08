<?php

namespace App\Filament\Resources;

use App\Domains\Blog\Models\BlogArticle;
use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Filament\Resources\BlogArticleResource\Pages\CreateBlogArticle;
use App\Filament\Resources\BlogArticleResource\Pages\EditBlogArticle;
use App\Filament\Resources\BlogArticleResource\Pages\ListBlogArticles;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class BlogArticleResource extends Resource
{
    protected static ?string $model = BlogArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        $translationTabs = $languages->map(function (Language $language): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->schema([
                    Forms\Components\TextInput::make("title_{$language->code}")
                        ->label('Title')
                        ->required($language->is_default)
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state) use ($language): void {
                            $oldStr = is_string($old) ? $old : '';
                            $stateStr = is_string($state) ? $state : '';
                            $currentSlug = $get("slug_{$language->code}") ?? '';
                            if ($currentSlug === '' || $currentSlug === Str::slug($oldStr)) {
                                $set("slug_{$language->code}", Str::slug($stateStr));
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
                        ->helperText('Auto-generated from title. You may override manually.'),

                    RichEditor::make("description_{$language->code}")
                        ->label('Description')
                        ->nullable()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make("meta_title_{$language->code}")
                        ->label('Meta Title')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\TextInput::make("meta_description_{$language->code}")
                        ->label('Meta Description')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\TextInput::make("meta_keywords_{$language->code}")
                        ->label('Meta Keywords')
                        ->maxLength(255)
                        ->nullable(),
                ]);
        })->toArray();

        return $form->schema([
            Forms\Components\Section::make('Translations')
                ->schema([
                    Tabs::make('Translations')
                        ->tabs($translationTabs)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Article Details')
                ->schema([
                    Forms\Components\TextInput::make('author')
                        ->label('Author')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\DatePicker::make('post_date')
                        ->label('Post Date')
                        ->nullable(),

                    Forms\Components\Toggle::make('status')
                        ->label('Active')
                        ->afterStateHydrated(fn (Forms\Components\Toggle $component, mixed $state): mixed => $component->state($state === 'active'))
                        ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Select::make('blogCategories')
                        ->label('Categories')
                        ->multiple()
                        ->relationship('blogCategories', 'title')
                        ->getOptionLabelFromRecordUsing(fn (BlogCategory $record): string => $record->getTranslation('title', app()->getLocale()) ?: (string) $record->id)
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Thumbnail')
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn (BlogArticle $record): string => $record->getTranslation('title', app()->getLocale(), false) ?: '')
                    ->searchable(query: fn (\Illuminate\Database\Eloquent\Builder $query, string $search): \Illuminate\Database\Eloquent\Builder => $query->where('title', 'like', "%{$search}%"))
                    ->sortable(),

                TextColumn::make('author')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('post_date')
                    ->label('Post Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('post_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => ListBlogArticles::route('/'),
            'create' => CreateBlogArticle::route('/create'),
            'edit'   => EditBlogArticle::route('/{record}/edit'),
        ];
    }
}
