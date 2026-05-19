<?php

namespace App\Http\Controllers;

use App\Models\Uom;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index(Request $request)
    {
        $query = Uom::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $uoms = $query->orderBy('name')->paginate(20);
        return view('uom.index', compact('uoms'));
    }

    public function create()
    {
        return view('uom.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:50',
    ]);

    Uom::create([
        'name' => $request->name,
        'status' => $request->has('status') ? 1 : 0,
    ]);

    return redirect()->route('uom.index')
        ->with('success', 'UOM added successfully');
}
public function edit(Uom $uom)
{
    return view('uom.edit', compact('uom'));
}

public function update(Request $request, Uom $uom)
{
    $request->validate([
        'name' => 'required|string|max:50',
    ]);

    $uom->update([
        'name' => $request->name,
        'status' => $request->has('status') ? 1 : 0,
    ]);

    return redirect()->route('uom.index')
        ->with('success', 'UOM updated successfully');
}

public function destroy(Uom $uom)
{
    $uom->delete();

    return redirect()->route('uom.index')
        ->with('success', 'UOM deleted successfully');
}

}
