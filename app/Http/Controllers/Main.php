<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Http\Controllers\admin\Category;
use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\Orders;
use App\Models\OrdersDetails;
use App\Models\Promotion;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Main extends Controller
{
    public function index(Request $request)
    {
        $table_id = $request->input('table');
        if ($table_id) {
            $table = Table::where('table_number', $table_id)->first();
            session(['table_id' => $table->id]);
        }
        $promotion = Promotion::where('is_status', 1)->get();
        $category = Categories::has('menu')->with('files')->get();
        return view('users.main_page', compact('category', 'promotion'));
    }

    public function detail($id)
    {
        $menu = Menu::where('categories_id', $id)->with('files', 'option')->orderBy('created_at', 'asc')->get();
        return view('users.detail_page', compact('menu'));
    }

    public function order()
    {
        return view('users.list_page');
    }

    public function SendOrder(Request $request)
    {
        $data = [
            'status' => false,
            'message' => 'สั่งออเดอร์ไม่สำเร็จ',
        ];
        $orderData = $request->input('orderData');
        $remark = $request->input('remark');
        $item = array();
        $total = 0;
        foreach ($orderData as $order) {
            foreach ($order as $rs) {
                $item[] = [
                    'id' => $rs['id'],
                    'price' => $rs['price'],
                    'option' => $rs['option'],
                    'qty' => $rs['qty'],
                ];
                $total = $total + ($rs['price'] * $rs['qty']);
            }
        }

        if (!empty($item)) {
            $order = new Orders();
            $order->table_id = session('table_id') ?? '1';
            $order->total = $total;
            $order->remark = $remark;
            $order->status = 1;
            if ($order->save()) {
                foreach ($item as $rs) {
                    $orderdetail = new OrdersDetails();
                    $orderdetail->order_id = $order->id;
                    $orderdetail->menu_id = $rs['id'];
                    $orderdetail->option_id = $rs['option'];
                    $orderdetail->quantity = $rs['qty'];
                    $orderdetail->price = $rs['price'];
                    $orderdetail->save();
                }
            }
            event(new OrderCreated(['📦 มีออเดอร์ใหม่']));
            $data = [
                'status' => true,
                'message' => 'สั่งออเดอร์เรียบร้อยแล้ว',
            ];
        }
        return response()->json($data);
    }

    public function sendEmp()
    {
        event(new OrderCreated(['ลูกค้าเรียกจากโต้ะที่ ' . session('table_id')]));
    }

    public function orderDetail()
    {
        $orders = Orders::where('table_id', session('table_id') ?? 1)
            ->whereIn('status', [1, 2])
            ->get();
        return view('users.orderDetail', compact('orders'));
    }

    public function listOrderDetailMain(Request $request)
    {
        $orderId = $request->input('id');
        $orders = OrdersDetails::select('menu_id')
            ->where('order_id', $orderId)
            ->groupBy('menu_id')
            ->get();

        $info = '';

        if ($orders->count() > 0) {
            foreach ($orders as $value) {
                $orderDetails = OrdersDetails::where('order_id', $orderId)
                    ->where('menu_id', $value->menu_id)
                    ->with('menu', 'option')
                    ->get();

                $menuName = $orderDetails[0]->menu->name ?? 'ไม่ทราบชื่อเมนู';

                $info .= '<div class="card shadow-sm mb-3">';
                $info .= '<div class="card-header bg-primary text-white fw-bold">ออเดอร์ #' . $orderId . '</div>';
                $info .= '<div class="card-body">';

                foreach ($orderDetails as $detail) {
                    $optionType = $detail->option->type ?? '-';
                    $menuName = $detail->menu->name ?? '-';
                    $quantity = $detail->quantity;
                    $price = number_format($detail->price * $quantity, 2);

                    $info .= '
                    <div class="d-flex justify-content-between flex-wrap mb-2 border-bottom pb-1">
                        <div>
                            <div><strong>' . $menuName . '</strong> (' . $optionType . ')</div>
                            <small class="text-muted">จำนวน: ' . $quantity . '</small>
                        </div>
                        <div class="text-end">
                            <span class="text-success fw-bold">' . $price . ' บาท</span>
                        </div>
                    </div>
                ';
                }

                $info .= '</div></div>';
            }
        } else {
            $info .= '<div class="alert alert-warning text-center">ไม่พบรายการในออเดอร์นี้</div>';
        }

        echo $info;
    }
}
