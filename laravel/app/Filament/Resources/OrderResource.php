<?php

namespace App\Filament\Resources;

use App\Domains\Currency\Models\Currency;
use App\Domains\Customer\Models\Customer;
use App\Domains\Language\Models\Language;
use App\Domains\Localisation\Models\City;
use App\Domains\Localisation\Models\Country;
use App\Domains\Localisation\Models\Zone;
use App\Domains\Order\Models\Order;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Rules\ReservedOrderTotalCode;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state === null) {
                                    return;
                                }
                                $customer = Customer::find($state);
                                if ($customer === null) {
                                    return;
                                }
                                $set('firstname', $customer->first_name);
                                $set('lastname', $customer->last_name);
                                $set('email', $customer->email);
                                $set('telephone', $customer->phone);
                            }),

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
                            ->options(fn (): array => Currency::pluck('name', 'code')->toArray())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $currency = Currency::where('code', $state)->first();
                                $set('exchange_rate', $currency?->exchange_rate ?? '1.0000000');
                            })
                            ->required(),

                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->numeric()
                            ->step('0.000001')
                            ->default('1.0000000')
                            ->required(),

                        Forms\Components\Select::make('language_code')
                            ->label('Language')
                            ->options(fn (): array => Language::pluck('name', 'code')->toArray())
                            ->searchable(),

                        Forms\Components\TextInput::make('firstname')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('lastname')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('telephone')
                            ->label('Telephone')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->step('0.01')
                            ->disabled()
                            ->hidden(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Billing Address')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('billing_firstname')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('billing_lastname')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('billing_business')
                            ->default(false)
                            ->live()
                            ->inline(false),

                        Forms\Components\TextInput::make('billing_company')
                            ->label('Company')
                            ->visible(fn (Get $get) => (bool) $get('billing_business'))
                            ->required(fn (Get $get) => (bool) $get('billing_business'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('billing_company_id')
                            ->label('Company ID')
                            ->visible(fn (Get $get) => (bool) $get('billing_business'))
                            ->required(fn (Get $get) => (bool) $get('billing_business'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('billing_tax_id')
                            ->label('Tax ID')
                            ->visible(fn (Get $get) => (bool) $get('billing_business'))
                            ->required(fn (Get $get) => (bool) $get('billing_business'))
                            ->maxLength(255),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('billing_country_id')
                                    ->label('Country')
                                    ->options(fn (): array => Country::pluck('name', 'id')->toArray())
                                    ->live()
                                    ->searchable(),

                                Forms\Components\Select::make('billing_zone_id')
                                    ->label('Zone')
                                    ->options(fn (Get $get): array => $get('billing_country_id') ? Zone::where('country_id', $get('billing_country_id'))->pluck('name', 'id')->toArray() : [])
                                    ->live()
                                    ->nullable()
                                    ->searchable(),

                                Forms\Components\Select::make('billing_city_id')
                                    ->label('City')
                                    ->options(fn (Get $get): array => $get('billing_zone_id') ? City::where('zone_id', $get('billing_zone_id'))->pluck('name', 'id')->toArray() : [])
                                    ->nullable()
                                    ->searchable(),
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('billing_address_line_1')
                            ->label('Address Line 1')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('billing_address_line_2')
                            ->label('Address Line 2')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('billing_postcode')
                            ->label('Postcode')
                            ->required()
                            ->maxLength(255),
                    ]),

                Forms\Components\Toggle::make('same_as_billing')
                    ->label('Shipping address is same as billing')
                    ->default(false)
                    ->live()
                    ->inline(false),

                Forms\Components\Section::make('Shipping Address')
                    ->visible(fn (Get $get) => ! $get('same_as_billing'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('shipping_firstname')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('shipping_lastname')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('shipping_business')
                            ->default(false)
                            ->live()
                            ->inline(false),

                        Forms\Components\TextInput::make('shipping_company')
                            ->label('Company')
                            ->visible(fn (Get $get) => (bool) $get('shipping_business'))
                            ->required(fn (Get $get) => (bool) $get('shipping_business'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('shipping_company_id')
                            ->label('Company ID')
                            ->visible(fn (Get $get) => (bool) $get('shipping_business'))
                            ->required(fn (Get $get) => (bool) $get('shipping_business'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('shipping_tax_id')
                            ->label('Tax ID')
                            ->visible(fn (Get $get) => (bool) $get('shipping_business'))
                            ->required(fn (Get $get) => (bool) $get('shipping_business'))
                            ->maxLength(255),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('shipping_country_id')
                                    ->label('Country')
                                    ->options(fn (): array => Country::pluck('name', 'id')->toArray())
                                    ->live()
                                    ->searchable(),

                                Forms\Components\Select::make('shipping_zone_id')
                                    ->label('Zone')
                                    ->options(fn (Get $get): array => $get('shipping_country_id') ? Zone::where('country_id', $get('shipping_country_id'))->pluck('name', 'id')->toArray() : [])
                                    ->live()
                                    ->nullable()
                                    ->searchable(),

                                Forms\Components\Select::make('shipping_city_id')
                                    ->label('City')
                                    ->options(fn (Get $get): array => $get('shipping_zone_id') ? City::where('zone_id', $get('shipping_zone_id'))->pluck('name', 'id')->toArray() : [])
                                    ->nullable()
                                    ->searchable(),
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('shipping_address_line_1')
                            ->label('Address Line 1')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('shipping_address_line_2')
                            ->label('Address Line 2')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('shipping_postcode')
                            ->label('Postcode')
                            ->required()
                            ->maxLength(255),
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
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        if ($state === null) {
                                            return;
                                        }
                                        $variant = ProductVariant::find($state);
                                        if (! $variant) {
                                            return;
                                        }
                                        if ($variant->special_price !== null && $variant->special_price != 0) {
                                            $set('unit_price_snapshot', $variant->special_price);
                                        } else {
                                            $set('unit_price_snapshot', $variant->regular_price);
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required()
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('unit_price_snapshot')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->step('0.01')
                                    ->required()
                                    ->live(onBlur: true),
                            ])
                            ->columns(4)
                            ->addActionLabel('Add Item')
                            ->collapsible()
                            ->reorderable(false)
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),

                Placeholder::make('subtotal_display')
                    ->label('Subtotal')
                    ->content(function (Get $get): string {
                        $items = $get('items') ?? [];
                        $subtotal = collect($items)->sum(fn (array $row): float => ((float) ($row['quantity'] ?? 0)) * ((float) ($row['unit_price_snapshot'] ?? 0)));

                        return number_format($subtotal, 2);
                    }),

                Forms\Components\Section::make('Order Totals')
                    ->schema([
                        Forms\Components\Repeater::make('order_totals')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->maxLength(50)
                                    ->rules([new ReservedOrderTotalCode()]),

                                Forms\Components\TextInput::make('value')
                                    ->label('Value')
                                    ->numeric()
                                    ->step('0.01')
                                    ->required()
                                    ->live(onBlur: true),
                            ])
                            ->columns(4)
                            ->addActionLabel('Add Total')
                            ->collapsible()
                            ->reorderable(false)
                            ->minItems(0),
                    ]),

                Placeholder::make('total_display')
                    ->label('Total')
                    ->content(function (Get $get): string {
                        $items = $get('items') ?? [];
                        $subtotal = collect($items)->sum(fn (array $row): float => ((float) ($row['quantity'] ?? 0)) * ((float) ($row['unit_price_snapshot'] ?? 0)));

                        $totals = $get('order_totals') ?? [];
                        $orderTotalsSum = collect($totals)->filter(fn ($row) => ($row['code'] ?? '') !== 'total')->sum(fn ($row) => (float) ($row['value'] ?? 0));

                        return number_format($subtotal + $orderTotalsSum, 2);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
