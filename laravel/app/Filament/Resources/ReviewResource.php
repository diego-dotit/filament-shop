<?php

namespace App\Filament\Resources;

use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use App\Filament\Resources\ReviewResource\Pages\CreateReview;
use App\Filament\Resources\ReviewResource\Pages\EditReview;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

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
        return $form
            ->schema([
                Forms\Components\Section::make('Review Information')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn (Product $record): string => $record->getTranslation('name', app()->getLocale()) ?: ($record->name ?: '')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'id')
                            ->getOptionLabelFromRecordUsing(
                                fn (Customer $record): string => $record->first_name.' '.$record->last_name
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules(fn (Get $get, Forms\Components\Component $component): array => $get('product_id')
                                ? [
                                    Rule::unique('reviews', 'customer_id')
                                        ->where('product_id', $get('product_id'))
                                        ->ignore($component->getRecord()?->id),
                                ]
                                : []),

                        Forms\Components\Select::make('rating')
                            ->label('Rating')
                            ->options([
                                1 => '1 Star',
                                2 => '2 Stars',
                                3 => '3 Stars',
                                4 => '4 Stars',
                                5 => '5 Stars',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('comment')
                            ->label('Comment')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                    ]),
            ]);
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
                    ->formatStateUsing(fn (Review $record): string => trim(($record->customer?->first_name ?? '').' '.($record->customer?->last_name ?? '')))
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
                        'danger' => 'rejected',
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
                        'pending' => 'Pending',
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
            'create' => CreateReview::route('/create'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}
