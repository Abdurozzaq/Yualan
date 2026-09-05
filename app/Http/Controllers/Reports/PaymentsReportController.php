<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class PaymentsReportController extends Controller
{
    public function index(Request $request, $tenantSlug)
    {
        // Ambil tenant via model
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Filter
        $filterType = $request->input('filterType', 'all');
        $filterDate = $request->input('filterDate'); // Default null untuk tampilkan semua

        // Ambil data pembayaran dan piutang
        $payments = DB::table('sales')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.tenant_id', $tenant->id)
            ->whereNull('sales.deleted_at') // Pastikan tidak ambil yang sudah dihapus
            ->when($filterDate, function ($q) use ($filterDate) {
                $q->whereDate('sales.created_at', $filterDate);
            })
            ->select(
                'sales.id',
                'sales.created_at as date',
                'sales.invoice_number',
                'customers.name as customer_name',
                'sales.payment_method',
                'sales.status',
                'sales.total_amount',
                'sales.paid_amount',
                DB::raw('(sales.total_amount - sales.paid_amount) as outstanding_amount'),
                'sales.notes'
            )
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'date' => $row->date ? date('Y-m-d', strtotime($row->date)) : '',
                    'invoice_number' => $row->invoice_number,
                    'customer_name' => $row->customer_name,
                    'payment_method' => $row->payment_method,
                    'status' => $row->status,
                    'total_amount' => (float)$row->total_amount,
                    'paid_amount' => (float)$row->paid_amount,
                    'outstanding_amount' => (float)$row->outstanding_amount,
                    'notes' => $row->notes,
                ];
            })
            ->filter(function ($row) use ($filterType) {
                if ($filterType === 'paid') {
                    return $row['outstanding_amount'] == 0;
                }
                if ($filterType === 'outstanding') {
                    return $row['outstanding_amount'] > 0;
                }
                return true;
            })
            ->values();

        return Inertia::render('Reports/Payments', [
            'payments' => $payments,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }
}
