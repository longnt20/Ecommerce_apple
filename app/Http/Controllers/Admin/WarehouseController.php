<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index()
{
    $warehouses = Warehouse::latest('id')->paginate(8);
    
    foreach ($warehouses as $warehouse) {
        // Lấy thống kê từ database
        $stats = DB::table('inventory')
            ->join('product_variants', 'inventory.product_variant_id', '=', 'product_variants.id')
            ->where('inventory.warehouse_id', $warehouse->id)
            ->where('inventory.quantity', '>', 0)
            ->select(
                DB::raw('COUNT(DISTINCT inventory.product_variant_id) as total_products'),
                DB::raw('SUM(inventory.quantity * product_variants.price) as total_value')
            )
            ->first();
        
        $warehouse->total_products = $stats ? $stats->total_products : 0;
        $warehouse->total_value = $stats ? $stats->total_value : 0;
    }

    return view('admin.warehouses.index', compact('warehouses'));
}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'manager_name' => 'required|string',
            'manager_phone' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $validated['is_active'] = true;

        Warehouse::create($validated);

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Kho hàng đã được thêm thành công!');
    }

    public function search(Request $request)
    {
        $query = $request->get('query');
        $type = $request->get('type');

        $warehouses = Warehouse::active()
            ->when($query, function ($q) use ($query) {
                return $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('manager_name', 'like', "%{$query}%");
            })
            ->when($type && $type !== 'All', function ($q) use ($type) {
                return $q->where('type', $type);
            })
            ->paginate(8);

        return response()->json([
            'warehouses' => $warehouses,
            'html' => view('admin.warehouses.partials.warehouse-list', compact('warehouses'))->render()
        ]);
    }
    public function toggleActive($id)
{
    $warehouse = Warehouse::findOrFail($id);
    $warehouse->is_active = !$warehouse->is_active;
    $warehouse->save();

    return response()->json([
        'success' => true,
        'status' => $warehouse->is_active ? 'Hoạt động' : 'Ngừng hoạt động'
    ]);
}
public function edit(Warehouse $warehouse)
{
    return response()->json([
        'success' => true,
        'warehouse' => $warehouse
    ]);
}

public function update(Request $request, Warehouse $warehouse)
{
    $validated = $request->validate([
        'code' => 'required|unique:warehouses,code,' . $warehouse->id,
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'address' => 'required|string',
        'city' => 'required|string',
        'district' => 'required|string',
        'phone' => 'required|string',
        'email' => 'required|email',
        'manager_name' => 'required|string',
        'manager_phone' => 'required|string',
        'is_active' => 'boolean',
        'notes' => 'nullable|string'
    ]);

    $warehouse->update($validated);

    return redirect()->route('admin.warehouses.index')
        ->with('success', 'Kho hàng đã được cập nhật thành công!');
}
}
