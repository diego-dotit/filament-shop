<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
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
                Forms\Components\TextInput::make('country')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('city')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('address_line_1')
                    ->required()
                    ->maxLength(255)
                    ->label('Address Line 1'),

                Forms\Components\TextInput::make('address_line_2')
                    ->nullable()
                    ->maxLength(255)
                    ->label('Address Line 2'),

                Forms\Components\TextInput::make('postcode')
                    ->required()
                    ->maxLength(255)
                    ->label('Postcode'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line_1')
                    ->label('Address'),
                Tables\Columns\TextColumn::make('postcode'),
            ]);
    }
}
