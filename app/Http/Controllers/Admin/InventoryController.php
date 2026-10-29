<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
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
        $products = Product::where('status', 'published')->get();

        return view('admin.inventory.import', compact('warehouses', 'variants', 'products'));
    }

    /**
     * Process import
     */
    public function import(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id', 'items' => 'required|array|min:1|max:100',
            'items.*.product_variant_id' => 'required|integer|distinct|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1|max:2147483647',
            'items.*.unit_cost' => 'nullable|numeric|min:0|decimal:0,2',
            'notes' => 'nullable|string|max:2000', 'operation_key' => 'required|uuid',
        ]);

        return $this->mutate('import', $request, $data, 'admin.inventory.transactions', function ($key, $data, $actor) {
            $results = [];
            foreach ($data['items'] as $item) {
                $results[] = $this->inventoryService->import($data['warehouse_id'], $item['product_variant_id'], $item['quantity'],
                    $item['unit_cost'] ?? null, $data['notes'] ?? null, $actor, $key.':'.$item['product_variant_id']);
            }

            return $results;
        });
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
        $data = $request->validate([
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1|max:100', 'items.*.product_variant_id' => 'required|integer|distinct|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1|max:2147483647', 'notes' => 'nullable|string|max:2000', 'operation_key' => 'required|uuid',
        ]);

        return $this->mutate('transfer', $request, $data, 'admin.inventory.transactions', function ($key, $data, $actor) {
            $results = [];
            foreach ($data['items'] as $item) {
                $results[] = $this->inventoryService->transfer($data['from_warehouse_id'], $data['to_warehouse_id'], $item['product_variant_id'],
                    $item['quantity'], $data['notes'] ?? null, $actor, $key.':'.$item['product_variant_id']);
            }

            return $results;
        });
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
            'creator',
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
        $data = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id', 'product_variant_id' => 'required|integer|exists:product_variants,id',
            'actual_quantity' => 'required|integer|min:0|max:2147483647', 'reason' => 'required|string|max:255', 'operation_key' => 'required|uuid',
        ]);

        return $this->mutate('adjust', $request, $data, null, fn ($key, $data, $actor) => [
            $this->inventoryService->adjust($data['warehouse_id'], $data['product_variant_id'], $data['actual_quantity'], $data['reason'], $actor, $key),
        ]);
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
        $data = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id', 'items' => 'required|array|min:1|max:100',
            'items.*.product_variant_id' => 'required|integer|distinct|exists:product_variants,id', 'items.*.quantity' => 'required|integer|min:1|max:2147483647',
            'reference_type' => 'nullable|string|max:255', 'reference_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000', 'operation_key' => 'required|uuid',
        ]);

        return $this->mutate('export', $request, $data, 'admin.inventory.transactions', function ($key, $data, $actor) {
            $results = [];
            foreach ($data['items'] as $item) {
                $results[] = $this->inventoryService->export($data['warehouse_id'], $item['product_variant_id'], $item['quantity'],
                    $data['reference_type'] ?? null, null, $data['notes'] ?? null, $actor, $key.':'.$item['product_variant_id'], $data['reference_code'] ?? null);
            }

            return $results;
        });
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
        $data = $request->validate([
            'adjustments' => 'required|array|min:1|max:500', 'adjustments.*.inventory_id' => 'required|integer|distinct|exists:inventory,id',
            'adjustments.*.actual_quantity' => 'required|integer|min:0|max:2147483647', 'operation_key' => 'required|uuid',
        ]);

        return $this->mutate('stocktake', $request, $data, 'admin.inventory.stocktake', function ($key, $data, $actor) {
            $stocks = Inventory::whereIn('id', array_column($data['adjustments'], 'inventory_id'))->get()->keyBy('id');
            $this->inventoryService->lockWarehouses($stocks->pluck('warehouse_id')->all());
            $results = [];
            foreach ($data['adjustments'] as $adjustment) {
                $stock = $stocks->get($adjustment['inventory_id']);
                $results[] = $this->inventoryService->adjust($stock->warehouse_id, $stock->product_variant_id, $adjustment['actual_quantity'],
                    'Kiểm kê định kỳ', $actor, $key.':'.$stock->id);
            }

            return $results;
        });
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
        ) as recent_transactions'), function ($join) {
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
        $filename = 'inventory_'.date('Y-m-d').'.csv';
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
                    $item->location,
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
                        'variant' => $item->productVariant->color.' - '.$item->productVariant->storage,
                        'sku' => $item->productVariant->sku,
                        'image' => asset('storage/'.$item->productVariant->thumbnail),
                        'quantity' => $item->quantity,
                        'available_quantity' => $item->available_quantity,
                        'unit_cost' => $item->productVariant->cost_price ?? 0,
                    ],
                ];
            });

        return response()->json($inventory);
    }

    private function mutate(string $kind, Request $request, array $payload, ?string $route, callable $work)
    {
        if ($request->boolean('save_progress') || $request->boolean('is_draft') || $request->input('action') === 'draft') {
            throw \Illuminate\Validation\ValidationException::withMessages(['action' => 'Lưu nháp chưa được hỗ trợ. Chưa thay đổi tồn kho; chỉ gửi khi muốn thực thi.']);
        }
        $key = $payload['operation_key'];
        unset($payload['operation_key']);
        if (isset($payload['items'])) {
            usort($payload['items'], fn ($a, $b) => $a['product_variant_id'] <=> $b['product_variant_id']);
        }
        if (isset($payload['adjustments'])) {
            usort($payload['adjustments'], fn ($a, $b) => $a['inventory_id'] <=> $b['inventory_id']);
        }
        ksort($payload);
        try {
            $result = $this->inventoryService->batch($kind, $request->user()->id, $key, $payload,
                fn ($rootKey) => $work($rootKey, $payload, $request->user()->id));
            $discrepancy = collect($result['lines'])->contains(fn ($line) => ($line['discrepancy_id'] ?? null) !== null);
            $redirect = $route ? redirect()->route($route) : back();

            return $redirect->with($discrepancy ? 'warning' : 'success', $discrepancy
                ? 'Đã ghi nhận chênh lệch kiểm kê. Số dư được giữ nguyên để đối soát.' : 'Cập nhật kho thành công');
        } catch (\App\Exceptions\InventoryConflict $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage(), 'code' => $exception->reasonCode],
                    $exception->reasonCode === 'forbidden_actor' ? 403 : 409);
            }

            return back()->with('error', $exception->getMessage())->withInput();
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Không thể cập nhật kho. Mã tra cứu: '.$request->attributes->get('request_id'))->withInput();
        }
    }
}
