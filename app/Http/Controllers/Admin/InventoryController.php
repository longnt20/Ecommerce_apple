<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Dashboard overview
     */
    public function dashboard()
    {
        $totalValue = Inventory::join('product_variants', 'inventory.product_variant_id', '=', 'product_variants.id')
            ->sum(DB::raw('inventory.quantity * product_variants.cost_price'));

        $lowStockCount = Inventory::lowStock()->count();
        $outOfStockCount = Inventory::outOfStock()->count();
        $lowStockItems = Inventory::lowStock()
            ->with(['productVariant.product', 'warehouse'])
            ->limit(10)
            ->get();
        $recentTransactions = InventoryTransaction::with(['productVariant.product', 'warehouse'])
            ->latest()
            ->take(10)
            ->get();

        $warehouses = Warehouse::withCount('inventory')->active()->get();

        return view('admin.inventory.dashboard', compact(
            'totalValue',
            'lowStockCount',
            'outOfStockCount',
            'recentTransactions',
            'warehouses',
            'lowStockItems'
        ));
    }

    /**
     * Stock listing
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['warehouse', 'productVariant.product']);

        // Filters
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->status == 'low_stock') {
            $query->lowStock();
        } elseif ($request->status == 'out_of_stock') {
            $query->outOfStock();
        }

        if ($request->search) {
            $query->whereHas('productVariant', function ($q) use ($request) {
                $q->where('sku', 'like', "%{$request->search}%");
            })->orWhereHas('productVariant.product', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        $inventory = $query->paginate(10);
        $warehouses = Warehouse::active()->get();

        return view('admin.inventory.index', compact('inventory', 'warehouses'));
    }

    /**
     * Import form
     */
    public function importForm()
    {
        $warehouses = Warehouse::active()->get();
        $variants = ProductVariant::with('product')->get();

        return view('admin.inventory.import', compact('warehouses', 'variants'));
    }

    /**
     * Process import
     */
    public function import(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'nullable|numeric|min:0'
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                $this->inventoryService->import(
                    $request->warehouse_id,
                    $item['product_variant_id'],
                    $item['quantity'],
                    $item['unit_cost'] ?? null,
                    $request->notes
                );
            }
        });

        return redirect()->route('admin.inventory.transactions')
            ->with('success', 'Nhập kho thành công');
    }

    /**
     * Transfer form
     */
    public function transferForm()
    {
        $warehouses = Warehouse::active()->get();

        return view('admin.inventory.transfer', compact('warehouses'));
    }

    /**
     * Process transfer
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                $this->inventoryService->transfer(
                    $request->from_warehouse_id,
                    $request->to_warehouse_id,
                    $item['product_variant_id'],
                    $item['quantity'],
                    $request->notes
                );
            }

            DB::commit();
            return redirect()->route('admin.inventory.transactions')
                ->with('success', 'Chuyển kho thành công');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Transactions history
     */
    public function transactions(Request $request)
    {
        $query = InventoryTransaction::with([
            'warehouse',
            'productVariant.product',
            'fromWarehouse',
            'toWarehouse',
            'creator'
        ]);

        // Filters
        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(20);
        $warehouses = Warehouse::active()->get();

        return view('admin.inventory.transactions', compact('transactions', 'warehouses'));
    }

    /**
     * Stock adjustment
     */
    public function adjust(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'actual_quantity' => 'required|integer|min:0',
            'reason' => 'required|string'
        ]);

        try {
            $this->inventoryService->adjust(
                $request->warehouse_id,
                $request->product_variant_id,
                $request->actual_quantity,
                $request->reason
            );

            return redirect()->back()->with('success', 'Điều chỉnh tồn kho thành công');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    /**
     * Export form
     */
    public function exportForm()
    {
        $warehouses = Warehouse::active()->get();
        $variants = ProductVariant::with('product')
            ->whereHas('inventory', function ($q) {
                $q->where('available_quantity', '>', 0);
            })
            ->get();

        return view('admin.inventory.export', compact('warehouses', 'variants'));
    }

    /**
     * Process export
     */
    public function export(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reference_type' => 'nullable|string',
            'reference_code' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->items as $item) {
                    $this->inventoryService->export(
                        $request->warehouse_id,
                        $item['product_variant_id'],
                        $item['quantity'],
                        $request->reference_type,
                        null, // reference_id
                        $request->notes
                    );
                }
            });

            return redirect()->route('admin.inventory.transactions')
                ->with('success', 'Xuất kho thành công');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Stock take form
     */
    public function stocktake()
    {
        $warehouses = Warehouse::active()->get();
        $inventory = Inventory::with(['productVariant.product', 'warehouse'])
            ->when(request('warehouse_id'), function ($q) {
                $q->where('warehouse_id', request('warehouse_id'));
            })
            ->paginate(20);

        return view('admin.inventory.stocktake', compact('warehouses', 'inventory'));
    }

    /**
     * Process stock take
     */
    public function processStocktake(Request $request)
    {
        $request->validate([
            'adjustments' => 'required|array',
            'adjustments.*.inventory_id' => 'required|exists:inventory,id',
            'adjustments.*.actual_quantity' => 'required|integer|min:0'
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->adjustments as $adjustment) {
                $inventory = Inventory::find($adjustment['inventory_id']);

                if ($inventory->quantity != $adjustment['actual_quantity']) {
                    $this->inventoryService->adjust(
                        $inventory->warehouse_id,
                        $inventory->product_variant_id,
                        $adjustment['actual_quantity'],
                        'Kiểm kê định kỳ'
                    );
                }
            }
        });

        return redirect()->route('admin.inventory.stocktake')
            ->with('success', 'Cập nhật kiểm kê thành công');
    }

    /**
     * Reports dashboard
     */
    public function reports()
{
    // Stock value by warehouse - Tính giá trị kho với price từ product_variants
    $stockByWarehouse = Warehouse::select('warehouses.*')
        ->selectRaw('COALESCE(SUM(inventory.quantity), 0) as total_quantity')
        ->selectRaw('COALESCE(SUM(inventory.quantity * product_variants.cost_price), 0) as total_value')
        ->leftJoin('inventory', 'warehouses.id', '=', 'inventory.warehouse_id')
        ->leftJoin('product_variants', 'inventory.product_variant_id', '=', 'product_variants.id')
        ->groupBy('warehouses.id')
        ->get();

    // Top moving products
    $topMoving = InventoryTransaction::select('product_variant_id')
        ->selectRaw('SUM(ABS(quantity)) as movement')
        ->where('type', 'export')
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('product_variant_id')
        ->orderByDesc('movement')
        ->with(['productVariant.product'])
        ->limit(10)
        ->get();

    // Slow moving products - Cải thiện query
    $slowMoving = Inventory::select('inventory.*')
        ->selectRaw('COALESCE(recent_transactions.movement, 0) as recent_movement')
        ->leftJoin(DB::raw('(
            SELECT product_variant_id, warehouse_id, SUM(ABS(quantity)) as movement
            FROM inventory_transactions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY product_variant_id, warehouse_id
        ) as recent_transactions'), function($join) {
            $join->on('inventory.product_variant_id', '=', 'recent_transactions.product_variant_id')
                 ->on('inventory.warehouse_id', '=', 'recent_transactions.warehouse_id');
        })
        ->where('inventory.quantity', '>', 0)
        ->having('recent_movement', '=', 0)
        ->with(['productVariant.product', 'warehouse'])
        ->limit(10)
        ->get();

    // Monthly transactions
    $monthlyTransactions = InventoryTransaction::selectRaw('
        DATE_FORMAT(created_at, "%Y-%m") as month,
        type,
        COUNT(*) as count,
        SUM(ABS(quantity)) as total_quantity
    ')
        ->where('created_at', '>=', now()->subMonths(6))
        ->groupBy('month', 'type')
        ->orderBy('month')
        ->get();

    // Additional metrics
    $totalProducts = ProductVariant::count();
    $totalWarehouses = Warehouse::count();
    $lowStockCount = Inventory::where('quantity', '<=', 10)->count();
    $totalStockValue = DB::table('inventory')
        ->join('product_variants', 'inventory.product_variant_id', '=', 'product_variants.id')
        ->sum(DB::raw('inventory.quantity * product_variants.cost_price'));

    return view('admin.inventory.report', compact(
        'stockByWarehouse',
        'topMoving',
        'slowMoving',
        'monthlyTransactions',
        'totalProducts',
        'totalWarehouses',
        'lowStockCount',
        'totalStockValue'
    ));
}

    /**
     * Export inventory to Excel
     */
    public function exportExcel(Request $request)
    {
        $inventory = Inventory::with(['productVariant.product', 'warehouse'])
            ->when($request->warehouse_id, function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            })
            ->when($request->status == 'low_stock', function ($q) {
                $q->lowStock();
            })
            ->when($request->status == 'out_of_stock', function ($q) {
                $q->outOfStock();
            })
            ->get();

        // You can use Laravel Excel package or simple CSV export
        $filename = 'inventory_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['SKU', 'Sản phẩm', 'Kho', 'Tồn kho', 'Có thể bán', 'Đã giữ', 'Vị trí'];

        $callback = function () use ($inventory, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($inventory as $item) {
                fputcsv($file, [
                    $item->productVariant->sku,
                    $item->productVariant->product->name,
                    $item->warehouse->name,
                    $item->quantity,
                    $item->available_quantity,
                    $item->reserved_quantity,
                    $item->location
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Settings page
     */
    public function settings()
    {
        $settings = [
            'low_stock_threshold' => config('inventory.low_stock_threshold', 10),
            'auto_reserve' => config('inventory.auto_reserve', true),
            'negative_stock_allowed' => config('inventory.negative_stock_allowed', false),
        ];

        return view('admin.inventory.settings', compact('settings'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:0',
            'auto_reserve' => 'boolean',
            'negative_stock_allowed' => 'boolean',
        ]);

        // Save settings to database or config
        // You might want to create a settings table for this

        return redirect()->route('admin.inventory.settings')
            ->with('success', 'Cài đặt đã được cập nhật');
    }

    /**
     * Get inventory for API (for AJAX requests)
     */
    public function getWarehouseInventory($warehouseId)
    {
        $inventory = Inventory::where('warehouse_id', $warehouseId)
            ->where('available_quantity', '>', 0)
            ->with(['productVariant.product'])
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->product_variant_id => [
                        'name' => $item->productVariant->product->name,
                        'variant' => $item->productVariant->color . ' - ' . $item->productVariant->storage,
                        'sku' => $item->productVariant->sku,
                        'image' => asset('storage/' . $item->productVariant->product->thumbnail),
                        'quantity' => $item->quantity,
                        'available_quantity' => $item->available_quantity,
                        'unit_cost' => $item->productVariant->cost_price ?? 0
                    ]
                ];
            });

        return response()->json($inventory);
    }
}
