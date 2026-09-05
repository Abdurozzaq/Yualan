<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'value', 'max_nominal', 'expiry_date', 'used'
    ];

    public static function findByCode($tenantId, $code)
    {
        return self::where('tenant_id', $tenantId)->where('code', $code)->first();
    }
}
