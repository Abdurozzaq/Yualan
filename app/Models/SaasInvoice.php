<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_name',
        'expired_at',
        'transaction_id',
        'amount',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
