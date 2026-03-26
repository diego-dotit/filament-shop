<?php

namespace App\Filament\Resources;

use App\Domains\Review\Models\Review;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 2;

    // -----------------------------------------------------------------------
    // Form
    // -----------------------------------------------------------------------

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    // -----------------------------------------------------------------------
    // Table
    // -----------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->formatStateUsing(fn (Review $record): string => trim(($record->customer?->first_name ?? '') . ' ' . ($record->customer?->last_name ?? '')))
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('customer', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->sortable(),

                TextColumn::make('comment')
                    ->label('Comment Preview')
                    ->limit(50),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->defaultPaginationPageOption(10)
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Review $record): bool => $record->status === 'pending')
                    ->action(function (Review $record): void {
                        $record->update(['status' => 'approved']);
                        Notification::make()
                            ->title('Review approved')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (Review $record): bool => $record->status === 'pending')
                    ->action(function (Review $record): void {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Review rejected')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    // -----------------------------------------------------------------------
    // Pages
    // -----------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
