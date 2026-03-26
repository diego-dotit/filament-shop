<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    public function mount(): void
    {
        parent::mount();

        // Pre-apply the pending filter by default so admins see reviews
        // that need moderation immediately on arrival.
        if ($this->tableFilters === null) {
            $this->tableFilters = ['status' => ['value' => 'pending']];
        }
    }
}
