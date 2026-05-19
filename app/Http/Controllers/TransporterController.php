<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
use Illuminate\Http\Request;

class TransporterController extends Controller
{
    public function index(Request $request)
    {
        $query = Transporter::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transporters = $query->orderBy('name')->get();
        return view('transporter.index', compact('transporters'));
    }

    public function create()
    {
        return view('transporter.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:transporters,name',
        ]);

        Transporter::create([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('transporter.index')
            ->with('success', 'Transporter added successfully');
    }

    public function edit(Transporter $transporter)
    {
        return view('transporter.edit', compact('transporter'));
    }

    public function update(Request $request, Transporter $transporter)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:transporters,name,' . $transporter->id,
        ]);

        $transporter->update([
            'name' => $request->name,
            'status' => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('transporter.index')
            ->with('success', 'Transporter updated successfully');
    }

    public function destroy(Transporter $transporter)
    {
        $transporter->delete();

        return redirect()->route('transporter.index')
            ->with('success', 'Transporter deleted successfully');
    }
}
