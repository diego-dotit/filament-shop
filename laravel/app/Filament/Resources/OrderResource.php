<?php

namespace App\Filament\Resources;

use App\Domains\Customer\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'id')
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->first_name . ' ' . $record->last_name)
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending'    => 'Pending',
                                'processing' => 'Processing',
                                'shipped'    => 'Shipped',
                                'completed'  => 'Completed',
                                'cancelled'  => 'Cancelled',
                            ])
                            ->required(),

                        Forms\Components\Select::make('currency_code')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                                'CAD' => 'CAD',
                                'AUD' => 'AUD',
                                'JPY' => 'JPY',
                                'CHF' => 'CHF',
                                'CNY' => 'CNY',
                                'INR' => 'INR',
                                'MXN' => 'MXN',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->numeric()
                            ->decimalPlaces(6)
                            ->required(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->decimalPlaces(2)
                            ->disabled()
                            ->hidden(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Billing Address')
                    ->schema([
                        Forms\Components\Repeater::make('billing_addresses')
                            ->schema([
                                Forms\Components\TextInput::make('country')
                                    ->label('Country')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('address_line_1')
                                    ->label('Address Line 1')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('address_line_2')
                                    ->label('Address Line 2')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('postcode')
                                    ->label('Postcode')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Hidden::make('type')
                                    ->default('billing'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Billing Address')
                            ->collapsible()
                            ->defaultItems(1),
                    ]),

                Forms\Components\Section::make('Shipping Address')
                    ->schema([
                        Forms\Components\Repeater::make('shipping_addresses')
                            ->schema([
                                Forms\Components\TextInput::make('country')
                                    ->label('Country')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('address_line_1')
                                    ->label('Address Line 1')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('address_line_2')
                                    ->label('Address Line 2')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('postcode')
                                    ->label('Postcode')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Hidden::make('type')
                                    ->default('shipping'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Shipping Address')
                            ->collapsible()
                            ->defaultItems(1),
                    ]),

                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn (): array => Product::query()->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('product_variant_id')
                                    ->label('Variant (SKU)')
                                    ->options(fn (Get $get): array => $get('product_id')
                                        ? ProductVariant::where('product_id', $get('product_id'))->pluck('sku', 'id')->toArray()
                                        : []
                                    )
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required(),

                                Forms\Components\TextInput::make('unit_price_snapshot')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->decimalPlaces(2)
                                    ->required(),
                            ])
                            ->columns(4)
                            ->addActionLabel('Add Item')
                            ->collapsible()
                            ->reorderable(false)
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOrders::route('/'),
            'view'   => \App\Filament\Resources\OrderResource\Pages\ViewOrder::route('/{record}'),
            'create' => \App\Filament\Resources\OrderResource\Pages\CreateOrder::route('/create'),
            'edit'   => \App\Filament\Resources\OrderResource\Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
