<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Order\Models\OrderTotal;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('updateStatus')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'shipped' => 'Shipped',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required(),
                    Textarea::make('comments')
                        ->label('Comments')
                        ->placeholder('Enter any comments about this status change (optional)')
                        ->nullable(),
                ])
                ->fillForm(fn ($record) => ['status' => $record->status])
                ->action(function ($record, array $data): void {
                    $record->update(['status' => $data['status']]);
                    $record->createHistoryEntry($data['status'], $data['comments'] ?: null);

                    Notification::make()
                        ->title('Status updated')
                        ->success()
                        ->send();
                })
                ->after(fn () => $this->refreshFormData(['status'])),
        ];
    }

    protected function resolveRecord(int|string $key): Order
    {
        return parent::resolveRecord($key)->load([
            'history' => fn ($q) => $q->latest('created_at'),
            'items.product',
            'totals',
            'billingAddress.country',
            'billingAddress.zone',
            'billingAddress.city',
            'shippingAddress.country',
            'shippingAddress.zone',
            'shippingAddress.city',
        ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Order Summary')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Order ID'),

                        TextEntry::make('firstname')
                            ->label('Customer Name')
                            ->formatStateUsing(
                                fn ($state, Order $record): string => trim(($record->firstname ?? '').' '.($record->lastname ?? '')) ?: 'N/A'
                            )
                            ->url(
                                fn ($state, Order $record): ?string => $record->customer_id
                                    ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                                    : null
                            ),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),

                        TextEntry::make('firstname')
                            ->label('First Name'),

                        TextEntry::make('lastname')
                            ->label('Last Name'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('telephone')
                            ->label('Telephone'),

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->formatStateUsing(
                                fn ($state, $record): string => $record->currency_code.' '.number_format((float) $state, 2)
                            ),
                    ])
                    ->columns(2),

                Section::make('Contents')
                    ->schema([
                        RepeatableEntry::make('contents')
                            ->label('')
                            ->schema([
                                TextEntry::make('product_name_snapshot')
                                    ->label('Product')
                                    ->url(fn ($record) => $record instanceof OrderItem && $record->product_id && $record->product
                                        ? ProductResource::getUrl('edit', ['record' => $record->product_id])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => $record instanceof OrderItem),

                                TextEntry::make('variant_sku_snapshot')
                                    ->label('SKU')
                                    ->visible(fn ($record) => $record instanceof OrderItem),

                                TextEntry::make('unit_price_snapshot')
                                    ->label('Unit Price')
                                    ->numeric(decimalPlaces: 2)
                                    ->visible(fn ($record) => $record instanceof OrderItem),

                                TextEntry::make('quantity')
                                    ->label('Qty')
                                    ->visible(fn ($record) => $record instanceof OrderItem),

                                TextEntry::make('line_total_snapshot')
                                    ->label('Line Total')
                                    ->numeric(decimalPlaces: 2)
                                    ->visible(fn ($record) => $record instanceof OrderItem),

                                TextEntry::make('name')
                                    ->label('Total Name')
                                    ->visible(fn ($record) => $record instanceof OrderTotal),

                                TextEntry::make('code')
                                    ->label('Code')
                                    ->visible(fn ($record) => $record instanceof OrderTotal),

                                TextEntry::make('value')
                                    ->label('Value')
                                    ->numeric(decimalPlaces: 2)
                                    ->visible(fn ($record) => $record instanceof OrderTotal),
                            ])
                            ->columns(5),
                    ]),

                Section::make('Billing Address')
                    ->schema([
                        RepeatableEntry::make('billingAddress')
                            ->label('')
                            ->schema([
                                TextEntry::make('firstname')
                                    ->label('First Name'),

                                TextEntry::make('lastname')
                                    ->label('Last Name'),

                                TextEntry::make('company')
                                    ->label('Company')
                                    ->placeholder('—'),

                                TextEntry::make('country.name')
                                    ->label('Country'),

                                TextEntry::make('zone.name')
                                    ->label('Zone')
                                    ->placeholder('—'),

                                TextEntry::make('city.name')
                                    ->label('City')
                                    ->placeholder('—'),

                                TextEntry::make('address_line_1')
                                    ->label('Address Line 1'),

                                TextEntry::make('address_line_2')
                                    ->label('Address Line 2')
                                    ->placeholder('—'),

                                TextEntry::make('postcode')
                                    ->label('Postcode'),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Shipping Address')
                    ->schema([
                        RepeatableEntry::make('shippingAddress')
                            ->label('')
                            ->schema([
                                TextEntry::make('firstname')
                                    ->label('First Name'),

                                TextEntry::make('lastname')
                                    ->label('Last Name'),

                                TextEntry::make('company')
                                    ->label('Company')
                                    ->placeholder('—'),

                                TextEntry::make('country.name')
                                    ->label('Country'),

                                TextEntry::make('zone.name')
                                    ->label('Zone')
                                    ->placeholder('—'),

                                TextEntry::make('city.name')
                                    ->label('City')
                                    ->placeholder('—'),

                                TextEntry::make('address_line_1')
                                    ->label('Address Line 1'),

                                TextEntry::make('address_line_2')
                                    ->label('Address Line 2')
                                    ->placeholder('—'),

                                TextEntry::make('postcode')
                                    ->label('Postcode'),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Order History')
                    ->schema([
                        RepeatableEntry::make('history')
                            ->label('')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                                TextEntry::make('comments')
                                    ->label('Comments')
                                    ->placeholder('—'),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
