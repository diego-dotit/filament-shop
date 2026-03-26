<?php

namespace App\Filament\Resources;

use App\Domains\Order\Models\Order;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(
                        fn ($state, Order $record): string =>
                            $record->currency_code . ' ' . number_format((float) $state, 2)
                    )
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view'  => \App\Filament\Resources\OrderResource\Pages\ViewOrder::route('/{record}'),
        ];
    }
}
