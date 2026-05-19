<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with('rows');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        // Apply capacity mode filter
        if ($request->filled('capacity_mode')) {
            $query->where('capacity_mode', $request->capacity_mode);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $warehouses = $query->orderBy('name')->get();
        return view('warehouse.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouse.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'capacity_mode' => 'required|in:manual,row',
            'manual_capacity' => 'nullable|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {

            $warehouse = Warehouse::create([
                'name' => $request->name,
                'city' => $request->city,
                'location' => $request->location,
                'capacity_mode' => $request->capacity_mode,
                'manual_capacity' => $request->capacity_mode === 'manual'
                    ? $request->manual_capacity
                    : null,
                'total_capacity' => 0,
                'status' => $request->has('status') ? 1 : 0,
            ]);

            // ROW MODE
            if ($request->capacity_mode === 'row' && $request->rows) {
                $total = 0;

                foreach ($request->rows as $row) {
                    if (!empty($row['row_name']) && !empty($row['pallet_capacity'])) {
                        WarehouseRow::create([
                            'warehouse_id' => $warehouse->id,
                            'row_name' => $row['row_name'],
                            'pallet_capacity' => $row['pallet_capacity'],
                        ]);

                        $total += (int) $row['pallet_capacity'];
                    }
                }

                $warehouse->update(['total_capacity' => $total]);
            }

            // MANUAL MODE
            if ($request->capacity_mode === 'manual') {
                $warehouse->update([
                    'total_capacity' => $request->manual_capacity,
                ]);
            }
        });

        return redirect()->route('warehouse.index')
            ->with('success', 'Warehouse created successfully');
    }

    public function edit(Warehouse $warehouse)
    {
        $warehouse->load('rows');
        return view('warehouse.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'capacity_mode' => 'required|in:manual,row',
            'manual_capacity' => 'nullable|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $warehouse) {

            // Remove old rows
            $warehouse->rows()->delete();

            $warehouse->update([
                'name' => $request->name,
                'city' => $request->city,
                'location' => $request->location,
                'capacity_mode' => $request->capacity_mode,
                'manual_capacity' => $request->capacity_mode === 'manual'
                    ? $request->manual_capacity
                    : null,
                'total_capacity' => 0,
                'status' => $request->has('status') ? 1 : 0,
            ]);

            // ROW MODE
            if ($request->capacity_mode === 'row' && $request->rows) {
                $total = 0;

                foreach ($request->rows as $row) {
                    if (!empty($row['row_name']) && !empty($row['pallet_capacity'])) {
                        WarehouseRow::create([
                            'warehouse_id' => $warehouse->id,
                            'row_name' => $row['row_name'],
                            'pallet_capacity' => $row['pallet_capacity'],
                        ]);

                        $total += (int) $row['pallet_capacity'];
                    }
                }

                $warehouse->update(['total_capacity' => $total]);
            }

            // MANUAL MODE
            if ($request->capacity_mode === 'manual') {
                $warehouse->update([
                    'total_capacity' => $request->manual_capacity,
                ]);
            }
        });

        return redirect()->route('warehouse.index')
            ->with('success', 'Warehouse updated successfully');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('warehouse.index')
            ->with('success', 'Warehouse deleted successfully');
    }
}

