<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Ordering\Contracts\AfterSales;
use App\SharedKernel\ActorContext;
use Illuminate\Http\Request;

final class AfterSalesController extends Controller
{
    public function show(Request $request, int $order, AfterSales $service)
    {
        $history = $service->history($order, new ActorContext($request->user()->id));

        return $request->expectsJson() ? response()->json($history)
            : view('admin.orders.after-sales', ['history' => $history, 'order' => \App\Models\Order::with('items')->findOrFail($order)]);
    }

    public function money(Request $request, int $order, AfterSales $service)
    {
        $data = $request->validate([
            'operation_key' => 'required|string|max:128', 'action' => 'required|in:collect_cod,request_refund,settle_refund,reject_refund',
            'amount' => 'required_if:action,collect_cod,request_refund|integer|min:1|max:9999999999',
            'refund_id' => 'required_if:action,settle_refund,reject_refund|integer|min:1',
            'reference' => 'required_if:action,collect_cod,settle_refund|nullable|string|max:128',
            'reason' => 'required_unless:action,collect_cod|nullable|string|max:500',
        ]);
        $key = $data['operation_key'];
        unset($data['operation_key']);

        $result = $service->money($order, new ActorContext($request->user()->id), $key, $data);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Đã ghi nhận kết quả đối soát tiền.');
    }

    public function receive(Request $request, int $order, AfterSales $service)
    {
        $data = $request->validate([
            'operation_key' => 'required|string|max:128', 'order_item_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:10000', 'disposition' => 'required|in:sellable,damaged',
            'reference' => 'required|string|max:128', 'reason' => 'required|string|max:500',
        ]);
        $key = $data['operation_key'];
        unset($data['operation_key']);

        $result = $service->receive($order, new ActorContext($request->user()->id), $key, $data);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Đã ghi nhận biên nhận hàng trả.');
    }
}
