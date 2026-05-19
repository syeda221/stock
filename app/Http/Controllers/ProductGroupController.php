<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductGroup::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $groups = $query->orderBy('name')->get();
        return view('product_group.index', compact('groups'));
    }

    public function create()
    {
        return view('product_group.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        ProductGroup::create([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('product-group.index')
            ->with('success', 'Product group added successfully');
    }

    public function edit(ProductGroup $productGroup)
    {
        return view('product_group.edit', compact('productGroup'));
    }

    public function update(Request $request, ProductGroup $productGroup)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $productGroup->update([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('product-group.index')
            ->with('success', 'Product group updated successfully');
    }

    public function destroy(ProductGroup $productGroup)
    {
        $productGroup->delete();

        return redirect()->route('product-group.index')
            ->with('success', 'Product group deleted successfully');
    }
}
