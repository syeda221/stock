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
        $thirtyDays = Carbon::today()->addMonths(3);

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

    public function toggleSale(StockInItem $item)
    {
        $item->allow_expired_sale = !$item->allow_expired_sale;
        $item->save();

        $status = $item->allow_expired_sale ? 'allowed' : 'blocked';
        return back()->with('success', "Sale of expired stock {$status} for '{$item->product?->name}'.");
    }
}
