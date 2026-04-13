<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Domains\Order\Models\Order;
use App\Filament\Resources\OrderResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable()
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record->id]))
                    ->openUrlInNewTab(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(
                        fn ($state, Order $record): string => $record->currency_code.' '.number_format((float) $state, 2)
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(10);
    }
}
