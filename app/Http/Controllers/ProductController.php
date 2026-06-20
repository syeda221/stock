<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use App\Models\Uom;
use App\Models\PackingType;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category','group','uom','packingType']);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        // Apply category filter
        if ($request->filled('category_id')) {
            $query->where('product_category_id', $request->category_id);
        }

        // Apply group filter
        if ($request->filled('group_id')) {
            $query->where('product_group_id', $request->group_id);
        }

        // Apply UOM filter
        if ($request->filled('uom_id')) {
            $query->where('uom_id', $request->uom_id);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('name')->paginate(20);

        return view('product.index', compact('products'));
    }

    public function create()
    {
        return view('product.create', [
            'categories' => ProductCategory::where('status',1)->orderBy('name')->get(),
            'groups' => ProductGroup::where('status',1)->orderBy('name')->get(),
            'uoms' => Uom::where('status',1)->orderBy('name')->get(),
            'packingTypes' => PackingType::where('status',1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_code' => 'required|unique:products,item_code',
            'name' => 'required|string|max:255',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_group_id' => 'required|exists:product_groups,id',
            'uom_id' => 'required|exists:uoms,id',
            'packing_type_id' => 'required|exists:packing_types,id',
            'pack_size' => 'required|integer|min:1',
            'pallets_per_packing' => 'nullable|integer|min:1',
        ]);

        Product::create([
            'item_code' => $request->item_code,
            'name' => $request->name,
            'product_category_id' => $request->product_category_id,
            'product_group_id' => $request->product_group_id,
            'uom_id' => $request->uom_id,
            'packing_type_id' => $request->packing_type_id,
            'pack_size' => $request->pack_size,
            'cartons_per_pallet' => $request->cartons_per_pallet ?: null,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('product.index')
            ->with('success', 'Product added successfully');
    }

    public function edit(Product $product)
    {
        return view('product.edit', [
            'product' => $product,
            'categories' => ProductCategory::where('status',1)->orderBy('name')->get(),
            'groups' => ProductGroup::where('status',1)->orderBy('name')->get(),
            'uoms' => Uom::where('status',1)->orderBy('name')->get(),
            'packingTypes' => PackingType::where('status',1)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'item_code' => 'required|unique:products,item_code,' . $product->id,
            'name' => 'required|string|max:255',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_group_id' => 'required|exists:product_groups,id',
            'uom_id' => 'required|exists:uoms,id',
            'packing_type_id' => 'required|exists:packing_types,id',
            'pack_size' => 'required|integer|min:1',
            'pallets_per_packing' => 'nullable|integer|min:1',
        ]);

        $product->update([
            'item_code' => $request->item_code,
            'name' => $request->name,
            'product_category_id' => $request->product_category_id,
            'product_group_id' => $request->product_group_id,
            'uom_id' => $request->uom_id,
            'packing_type_id' => $request->packing_type_id,
            'pack_size' => $request->pack_size,
            'cartons_per_pallet' => $request->cartons_per_pallet ?: null,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('product.index')
            ->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('product.index')
            ->with('success', 'Product deleted successfully');
    }

    public function export()
    {
        $products = Product::with(['category', 'group', 'uom', 'packingType'])
            ->orderBy('name')->get();

        $filename = 'products_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Item Code', 'Name', 'Category', 'Group', 'UOM',
                'Packing Type', 'Pack Size', 'Cartons Per Pallet', 'Status'
            ]);

            foreach ($products as $p) {
                fputcsv($file, [
                    $p->item_code,
                    $p->name,
                    $p->category?->name ?? '',
                    $p->group?->name ?? '',
                    $p->uom?->name ?? '',
                    $p->packingType?->name ?? '',
                    $p->pack_size,
                    $p->cartons_per_pallet ?? '',
                    $p->status ? 'Active' : 'Inactive',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $filename = 'product_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Item Code', 'Name', 'Category', 'Group', 'UOM',
                'Packing Type', 'Pack Size', 'Cartons Per Pallet', 'Status'
            ]);

            fputcsv($file, [
                'PRD001', 'Sample Product', 'Raw Material', 'Group A', 'Kg',
                'Bag', '25', '40', 'Active'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        return view('product.import');
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            return back()->with('error', 'Could not read CSV headers.');
        }

        // Strip BOM and trim headers
        $headers = array_map(function ($h) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', $h));
        }, $rawHeaders);

        $expectedHeaders = ['Item Code', 'Name', 'Category', 'Group', 'UOM', 'Packing Type', 'Pack Size', 'Cartons Per Pallet', 'Status'];

        $headerMap = [];
        foreach ($expectedHeaders as $h) {
            $pos = array_search($h, $headers);
            if ($pos === false) {
                // Case-insensitive fallback
                $pos = array_search(strtolower($h), array_map('strtolower', $headers));
            }
            if ($pos === false) {
                fclose($handle);
                return back()->with('error', 'Missing column "' . $h . '". Found: ' . implode(', ', $headers));
            }
            $headerMap[$h] = $pos;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Cache lookups: name => id
        $catMap = \App\Models\ProductCategory::pluck('id', 'name')->toArray();
        $grpMap = \App\Models\ProductGroup::pluck('id', 'name')->toArray();
        $uomMap = \App\Models\Uom::pluck('id', 'name')->toArray();
        $pkMap  = \App\Models\PackingType::pluck('id', 'name')->toArray();

        $existingCodes = Product::pluck('item_code')->toArray();

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) <= 1 && ($row[0] === null || trim($row[0]) === '')) {
                continue;
            }

            $data = [];
            foreach ($headerMap as $field => $index) {
                $data[$field] = trim($row[$index] ?? '');
            }

            $itemCode       = $data['Item Code'];
            $name           = $data['Name'];
            $categoryName   = $data['Category'];
            $groupName      = $data['Group'];
            $uomName        = $data['UOM'];
            $packingName    = $data['Packing Type'];
            $packSize       = $data['Pack Size'];
            $cartonsPerPallet = $data['Cartons Per Pallet'];
            $statusStr      = $data['Status'];

            $rowErrors = [];

            if (empty($itemCode))          $rowErrors[] = 'Missing Item Code';
            if (empty($name))              $rowErrors[] = 'Missing Name';

            if (!empty($itemCode) && in_array($itemCode, $existingCodes)) {
                $rowErrors[] = "Item Code '{$itemCode}' already exists";
            }

            $catId = $catMap[$categoryName] ?? null;
            $grpId = $grpMap[$groupName] ?? null;
            $uomId = $uomMap[$uomName] ?? null;
            $pkId  = $pkMap[$packingName] ?? null;

            if (!$catId) $rowErrors[] = "Category '{$categoryName}' not found";
            if (!$grpId) $rowErrors[] = "Group '{$groupName}' not found";
            if (!$uomId) $rowErrors[] = "UOM '{$uomName}' not found";
            if (!$pkId)  $rowErrors[] = "Packing Type '{$packingName}' not found";

            if (!empty($rowErrors)) {
                $errors[] = "Row {$rowNum}: " . implode('; ', $rowErrors);
                $skipped++;
                continue;
            }

            try {
                Product::create([
                    'item_code'         => $itemCode,
                    'name'              => $name,
                    'product_category_id' => $catId,
                    'product_group_id'    => $grpId,
                    'uom_id'            => $uomId,
                    'packing_type_id'   => $pkId,
                    'pack_size'         => max((int) ($packSize ?: 1), 1),
                    'cartons_per_pallet' => is_numeric($cartonsPerPallet) ? (int) $cartonsPerPallet : null,
                    'status'            => strtolower($statusStr) === 'active' ? 1 : 0,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($handle);

        if ($imported > 0) {
            $message = "Imported {$imported} product(s).";
        } else {
            $message = "No products were imported.";
        }

        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        if (count($errors) > 0) {
            $message .= " Details: " . implode(' | ', $errors);
        }

        return redirect()->route('product.index')
            ->with('success', $message);
    }
}
