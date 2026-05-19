<?php

namespace App\Http\Controllers;

use App\Models\PackingType;
use Illuminate\Http\Request;

class PackingTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = PackingType::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $packingTypes = $query->orderBy('name')->get();
        return view('packing_type.index', compact('packingTypes'));
    }

    public function create()
    {
        return view('packing_type.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ]);

        PackingType::create([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('packing-type.index')
            ->with('success', 'Packing Type added successfully');
    }

    public function edit(PackingType $packingType)
    {
        return view('packing_type.edit', compact('packingType'));
    }

    public function update(Request $request, PackingType $packingType)
    {
        $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $packingType->update([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('packing-type.index')
            ->with('success', 'Packing Type updated successfully');
    }

    public function destroy(PackingType $packingType)
    {
        $packingType->delete();

        return redirect()->route('packing-type.index')
            ->with('success', 'Packing Type deleted successfully');
    }
}
