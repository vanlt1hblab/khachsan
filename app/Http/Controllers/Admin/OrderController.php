<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OrderRequest;
use App\Models\Card;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderPromotion;
use App\Models\OrderTypeRoom;
use App\Service\OrderService;
use App\Http\Controllers\Controller;
use App\Service\PaymentService;
use App\Service\PromotionService;
use App\Service\RoomService;
use App\Service\StatusOrderService;
use App\Service\TypeRoomService;
use App\Service\UserService;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Stripe\Charge;
use Stripe\Stripe;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    const HANDLED = 2;
    const WAIT = 1;

    protected $orderService;
    protected $userService;
    protected $paymentService;
    protected $statusOrderService;
    protected $typeRoomService;
    protected $roomService;
    protected $promotionService;

    public function __construct(
        OrderService $orderService,
        UserService $userService,
        PaymentService $paymentService,
        StatusOrderService $statusOrderService,
        TypeRoomService $typeRoomService,
        RoomService $roomService,
        PromotionService $promotionService
    ) {
        $this->orderService = $orderService;
        $this->userService = $userService;
        $this->paymentService = $paymentService;
        $this->statusOrderService = $statusOrderService;
        $this->typeRoomService = $typeRoomService;
        $this->roomService = $roomService;
        $this->promotionService = $promotionService;
    }

    public function index()
    {
        return view('admin.order.index');
    }

    public function orderWait()
    {
        return view('admin.order.wait');
    }

    public function orderHandles()
    {
        return view('admin.order.handled');
    }

    public function getList()
    {
        return DataTables::of($this->orderService->orders())
            ->addColumn('user_name', function ($order) {
                return $order->user->name;
            })
            ->addColumn('status_name', function ($order) {
                return $order->statusOrder->name;
            })
            ->addColumn('action', function ($order) {
                $html = null;
                if ($order->status_order_id === self::WAIT) {
                    $html = '<a href="orders/wait/' . $order->id . '/edit" class="btn btn-sm btn-outline-primary" > <i class="fa fa-pencil"></i></a>';
                } else {
                    $html = '<a href="orders/handled/' . $order->id . '/edit" class="btn btn-sm btn-outline-primary" > <i class="fa fa-pencil"></i></a>';
                }
                return
                    $html.'<a href="orders/' . $order->id . '/delete" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure ?\')"> <i class="fa fa-trash-o"></i></a>';
            })
            ->make(true);
    }

    public function getOrderWait()
    {
        return DataTables::of($this->orderService->getOrderWait())
            ->addColumn('user_name', function ($order) {
                return $order->user->name;
            })
            ->addColumn('action', function ($order) {
                return
                    '<a href="wait/' . $order->id . '/edit" class="btn btn-sm btn-outline-primary" > <i class="fa fa-pencil"></i></a>
                    <a href="' . $order->id . '/delete" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure ?\')"> <i class="fa fa-trash-o"></i></a>
                    ';
            })
            ->make(true);
    }

    public function getOrderHandled()
    {
        return DataTables::of($this->orderService->getOrderHanded())
            ->addColumn('user_name', function ($order) {
                return $order->user->name;
            })
            ->addColumn('action', function ($order) {
                return
                    '<a href="handled/' . $order->id . '/edit" class="btn btn-sm btn-outline-primary" > <i class="fa fa-pencil"></i></a>
                    <a href="' . $order->id . '/delete" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure ?\')"> <i class="fa fa-trash-o"></i></a>
                    <a href="export-pdf/'. $order->id.'" class="btn btn-sm btn-outline-info"><i class="fa fa-file-pdf-o"></i></a>
                    ';
            })
            ->make(true);
    }

    public function create()
    {
        $users = $this->userService->getCustomers();
        $status = $this->statusOrderService->statusOrders();
        $payments = $this->paymentService->payments();
        $infoTypeRooms = $this->orderService->getNumberRoomsMoreDateNow();
        $typeRooms = $this->typeRoomService->getTypeRooms();

        return view('admin.order.form', compact('users', 'status', 'payments', 'infoTypeRooms', 'typeRooms'));
    }

    public function searchRoom(Request $request)
    {
        $rooms = $this->orderService->getRoomsWhenSearchInAdmin($request);
        $typeRoom = $this->typeRoomService->find((int) $request->typeRoom);

        if (count($rooms)*$typeRoom->people < $request->number_people) {
            return response()->json(0, 200);
        } else {
            return response()->json($rooms, 200);
        }
    }

    public function selectUser(Request $request)
    {
        $user = $this->userService->find($request->userID);

        return response()->json($user, 200);
    }

    public function calculate(Request $request)
    {
        $typeRoomId = $request->typeRoom;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $number_people = $request->number_people;

        if (!$startDate && !$endDate && !$number_people) {
            return response()->json(null, 200);
        }
        $nameRooms = $request->nameRooms;
        $arrRoom = [];
        $arrNameRooms = explode(',', $nameRooms);
        foreach ($arrNameRooms as $nameRoom) {
            $room = $this->roomService->getRoomByName($nameRoom);
            if ((int)$room->type_room_id === (int)$typeRoomId) {
                $arrRoom[] = $room;
            }
        }
        $oldCard = Session::has('card') ? Session::get('card') : null;
        $card = new Card($oldCard);
        $typeRoom = $this->typeRoomService->find($typeRoomId);
        $card->addTypeRoom($typeRoomId, $typeRoom, $startDate, $endDate, $number_people, 0, $arrRoom);
        Session::put('card', $card);

        $card = Session::get('card');
        foreach ($card->typeRooms as $typeRoom) {
            $nameTypeRooms[] = $typeRoom['typeRoom']->name;
        }
        $data = ['total' => $card->total, 'nameTypeRooms' => $nameTypeRooms ];

        return response()->json($data, 200);
    }

    public function actionCreate(OrderRequest $request)
    {
        $card = Session::get('card');
        $typeRooms = $this->typeRoomService->getTypeRooms();
        $promotion = $this->promotionService->checkCode(trim($request->promotion));
        $user = $this->userService->getUserByEmai($request->email);
        $id = null;
        if ($promotion) {
            $promotionOrder = $this->promotionService->checkOrderPromotion($promotion->id, $user->id);
            if (!$promotionOrder) {
                $card->promotion = $promotion->sale;
                $card->paymentTotal = $card->total - $promotion->sale;
                $id = $promotion->id;
            }
        }
        Session::put('card', $card);
        $card = Session::get('card');
        //dd($card->typeRooms[1]['rooms']);
        $infoBooling = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'sex' => $request->sex,
            'address' => $request->address,
            'payment' => $request->payment_method,
            'stripeToken' => $request->stripeToken ?? null,
            'promotionId' => $id
        ];
        Session::put('infoBooking', $infoBooling);

        return view('admin.order.confirm', compact('request', 'card', 'typeRooms'));
    }

    public function deleteTypeRoomWhenBooking(Request $request)
    {
        $oldCard = Session::has('card') ? Session::get('card') : null;
        $card = new Card($oldCard);
        $card->deleteTypeRoom($request->id);
        $data = null;
        if (count($card->typeRooms) > 0) {
            Session::put('card', $card);
            $data = ['total' => $card->total, 'promotion' => $card->promotion, 'paymentTotal' => $card->paymentTotal];
        } else {
            Session::forget('card');
        }
        return response()->json($data, 200);
    }

    public function finishCreate()
    {
        $card = Session::get('card');
        $customer = Session::get('infoBooking');
        if ($customer['stripeToken']) {
            Stripe::setApiKey("sk_test_aBWzRKCBKy6L86mfuc3WqJgI");
            $token = $customer['stripeToken'];
//            Change price * 10
            Charge::create([
                "amount" => $card->paymentTotal * 100,
                "currency" => "usd",
                "source" => $token,
                "description" => "Charge",
            ]);
        }
        $newUser = $this->userService->getUserByEmai($customer['email']);
        if (!$newUser) {
            $this->userService->createOrUpdate($customer);
        } else {
            $this->userService->createOrUpdate($customer, $newUser->id);
        }
        $newUser = $this->userService->getUserByEmai($customer['email']);
        $order = new Order();
        $order->user_id = $newUser->id;
        $order->status_order_id =self::HANDLED;
        $order->payment_method = $customer['payment'];
        $order->quantity = $card->sumRoom;
        $order->promotion = $card->promotion;
        $order->total = $card->total;
        $order->payment_total = $card->paymentTotal;
        $order->date = Carbon::now()->format('Y-m-d');
        $this->orderService->createOrUpdate($order);
        $orderID = Order::max('id');
        if ($customer['promotionId']) {
            $promotionOrder = new OrderPromotion();
            $promotionOrder->promotion_id = $customer['promotionId'];
            $promotionOrder->order_id = $orderID;
            $promotionOrder->user_id = $newUser->id;
            $promotionOrder->date = Carbon::now()->format('Y-m-d');
            $promotionOrder->save();
        }

        foreach ($card->typeRooms as $typeRoom) {
            $this->orderService->createOrUpdateOrderTypeRoom($orderID, $typeRoom, count($typeRoom['rooms']) ?? 0);
            if ($typeRoom['rooms']) {
                foreach ($typeRoom['rooms'] as $room) {
                    $orderTypeRoomId = OrderTypeRoom::max('id');
                    $orderDetail = new OrderTypeRoom();
                    $orderDetail->order_type_room_id = $orderTypeRoomId;
                    $orderDetail->room_id = $room->id;
                    $orderDetail->date = Carbon::now()->format('Y-m-d');
                    $orderDetail->start_date = $typeRoom['startDate'];
                    $orderDetail->end_date = $typeRoom['endDate'];
                    $this->orderService->createOrUpdateOrderDetail($orderDetail);
                }
            }
        }

        Session::forget('card');
        Session::forget('infoBooking');

        return redirect()->route('admin.orders.handled')->with('message', 'Create Order Successfully !');
    }

    public function editHandled(Order $order)
    {
        $status = $this->statusOrderService->statusOrders();
        $payments = $this->paymentService->payments();
        $infoTypeRooms = $this->orderService->getNumberRoomsMoreDateNow();
        $typeRooms = $this->typeRoomService->getTypeRooms();
        //dd($order->promotions->first());

        return view('admin.order.form-edit', compact('order', 'status', 'payments', 'typeRooms', 'infoTypeRooms'));
    }

    public function confirm(Order $order, Request $request)
    {
        if (!$request->nameRoom) {
            return redirect()->back()->with('error', 'Please choose room before change!');
        }
        $total = 0;
        $info = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'sex' => $request->sex,
            'address' => $request->address,
            'payment' => $order->payment_method,
        ];

        foreach ($order->orderTypeRooms as $orderTypeRoom) {
            $typeRoom = $orderTypeRoom->typeRoom;
            $startDate =Carbon::parse($request['startDate'.$orderTypeRoom->type_room_id]);
            $endDate =Carbon::parse($request['endDate'.$orderTypeRoom->type_room_id]);
            $sum_day=$startDate && $endDate ? ((int)($endDate->diffInDays($startDate)) == 0?1:(int)($endDate->diffInDays($startDate))):1;
            if ($typeRoom->sale > 0) {
                $totalType = $typeRoom->price * $sum_day * $request['number_people'.$orderTypeRoom->type_room_id]
                    * (100 - $typeRoom->sale) /100;
            } else {
                $totalType = $typeRoom->price * $sum_day * $request['number_people'.$orderTypeRoom->type_room_id];
            }

            $total += $totalType;

            $nameRooms = $request['nameRoom'];
            $arrNameRooms = explode(',', $nameRooms);
            foreach ($arrNameRooms as $nameRoom) {
                $room = $this->roomService->getRoomByName($nameRoom);
                if ($room->typeRoom->id === $orderTypeRoom->type_room_id) {
                    $rooms[] = $room;
                }
            }
            $infoTypeRoom = [
                'id' => $orderTypeRoom->id,
                'typeRoom' => $typeRoom,
                'rooms' => $rooms,
                'start_date' => $request['startDate'.$orderTypeRoom->type_room_id],
                'end_date' => $request['endDate'.$orderTypeRoom->type_room_id],
                'number_people' => $request['number_people'.$orderTypeRoom->type_room_id],
                'total' => $totalType
            ];
            $rooms = [];
            $orders[] = $infoTypeRoom;
        }
        Session::put('order', [
            'orderOld' => $order,
            'info' => $info,
            'orders' => $orders,
            'total' => $total,
            'promotion' => $order->promotion,
            'paymentTotal' => $total - $order->promotion,
            'paymentNew' => $total - $order->promotion - $order->payment_total
        ]);

        $order = Session::get('order');
        //dd($order['id']);
        return view('admin.order.edit-handled', compact('order'));
    }

    public function finishEditHandled()
    {
        $order = Session::get('order');
        DB::transaction(function () use ($order) {
            $user = $this->userService->getUserByEmai($order['info']['email']);
            if (!$user) {
                $this->userService->createOrUpdate($order['info']);
            }
            $user = $this->userService->getUserByEmai($order['info']['email']);
            $orderNew = $this->orderService->find($order['orderOld']->id);
            $orderNew->user_id = $user->id;
            $orderNew->quantity = count($order['orders']);
            $orderNew->promotion = $order['promotion'];
            $orderNew->total = $order['total'];
            $orderNew->payment_total = $order['paymentTotal'];
            $orderNew->date = Carbon::now()->format('Y-m-d');
            $orderNew->save();
            // Update order type room
            foreach ($order['orders'] as $item) {
                $orderTypeRoom = $this->orderService->findOrderTypeRoom($item['id']);
                $orderTypeRoom->number_people = $item['number_people'];
                $orderTypeRoom->number_room = count($item['rooms']);
                $orderTypeRoom->price = $item['typeRoom']->price;
                $orderTypeRoom->sale = $item['typeRoom']->sale;
                $orderTypeRoom->total = $item['total'];
                $orderTypeRoom->start_date = $item['start_date'];
                $orderTypeRoom->end_date = $item['end_date'];

                $orderTypeRoom->save();
                $this->orderService->deleteOrderDetailByOrderTypeRoom($item['id']);

                foreach ($item['rooms'] as $room) {
                    $orderDetail = new OrderDetail();
                    $orderDetail->order_type_room_id = $item['id'];
                    $orderDetail->room_id = $room->id;
                    $orderDetail->date = Carbon::now()->format('Y-m-d');
                    $orderDetail->start_date = $item['start_date'];
                    $orderDetail->end_date = $item['end_date'];

                    $orderDetail->save();

                }
            }
        });

        return redirect()->route('admin.orders.handled')
            ->with('message', 'Update '.$order['orderOld']->id.' Successfully !');
    }

    public function deleteOrder(Order $order)
    {
        if ($order->status_order_id === self::WAIT) {
            $this->orderService->sendMailDeleteOrderWait($order);
        }
        $this->orderService->deleteOrder($order);
        return redirect()->back()->with('message', 'Delete order successfully !');
    }

    public function editWait(Order $order)
    {
        $status = $this->statusOrderService->statusOrders();
        $payments = $this->paymentService->payments();
        $infoTypeRooms = $this->orderService->getNumberRoomsMoreDateNow();
        $typeRooms = $this->typeRoomService->getTypeRooms();

        return view('admin.order.wait.form', compact('order', 'status', 'payments', 'typeRooms', 'infoTypeRooms'));
    }

    public function actionEditWait(Order $order, Request $request)
    {
        DB::transaction(function () use ($order, $request) {
            foreach ($order->orderTypeRooms as $orderTypeRoom) {
                $nameRooms = $request['nameRoom'];
                $arrNameRooms = explode(',', $nameRooms);
                foreach ($arrNameRooms as $nameRoom) {
                    $room = $this->roomService->getRoomByName($nameRoom);
                    if ($room->typeRoom->id === $orderTypeRoom->type_room_id) {
                        $orderDetail = new OrderTypeRoom();
                        $orderDetail->order_type_room_id = $orderTypeRoom->id;
                        $orderDetail->room_id = $room->id;
                        $orderDetail->date = Carbon::now()->format('Y-m-d');
                        $orderDetail->start_date = $orderTypeRoom->start_date;
                        $orderDetail->end_date = $orderTypeRoom->end_date;
                        $this->orderService->createOrUpdateOrderDetail($orderDetail);
                    }
                }
            }
            $order->status_order_id = self::HANDLED;
            $this->orderService->createOrUpdate($order, $order->id);
            $this->orderService->sendMailHandelOrderWait($order);
        });


        return redirect()->route('admin.orders.wait')->with('message', 'Order '.$order->id.' Handled Successfully !');
    }

    public function delete()
    {
        Session::forget('card');

        return response()->json(null, 204);
    }

    public function exportPDFs()
    {
        $orders = $this->orderService->getOrderHanded();
        PDF::setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif', 'defaultPaperSize' => 'a4']);

        $pdf = PDF::loadView('admin.export-pdf.orders', compact('orders'));
        //$pdf->save(storage_path().'_orders.pdf');
        return $pdf->download('orders'.Carbon::now().'.pdf');
    }

    public function exportPDF(Order $order)
    {
        $pdf = PDF::loadView('admin.export-pdf.order', compact('order'));
        //$pdf->save(storage_path().'_order.pdf');
        return $pdf->download('orders'.Carbon::now().'.pdf');
    }

}
