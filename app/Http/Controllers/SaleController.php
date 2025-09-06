<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment; // Import Payment model
use App\Models\Inventory; // Import Inventory model for logging movements
use App\Services\IpaymuService; // Import IpaymuService
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log; // Import Log Facade
use Illuminate\Support\Str; // Import Str for UUID in Sale model creation

class SaleController extends Controller {
    /**
     * API: Get detail sale/order for edit (kasir)
     */
    public function show(Request $request, string $tenantSlug, string $saleId)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $sale = Sale::with(['saleItems.product', 'customer'])
            ->where('id', $saleId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        // Format items for frontend
        $items = collect($sale->saleItems)->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'name' => $item->product ? $item->product->name : ($item->product_name ?? ''),
                'unit' => $item->product ? $item->product->unit : null,
                'stock' => $item->product ? $item->product->stock : 9999,
            ];
        })->toArray();

        // Support voucher_codes and promo_codes as array or string
        $voucherCodes = [];
        if (!empty($sale->voucher_codes)) {
            $voucherCodes = is_array($sale->voucher_codes) ? $sale->voucher_codes : [$sale->voucher_codes];
        } elseif (!empty($sale->voucher_code)) {
            $voucherCodes = [$sale->voucher_code];
        }
        $promoCodes = [];
        if (!empty($sale->promo_codes)) {
            $promoCodes = is_array($sale->promo_codes) ? $sale->promo_codes : [$sale->promo_codes];
        } elseif (!empty($sale->promo_code)) {
            $promoCodes = [$sale->promo_code];
        }

        return response()->json([
            'sale' => [
                'id' => $sale->id,
                'items' => $items,
                'discount_amount' => $sale->discount_amount,
                'tax_rate' => $sale->tax_rate ?? 0,
                'payment_method' => $sale->payment_method,
                'paid_amount' => $sale->paid_amount,
                'customer_id' => $sale->customer_id,
                'notes' => $sale->notes,
                'voucher_codes' => $voucherCodes,
                'promo_codes' => $promoCodes,
            ]
        ]);
    }

    /**
     * Update an existing sale (edit mode from cashier)
     */
    public function update(Request $request, string $tenantSlug, string $saleId)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();
        $sale = Sale::where('id', $saleId)->where('tenant_id', $tenant->id)->firstOrFail();

        // Only allow update if status is pending
        if ($sale->status !== 'pending') {
            return response()->json(['error' => 'Order tidak bisa diedit karena sudah diproses.'], 400);
        }

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'string', 'exists:customers,id'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['required', 'string', 'in:cash,ipaymu,midtrans'],
            'notes' => ['nullable', 'string', 'max:500'],
            'voucher_codes' => ['nullable', 'array'],
            'promo_codes' => ['nullable', 'array'],
        ]);

        // Restore stock for previous items
        foreach ($sale->saleItems as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock += $item->quantity;
                $product->save();
            }
            // Optionally: delete inventory log for this sale item
            \App\Models\Inventory::where('related_sale_item_id', $item->id)->delete();
        }
        $sale->saleItems()->delete();

        // Recalculate items, subtotal, etc
        $subtotal = 0;
        $saleItemsData = [];
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $itemSubtotal = $product->price * $item['quantity'];
            $subtotal += $itemSubtotal;
            if ($product->stock < $item['quantity']) {
                return response()->json(['error' => 'Stok ' . $product->name . ' tidak mencukupi.'], 400);
            }
            $saleItemsData[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'subtotal' => $itemSubtotal,
                'cost_price_at_sale' => (float)$product->cost_price,
            ];
        }
        // TODO: handle promo & free items if needed (see store logic)

        $discountAmount = $request->discount_amount;
        $taxAmount = ($subtotal - $discountAmount) * ($request->tax_rate / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        // Update sale main fields
        $sale->update([
            'customer_id' => $request->customer_id,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
        ]);

        // Save new sale items and reduce stock
        foreach ($saleItemsData as $itemData) {
            $saleItem = $sale->saleItems()->create(array_merge($itemData, ['id' => \Illuminate\Support\Str::uuid()]));
            $product = Product::find($itemData['product_id']);
            $product->stock -= $itemData['quantity'];
            $product->save();
            \App\Models\Inventory::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'quantity_change' => -$itemData['quantity'],
                'type' => 'out',
                'reason' => 'Edit Order: ' . $sale->invoice_number,
                'user_id' => \Auth::id(),
                'cost_price_at_movement' => $product->cost_price,
                'related_sale_item_id' => $saleItem->id,
            ]);
        }

        return response()->json(['success' => true, 'saleId' => $sale->id]);
    }

    /**
     * Endpoint untuk produk paginasi (untuk kasir)
     */
    public function paginatedProducts(Request $request, $tenantSlug)
    {
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)->firstOrFail();
        $perPage = (int) $request->get('per_page', 20);
        $page = (int) $request->get('page', 1);
        $categoryId = $request->get('category_id');
        $search = $request->get('search');
        $sortField = $request->get('sort_field', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedFields = ['name', 'price', 'stock', 'sku'];
        if (!in_array($sortField, $allowedFields)) {
            $sortField = 'name';
        }
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        $query = \App\Models\Product::where('tenant_id', $tenant->id);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('unit', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }
        $products = $query->with('category')->orderBy($sortField, $sortDirection)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'products' => $products->items(),
            'total_pages' => $products->lastPage(),
            'current_page' => $products->currentPage(),
            'total' => $products->total(),
        ]);
    }
    /**
     * Get voucher by code (for validation in frontend)
     */
    public function getVoucherByCode(Request $request, $tenantSlug, $code)
    {
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)->firstOrFail();
        $voucher = \App\Models\Voucher::findByCode($tenant->id, $code);
        if (!$voucher) {
            return response()->json(['voucher' => null], 404);
        }
        return response()->json(['voucher' => $voucher]);
    }

    /**
     * Mark voucher as used (when sale is submitted)
     */
    public function useVoucher(Request $request, $tenantSlug, $code)
    {
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)->firstOrFail();
        $voucher = \App\Models\Voucher::findByCode($tenant->id, $code);
        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher tidak ditemukan.'], 404);
        }
        if ($voucher->used) {
            return response()->json(['success' => false, 'message' => 'Voucher sudah digunakan.'], 400);
        }
        $voucher->used = true;
        $voucher->save();
        return response()->json(['success' => true]);
    }

    /**
     * Get Sale ID (UUID) by order_id for Midtrans redirect.
     */
    public function getSaleIdByOrderId(Request $request, string $tenantSlug, string $orderId)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Find sale by order_id and tenant
        $sale = \App\Models\Sale::where('tenant_id', $tenant->id)
            ->where('invoice_number', $orderId)
            ->first();

        if ($sale) {
            return response()->json(['saleId' => $sale->id]);
        } else {
            return response()->json(['saleId' => null], 404);
        }
    }

    /**
     * Endpoint for retrying Midtrans payment from receipt page.
     * Returns new snapToken for the sale.
     */
    public function midtransRetry(Request $request, string $tenantSlug, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant and sale
        if (Auth::user()->tenant_id !== $tenant->id || $sale->tenant_id !== $tenant->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow retry if payment method is midtrans and status is pending/failed
        if ($sale->payment_method !== 'midtrans' || !in_array($sale->status, ['pending', 'failed'])) {
            return response()->json(['error' => 'Invalid sale for Midtrans retry'], 400);
        }

        // Prepare items for Snap
        $items = [];
        foreach ($sale->saleItems as $item) {
            $items[] = [
                'id' => $item->product_id,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'name' => $item->product->name,
            ];
        }

        $customerDetails = [
            'first_name' => $sale->customer ? $sale->customer->name : 'Guest',
            'email' => $sale->customer ? $sale->customer->email : 'guest@example.com',
            'phone' => $sale->customer ? $sale->customer->phone : '081234567890',
        ];

        $midtransService = new \App\Services\MidtransService($tenant);
        $snapResponse = $midtransService->createSnapTransaction([
            'order_id' => $sale->invoice_number,
            'gross_amount' => $sale->total_amount,
            'items' => $items,
            'customer_details' => $customerDetails,
            'callback_url' => route('sales.midtransNotify'),
        ]);

        // Optionally update sale with new transaction id/payload if needed
        $sale->update([
            'midtrans_transaction_id' => $snapResponse['transaction_id'] ?? null,
            'midtrans_payload' => json_encode($snapResponse),
            'payment_status' => 'pending',
            'payment_type' => $snapResponse['payment_type'] ?? null,
            'gross_amount' => $snapResponse['gross_amount'] ?? $sale->total_amount,
        ]);

        // Return snapToken to frontend
        return response()->json([
            'snapToken' => $snapResponse['token'] ?? null,
        ]);
    }

    /**
     * Display a listing of the sales for the current tenant.
     */
    public function index(string $tenantSlug, Request $request): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        $sortBy = $request->input('sortBy', 'created_at'); // Default sort by creation date
        $sortDirection = $request->input('sortDirection', 'desc'); // Default sort descending
        $perPage = $request->input('perPage', 10); // Default items per page
        $search = $request->input('search'); // Search term for invoice number or customer name

        $salesQuery = Sale::where('tenant_id', $tenant->id)
            ->with(['customer', 'user']); // Eager load customer and user

        // Apply search filter
        if ($search) {
            $salesQuery->where(function ($query) use ($search) {
                $query->where('invoice_number', 'ILIKE', '%' . $search . '%')
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', '%' . $search . '%');
                    });
            });
        }

        // Apply sorting
        $salesQuery->orderBy($sortBy, $sortDirection);

        // Get paginated results
        $sales = $salesQuery->paginate($perPage)->withQueryString();

        return Inertia::render('Cashier/SalesHistory', [ // Assuming this is your sales history component
            'sales' => $sales,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Display the sales order page.
     */
    public function order(string $tenantSlug): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Retrieve products, categories, customers, vouchers, promos for this tenant
        $products = Product::where('tenant_id', $tenant->id)->with('category')->get();
        $categories = Category::where('tenant_id', $tenant->id)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->get();
        $vouchers = \App\Models\Voucher::
            // Uncomment if you add tenant_id to vouchers table:
            // ->where('tenant_id', $tenant->id)
            whereDate('expiry_date', '>=', now())
            ->get();
        $promos = \App\Models\Promo::where('is_active', true)
            // Uncomment if you add tenant_id to promos table:
            // ->where('tenant_id', $tenant->id)
            ->whereDate('expiry_date', '>=', now())
            ->get();

        // Check if iPaymu credentials are configured for the tenant
        $ipaymuConfigured = (bool)$tenant->ipaymu_api_key && (bool)$tenant->ipaymu_secret_key;
        $midtransConfigured = !empty($tenant->midtrans_server_key) && !empty($tenant->midtrans_client_key) && !empty($tenant->midtrans_merchant_id);

        // Kirim client key ke frontend agar Snap.js bisa custom UI
        $midtransClientKey = $tenant->midtrans_client_key ?? '';

        // Ambil orderId dari query param jika ada
        $orderId = request()->query('orderId');

        return Inertia::render('Cashier/Order', [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
            'ipaymuConfigured' => $ipaymuConfigured,
            'midtransConfigured' => $midtransConfigured,
            'midtransClientKey' => $midtransClientKey,
            'vouchers' => $vouchers,
            'promos' => $promos,
            'orderId' => $orderId,
        ]);
    }

    /**
     * Store a new sale.
     * Return type changed to Response to allow Inertia::render for iPaymu redirect.
     */
    public function store(Request $request, string $tenantSlug): \Illuminate\Http\JsonResponse|\Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'string', 'exists:customers,id'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['required', 'string', 'in:cash,ipaymu,midtrans'],
            // paid_amount hanya required jika status bukan pending
            'paid_amount' => [
                function ($attribute, $value, $fail) use ($request) {
                    if (($request->status ?? null) !== 'pending' && (is_null($value) || $value === '')) {
                        $fail('Jumlah dibayar wajib diisi.');
                    }
                    if (!is_null($value) && !is_numeric($value)) {
                        $fail('Jumlah dibayar harus berupa angka.');
                    }
                    if (!is_null($value) && $value < 0) {
                        $fail('Jumlah dibayar minimal 0.');
                    }
                }
            ],
            'notes' => ['nullable', 'string', 'max:500'],
            'promo_code' => ['nullable', 'string'],
        ]);

        // Jika status pending, selalu create order baru (tanpa trigger pembayaran) untuk semua metode
        if ($request->status === 'pending') {
            $subtotal = 0;
            $saleItemsData = [];
            $buyQtyMap = [];
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                // Check sufficient stock
                if ($product->stock < $item['quantity']) {
                    return back()->withErrors(['items' => 'Stok ' . $product->name . ' tidak mencukupi.']);
                }

                $saleItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price, // Price at the time of sale
                    'subtotal' => $itemSubtotal,
                    'cost_price_at_sale' => (float)$product->cost_price, // Explicitly cast to float
                ];

                // Akumulasi jumlah beli untuk promo
                $buyQtyMap[$product->id] = ($buyQtyMap[$product->id] ?? 0) + $item['quantity'];
            }

            // Ambil semua promo aktif
            $promos = \App\Models\Promo::where('is_active', true)
                ->where('tenant_id', $tenant->id)
                ->whereDate('expiry_date', '>=', now())
                ->get();

            // Hitung free item dari semua promo
            $freeItemMap = [];
            foreach ($promos as $promo) {
                $buy_qty = $buyQtyMap[$promo->product_id] ?? 0;
                $multiplier = intdiv($buy_qty, $promo->buy_qty);
                $freeProductId = $promo->type === 'buyxgetx' ? $promo->product_id : $promo->another_product_id;
                if ($multiplier >= 1 && $freeProductId) {
                    $freeQty = $promo->get_qty * $multiplier;
                    $freeItemMap[$freeProductId] = ($freeItemMap[$freeProductId] ?? 0) + $freeQty;
                }
            }

            // Tambahkan free item ke saleItemsData
            foreach ($freeItemMap as $freeProductId => $qty) {
                $freeProduct = Product::find($freeProductId);
                if ($freeProduct && $qty > 0) {
                    $saleItemsData[] = [
                        'product_id' => $freeProduct->id,
                        'quantity' => $qty,
                        'price' => 0,
                        'subtotal' => 0,
                        'cost_price_at_sale' => (float)$freeProduct->cost_price,
                    ];
                }
            }

            $discountAmount = $request->discount_amount;
            $taxAmount = ($subtotal - $discountAmount) * ($request->tax_rate / 100);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // paid_amount selalu 0 untuk pending
            $paidAmount = 0;
            $changeAmount = 0;

            // Generate a unique invoice number (simple example)
            $invoiceNumber = 'INV-' . date('YmdHis') . '-' . Str::random(4);

            $sale = Sale::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'user_id' => Auth::id(),
                'customer_id' => $request->customer_id,
                'voucher_code' => $request->voucher_code ?? null,
                'promo_code' => $request->promo_code ?? null,
                'invoice_number' => $invoiceNumber,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Save sale items and reduce stock
            foreach ($saleItemsData as $itemData) {
                $saleItem = $sale->saleItems()->create(array_merge($itemData, ['id' => Str::uuid()]));
                $product = Product::find($itemData['product_id']);
                $product->stock -= $itemData['quantity'];
                $product->save();
                Inventory::create([
                    'id' => Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'quantity_change' => -$itemData['quantity'],
                    'type' => 'out',
                    'reason' => 'Penjualan: ' . $sale->invoice_number,
                    'user_id' => Auth::id(),
                    'cost_price_at_movement' => $product->cost_price,
                    'related_sale_item_id' => $saleItem->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'saleId' => $sale->id,
                'message' => 'Order berhasil disimpan sebagai pending!'
            ]);
        }

        // Jika request ada id dan status paid, update sales (khusus cash payment update)
        if ($request->has('id') && $request->status !== 'pending' && $request->payment_method === 'cash') {
            $sale = Sale::where('id', $request->id)->where('tenant_id', $tenant->id)->firstOrFail();
            $paidAmount = $request->paid_amount;
            $totalAmount = $sale->total_amount;
            if ($paidAmount < $totalAmount) {
                return back()->withErrors(['paid_amount' => 'Jumlah yang dibayar kurang dari total penjualan.']);
            }
            $sale->update([
                'paid_amount' => $paidAmount,
                'change_amount' => $paidAmount - $totalAmount,
                'status' => 'completed',
            ]);
            return back()->with('success', 'Pembayaran tunai berhasil!');
        }

        // If payment method is iPaymu, initiate payment
        if ($request->has('id') && $request->payment_method === 'ipaymu' && $request->status !== 'pending') {
            $sale = Sale::where('id', $request->id)->where('tenant_id', $tenant->id)->firstOrFail();
            return $this->initiateIpaymuPayment($sale, $tenant);
        }

        // If payment method is midtrans, initiate payment and return snapToken for Snap.js
        if ($request->has('id') && $request->payment_method === 'midtrans' && $request->status !== 'pending') {
            $sale = Sale::where('id', $request->id)->where('tenant_id', $tenant->id)->firstOrFail();
            $midtransService = new \App\Services\MidtransService($tenant);
            // Build item details for Midtrans, including discount and tax as separate items
            $items = $sale->saleItems->map(function($item) {
                return [
                    'id' => $item->product_id,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product->name,
                ];
            })->toArray();
            // Tambahkan diskon sebagai item negatif jika ada
            if ($sale->discount_amount > 0) {
                $items[] = [
                    'id' => 'DISCOUNT',
                    'price' => -$sale->discount_amount,
                    'quantity' => 1,
                    'name' => 'Diskon',
                ];
            }
            // Tambahkan pajak sebagai item positif jika ada
            if ($sale->tax_amount > 0) {
                $items[] = [
                    'id' => 'TAX',
                    'price' => $sale->tax_amount,
                    'quantity' => 1,
                    'name' => 'Pajak',
                ];
            }
            $snapResponse = $midtransService->createSnapTransaction([
                'order_id' => $sale->invoice_number,
                'gross_amount' => $sale->total_amount,
                'items' => $items,
                'customer_details' => [
                    'first_name' => $sale->customer ? $sale->customer->name : 'Guest',
                    'email' => $sale->customer ? $sale->customer->email : 'guest@example.com',
                    'phone' => $sale->customer ? $sale->customer->phone : '081234567890',
                ],
                'callback_url' => route('sales.midtransNotify'),
            ]);

            // Simpan data Midtrans ke sale
            $sale->update([
                'order_id' => $sale->invoice_number,
                'midtrans_transaction_id' => $snapResponse['transaction_id'] ?? null,
                'midtrans_payload' => json_encode($snapResponse),
                'payment_status' => 'pending',
                'payment_type' => $snapResponse['payment_type'] ?? null,
                'gross_amount' => $snapResponse['gross_amount'] ?? $sale->total_amount,
            ]);

            // Simpan ke payments
            Payment::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'sale_id' => $sale->id,
                'payment_method' => 'midtrans',
                'amount' => $sale->total_amount,
                'currency' => 'IDR',
                'status' => 'pending',
                'transaction_id' => $snapResponse['transaction_id'] ?? null,
                'gateway_response' => $snapResponse,
                'notes' => 'Pembayaran Midtrans Snap diinisiasi',
            ]);

            // Return snapToken to frontend for Snap.js
            return Inertia::render('Cashier/Order', [
                'products' => Product::where('tenant_id', $tenant->id)->with('category')->get(),
                'categories' => Category::where('tenant_id', $tenant->id)->get(),
                'customers' => Customer::where('tenant_id', $tenant->id)->get(),
                'tenantSlug' => $tenantSlug,
                'tenantName' => $tenant->name,
                'ipaymuConfigured' => (bool)$tenant->ipaymu_api_key && (bool)$tenant->ipaymu_secret_key,
                'midtransConfigured' => !empty($tenant->midtrans_server_key) && !empty($tenant->midtrans_client_key) && !empty($tenant->midtrans_merchant_id),
                'snapToken' => $snapResponse['token'] ?? null,
            ]);
        }

        // For cash payments, redirect to receipt page
        if (!$request->has('id')) {
            return back()->withErrors(['id' => 'ID penjualan tidak ditemukan.']);
        }
        return redirect()->route('sales.receipt', ['tenantSlug' => $tenantSlug, 'sale' => $request->id])
            ->with('success', 'Order berhasil disimpan dan masuk mode edit!');
    }
    /**
     * Initiate Midtrans payment (Snap API).
     */
    protected function initiateMidtransPayment(Sale $sale, Tenant $tenant): Response|RedirectResponse
    {
        try {
            // Pastikan ada MidtransService dengan tenant
            $midtransService = new \App\Services\MidtransService($tenant);

            // Prepare items for Snap
            $items = [];
            foreach ($sale->saleItems as $item) {
                $items[] = [
                    'id' => $item->product_id,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product->name,
                ];
            }

            $customerDetails = [
                'first_name' => $sale->customer ? $sale->customer->name : 'Guest',
                'email' => $sale->customer ? $sale->customer->email : 'guest@example.com',
                'phone' => $sale->customer ? $sale->customer->phone : '081234567890',
            ];

            // Call MidtransService to create Snap transaction
            $snapResponse = $midtransService->createSnapTransaction([
                'order_id' => $sale->invoice_number,
                'gross_amount' => $sale->total_amount,
                'items' => $items,
                'customer_details' => $customerDetails,
                'callback_url' => route('sales.midtransNotify'),
            ]);

            // Simpan data Midtrans ke sale
            $sale->update([
                'order_id' => $sale->invoice_number,
                'midtrans_transaction_id' => $snapResponse['transaction_id'] ?? null,
                'midtrans_payload' => json_encode($snapResponse),
                'payment_status' => 'pending',
                'payment_type' => $snapResponse['payment_type'] ?? null,
                'gross_amount' => $snapResponse['gross_amount'] ?? $sale->total_amount,
            ]);

            // Simpan ke payments
            Payment::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'sale_id' => $sale->id,
                'payment_method' => 'midtrans',
                'amount' => $sale->total_amount,
                'currency' => 'IDR',
                'status' => 'pending',
                'transaction_id' => $snapResponse['transaction_id'] ?? null,
                'gateway_response' => $snapResponse,
                'notes' => 'Pembayaran Midtrans Snap diinisiasi',
            ]);

            // Redirect ke Snap URL
            if (isset($snapResponse['redirect_url'])) {
                return redirect()->away($snapResponse['redirect_url']);
            }
            return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
                ->with('error', 'URL pembayaran Midtrans tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Midtrans Service Error: ' . $e->getMessage());
            return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
                ->with('error', 'Terjadi kesalahan saat menginisiasi pembayaran Midtrans: ' . $e->getMessage());
        }
    }
    /**
     * Handle Midtrans payment notification (callback).
     * POST dari Midtrans ke endpoint ini.
     */
    public function midtransNotify(Request $request)
    {
        Log::info('Midtrans Notify Callback Received:', $request->all());

        $orderId = $request->input('order_id');
        $transactionStatus = $request->input('transaction_status');
        $transactionId = $request->input('transaction_id');
        $grossAmount = $request->input('gross_amount');
        $paymentType = $request->input('payment_type');
        $signatureKey = $request->input('signature_key');

        // Cari sale berdasarkan order_id
        $sale = Sale::where('order_id', $orderId)->first();
        if (!$sale) {
            Log::warning('Midtrans Notify: Sale not found for order_id: ' . $orderId);
            return response()->json(['message' => 'Sale not found'], 404);
        }

        // Verifikasi signature (opsional, bisa tambahkan di MidtransService)
        // ...implementasi signature check jika diperlukan...

        // Update status pembayaran
        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            $sale->update([
                'status' => 'completed',
                'payment_status' => $transactionStatus,
                'midtrans_transaction_id' => $transactionId,
                'gross_amount' => $grossAmount,
                'payment_type' => $paymentType,
                'midtrans_payload' => json_encode($request->all()),
            ]);

            // Update Payment record
            $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
            $payment->fill([
                'tenant_id' => $sale->tenant_id,
                'payment_method' => 'midtrans',
                'amount' => $grossAmount,
                'currency' => 'IDR',
                'status' => 'completed',
                'gateway_response' => $request->all(),
                'notes' => 'Pembayaran Midtrans (callback)',
            ])->save();
        } elseif ($transactionStatus === 'pending') {
            $sale->update([
                'status' => 'pending',
                'payment_status' => $transactionStatus,
                'midtrans_transaction_id' => $transactionId,
                'gross_amount' => $grossAmount,
                'payment_type' => $paymentType,
                'midtrans_payload' => json_encode($request->all()),
            ]);
            $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
            $payment->fill([
                'tenant_id' => $sale->tenant_id,
                'payment_method' => 'midtrans',
                'amount' => $grossAmount,
                'currency' => 'IDR',
                'status' => 'pending',
                'gateway_response' => $request->all(),
                'notes' => 'Pembayaran Midtrans pending (callback)',
            ])->save();
        } elseif ($transactionStatus === 'cancel' || $transactionStatus === 'deny' || $transactionStatus === 'expire') {
            $sale->update([
                'status' => 'failed',
                'payment_status' => $transactionStatus,
                'midtrans_transaction_id' => $transactionId,
                'gross_amount' => $grossAmount,
                'payment_type' => $paymentType,
                'midtrans_payload' => json_encode($request->all()),
            ]);
            $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
            $payment->fill([
                'tenant_id' => $sale->tenant_id,
                'payment_method' => 'midtrans',
                'amount' => $grossAmount,
                'currency' => 'IDR',
                'status' => 'failed',
                'gateway_response' => $request->all(),
                'notes' => 'Pembayaran Midtrans gagal/cancel/expire (callback)',
            ])->save();
        }

        return response()->json(['message' => 'Midtrans notification processed'], 200);
    }

    /**
     * Initiate iPaymu payment.
     * Return type changed to Response to allow Inertia::render.
     */
    protected function initiateIpaymuPayment(Sale $sale, Tenant $tenant): \Illuminate\Http\JsonResponse|\Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        try {
            $ipaymuService = new IpaymuService($tenant);

            // Prepare items data for IpaymuService
            $items = [];
            foreach ($sale->saleItems as $item) {
                $items[] = [
                    'name' => $item->product->name,
                    'qty' => $item->quantity,
                    'price' => $item->price,
                ];
            }

            $buyerName = $sale->customer ? $sale->customer->name : 'Guest Customer';
            $buyerEmail = $sale->customer ? $sale->customer->email : 'guest@example.com';
            $buyerPhone = $sale->customer ? $sale->customer->phone : '081234567890';

            // Use IpaymuService to initiate payment
            $response = $ipaymuService->initiatePayment(
                $items,
                $sale->id, // referenceId
                $buyerName,
                $buyerEmail,
                $buyerPhone,
                route('sales.ipaymuReturn', ['tenantSlug' => $tenant->slug, 'sale' => $sale->id]),
                route('sales.ipaymuCancel', ['tenantSlug' => $tenant->slug, 'sale' => $sale->id]),
                route('sales.ipaymuNotify')
                // Omit pickup area and address to avoid "Pickup area not registered" error
            );

            if ($response['Status'] == 200) {
                // Update sale status to 'pending'
                $sale->update(['status' => 'pending', 'notes' => 'Menunggu pembayaran via iPaymu.']);
                
                // Debug log untuk melihat response structure
                Log::info('iPaymu Initiate Payment Response Structure', [
                    'response' => $response,
                    'sale_id' => $sale->id,
                    'available_keys' => array_keys($response['Data'] ?? [])
                ]);
                
                // Ambil transaction_id dengan fallback untuk berbagai kemungkinan field name
                $transactionId = $response['Data']['TransactionId'] 
                    ?? $response['Data']['transactionId'] 
                    ?? $response['Data']['trx_id']
                    ?? $response['Data']['id']
                    ?? null;
                
                // Create a payment record
                Payment::create([
                    'id' => Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'sale_id' => $sale->id,
                    'payment_method' => 'ipaymu',
                    'amount' => $sale->total_amount,
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'transaction_id' => $transactionId,
                    'gateway_response' => $response,
                    'notes' => 'Pembayaran iPaymu diinisiasi - TRX ID: ' . ($transactionId ?? 'null'),
                ]);
                
                Log::info('Payment record created', [
                    'sale_id' => $sale->id,
                    'transaction_id' => $transactionId,
                    'extracted_from_response' => [
                        'TransactionId' => $response['Data']['TransactionId'] ?? 'not_found',
                        'transactionId' => $response['Data']['transactionId'] ?? 'not_found',
                        'trx_id' => $response['Data']['trx_id'] ?? 'not_found',
                        'id' => $response['Data']['id'] ?? 'not_found',
                    ],
                ]);

                // Kembalikan response JSON agar frontend bisa melakukan window.location.href
                return response()->json([
                    'success' => true,
                    'payment_url' => $response['Data']['Url'] ?? null,
                    'sale_id' => $sale->id
                ]);
            } else {
                Log::error('iPaymu API Error: ' . json_encode($response));
                // Keterangan khusus untuk error tertentu
                $additionalInfo = null;
                if (($response['Message'] ?? '') === 'Invalid IP') {
                    $additionalInfo = 'Update allowed IP address Anda di dashboard iPaymu dengan IP address sistem POS ini.';
                } elseif (($response['Message'] ?? '') === 'Invalid Domain') {
                    $additionalInfo = 'Update allowed domain address Anda di dashboard iPaymu dengan domain address sistem POS ini.';
                }
                return response()->json([
                    'success' => false,
                    'error' => $response['Message'] ?? 'Pembayaran iPaymu gagal diinisiasi.',
                    'status' => $response['Status'] ?? 400,
                    'data' => $response['Data'] ?? null,
                    'info' => $additionalInfo
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('iPaymu Service Error: ' . $e->getMessage());
            // Coba parse response error dari iPaymu jika ada JSON di message
            $errorMsg = $e->getMessage();
            $additionalInfo = null;
            $parsed = null;
            if (preg_match('/\{\"Status\":400,.*\}/', $errorMsg, $matches)) {
                try {
                    $parsed = json_decode(str_replace('\"', '"', $matches[0]), true);
                } catch (\Exception $ex) {}
            }
            if ($parsed && isset($parsed['Message'])) {
                if ($parsed['Message'] === 'Invalid IP') {
                    $additionalInfo = 'Update allowed IP address Anda di dashboard iPaymu dengan IP address sistem POS ini.';
                } elseif ($parsed['Message'] === 'Invalid Domain') {
                    $additionalInfo = 'Update allowed domain address Anda di dashboard iPaymu dengan domain address sistem POS ini.';
                }
            }
            return response()->json([
                'success' => false,
                'error' => $errorMsg,
                'status' => 500,
                'info' => $additionalInfo
            ], 500);
        }
    }

    /**
     * Re-initiate iPaymu payment for an existing sale.
     */
    public function reinitiatePayment(string $tenantSlug, Sale $sale): Response|RedirectResponse
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the authenticated user belongs to this tenant AND the sale belongs to this tenant
        if (Auth::user()->tenant_id !== $tenant->id || $sale->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the sale is actually pending or failed and uses iPaymu
        if ($sale->payment_method !== 'ipaymu' || !in_array($sale->status, ['pending', 'failed', 'cancelled'])) {
            return redirect()->route('sales.receipt', ['tenantSlug' => $tenantSlug, 'sale' => $sale->id])
                ->with('error', 'Pembayaran untuk penjualan ini tidak dapat diinisiasi ulang.');
        }

        // Reload sale items to ensure they are available for payment initiation
        $sale->load('saleItems.product');

        // Call the public initiateIpaymuPayment method
        return $this->initiateIpaymuPayment($sale, $tenant);
    }

    /**
     * Display the sales receipt page.
     */
    public function receipt(string $tenantSlug, Sale $sale): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Ensure this sale belongs to the correct tenant
        if ($sale->tenant_id !== $tenant->id) {
            abort(404); // Not found if sale doesn't belong to this tenant
        }

        // Load necessary relationships for the receipt
        $sale->load(['saleItems.product', 'customer', 'user', 'payments']); // Use saleItems and payments

        return Inertia::render('Cashier/Receipt', [
            'sale' => $sale,
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Generate PDF receipt for a specific sale.
     */
    public function generateReceiptPdf(string $tenantSlug, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Ensure this sale belongs to the correct tenant
        if ($sale->tenant_id !== $tenant->id) {
            abort(404);
        }

        // Load necessary relationships for the PDF
        $sale->load(['saleItems.product', 'customer', 'user']); // Use saleItems

        // Format date for Blade view
        $formattedDate = (new \DateTime($sale->created_at))->format('d F Y H:i');

        // Render the Blade view to PDF
        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'tenantName' => $tenant->name,
            'formattedDate' => $formattedDate,
        ]);

        // Return the PDF as a download
        return $pdf->stream('resi-penjualan-' . $sale->invoice_number . '.pdf');
    }

    /**
     * Show HTML receipt for direct browser print (thermal).
     */
    public function showReceiptThermalHtml(string $tenantSlug, Sale $sale)
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Ensure this sale belongs to the correct tenant
        if ($sale->tenant_id !== $tenant->id) {
            abort(404);
        }

        $sale->load(['saleItems.product', 'customer', 'user']);
        $formattedDate = (new \DateTime($sale->created_at))->format('d F Y H:i');

        // --- PASS 1: Render dan ukur tinggi konten ---
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt-thermal', [
            'sale' => $sale,
            'tenantName' => $tenant->name,
            'formattedDate' => $formattedDate,
        ]);
        $dompdf = $pdf->getDomPDF();
        $GLOBALS['bodyHeight'] = 0;
        $dompdf->set_callbacks([
            'myCallbacks' => [
                'event' => 'end_frame',
                'f' => function ($frame) {
                    $node = $frame->get_node();
                    if (strtolower($node->nodeName) === "body") {
                        $padding_box = $frame->get_padding_box();
                        $GLOBALS['bodyHeight'] += $padding_box['h'];
                    }
                }
            ]
        ]);
        $dompdf->setPaper([0, 0, 161.417, 2000], 'portrait'); // tinggi besar sementara
        $dompdf->render();
        $height = $GLOBALS['bodyHeight'] + 30; // padding bawah
        unset($dompdf);

        // --- PASS 2: Render ulang dengan tinggi sesuai konten ---
        $pdf2 = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt-thermal', [
            'sale' => $sale,
            'tenantName' => $tenant->name,
            'formattedDate' => $formattedDate,
        ]);
        $pdf2->setPaper([0, 0, 161.417, $height], 'portrait');
        return $pdf2->stream('resi-thermal-' . $sale->invoice_number . '.pdf');
    }

    /**
     * Display the sales history page for the current tenant.
     */
    public function history(string $tenantSlug, Request $request): Response
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Ensure the logged-in user has access to this tenant
        if (Auth::user()->tenant_id !== $tenant->id) {
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        // Default sorting and pagination
        $sortBy = $request->input('sortBy', 'created_at'); // Default sort by creation date
        $sortDirection = $request->input('sortDirection', 'desc'); // Default sort descending
        $perPage = $request->input('perPage', 10); // Default items per page
        $search = $request->input('search'); // Search term for invoice number or customer name

        $salesQuery = Sale::where('tenant_id', $tenant->id)
            ->with(['customer', 'user']); // Eager load customer and user

        // Apply search filter
        if ($search) {
            $salesQuery->where(function ($query) use ($search) {
                $query->where('invoice_number', 'ILIKE', '%' . $search . '%')
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'ILIKE', '%' . $search . '%');
                    });
            });
        }

        // Apply sorting
        $salesQuery->orderBy($sortBy, $sortDirection);

        // Get paginated results
        $sales = $salesQuery->paginate($perPage)->withQueryString();

        return Inertia::render('Cashier/SalesHistory', [
            'sales' => $sales,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => (int)$perPage,
                'search' => $search,
            ],
            'tenantSlug' => $tenantSlug,
            'tenantName' => $tenant->name,
        ]);
    }

    /**
     * Handle iPaymu return URL after payment.
     * This is a GET request from iPaymu after user completes/cancels payment.
     */
    public function ipaymuReturn(string $tenantSlug, Sale $sale): RedirectResponse // Renamed method
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        Log::info('iPaymu Return URL hit for Sale ID: ' . $sale->id . ' with status: ' . $sale->status);

        // Redirect to the receipt page with a success message.
        // IMPORTANT: Do NOT update payment status here. Status updates must be done via notifyUrl (webhook).
        return redirect()->route('sales.receipt', ['tenantSlug' => $tenantSlug, 'sale' => $sale->id])
            ->with('success', 'Transaksi Anda sedang diproses. Status akan diperbarui setelah konfirmasi pembayaran.');
    }

    /**
     * Handle iPaymu cancel URL.
     * This is a GET request from iPaymu if user cancels payment.
     */
    public function ipaymuCancel(string $tenantSlug, Sale $sale): RedirectResponse // Renamed method
    {
        $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

        // Update sale status to 'cancelled' if it's not already 'completed'
        if ($sale->status !== 'completed') {
            $sale->update(['status' => 'cancelled', 'notes' => 'Pembayaran dibatalkan oleh pengguna.']);
        }
        Log::info('iPaymu Cancel URL hit for Sale ID: ' . $sale->id);

        return redirect()->route('sales.order', ['tenantSlug' => $tenant->slug])
            ->with('error', 'Pembayaran dibatalkan.');
    }

    /**
     * Handle iPaymu notify URL (webhook).
     * This is a POST request from iPaymu to notify payment status changes.
     * This route should be publicly accessible and not require CSRF token.
     */
    public function ipaymuNotify(Request $request) // Removed string $tenantSlug parameter
    {
        // Log the entire notification request for debugging
        Log::info('iPaymu Notify Callback Received:', $request->all());

        // Get referenceId (our Sale ID) from the request
        $referenceId = $request->input('reference_id');
        $sale = Sale::with('tenant')->find($referenceId); // Eager load tenant relationship

        if (!$sale) {
            Log::warning('iPaymu Notify: Sale not found for referenceId: ' . $referenceId);
            return response()->json(['message' => 'Sale not found'], 404);
        }

        // Now that sale is found, we can get the tenant from the sale model
        $tenant = $sale->tenant;

        if (!$tenant) {
            Log::error('iPaymu Notify: Tenant not found for Sale ID: ' . $sale->id);
            return response()->json(['message' => 'Tenant not found for sale'], 500);
        }

        $ipaymuService = new IpaymuService($tenant); // Instantiate the service with the found tenant

        // --- REMOVED SIGNATURE VERIFICATION BLOCK ---
        // The previous signature verification relied on a 'signature' header
        // which appears to be null based on your logs.
        // We will rely on the checkTransaction API call for robust verification.

        // Get data from notification
        $transactionId = $request->input('trx_id'); // iPaymu Transaction ID
        $status = $request->input('status'); // Payment status from iPaymu (e.g., 'berhasil', 'gagal', 'pending')
        $amount = $request->input('amount'); // Amount paid

        // Check transaction status with iPaymu API for certainty (recommended for IPN)
        try {
            $checkStatusResponse = $ipaymuService->checkTransaction($transactionId);
            $ipaymuActualStatus = $checkStatusResponse['Data']['StatusDesc'] ?? 'unknown';
            $ipaymuActualAmount = $checkStatusResponse['Data']['Amount'] ?? 0;

            Log::info("iPaymu Notify: Transaction {$transactionId} - Actual iPaymu Status: {$ipaymuActualStatus}, Amount: {$ipaymuActualAmount}");

            // Update sale status based on iPaymu's actual status
            if ($ipaymuActualStatus === 'Berhasil') {
                if ($sale->status !== 'completed') {
                    $sale->update([
                        'status' => 'completed',
                        'paid_amount' => $ipaymuActualAmount, // Use actual amount from iPaymu check
                        'change_amount' => $ipaymuActualAmount - $sale->total_amount,
                        'notes' => 'Pembayaran berhasil via iPaymu (TRX ID: ' . $transactionId . ')',
                    ]);
                    Log::info('iPaymu Notify: Sale ID ' . $sale->id . ' updated to completed.');

                    // Log inventory movements for 'out' (sale)
                    $sale->load('saleItems.product'); // Ensure sale items and products are loaded
                    foreach ($sale->saleItems as $saleItem) {
                        Inventory::create([
                            'id' => Str::uuid(),
                            'tenant_id' => $sale->tenant_id,
                            'product_id' => $saleItem->product_id,
                            'quantity_change' => -$saleItem->quantity, // Use quantity_change for stock reduction
                            'type' => 'out',
                            'reason' => 'Penjualan iPaymu: ' . $sale->invoice_number,
                            'user_id' => $sale->user_id, // User yang membuat penjualan
                            'cost_price_at_movement' => $saleItem->product->cost_price, // Use product's current cost price
                            'related_sale_item_id' => $saleItem->id,
                        ]);
                        Log::info('iPaymu Notify: Inventory "out" logged for product ' . $saleItem->product_id . ' (Sale ID: ' . $sale->id . ')');
                    }

                    // Update or create Payment record
                    $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
                    $payment->fill([
                        'tenant_id' => $sale->tenant_id,
                        'payment_method' => 'ipaymu',
                        'amount' => $ipaymuActualAmount,
                        'currency' => 'IDR',
                        'status' => 'completed',
                        'gateway_response' => $checkStatusResponse,
                        'notes' => 'Pembayaran iPaymu (IPN)',
                    ]);
                    $payment->save();
                    Log::info('iPaymu Notify: Payment record for Sale ID ' . $sale->id . ' updated/created as completed.');
                }
            } elseif ($ipaymuActualStatus === 'Gagal') {
                if ($sale->status !== 'failed' && $sale->status !== 'completed') {
                    $sale->update([
                        'status' => 'failed',
                        'notes' => 'Pembayaran gagal via iPaymu (TRX ID: ' . $transactionId . ')',
                    ]);
                    Log::info('iPaymu Notify: Sale ID ' . $sale->id . ' updated to failed.');
                    // Update Payment record
                    $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
                    $payment->fill([
                        'tenant_id' => $sale->tenant_id,
                        'payment_method' => 'ipaymu',
                        'amount' => $ipaymuActualAmount,
                        'currency' => 'IDR',
                        'status' => 'failed',
                        'gateway_response' => $checkStatusResponse,
                        'notes' => 'Pembayaran iPaymu gagal (IPN)',
                    ]);
                    $payment->save();
                }
            } elseif ($ipaymuActualStatus === 'Pending') {
                if ($sale->status !== 'pending' && $sale->status !== 'completed') {
                    $sale->update([
                        'status' => 'pending',
                        'notes' => 'Pembayaran pending via iPaymu (TRX ID: ' . $transactionId . ')',
                    ]);
                    Log::info('iPaymu Notify: Sale ID ' . $sale->id . ' updated to pending.');
                    // Update Payment record
                    $payment = Payment::firstOrNew(['transaction_id' => $transactionId, 'sale_id' => $sale->id]);
                    $payment->fill([
                        'tenant_id' => $sale->tenant_id,
                        'payment_method' => 'ipaymu',
                        'amount' => $ipaymuActualAmount,
                        'currency' => 'IDR',
                        'status' => 'pending',
                        'gateway_response' => $checkStatusResponse,
                        'notes' => 'Pembayaran iPaymu pending (IPN)',
                    ]);
                    $payment->save();
                }
            } else {
                Log::warning('iPaymu Notify: Unknown status from checkTransaction for Sale ID ' . $sale->id . ': ' . $ipaymuActualStatus);
            }

        } catch (\Exception $e) {
            Log::error('iPaymu Notify: Error checking transaction status: ' . $e->getMessage(), ['exception' => $e, 'transaction_id' => $transactionId]);
            return response()->json(['message' => 'Error processing notification: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Notification received and processed'], 200);
    }
}
