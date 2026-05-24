<?php

namespace App\Http\Controllers;

use App\Models\StockInItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpiryController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $thirtyDays = Carbon::today()->addDays(30);

        $expired = StockInItem::with(['product', 'warehouse', 'stockIn'])
            ->where('balance_quantity', '>', 0)
            ->where('expiry_date', '<', $today)
            ->orderBy('expiry_date')
            ->get();

        $expiring = StockInItem::with(['product', 'warehouse', 'stockIn'])
            ->where('balance_quantity', '>', 0)
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $thirtyDays)
            ->orderBy('expiry_date')
            ->get();

        $countExpired = $expired->count();
        $countExpiring = $expiring->count();

        return view('expiry.index', compact('expired', 'expiring', 'countExpired', 'countExpiring', 'today'));
    }
}
