<?php

namespace App\Http\Controllers;

use App\Models\StockInItem;
use Illuminate\Http\Request;

class QcStatusController extends Controller
{
    /**
     * Update QC status for a stock in item
     */
    public function update(Request $request, StockInItem $item)
    {
        $request->validate([
            'quality_clearance' => 'required|in:pending,approved,rejected'
        ]);

        $item->quality_clearance = $request->quality_clearance;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'QC status updated successfully',
            'quality_clearance' => $item->quality_clearance
        ]);
    }
}

