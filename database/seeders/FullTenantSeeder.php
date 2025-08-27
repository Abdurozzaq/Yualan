<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Voucher;
use App\Models\Promo;
use App\Models\Payment;
use App\Models\Inventory;
use App\Models\Table;
use App\Models\Supplier;
use App\Models\PricingPlan;
use App\Models\SaasSetting;
use App\Models\SaasInvoice;

class FullTenantSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Demo Tenant',
            'invitation_code' => 'INV-DEMO-001',
            'slug' => 'demo-tenant',
            'email' => 'demo@tenant.com',
            'business_type' => 'retail',
            'is_active' => true,
            'is_subscribed' => true,
            'subscription_ends_at' => '2030-12-01 07:21:45'
        ]);

        // User
        $user = User::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Admin Tenant',
            'email' => 'admin@tenant.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Category
        $category = Category::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Minuman',
        ]);

        // Product
        $product = Product::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Teh Botol',
            'sku' => 'TB001',
            'price' => 5000,
            'stock' => 100,
        ]);

        // Customer
        $customer = Customer::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Budi',
        ]);

        // Sale
        $sale = Sale::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001',
            'total_amount' => 5000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 5000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
            'created_at' => now(),
        ]);

        // Sale Item
        SaleItem::create([
            'id' => (string) Str::uuid(),
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 5000,
            'subtotal' => 5000,
        ]);

        // Voucher
        Voucher::create([
            'tenant_id' => $tenant->id,
            'code' => 'DISC10',
            'name' => 'Diskon 10%',
            'type' => 'percentage',
            'value' => 10,
            'expiry_date' => now()->addMonth(),
        ]);

        // Promo
        Promo::create([
            'code' => 'PROMO1',
            'name' => 'Beli 2 Gratis 1',
            'type' => 'buyxgetx',
            'buy_qty' => 2,
            'get_qty' => 1,
            'product_id' => $product->id,
            'expiry_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        // Payment
        Payment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 5000,
            'currency' => 'IDR',
            'status' => 'completed',
        ]);

        // Inventory
        Inventory::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'quantity_change' => 100,
            'type' => 'in',
            'reason' => 'Initial stock',
            'cost_per_unit' => 4000,
        ]);

        // Supplier
        Supplier::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Supplier A',
        ]);
    }
}