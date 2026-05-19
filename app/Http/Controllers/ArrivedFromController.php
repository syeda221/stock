<?php

namespace App\Http\Controllers;

use App\Models\ArrivedFrom;
use Illuminate\Http\Request;

class ArrivedFromController extends Controller
{
    public function index(Request $request)
    {
        $query = ArrivedFrom::query();

        // Apply search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $arrivedFroms = $query->orderBy('name')->get();
        return view('arrived_from.index', compact('arrivedFroms'));
    }

    public function create()
    {
        return view('arrived_from.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:arrived_froms,name',
        ]);

        ArrivedFrom::create([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('arrived-from.index')
            ->with('success', 'Arrived From added successfully');
    }

    public function edit(ArrivedFrom $arrivedFrom)
    {
        return view('arrived_from.edit', compact('arrivedFrom'));
    }

    public function update(Request $request, ArrivedFrom $arrivedFrom)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:arrived_froms,name,' . $arrivedFrom->id,
        ]);

        $arrivedFrom->update([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('arrived-from.index')
            ->with('success', 'Arrived From updated successfully');
    }

    public function destroy(ArrivedFrom $arrivedFrom)
    {
        $arrivedFrom->delete();

        return redirect()->route('arrived-from.index')
            ->with('success', 'Arrived From deleted successfully');
    }
}
