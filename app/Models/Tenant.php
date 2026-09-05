<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str; // Untuk UUID

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id', // Tambahkan 'id' karena kita akan mengaturnya secara manual (UUID)
        'name',
        'invitation_code',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'business_type',
        'is_active',
        'ipaymu_api_key',
        'ipaymu_secret_key',
        'ipaymu_mode',
        'pricing_plan_id',
        'subscription_ends_at',
        'last_transaction_id',
        'is_subscribed',
        'midtrans_server_key',
        'midtrans_client_key',
        'midtrans_merchant_id',
    'midtrans_is_production',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string', // Pastikan ID di-cast sebagai string
            'is_active' => 'boolean',
            'is_subscribed' => 'boolean',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Boot method to generate UUID for new models.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the pricing plan for the Tenant.
     */
    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    /**
     * Get the users for the Tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the products for the Tenant.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the inventory movements for the Tenant.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the sales for the Tenant.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the suppliers for the Tenant.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Get the owner of the Tenant.
     */
    public function owner()
    {
        // Assuming the owner is the user with the 'admin' role for this tenant.
        return $this->hasOne(User::class)->where('role', 'admin');
    }
}

