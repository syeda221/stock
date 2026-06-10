<?php

namespace App\Http\Controllers;

use App\Models\StockInItem;
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

    public function details()
    {
        $warehouses = Warehouse::with('rows')
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($warehouse) {
                $usedPallets = StockInItem::where('warehouse_id', $warehouse->id)
                    ->where('balance_quantity', '>', 0)
                    ->sum('pallets_used');
                $warehouse->used_pallets = (int) $usedPallets;
                $warehouse->free_pallets = $warehouse->total_capacity
                    ? max(0, $warehouse->total_capacity - $usedPallets)
                    : null;
                $warehouse->is_full = $warehouse->free_pallets !== null && $warehouse->free_pallets === 0;
                return $warehouse;
            });
        return view('warehouse.details', compact('warehouses'));
    }

    public function getRows(Warehouse $warehouse)
    {
        $warehouse->load('rows');
        $rows = $warehouse->rows->map(function ($row) {
            $usedPallets = StockInItem::where('warehouse_row_id', $row->id)
                ->where('balance_quantity', '>', 0)
                ->sum('pallets_used');
            $row->used_pallets = (int) $usedPallets;
            $row->free_pallets = $row->pallet_capacity
                ? max(0, $row->pallet_capacity - $usedPallets)
                : null;
            $row->is_full = $row->free_pallets !== null && $row->free_pallets === 0;
            return $row;
        });
        return response()->json($rows);
    }

    public function getPallets(WarehouseRow $row)
    {
        $row->load('warehouse');
        $items = StockInItem::with('product')
            ->where('warehouse_row_id', $row->id)
            ->where('balance_quantity', '>', 0)
            ->orderBy('id')
            ->get();

        $palletData = [];
        $offset = 0;

        foreach ($items as $item) {
            $start = $offset + 1;
            $end = $offset + $item->pallets_used;

            $product = $item->product;
            $maxPerPallet = $product->cartons_per_pallet ?? null;
            $totalCapacity = $maxPerPallet ? $item->pallets_used * $maxPerPallet : null;
            $itemIsOverCapacity = $totalCapacity && $item->units_received > $totalCapacity;

            // Distribute cartons: fill earlier pallets to max, last pallet gets remainder
            $remainingUnits = $item->units_received;

            for ($i = $start; $i <= $end; $i++) {
                if ($maxPerPallet) {
                    $cartonQty = min($maxPerPallet, $remainingUnits);
                    $remainingUnits -= $cartonQty;
                } else {
                    $cartonQty = $item->pallets_used > 0
                        ? round($item->units_received / $item->pallets_used, 2)
                        : $item->units_received;
                }

                $palletData[] = [
                    'pallet_number' => $i,
                    'product_name' => $product->name ?? '-',
                    'item_code' => $product->item_code ?? '-',
                    'carton_qty' => $cartonQty,
                    'carton_capacity' => $maxPerPallet,
                    'is_empty' => false,
                    'is_over_capacity' => $itemIsOverCapacity,
                ];
            }
            $offset = $end;
        }

        $totalCapacity = $row->pallet_capacity;
        for ($i = $offset + 1; $i <= $totalCapacity; $i++) {
            $palletData[] = [
                'pallet_number' => $i,
                'product_name' => null,
                'item_code' => null,
                'carton_qty' => 0,
                'carton_capacity' => null,
                'is_empty' => true,
                'is_over_capacity' => false,
            ];
        }

        return response()->json([
            'row' => $row,
            'pallets' => $palletData,
            'total_capacity' => $totalCapacity,
            'used' => $offset,
            'empty' => $totalCapacity - $offset,
        ]);
    }
}

