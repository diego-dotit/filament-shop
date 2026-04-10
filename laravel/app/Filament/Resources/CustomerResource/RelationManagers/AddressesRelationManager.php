<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Domains\Localisation\Models\City;
use App\Domains\Localisation\Models\Country;
use App\Domains\Localisation\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('shipping')
                    ->default(false)
                    ->live()
                    ->inline(false),

                Forms\Components\TextInput::make('firstname')
                    ->label('First Name')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('shipping') == 0)
                    ->required(fn (Get $get): bool => $get('shipping') == 0),

                Forms\Components\TextInput::make('lastname')
                    ->label('Last Name')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('shipping') == 0)
                    ->required(fn (Get $get): bool => $get('shipping') == 0),

                Forms\Components\Toggle::make('business')
                    ->default(false)
                    ->live()
                    ->inline(false)
                    ->visible(fn (Get $get): bool => $get('shipping') == 0),

                Forms\Components\TextInput::make('company')
                    ->label('Company')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get('business') && $get('shipping') == 0)
                    ->required(fn (Get $get): bool => (bool) $get('business') && $get('shipping') == 0),

                Forms\Components\TextInput::make('company_id')
                    ->label('Company ID')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get('business') && $get('shipping') == 0)
                    ->required(fn (Get $get): bool => (bool) $get('business') && $get('shipping') == 0),

                Forms\Components\TextInput::make('tax_id')
                    ->label('Tax ID')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => (bool) $get('business') && $get('shipping') == 0)
                    ->required(fn (Get $get): bool => (bool) $get('business') && $get('shipping') == 0),

                Forms\Components\Select::make('country_id')
                    ->label('Country')
                    ->options(fn (): array => Country::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->live()
                    ->required(),

                Forms\Components\Select::make('zone_id')
                    ->label('Zone')
                    ->options(fn (Get $get): array => $get('country_id')
                        ? Zone::where('country_id', $get('country_id'))->pluck('name', 'id')->toArray()
                        : []
                    )
                    ->searchable()
                    ->live()
                    ->nullable(),

                Forms\Components\Select::make('city_id')
                    ->label('City')
                    ->options(fn (Get $get): array => $get('zone_id')
                        ? City::where('zone_id', $get('zone_id'))->pluck('name', 'id')->toArray()
                        : []
                    )
                    ->searchable()
                    ->nullable(),

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
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('address_line_1')
                    ->label('Address'),
                Tables\Columns\TextColumn::make('postcode'),
            ]);
    }
}
