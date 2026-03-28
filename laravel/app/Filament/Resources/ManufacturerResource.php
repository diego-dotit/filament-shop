<?php

namespace App\Filament\Resources;

use App\Domains\Manufacturer\Models\Manufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\CreateManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\EditManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\ListManufacturers;
use Filament\Forms;
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
        return $form->schema([
            Forms\Components\Section::make('Manufacturer Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $old, mixed $state): void {
                            $oldStr      = is_string($old) ? $old : '';
                            $stateStr    = is_string($state) ? $state : '';
                            $currentSlug = $get('slug') ?? '';
                            if ($currentSlug === '' || $currentSlug === Str::slug($oldStr)) {
                                $set('slug', Str::slug($stateStr));
                            }
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(
                            table: 'slugs',
                            column: 'slug',
                            modifyRuleUsing: function (Unique $rule, ?Model $record): Unique {
                                if ($record !== null) {
                                    // Ignore only the current manufacturer's own slug entry.
                                    // This preserves cross-resource uniqueness (a Product's slug
                                    // still blocks a Manufacturer from using the same value).
                                    $rule->where(function ($query) use ($record): void {
                                        $query->where('sluggable_type', '!=', Manufacturer::class)
                                            ->orWhere('sluggable_id', '!=', $record->id);
                                    });
                                }

                                return $rule;
                            },
                        )
                        ->alphaDash()
                        ->helperText('Auto-generated from name. Must be unique.'),
                ])
                ->columns(2),
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
