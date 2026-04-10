<?php

namespace App\Filament\Resources;

use App\Domains\Customer\Models\Customer;
use App\Domains\Localisation\Models\City;
use App\Domains\Localisation\Models\Country;
use App\Domains\Localisation\Models\Zone;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Customer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Profile')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: 'customers',
                                column: 'email',
                                ignoreRecord: true,
                            ),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Password')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->confirmed()
                            ->required(fn (?Model $record): bool => $record === null)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->dehydrated(false)
                            ->required(fn (?Model $record): bool => $record === null)
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Addresses')
                    ->visibleOn('create')
                    ->schema([
                        Forms\Components\Repeater::make('addresses')
                            ->schema([
                                Forms\Components\Toggle::make('shipping')
                                    ->label('Shipping Address')
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
                                    ->label('Business')
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
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('zone_id')
                                    ->label('Zone')
                                    ->options(fn (Get $get): array => $get('country_id') ? Zone::where('country_id', $get('country_id'))->pluck('name', 'id')->toArray() : [])
                                    ->live()
                                    ->searchable(),

                                Forms\Components\Select::make('city_id')
                                    ->label('City')
                                    ->options(fn (Get $get): array => $get('zone_id') ? City::where('zone_id', $get('zone_id'))->pluck('name', 'id')->toArray() : [])
                                    ->searchable(),

                                Forms\Components\TextInput::make('address_line_1')
                                    ->label('Address Line 1')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('address_line_2')
                                    ->label('Address Line 2')
                                    ->nullable()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('postcode')
                                    ->label('Postcode')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Address')
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Profile')
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name'),
                        Infolists\Components\TextEntry::make('last_name'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AddressesRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
