<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();

        // Apply search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->orderBy('name')->get();
        return view('vendor.index', compact('vendors'));
    }

    public function create()
    {
        return view('vendor.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:vendors,name',
        ]);

        Vendor::create([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('vendor.index')
            ->with('success', 'Vendor added successfully');
    }

    public function edit(Vendor $vendor)
    {
        return view('vendor.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:vendors,name,' . $vendor->id,
        ]);

        $vendor->update([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('vendor.index')
            ->with('success', 'Vendor updated successfully');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendor.index')
            ->with('success', 'Vendor deleted successfully');
    }
}
