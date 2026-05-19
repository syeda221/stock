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
}
