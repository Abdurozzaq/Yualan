<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    //
    protected $fillable = [
        'code', 'name', 'type', 'buy_qty', 'get_qty', 'product_id', 'another_product_id', 'expiry_date', 'is_active'
    ];
}
