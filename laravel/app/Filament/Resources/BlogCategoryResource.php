<?php

namespace App\Filament\Resources;

use App\Domains\Blog\Models\BlogCategory;
use App\Domains\Language\Models\Language;
use App\Filament\Resources\BlogCategoryResource\Pages\CreateBlogCategory;
use App\Filament\Resources\BlogCategoryResource\Pages\EditBlogCategory;
use App\Filament\Resources\BlogCategoryResource\Pages\ListBlogCategories;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Blog Categories';

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
                        ->maxLength(255),

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

            Forms\Components\Section::make('Thumbnail')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('thumbnail')
                        ->label('Thumbnail Image')
                        ->collection('thumbnail')
                        ->image()
                        ->imageEditor()
                        ->nullable(),
                ]),

            Forms\Components\Section::make('Settings')
                ->schema([
                    Forms\Components\Toggle::make('status')
                        ->label('Active')
                        ->afterStateHydrated(fn (Forms\Components\Toggle $component, mixed $state): mixed => $component->state($state === 'active'))
                        ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->collection('thumbnail')
                    ->circular(false),

                TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn (BlogCategory $record): string => $record->getTranslation('title', app()->getLocale(), false) ?: ($record->title ?? ''))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index'  => ListBlogCategories::route('/'),
            'create' => CreateBlogCategory::route('/create'),
            'edit'   => EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
