<?php

namespace App\Filament\Resources;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\LanguageResource\Pages\CreateLanguage;
use App\Filament\Resources\LanguageResource\Pages\EditLanguage;
use App\Filament\Resources\LanguageResource\Pages\ListLanguages;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationLabel = 'Languages';

    protected static ?string $modelLabel = 'Language';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(10)
                    ->unique('languages', 'code', ignoreRecord: true),

                TextInput::make('name')
                    ->label('Name')
                    ->required(),

                Toggle::make('is_default')
                    ->label('Default Language'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->actions([
                Action::make('setDefault')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->action(function (Language $record): void {
                        DB::transaction(function () use ($record): void {
                            Language::query()->update(['is_default' => false]);
                            $record->update(['is_default' => true]);
                        });
                    })
                    ->successNotificationTitle('Language set as default'),

                EditAction::make(),

                DeleteAction::make(),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit'   => EditLanguage::route('/{record}/edit'),
        ];
    }
}
