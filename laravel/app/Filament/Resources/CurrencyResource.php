<?php

namespace App\Filament\Resources;

use App\Domains\Currency\Models\Currency;
use App\Domains\Order\Models\Order;
use App\Filament\Resources\Currency\Pages\CreateCurrency;
use App\Filament\Resources\Currency\Pages\EditCurrency;
use App\Filament\Resources\Currency\Pages\ListCurrencies;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Currencies';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(3)
                    ->unique('currencies', 'code', ignoreRecord: true)
                    ->placeholder('USD'),

                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('symbol')
                    ->label('Symbol')
                    ->required()
                    ->maxLength(10),

                Forms\Components\TextInput::make('exchange_rate')
                    ->label('Exchange Rate')
                    ->numeric()
                    ->default(1.0)
                    ->required()
                    ->minValue(0)
                    ->disabled(fn (Forms\Get $get): bool => (bool) $get('is_base')),

                Forms\Components\Toggle::make('is_base')
                    ->label('Base Currency')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, bool $state): void {
                        if ($state) {
                            $set('exchange_rate', 1.0);
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbol'),

                Tables\Columns\TextColumn::make('exchange_rate')
                    ->label('Exchange Rate')
                    ->numeric(6),

                Tables\Columns\IconColumn::make('is_base')
                    ->label('Base')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('set_as_base')
                    ->label('Set as Base')
                    ->icon('heroicon-o-star')
                    ->requiresConfirmation()
                    ->action(function (Currency $record): void {
                        DB::transaction(function () use ($record): void {
                            Currency::query()->update(['is_base' => false]);
                            $record->update([
                                'is_base'       => true,
                                'exchange_rate' => '1.000000',
                            ]);
                        });

                        Notification::make()
                            ->title("{$record->code} set as base currency")
                            ->success()
                            ->send();
                    })
                    ->hidden(fn (Currency $record): bool => (bool) $record->is_base),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->before(function (Currency $record, Tables\Actions\DeleteAction $action): void {
                        $hasOrders = Order::where('currency_code', $record->code)->exists();
                        if ($hasOrders) {
                            Notification::make()
                                ->title("Cannot delete {$record->code}: orders reference this currency")
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->defaultSort('id')
            ->paginated([20, 50, 100])
            ->defaultPaginationPageOption(20);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit'   => EditCurrency::route('/{record}/edit'),
        ];
    }
}
