<?php

namespace App\Filament\Resources;

use App\Domains\Language\Models\Language;
use App\Domains\Page\Models\Page;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages\ListPages;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

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
                TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn (Page $record): string => $record->getTranslation('title', app()->getLocale(), false) ?: ($record->title ?? ''))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
