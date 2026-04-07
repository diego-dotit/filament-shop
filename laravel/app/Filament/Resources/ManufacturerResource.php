<?php

namespace App\Filament\Resources;

use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\CreateManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\EditManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\ListManufacturers;
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

class ManufacturerResource extends Resource
{
    protected static ?string $model = Manufacturer::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Manufacturers';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        $translationTabs = $languages->map(function (Language $language): Tabs\Tab {
            return Tabs\Tab::make($language->name)
                ->schema([
                    Forms\Components\TextInput::make("name_{$language->code}")
                        ->label('Name')
                        ->required($language->is_default)
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state) use ($language): void {
                            $oldStr      = is_string($old) ? $old : '';
                            $stateStr    = is_string($state) ? $state : '';
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
                        ->helperText('Auto-generated from name. You may override manually.'),

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

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id')
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
            'index'  => ListManufacturers::route('/'),
            'create' => CreateManufacturer::route('/create'),
            'edit'   => EditManufacturer::route('/{record}/edit'),
        ];
    }
}
