<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\OrderTransitionConflict;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Modules\Ordering\Contracts\OrderTransitions;
use App\Modules\Ordering\Domain\OrderTransitionPolicy;
use App\SharedKernel\ActorContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::latest()->paginate(10);

        return view('admin.orders.index', [
            'orders' => $orders,
            'orders_total' => Order::count(),
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_shipping' => Order::where('status', 'shipping')->count(),
            'orders_delivered' => Order::where('status', 'completed')->count(),
            'orders_cancelled' => Order::where('status', 'cancelled')->count(),
        ]);
    }

    public function show(Order $order, OrderTransitionPolicy $policy)
    {
        $normalFlow = ['pending', 'confirmed', 'shipping', 'completed'];
        $cancelFlow = ['pending', 'confirmed', 'shipping', 'cancelled'];

        if ($order->status === 'cancelled') {
            // Nếu hủy → chỉ active cancelled
            $activeSteps = ['cancelled'];
        } else {
            // Active từ đầu tới trạng thái hiện tại
            $currentIndex = array_search($order->status, $normalFlow);
            $activeSteps = array_slice($normalFlow, 0, $currentIndex + 1);
        }
        $transitionsEnabled = config('ordering.transitions_enabled') && Schema::hasTable('order_transitions');
        $nextStatuses = $transitionsEnabled ? $policy->next($order->status) : [];
        $history = Schema::hasTable('order_transitions') ? $order->transitions()->get() : collect();

        return view('admin.orders.show', compact('order', 'activeSteps', 'nextStatuses', 'history', 'transitionsEnabled'));
    }

    public function updateStatus(Request $request, Order $order, OrderTransitions $orderService)
    {
        try {
            $request->validate([
                'status' => 'required|in:confirmed,shipping,completed,cancelled',
                'operation_key' => 'nullable|string|max:128',
                'reason' => 'nullable|string|max:500',
            ]);

            // Ủy quyền cho service
            $orderService->change($order->id, $request->status, new ActorContext($request->user()->id),
                $request->input('operation_key'), $request->input('reason'), $request->attributes->get('request_id'));

            return redirect()
                ->to(url()->previous() ?: route('admin.orders.index'))
                ->with('success', 'Cập nhật trạng thái thành công');
        } catch (OrderTransitionConflict $e) {
            $status = $e->reasonCode === 'forbidden_actor' ? 403 : 409;
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage(), 'code' => $e->reasonCode,
                    'request_id' => $request->attributes->get('request_id')], $status);
            }

            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $e;
            }
            report($e);

            return back()->with('error', 'Không thể cập nhật đơn. Mã tra cứu: '.$request->attributes->get('request_id'));
        }
    }
}
