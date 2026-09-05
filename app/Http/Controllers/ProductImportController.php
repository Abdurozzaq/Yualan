<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Inventory; // add this import
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ProductImportController extends Controller
{
    public function import(Request $request, $tenantSlug)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'File tidak ditemukan.']);
        }

        $file = $request->file('file');
        $rows = Excel::toArray([], $file)[0] ?? [];
        $rows = array_slice($rows, 1); // Lewati header
        $errorRows = [];
        $totalRows = count($rows);

        foreach ($rows as $index => $row) {
            // Example: adjust column indexes as needed
            $data = [
                'name' => $row[0] ?? null,
                'sku' => $row[1] ?? null,
                'price' => $row[2] ?? null,
                'cost_price' => $row[3] ?? null,
                'stock' => $row[4] ?? null,
                'unit' => $row[5] ?? null,
            ];

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'sku' => 'nullable|string|max:255',
                'price' => 'required|numeric|min:0',
                'cost_price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'unit' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                $errorRows[] = array_merge($data, [
                    'row_number' => $index + 2,
                    'error' => implode(', ', $validator->errors()->all()),
                ]);
                continue;
            }

            // Save product (skip if error)
            try {
                DB::beginTransaction();

                // Restore soft deleted product if exists
                $trashedProduct = Product::withTrashed()
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->where('sku', $data['sku'])
                    ->first();
                if ($trashedProduct && $trashedProduct->trashed()) {
                    $trashedProduct->restore();
                }

                $product = Product::updateOrCreate(
                    [
                        'tenant_id' => $request->user()->tenant_id,
                        'sku' => $data['sku'],
                    ],
                    array_merge($data, [
                        'tenant_id' => $request->user()->tenant_id,
                    ])
                );

                // Inventory movement if stock > 0
                if (isset($data['stock']) && $data['stock'] > 0) {
                    Inventory::create([
                        'tenant_id' => $request->user()->tenant_id,
                        'product_id' => $product->id,
                        'type' => 'in', // assuming 'in' for stock addition
                        'quantity_change' => $data['stock'], // <-- wajib diisi
                        'hpp' => $data['cost_price'],
                        'description' => 'Import produk dari Excel',
                        // add other required fields if needed
                    ]);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errorRows[] = array_merge($data, [
                    'row_number' => $index + 2,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (count($errorRows) > 0) {
            return response()->json([
                'success' => false,
                'error_rows' => $errorRows,
                'total_rows' => $totalRows,
                'message' => 'Beberapa baris gagal diimport.',
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function importErrorRows(Request $request, $tenantSlug)
    {
        $rows = $request->input('rows', []);
        $errorRows = [];
        $totalRows = count($rows);

        foreach ($rows as $row) {
            $data = [
                'name' => $row['name'] ?? null,
                'sku' => $row['sku'] ?? null,
                'price' => $row['price'] ?? null,
                'cost_price' => $row['cost_price'] ?? null,
                'stock' => $row['stock'] ?? null,
                'unit' => $row['unit'] ?? null,
            ];

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'sku' => 'nullable|string|max:255',
                'price' => 'required|numeric|min:0',
                'cost_price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'unit' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                $errorRows[] = array_merge($data, [
                    'row_number' => $row['row_number'] ?? null,
                    'error' => implode(', ', $validator->errors()->all()),
                ]);
                continue;
            }

            try {
                DB::beginTransaction();

                // Restore soft deleted product if exists
                $trashedProduct = Product::withTrashed()
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->where('sku', $data['sku'])
                    ->first();
                if ($trashedProduct && $trashedProduct->trashed()) {
                    $trashedProduct->restore();
                }

                $product = Product::updateOrCreate(
                    [
                        'tenant_id' => $request->user()->tenant_id,
                        'sku' => $data['sku'],
                    ],
                    array_merge($data, [
                        'tenant_id' => $request->user()->tenant_id,
                    ])
                );

                // Inventory movement if stock > 0
                if (isset($data['stock']) && $data['stock'] > 0) {
                    Inventory::create([
                        'tenant_id' => $request->user()->tenant_id,
                        'product_id' => $product->id,
                        'type' => 'in',
                        'quantity_change' => $data['stock'], // <-- wajib diisi
                        'hpp' => $data['cost_price'],
                        'description' => 'Import produk dari Excel',
                        // add other required fields if needed
                    ]);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errorRows[] = array_merge($data, [
                    'row_number' => $row['row_number'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (count($errorRows) > 0) {
            return response()->json([
                'success' => false,
                'error_rows' => $errorRows,
                'total_rows' => $totalRows,
                'message' => 'Beberapa baris gagal diimport.',
            ]);
        }

        return response()->json(['success' => true]);
    }
}

