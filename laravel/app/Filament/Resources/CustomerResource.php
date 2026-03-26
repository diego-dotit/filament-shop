<?php

namespace App\Filament\Resources;

use App\Domains\Customer\Models\Customer;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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

                Infolists\Components\Section::make('Addresses')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('addresses')
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('ID'),
                                Infolists\Components\TextEntry::make('country'),
                                Infolists\Components\TextEntry::make('city'),
                                Infolists\Components\TextEntry::make('address_line_1')
                                    ->label('Address'),
                                Infolists\Components\TextEntry::make('postcode'),
                            ])
                            ->columns(5),
                    ]),
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
            'index'  => Pages\ListCustomers::route('/'),
            'view'   => Pages\ViewCustomer::route('/{record}'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
