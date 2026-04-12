<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
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
                ])
                ->fillForm(fn ($record) => ['status' => $record->status])
                ->action(function ($record, array $data): void {
                    $record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Status updated')
                        ->success()
                        ->send();
                })
                ->after(fn () => $this->refreshFormData(['status'])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Order Summary')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Order ID'),

                        TextEntry::make('customer.first_name')
                            ->label('Customer Name'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->formatStateUsing(
                                fn ($state, $record): string => $record->currency_code.' '.number_format((float) $state, 2)
                            ),
                    ])
                    ->columns(2),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('product_name_snapshot')
                                    ->label('Product'),

                                TextEntry::make('variant_sku_snapshot')
                                    ->label('SKU'),

                                TextEntry::make('unit_price_snapshot')
                                    ->label('Unit Price')
                                    ->numeric(decimalPlaces: 2),

                                TextEntry::make('quantity')
                                    ->label('Qty'),

                                TextEntry::make('line_total_snapshot')
                                    ->label('Line Total')
                                    ->numeric(decimalPlaces: 2),
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
            ]);
    }
}
