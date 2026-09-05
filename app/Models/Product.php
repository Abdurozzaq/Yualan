<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str; // Untuk UUID

class Product extends Model
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
        'id',
        'tenant_id',
        'category_id',
        'name',
        'sku',
        'description',
        'price',
        'stock',
        'unit',
        'image',
        'is_food_item',
        'ingredients',
        'cost_price', // Pastikan ini ada di fillable
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'tenant_id' => 'string',
            'category_id' => 'string',
            'price' => 'decimal:2', // Cast to decimal with 2 places
            'cost_price' => 'decimal:2', // Pastikan ini di-cast juga
            'stock' => 'integer',   // Cast to integer
            'is_food_item' => 'boolean', // Cast to boolean
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
     * Get the tenant that owns the Product.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the category that the Product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

