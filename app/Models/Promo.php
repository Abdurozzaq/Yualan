<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    //
    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'buy_qty', 'get_qty', 'product_id', 'another_product_id', 'expiry_date', 'is_active'
    ];
}
