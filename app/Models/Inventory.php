<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str; // Untuk UUID

class Inventory extends Model
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
        'product_id',
        'quantity_change', // Perubahan kuantitas (positif untuk masuk, negatif untuk keluar)
        'cost_per_unit',   // Harga pokok per unit pada saat pergerakan
        'type',            // Tipe pergerakan (e.g., 'in', 'out', 'adjustment', 'sale', 'return')
        'reason',          // Alasan pergerakan (e.g., 'Penerimaan barang', 'Penyesuaian stok', 'Penjualan')
        'source_id',       // ID dari sumber pergerakan (e.g., sale_item_id, purchase_order_id)
        'source_type',     // Tipe model dari sumber (e.g., 'App\Models\SaleItem')
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
            'product_id' => 'string',
            'quantity_change' => 'integer',
            'cost_per_unit' => 'decimal:2',
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
     * Get the tenant that owns the Inventory movement.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the product associated with the Inventory movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the parent model (sale item, purchase order, etc.) of the inventory movement.
     */
    public function source()
    {
        return $this->morphTo();
    }
}
