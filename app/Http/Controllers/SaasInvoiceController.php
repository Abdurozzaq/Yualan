<?php
namespace App\Http\Controllers;

use App\Models\SaasInvoice;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaasInvoiceController extends Controller
{
    public function show($tenantSlug, $id)
    {
        $invoice = SaasInvoice::with('tenant')->findOrFail($id);
        return Inertia::render('Subscription/Invoice', [
            'invoice' => $invoice,
        ]);
    }
}
