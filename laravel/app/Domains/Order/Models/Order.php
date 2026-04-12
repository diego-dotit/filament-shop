<?php

namespace App\Domains\Order\Models;

use App\Domains\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'status',
        'total_amount',
        'currency_code',
        'language_code',
        'exchange_rate',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function billingAddress(): HasMany
    {
        return $this->hasMany(OrderAddress::class)->where('shipping', 0);
    }

    public function shippingAddress(): HasMany
    {
        return $this->hasMany(OrderAddress::class)->where('shipping', 1);
    }

    public function totals(): HasMany
    {
        return $this->hasMany(OrderTotal::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderHistory::class)->orderBy('created_at', 'asc');
    }

    // -----------------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------------

    /**
     * Create and persist a new history entry for this order.
     *
     * @param  string            $status     The status value to record.
     * @param  string|null       $comments   Optional human-readable notes for the entry.
     * @param  \Carbon\Carbon|null $createdAt Optional timestamp to use as created_at (e.g. order's own created_at).
     * @return OrderHistory                   The newly created history record.
     */
    public function createHistoryEntry(string $status, ?string $comments = null, ?\Carbon\Carbon $createdAt = null): OrderHistory
    {
        /** @var OrderHistory $entry */
        $entry = $this->history()->create([
            'status'     => $status,
            'comments'   => $comments,
            'created_at' => $createdAt,
        ]);

        return $entry;
    }
}
