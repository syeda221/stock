<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\User;
use App\Models\StockInItem;
use App\Models\StockOutItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard overview with inbound/outbound tracking.
     */
    public function index(Request $request)
    {
        // Basic counts
        $stats = [
            'products' => Product::count(),
            'warehouses' => Warehouse::count(),
            'vendors' => Vendor::count(),
            'customers' => Customer::count(),
            'users' => User::count(),
        ];

        // Inbound tracking
        $inboundCount = StockIn::count();
        $inboundItems = StockInItem::count();
        $inboundTotal = StockInItem::sum('total_quantity') ?? 0;
        
        // Outbound tracking
        $outboundCount = StockOut::count();
        $outboundItems = StockOutItem::count();
        $outboundTotal = StockOutItem::sum('dispatch_quantity') ?? 0;

        // Total tracking
        $totalTransactions = $inboundCount + $outboundCount;
        $totalItems = $inboundItems + $outboundItems;
        $totalQty = \App\Models\StockInItem::sum('balance_quantity') ?? 0;

        // Recent inbound (last 5)
        $recentInbound = StockIn::with('warehouse', 'vendor')
            ->latest()
            ->take(5)
            ->get();

        // Recent outbound (last 5)
        $recentOutbound = StockOut::with('warehouse', 'customer')
            ->latest()
            ->take(5)
            ->get();

        // Last 7 days activity (for line chart)
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $inCount = StockIn::whereDate('created_at', $date)->count();
            $outCount = StockOut::whereDate('created_at', $date)->count();
            
            $last7Days[] = [
                'date' => $date->format('D'),
                'inbound' => $inCount,
                'outbound' => $outCount,
            ];
        }

        // Alerts
        $lowStockItems = StockInItem::where('balance_quantity', '>', 0)
            ->where('balance_quantity', '<=', 10)
            ->with('product', 'warehouse')
            ->take(5)
            ->get();

        $expiringSoon = StockInItem::where('balance_quantity', '>', 0)
            ->where('expiry_date', '>=', Carbon::today())
            ->where('expiry_date', '<=', Carbon::today()->addDays(30))
            ->with('product', 'warehouse')
            ->take(5)
            ->get();

        $expired = StockInItem::where('balance_quantity', '>', 0)
            ->where('expiry_date', '<', Carbon::today())
            ->count();

        $qcPending = StockInItem::where('quality_clearance', 'pending')->count();

        $lowStockCount = StockInItem::where('balance_quantity', '>', 0)
            ->where('balance_quantity', '<=', 10)
            ->count();

        $expiringCount = StockInItem::where('balance_quantity', '>', 0)
            ->where('expiry_date', '>=', Carbon::today())
            ->where('expiry_date', '<=', Carbon::today()->addDays(30))
            ->count();

        // Top vendors
        $topVendors = Vendor::withCount('stockIns')
            ->orderBy('stock_ins_count', 'desc')
            ->take(5)
            ->get();

        // Top customers
        $topCustomers = Customer::withCount('stockOuts')
            ->orderBy('stock_outs_count', 'desc')
            ->take(5)
            ->get();

        // Warehouse capacity overview
        $warehouseCapacity = Warehouse::withCount('rows')
            ->get()
            ->map(function ($w) {
                $used = \App\Models\StockInItem::where('warehouse_id', $w->id)
                    ->where('balance_quantity', '>', 0)
                    ->sum('pallets_used');
                $total = $w->rows->sum('pallet_capacity');
                return [
                    'name' => $w->name,
                    'used' => (int) $used,
                    'total' => (int) ($total ?: 100),
                    'pct' => $total > 0 ? round(min(($used / $total) * 100, 100)) : 0,
                ];
            })
            ->sortByDesc('pct')
            ->take(5)
            ->values();

        // Monthly comparison
        $now = Carbon::now();
        $currentMonthIn = StockIn::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();
        $currentMonthOut = StockOut::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();
        $lastMonthIn = StockIn::whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)->count();
        $lastMonthOut = StockOut::whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)->count();

        // Stock by warehouse (balance distribution)
        $stockByWarehouse = Warehouse::all()->map(function ($w) {
            $qty = StockInItem::where('warehouse_id', $w->id)
                ->where('balance_quantity', '>', 0)
                ->sum('balance_quantity');
            return ['name' => $w->name, 'qty' => (int) $qty];
        })->filter(fn($i) => $i['qty'] > 0)->sortByDesc('qty')->values();

        // Product categories breakdown
        $categoryBreakdown = \App\Models\ProductCategory::withCount('products')
            ->get()->filter(fn($c) => $c->products_count > 0);

        // Top products by stock quantity
        $topProducts = StockInItem::where('balance_quantity', '>', 0)
            ->selectRaw('product_id, sum(balance_quantity) as total_qty')
            ->groupBy('product_id')
            ->with('product')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get()
            ->map(fn($i) => ['name' => $i->product->name ?? 'N/A', 'qty' => (int) $i->total_qty]);

        // Recent activity feed (last 10 inbound + outbound, merged by date)
        $recentFeed = collect();
        $recentFeed = $recentFeed->merge(
            StockIn::with('warehouse')->latest()->take(10)->get()->map(fn($s) => [
                'type' => 'inbound',
                'ref' => $s->inbound_invoice_no ?? 'N/A',
                'location' => $s->warehouse->name ?? '',
                'date' => $s->created_at,
            ])
        )->merge(
            StockOut::with('warehouse')->latest()->take(10)->get()->map(fn($s) => [
                'type' => 'outbound',
                'ref' => $s->dispatched_invoice_no ?? 'N/A',
                'location' => $s->warehouse->name ?? '',
                'date' => $s->created_at,
            ])
        )->sortByDesc('date')->take(10)->values();

        return view('dashboard', compact(
            'stats',
            'inboundCount',
            'inboundItems',
            'inboundTotal',
            'outboundCount',
            'outboundItems',
            'outboundTotal',
            'totalTransactions',
            'totalItems',
            'totalQty',
            'recentInbound',
            'recentOutbound',
            'last7Days',
            'lowStockItems',
            'expiringSoon',
            'expired',
            'qcPending',
            'lowStockCount',
            'expiringCount',
            'topVendors',
            'topCustomers',
            'warehouseCapacity',
            'currentMonthIn',
            'currentMonthOut',
            'lastMonthIn',
            'lastMonthOut',
            'stockByWarehouse',
            'categoryBreakdown',
            'topProducts',
            'recentFeed'
        ));
    }

    public function notifications()
    {
        $lowStock = StockInItem::where('balance_quantity', '>', 0)
            ->where('balance_quantity', '<=', 10)->count();

        $expiring = StockInItem::where('balance_quantity', '>', 0)
            ->where('expiry_date', '>=', Carbon::today())
            ->where('expiry_date', '<=', Carbon::today()->addDays(30))->count();

        $expired = StockInItem::where('balance_quantity', '>', 0)
            ->where('expiry_date', '<', Carbon::today())->count();

        $qcPending = StockInItem::where('quality_clearance', 'pending')->count();

        return response()->json([
            'low_stock' => $lowStock,
            'expiring' => $expiring,
            'expired' => $expired,
            'qc_pending' => $qcPending,
            'has_alerts' => ($lowStock + $expiring + $expired + $qcPending) > 0,
        ]);
    }
}
