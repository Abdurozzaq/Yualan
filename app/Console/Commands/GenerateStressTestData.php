<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateStressTestData extends Command
{
    protected $signature = 'generate:stress-test-data
        {--tenants=100 : Number of tenants to create}
        {--products=100 : Number of products per tenant}
        {--sales=100 : Number of sales per tenant}
        {--batch-size=1000 : Batch size for bulk inserts}
        {--clean : Clean existing data before generating}';

    protected $description = 'Generate optimized stress test data for Yualan database';

    private $batchSize;
    private $currentTime;

    public function handle()
    {
        $tenantCount = (int) $this->option('tenants');
        $productCount = (int) $this->option('products');
        $salesCount = (int) $this->option('sales');
        $this->batchSize = (int) $this->option('batch-size');
        $this->currentTime = now();

        if ($this->option('clean')) {
            $this->cleanExistingData();
        }

        $this->info("Generating {$tenantCount} tenants with {$productCount} products and {$salesCount} sales each...");
        
        $startTime = microtime(true);
        
        // Generate tenants in batches
        $this->generateTenants($tenantCount);
        
        // Get all tenant IDs for further processing
        $tenantIds = Tenant::pluck('id')->toArray();
        
        // Generate categories, products, and sales for each tenant
        foreach (array_chunk($tenantIds, 10) as $chunk) {
            $this->generateDataForTenantChunk($chunk, $productCount, $salesCount);
        }

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        $this->info("Stress test data generation completed in {$executionTime} seconds!");
    }

    private function cleanExistingData()
    {
        $this->info("Cleaning existing data...");
        
        DB::transaction(function () {
            // Delete in proper order to avoid foreign key constraints
            DB::table('sale_items')->truncate();
            DB::table('payments')->truncate();
            DB::table('inventories')->truncate();
            Sale::truncate();
            Product::truncate();
            DB::table('categories')->truncate();
            DB::table('customers')->truncate();
            DB::table('suppliers')->truncate();
            
            // Clean users except superadmins
            User::where('role', '!=', 'superadmin')->delete();
            
            Tenant::truncate();
        });
        
        $this->info("Existing data cleaned.");
    }

    private function generateTenants($count)
    {
        $this->info("Generating {$count} tenants...");
        
        $tenants = [];
        $users = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $tenantId = Str::uuid()->toString();
            
            $tenants[] = [
                'id' => $tenantId,
                'name' => "Tenant {$i}",
                'invitation_code' => "INVITE" . str_pad($i, 6, '0', STR_PAD_LEFT),
                'slug' => "tenant-{$i}-" . Str::random(5),
                'email' => "tenant{$i}@yualan.test",
                'phone' => '0812345678' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'address' => "Address for Tenant {$i}",
                'city' => "City {$i}",
                'state' => "State {$i}",
                'zip_code' => "1234" . ($i % 10),
                'country' => "Indonesia",
                'business_type' => ['store', 'restaurant', 'service'][($i - 1) % 3],
                'is_active' => true,
                'ipaymu_api_key' => "APIKEY{$i}",
                'ipaymu_secret_key' => "SECRET{$i}",
                'ipaymu_mode' => 'sandbox',
                'subscription_status' => 'trial',
                'is_subscribed' => true,
                'midtrans_is_production' => false,
                'created_at' => $this->currentTime,
                'updated_at' => $this->currentTime,
            ];
            
            $users[] = [
                'id' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'name' => "Admin {$i}",
                'email' => "admin{$i}@tenant{$i}.test",
                'email_verified_at' => $this->currentTime,
                'password' => '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'role' => 'admin',
                'created_at' => $this->currentTime,
                'updated_at' => $this->currentTime,
            ];
            
            // Insert in batches
            if (count($tenants) >= $this->batchSize) {
                DB::table('tenants')->insert($tenants);
                DB::table('users')->insert($users);
                $tenants = [];
                $users = [];
                $this->info("Inserted batch of tenants... ({$i}/{$count})");
            }
        }
        
        // Insert remaining records
        if (!empty($tenants)) {
            DB::table('tenants')->insert($tenants);
            DB::table('users')->insert($users);
        }
        
        $this->info("All {$count} tenants created successfully.");
    }

    private function generateDataForTenantChunk($tenantIds, $productCount, $salesCount)
    {
        foreach ($tenantIds as $tenantId) {
            $this->info("Processing tenant: {$tenantId}");
            
            // Generate categories
            $categoryIds = $this->generateCategories($tenantId);
            
            // Generate products
            $productIds = $this->generateProducts($tenantId, $categoryIds, $productCount);
            
            // Generate sales
            $this->generateSales($tenantId, $productIds, $salesCount);
        }
    }

    private function generateCategories($tenantId)
    {
        $categories = [
            ['id' => Str::uuid()->toString(), 'name' => 'Electronics', 'description' => 'Electronic products'],
            ['id' => Str::uuid()->toString(), 'name' => 'Clothing', 'description' => 'Clothing items'],
            ['id' => Str::uuid()->toString(), 'name' => 'Food & Beverage', 'description' => 'Food and drinks'],
            ['id' => Str::uuid()->toString(), 'name' => 'Books', 'description' => 'Books and publications'],
            ['id' => Str::uuid()->toString(), 'name' => 'Sports', 'description' => 'Sports equipment'],
        ];
        
        $categoryData = [];
        foreach ($categories as $category) {
            $categoryData[] = [
                'id' => $category['id'],
                'tenant_id' => $tenantId,
                'name' => $category['name'],
                'description' => $category['description'],
                'created_at' => $this->currentTime,
                'updated_at' => $this->currentTime,
            ];
        }
        
        DB::table('categories')->insert($categoryData);
        
        return array_column($categories, 'id');
    }

    private function generateProducts($tenantId, $categoryIds, $count)
    {
        $products = [];
        $inventories = [];
        $productIds = [];
        
        // Create a unique prefix for this tenant based on its UUID
        $tenantPrefix = substr(str_replace('-', '', $tenantId), 0, 6);
        
        for ($i = 1; $i <= $count; $i++) {
            $productId = Str::uuid()->toString();
            $productIds[] = $productId;
            $stock = random_int(10, 100);
            $costPrice = random_int(1000, 50000);
            $price = $costPrice + random_int(500, $costPrice * 0.5);
            
            // Generate globally unique SKU using tenant UUID prefix + sequential number + random suffix
            $uniqueSku = "SKU{$tenantPrefix}" . str_pad($i, 4, '0', STR_PAD_LEFT) . strtoupper(Str::random(4));
            
            $products[] = [
                'id' => $productId,
                'tenant_id' => $tenantId,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'name' => "Product {$i}",
                'sku' => $uniqueSku,
                'description' => "Description for product {$i}",
                'price' => $price,
                'stock' => $stock,
                'cost_price' => $costPrice,
                'unit' => ['pcs', 'box', 'kg', 'liter'][array_rand(['pcs', 'box', 'kg', 'liter'])],
                'is_food_item' => random_int(0, 1) === 1,
                'min_stock_level' => random_int(5, 20),
                'created_at' => $this->currentTime,
                'updated_at' => $this->currentTime,
            ];
            
            // Add initial inventory
            $inventories[] = [
                'id' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'quantity_change' => $stock,
                'type' => 'in',
                'reason' => 'Initial stock',
                'cost_per_unit' => $costPrice,
                'created_at' => $this->currentTime,
                'updated_at' => $this->currentTime,
            ];
            
            if (count($products) >= $this->batchSize) {
                DB::table('products')->insert($products);
                DB::table('inventories')->insert($inventories);
                $products = [];
                $inventories = [];
            }
        }
        
        if (!empty($products)) {
            DB::table('products')->insert($products);
            DB::table('inventories')->insert($inventories);
        }
        
        return $productIds;
    }

    private function generateSales($tenantId, $productIds, $count)
    {
        // Get user ID for this tenant
        $userId = User::where('tenant_id', $tenantId)->first()->id;

        $sales = [];
        $saleItems = [];
        $payments = [];

        // Use a counter that is unique per tenant
        $invoiceCounter = 0; 
        $tenantInvoicePrefix = Str::uuid()->toString();

        for ($i = 1; $i <= $count; $i++) {
            $saleId = Str::uuid()->toString();
            $invoiceCounter++;

            // Generate a unique invoice number using the tenant's UUID as a prefix
            $invoiceNumber = "INV-{$tenantInvoicePrefix}-" . str_pad($invoiceCounter, 6, '0', STR_PAD_LEFT);
            
            $saleDate = $this->currentTime->copy()->subDays(random_int(0, 30));

            // Generate sale items
            $itemCount = random_int(1, 5);
            $subtotal = 0;
            $currentSaleItems = [];

            for ($j = 1; $j <= $itemCount; $j++) {
                $productId = $productIds[array_rand($productIds)];
                $quantity = random_int(1, 5);
                $price = random_int(10000, 100000);
                $itemSubtotal = $quantity * $price;
                $subtotal += $itemSubtotal;

                $currentSaleItems[] = [
                    'id' => Str::uuid()->toString(),
                    'sale_id' => $saleId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $itemSubtotal,
                    'cost_price_at_sale' => random_int(5000, $price * 0.8),
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ];
            }
            
            $discountAmount = random_int(0, $subtotal * 0.1);
            $taxAmount = random_int(0, $subtotal * 0.1);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            $paidAmount = $totalAmount;
            $changeAmount = 0;

            $paymentMethods = ['cash', 'ipaymu', 'midtrans'];
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
            $status = $paymentMethod === 'cash' ? 'completed' : ['completed', 'pending'][array_rand(['completed', 'pending'])];

            $sales[] = [
                'id' => $saleId,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'invoice_number' => $invoiceNumber,
                'subtotal_amount' => $subtotal,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'status' => $status,
                'notes' => $status === 'pending' ? 'Waiting for payment' : null,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ];
            
            $saleItems = array_merge($saleItems, $currentSaleItems);
            
            // Add payment record
            $payments[] = [
                'id' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                'sale_id' => $saleId,
                'payment_method' => $paymentMethod,
                'amount' => $totalAmount,
                'currency' => 'IDR',
                'status' => $status === 'completed' ? 'completed' : 'pending',
                'transaction_id' => $status === 'completed' ? 'TRX' . Str::uuid()->getHex() : null,
                'notes' => "Payment for {$invoiceNumber}",
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ];
            
            // Insert in batches
            if (count($sales) >= $this->batchSize / 10) {
                $this->insertSalesBatch($sales, $saleItems, $payments);
                $sales = [];
                $saleItems = [];
                $payments = [];
            }
        }
        
        // Insert remaining records
        if (!empty($sales)) {
            $this->insertSalesBatch($sales, $saleItems, $payments);
        }
    }

    private function insertSalesBatch($sales, $saleItems, $payments)
    {
        DB::transaction(function () use ($sales, $saleItems, $payments) {
            DB::table('sales')->insert($sales);
            DB::table('sale_items')->insert($saleItems);
            DB::table('payments')->insert($payments);
        });
    }
}