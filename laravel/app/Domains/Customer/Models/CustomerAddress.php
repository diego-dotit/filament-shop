<?php

namespace App\Domains\Customer\Models;

use App\Domains\Localisation\Models\City;
use App\Domains\Localisation\Models\Country;
use App\Domains\Localisation\Models\Zone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shipping' => 'boolean',
            'business' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
