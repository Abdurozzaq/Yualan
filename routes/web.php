<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TenantLinkController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperadminDashboardController;
use App\Http\Controllers\Superadmin\PricingPlanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\SaasInvoiceController;
use App\Http\Controllers\SaasInvoiceHistoryController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\MidtransController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\PricingPlan;
use App\Models\SaasSetting;
use App\Http\Controllers\EmployeeController;
use App\Models\Tenant;

Route::get('/', function () {
    $plans = PricingPlan::query()
        ->select(['id', 'plan_name', 'plan_description', 'period_type', 'price', 'discount_percentage'])
        ->orderBy('period_type')
        ->orderBy('price')
        ->get();

    $trialDays = SaasSetting::get('trial_days', 'INTERNAL');

    return Inertia::render('Welcome', [
        'pricingPlans' => $plans,
        'trialDays' => $trialDays,
    ]);
})->name('home');

// Midtrans Payment
Route::post('/{tenantSlug}/midtrans/pay', [MidtransController::class, 'pay'])->name('midtrans.pay');
Route::post('/midtrans/callback', [SaleController::class, 'midtransNotify'])->name('midtrans.callback');
// Informational pages (FAQ, Terms, Refund)
Route::get('faq', function () {
    return Inertia::render('Faq');
})->name('faq');

Route::get('terms', function () {
    return Inertia::render('Terms');
})->name('terms');

Route::get('refund', function () {
    return Inertia::render('Refund');
})->name('refund');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {

    /**
     * START
     * ############### AUTH AREA ###############
     */
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::post('/tenant/link', [TenantLinkController::class, 'link'])
        ->name('tenant.link');
    /**
     * END
     * ############### AUTH AREA ###############
     */


    /**
     * START
     * ############### DASHBOARD REDIRECTION LOGIC ###############
     * This route is crucial for directing users after login/registration.
     */
    Route::get('/dashboard', function () {
        // Eager load the tenant relationship to ensure it's available
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user) {
            // Ensure $user is an Eloquent model instance
            $user = \App\Models\User::find($user->id);
            $user->load('tenant');
        }

        // Prioritize redirect for Superadmin
        if ($user && $user->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard');
        }
        // Redirect for regular tenant users
        if ($user && $user->tenant_id && ($tenant = $user->tenant) && $tenant->is_active) {
            return redirect()->route('tenant.dashboard', ['tenantSlug' => $tenant->slug]);
        }
        // Fallback for users not assigned to any active tenant
        return Inertia::render('TenantUnassigned');
    })->name('dashboard.default');
    /**
     * END
     * ############### DASHBOARD REDIRECTION LOGIC ###############
     */


    /**
     * START
     * ############### SUPERADMIN AREA ###############
     * Moved above USER AREA to prevent route conflicts.
     */

    // Rute khusus untuk Superadmin Dashboard
    Route::get('/superadmin/dashboard', [SuperadminDashboardController::class, 'index'])
        ->middleware(['superadmin.access'])->name('superadmin.dashboard');

    // Superadmin Pricing Plans CRUD
    Route::middleware(['superadmin.access'])->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('pricing', [PricingPlanController::class, 'index'])->name('pricing.index');
        Route::post('pricing', [PricingPlanController::class, 'store'])->name('pricing.store');
        Route::put('pricing/{pricing}', [PricingPlanController::class, 'update'])->name('pricing.update');
        Route::delete('pricing/{pricing}', [PricingPlanController::class, 'destroy'])->name('pricing.destroy');

        Route::get('settings', [\App\Http\Controllers\Superadmin\SaasSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Superadmin\SaasSettingsController::class, 'store'])->name('settings.store');
    });


    /**
     * END
     * ############### SUPERADMIN AREA ###############
     */


    /**
     * START
     * ############### USER AREA ###############
     */

    // Rute baru untuk user yang belum terhubung ke tenant
    Route::get('/tenant-unassigned', function () {
        return Inertia::render('TenantUnassigned');
    })->name('tenant.unassigned');

    Route::get('/{tenantSlug}/dashboard', [DashboardController::class, 'index'])
        ->middleware(['tenant.access', 'check.subscription'])->name('tenant.dashboard');

    // Lightweight JSON endpoint for fetching tenant info (used by sidebar via AJAX)
    Route::get('/{tenantSlug}/tenant/info', [TenantController::class, 'info'])
        ->middleware(['tenant.access'])
        ->name('tenant.info');

    Route::resource('{tenantSlug}/categories', CategoryController::class)
        ->middleware(['tenant.access', 'check.subscription']);

    Route::resource('{tenantSlug}/products', ProductController::class)
        ->middleware(['tenant.access', 'check.subscription']);

    // Master Customers routes, tenant-scoped
    Route::resource('{tenantSlug}/customers', CustomerController::class)
        ->middleware(['tenant.access', 'check.subscription']); // Apply tenant access middleware

    // Route for exporting customer ID card
    Route::get('{tenantSlug}/customers/{customer}/id-card', [CustomerController::class, 'exportIdCard'])
        ->name('customers.idCard')
        ->middleware(['tenant.access', 'check.subscription']);

    // Rute untuk Sales/Pemesanan dan Riwayat
    Route::prefix('{tenantSlug}')->middleware(['tenant.access', 'check.subscription'])->group(function () {
        Route::get('sales/order', [SaleController::class, 'order'])->name('sales.order');
        Route::post('sales/store', [SaleController::class, 'store'])->name('sales.store');
        Route::get('sales/receipt/{sale}', [SaleController::class, 'receipt'])->name('sales.receipt');
        Route::get('sales/receipt/{sale}/pdf', [SaleController::class, 'generateReceiptPdf'])->name('sales.receipt.pdf');
        Route::get('sales/history', [SaleController::class, 'history'])->name('sales.history');
        Route::post('sales/{sale}/reinitiate-payment', [SaleController::class, 'reinitiatePayment'])->name('sales.reinitiatePayment');

        // Rute Callback iPaymu untuk return/cancel (masih dalam grup tenantSlug)
        Route::get('sales/ipaymu/return/{sale}', [SaleController::class, 'ipaymuReturn'])->name('sales.ipaymuReturn');
        Route::get('sales/ipaymu/cancel/{sale}', [SaleController::class, 'ipaymuCancel'])->name('sales.ipaymuCancel');
        
        // Report Routes
        Route::prefix('reports')->group(function () { // Removed leading '/'
            Route::get('gross-profit', [ReportController::class, 'grossProfitReport'])->name('reports.grossProfit'); // Removed leading '/'
            Route::get('stock', [ReportController::class, 'stockReport'])->name('reports.stock'); // Removed leading '/'
            Route::get('sales-detail', [\App\Http\Controllers\ReportSalesDetailController::class, 'index'])
                ->name('reports.salesDetail');
        });

        Route::get('/reports/net-profit', [\App\Http\Controllers\Reports\NetProfitController::class, 'index'])->name('reports.netProfit');

        // Master Suppliers routes, tenant-scoped (NEW)
        Route::resource('suppliers', SupplierController::class); // Removed leading '/'

        // Inventory Management Routes
        Route::prefix('inventory')->group(function () { // Removed leading '/'
            Route::get('overview', [InventoryController::class, 'index'])->name('inventory.overview'); // Removed leading '/'
            Route::get('movements', [InventoryController::class, 'movements'])->name('inventory.movements'); // Removed leading '/'
            Route::get('receive', [InventoryController::class, 'receiveGoodsForm'])->name('inventory.receive.form'); // Removed leading '/'
            Route::post('receive', [InventoryController::class, 'receiveGoods'])->name('inventory.receive'); // Removed leading '/'
            Route::get('adjust', [InventoryController::class, 'adjustStockForm'])->name('inventory.adjust.form'); // Removed leading '/'
            Route::post('adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust'); // Removed leading '/'
            // Optional: Route for returns
            Route::get('return', [InventoryController::class, 'returnGoodsForm'])->name('inventory.return.form'); // Removed leading '/'
            Route::post('return', [InventoryController::class, 'returnGoods'])->name('inventory.return'); // Removed leading '/'
        });

        Route::prefix('settings')->middleware(['tenant.access', 'admin.access'])->group(function () {
            Route::get('tenant-info', [TenantSettingsController::class, 'edit'])->name('tenant.settings.info');
            Route::patch('tenant-info', [TenantSettingsController::class, 'update'])->name('tenant.settings.update');
            Route::post('tenant-info/generate-code', [TenantSettingsController::class, 'generateInvitationCode'])->name('tenant.settings.generateInvitationCode');
        });
        
        Route::get('/invoices/history', [SaasInvoiceHistoryController::class, 'index'])->name('invoices.history');
    });

    
    Route::get('/{tenantSlug}/invoice/{id}', [SaasInvoiceController::class, 'show'])->name('invoice.show');

    /**
     * END
     * ############### USER AREA ###############
     */
});

// Subscription routes
Route::middleware('auth')->group(function () {
    Route::get('/subscription/info', [\App\Http\Controllers\SubscriptionController::class, 'info'])->name('subscription.info');
    Route::get('/subscription/payment', [\App\Http\Controllers\SubscriptionController::class, 'payment'])->name('subscription.payment');
    Route::post('/subscription/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::get('/subscription/success', [\App\Http\Controllers\SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/invoice', [\App\Http\Controllers\SubscriptionController::class, 'invoice'])->name('subscription.invoice');
});

// Webhook notification route - must be outside auth and CSRF protection
Route::post('/subscription/notify', [\App\Http\Controllers\SubscriptionController::class, 'notify'])->name('subscription.notify');


// Rute notify Midtrans Snap (webhook) - harus global
Route::post('/sales/midtrans/notify', [SaleController::class, 'midtransNotify'])->name('sales.midtransNotify');
// Get Sale ID by order_id for Midtrans redirect
Route::middleware(['auth', 'tenant.access'])->get('sales/{tenantSlug}/get-sale-id/{orderId}', [\App\Http\Controllers\SaleController::class, 'getSaleIdByOrderId'])->name('sales.getSaleIdByOrderId');

// Rute notify iPaymu (webhook) - DIPINDAHKAN KELUAR DARI GRUP tenantSlug
// Ini harus dapat diakses secara global oleh iPaymu
Route::post('/sales/ipaymu/notify', [SaleController::class, 'ipaymuNotify'])->name('sales.ipaymuNotify');

Route::middleware(['auth', 'tenant.access'])->group(function () {
    Route::get('/{tenantSlug}/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/{tenantSlug}/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::post('/{tenantSlug}/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/{tenantSlug}/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::put('employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
    Route::put('employees/{employee}/change-password', [EmployeeController::class, 'changePassword'])->name('employees.change_password');
});

// Excel import routes
Route::middleware(['auth', 'tenant.access'])->group(function () {
    Route::post('/{tenantSlug}/products/import', [\App\Http\Controllers\ProductImportController::class, 'import'])->name('products.import');
    Route::post('/{tenantSlug}/products/import/error-rows', [\App\Http\Controllers\ProductImportController::class, 'importErrorRows'])->name('products.import.error-rows');
    Route::get('/products/import/sample', [\App\Http\Controllers\ProductImportController::class, 'downloadSample'])->name('products.import.sample');
});

// Laporan Pembayaran & Piutang
Route::middleware(['auth', 'tenant.access'])->group(function () {
    Route::get('/{tenantSlug}/reports/payments', [\App\Http\Controllers\Reports\PaymentsReportController::class, 'index'])
        ->name('reports.payments');
    Route::get('/{tenantSlug}/reports/product-margin', [ReportController::class, 'productMargin'])->name('reports.product-margin');
});




Route::middleware(['auth', 'tenant.access'])->group(function () {
    // Voucher & Promo Management (per tenant)
    Route::resource('{tenantSlug}/vouchers', App\Http\Controllers\VoucherController::class);
    Route::resource('{tenantSlug}/promos', App\Http\Controllers\PromoController::class);

    // Voucher API for order page
    Route::get('/{tenantSlug}/voucher/{code}', [SaleController::class, 'getVoucherByCode'])->name('sales.getVoucherByCode');
    Route::post('/{tenantSlug}/voucher/{code}/use', [SaleController::class, 'useVoucher'])->name('sales.useVoucher');
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


