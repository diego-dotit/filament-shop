<?php

namespace App\Domains\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipping',
        'business',
        'firstname',
        'lastname',
        'company',
        'company_id',
        'tax_id',
        'country_id',
        'zone_id',
        'city_id',
        'address_line_1',
        'address_line_2',
        'postcode',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shipping' => 'boolean',
        'business' => 'boolean',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
