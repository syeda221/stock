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
            if ($request->capacity_mode === 'row') {
                $total = 0;
                $submittedRowIds = [];

                if ($request->rows) {
                    foreach ($request->rows as $row) {
                        if (!empty($row['row_name']) && !empty($row['pallet_capacity'])) {
                            if (!empty($row['id'])) {
                                // Update existing row
                                $existingRow = WarehouseRow::where('id', $row['id'])
                                    ->where('warehouse_id', $warehouse->id)
                                    ->first();
                                
                                if ($existingRow) {
                                    $existingRow->update([
                                        'row_name' => $row['row_name'],
                                        'pallet_capacity' => $row['pallet_capacity'],
                                    ]);
                                    $submittedRowIds[] = $existingRow->id;
                                }
                            } else {
                                // Create new row
                                $newRow = WarehouseRow::create([
                                    'warehouse_id' => $warehouse->id,
                                    'row_name' => $row['row_name'],
                                    'pallet_capacity' => $row['pallet_capacity'],
                                ]);
                                $submittedRowIds[] = $newRow->id;
                            }

                            $total += (int) $row['pallet_capacity'];
                        }
                    }
                }

                // Delete rows that were removed
                $warehouse->rows()->whereNotIn('id', $submittedRowIds)->delete();
                $warehouse->update(['total_capacity' => $total]);
            }

            // MANUAL MODE
            if ($request->capacity_mode === 'manual') {
                // Remove old rows if switching from row mode to manual mode
                $warehouse->rows()->delete();
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
                $items = StockInItem::with('product')
                    ->where('warehouse_id', $warehouse->id)
                    ->where('balance_quantity', '>', 0)
                    ->get();
                $usedPallets = $items->sum(fn($i) => StockInItem::computeActivePallets($i));
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
            $items = StockInItem::with('product')
                ->where('warehouse_row_id', $row->id)
                ->where('balance_quantity', '>', 0)
                ->get();
            $usedPallets = $items->sum(fn($i) => StockInItem::computeActivePallets($i));
            $row->used_pallets = (int) $usedPallets;
            $row->free_pallets = $row->pallet_capacity
                ? max(0, $row->pallet_capacity - $usedPallets)
                : null;
            $row->is_full = $row->free_pallets !== null && $row->free_pallets === 0;
            return $row;
        });

        // Add virtual row for unassigned stock
        $unassignedItems = StockInItem::with('product')
            ->where('warehouse_id', $warehouse->id)
            ->whereNull('warehouse_row_id')
            ->where('balance_quantity', '>', 0)
            ->get();
            
        if ($unassignedItems->isNotEmpty()) {
            $usedPallets = $unassignedItems->sum(fn($i) => StockInItem::computeActivePallets($i));
            $rows->push((object)[
                'id' => 'unassigned',
                'row_name' => 'Unassigned Stock (No Row)',
                'pallet_capacity' => $usedPallets,
                'used_pallets' => (int) $usedPallets,
                'free_pallets' => 0,
                'is_full' => true,
            ]);
        }

        return response()->json($rows);
    }

    public function getPallets($rowId)
    {
        if ($rowId === 'unassigned') {
            $items = StockInItem::with('product')
                ->whereNull('warehouse_row_id')
                ->where('balance_quantity', '>', 0)
                ->orderBy('id')
                ->get();
            $totalCapacity = $items->sum(fn($i) => StockInItem::computeActivePallets($i));
            $row = (object)['pallet_capacity' => $totalCapacity, 'row_name' => 'Unassigned Stock'];
        } else {
            $row = WarehouseRow::findOrFail($rowId);
            $row->load('warehouse');
            $items = StockInItem::with('product')
                ->where('warehouse_row_id', $row->id)
                ->where('balance_quantity', '>', 0)
                ->orderBy('pallet_start')
                ->orderBy('id')
                ->get();
        }

        $totalCapacity = $row->pallet_capacity;
        $occupied = [];
        $cumulativeOffset = 0;

        foreach ($items as $item) {
            $product = $item->product;
            $maxPerPallet = $product->cartons_per_pallet ?? null;

            if ($item->pallet_start !== null) {
                $start = $item->pallet_start;
            } else {
                $start = $cumulativeOffset + 1;
            }

            $activePallets = StockInItem::computeActivePallets($item);

            $end = $start + $activePallets - 1;
            $packSize = $item->pack_size_snapshot > 0 ? $item->pack_size_snapshot : 1;
            $maxPerPalletInUnits = $maxPerPallet ? $maxPerPallet * $packSize : null;
            $totalCapacityCheckInUnits = $maxPerPalletInUnits ? $item->pallets_used * $maxPerPalletInUnits : null;
            $itemIsOverCapacity = $totalCapacityCheckInUnits && $item->balance_quantity > $totalCapacityCheckInUnits;

            $currentPalletsArr = $item->getPalletBalances();

            foreach ($currentPalletsArr as $idx => $qty) {
                if ($qty <= 0) continue;
                $i = $start + $idx;

                $cartonQtyDisplay = $maxPerPallet ? $qty / $item->pack_size_snapshot : $qty;

                $occupied[$i] = [
                    'pallet_number' => $i,
                    'product_name' => $product->name ?? '-',
                    'item_code' => $product->item_code ?? '-',
                    'carton_qty' => round($cartonQtyDisplay, 2),
                    'carton_capacity' => $maxPerPallet,
                    'is_empty' => false,
                    'is_over_capacity' => $itemIsOverCapacity,
                ];
            }

            $blockStart = $item->pallet_start !== null ? $item->pallet_start : $start;
            if ($item->pallets_used > 0) {
                $blockEnd = $blockStart + $item->pallets_used - 1;
                if ($blockEnd > $cumulativeOffset) {
                    $cumulativeOffset = $blockEnd;
                }
            }
        }

        $palletData = [];
        $maxRenderPallet = max($totalCapacity, max(array_keys($occupied) ?: [0]));
        
        for ($i = 1; $i <= $maxRenderPallet; $i++) {
            if (isset($occupied[$i])) {
                $palletData[] = $occupied[$i];
            } else {
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
        }

        $totalActive = count($occupied);

        return response()->json([
            'row' => $row,
            'pallets' => $palletData,
            'total_capacity' => $totalCapacity,
            'used' => $totalActive,
            'empty' => $totalCapacity - $totalActive,
        ]);
    }

    public function exportPdf()
    {
        $warehouses = Warehouse::with('rows')
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($warehouse) {
                $items = StockInItem::with('product')
                    ->where('warehouse_id', $warehouse->id)
                    ->where('balance_quantity', '>', 0)
                    ->get();

                $byRow = $items->groupBy('warehouse_row_id');

                $usedPallets = $items->sum(fn($i) => StockInItem::computeActivePallets($i));
                $warehouse->used_pallets = (int) $usedPallets;
                $warehouse->free_pallets = $warehouse->total_capacity
                    ? max(0, $warehouse->total_capacity - $usedPallets)
                    : null;
                $warehouse->is_full = $warehouse->free_pallets !== null && $warehouse->free_pallets === 0;

                $warehouse->rows->each(function ($row) use ($byRow) {
                    $rowItems = $byRow->get($row->id, collect());
                    $used = $rowItems->sum(fn($i) => StockInItem::computeActivePallets($i));
                    $row->used_pallets = (int) $used;
                    $row->free_pallets = $row->pallet_capacity
                        ? max(0, $row->pallet_capacity - $used)
                        : null;
                });

                // Add virtual row for unassigned stock in PDF
                $unassignedItems = $byRow->get('', collect()); // null keys in groupBy are cast to empty string
                if ($unassignedItems->isNotEmpty()) {
                    $used = $unassignedItems->sum(fn($i) => StockInItem::computeActivePallets($i));
                    $warehouse->rows->push((object)[
                        'id' => 'unassigned',
                        'row_name' => 'Unassigned Stock (No Row)',
                        'pallet_capacity' => $used,
                        'used_pallets' => (int) $used,
                        'free_pallets' => 0,
                        'is_full' => true,
                    ]);
                }

                return $warehouse;
            });

        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf') || class_exists('PDF')) {
            try {
                $pdf = \PDF::loadView('reports.pdf.warehouse-details', compact('warehouses'));
                $pdf->setPaper('a4', 'landscape');
                return $pdf->download('warehouse-capacity-' . date('Ymd') . '.pdf');
            } catch (\Throwable $e) {
                \Log::error('PDF export failed: ' . $e->getMessage());
                return back()->with('error', 'PDF generation failed. Please try again.');
            }
        }

        return back()->with('error', 'PDF library not available.');
    }
}

