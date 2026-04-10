<?php

namespace App\Filament\Resources;

use App\Domains\Customer\Models\Customer;
use App\Domains\Localisation\Models\City;
use App\Domains\Localisation\Models\Country;
use App\Domains\Localisation\Models\Zone;
use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
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
                        fn ($state, Order $record): string => $record->currency_code.' '.number_format((float) $state, 2)
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
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
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
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->first_name.' '.$record->last_name)
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
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
                                Forms\Components\Toggle::make('shipping')
                                    ->default(false)
                                    ->live()
                                    ->inline(false),

                                Forms\Components\TextInput::make('firstname')
                                    ->label('First Name')
                                    ->visible(fn (Get $get) => $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('lastname')
                                    ->label('Last Name')
                                    ->visible(fn (Get $get) => $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('business')
                                    ->default(false)
                                    ->live()
                                    ->inline(false)
                                    ->visible(fn (Get $get) => $get('shipping') == 0),

                                Forms\Components\TextInput::make('company')
                                    ->label('Company')
                                    ->visible(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('company_id')
                                    ->label('Company ID')
                                    ->visible(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('tax_id')
                                    ->label('Tax ID')
                                    ->visible(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\Select::make('country_id')
                                    ->label('Country')
                                    ->options(fn (): array => Country::pluck('name', 'id')->toArray())
                                    ->live()
                                    ->searchable(),

                                Forms\Components\Select::make('zone_id')
                                    ->label('Zone')
                                    ->options(fn (Get $get): array => $get('country_id') ? Zone::where('country_id', $get('country_id'))->pluck('name', 'id')->toArray() : [])
                                    ->live()
                                    ->nullable()
                                    ->searchable(),

                                Forms\Components\Select::make('city_id')
                                    ->label('City')
                                    ->options(fn (Get $get): array => $get('zone_id') ? City::where('zone_id', $get('zone_id'))->pluck('name', 'id')->toArray() : [])
                                    ->nullable()
                                    ->searchable(),

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
                                Forms\Components\Toggle::make('shipping')
                                    ->default(true)
                                    ->live()
                                    ->inline(false),

                                Forms\Components\TextInput::make('firstname')
                                    ->label('First Name')
                                    ->visible(fn (Get $get) => $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('lastname')
                                    ->label('Last Name')
                                    ->visible(fn (Get $get) => $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('business')
                                    ->default(false)
                                    ->live()
                                    ->inline(false)
                                    ->visible(fn (Get $get) => $get('shipping') == 0),

                                Forms\Components\TextInput::make('company')
                                    ->label('Company')
                                    ->visible(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('company_id')
                                    ->label('Company ID')
                                    ->visible(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('tax_id')
                                    ->label('Tax ID')
                                    ->visible(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->required(fn (Get $get) => $get('business') && $get('shipping') == 0)
                                    ->maxLength(255),

                                Forms\Components\Select::make('country_id')
                                    ->label('Country')
                                    ->options(fn (): array => Country::pluck('name', 'id')->toArray())
                                    ->live()
                                    ->searchable(),

                                Forms\Components\Select::make('zone_id')
                                    ->label('Zone')
                                    ->options(fn (Get $get): array => $get('country_id') ? Zone::where('country_id', $get('country_id'))->pluck('name', 'id')->toArray() : [])
                                    ->live()
                                    ->nullable()
                                    ->searchable(),

                                Forms\Components\Select::make('city_id')
                                    ->label('City')
                                    ->options(fn (Get $get): array => $get('zone_id') ? City::where('zone_id', $get('zone_id'))->pluck('name', 'id')->toArray() : [])
                                    ->nullable()
                                    ->searchable(),

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
                                    ->options(fn (): array => Product::query()->get()->mapWithKeys(
                                        fn (Product $product): array => [
                                            $product->id => $product->getTranslation('name', app()->getLocale()) ?: ($product->name ?: ''),
                                        ]
                                    )->toArray())
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
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
