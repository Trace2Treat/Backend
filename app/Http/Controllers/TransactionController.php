<?php

namespace App\Http\Controllers;

use App\Models\{Transaction,Food};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        if (isset($_GET['id'])) {
            $data = Transaction::where('id', $_GET['id'])->first();
            if ($data) {
                $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data,'timestamp' => now()->toIso8601String()]);
                return response()->json($custom, 200);
            } else {
                $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
                $data = $custom->merge($data);
                return response()->json($data, 404);
            }
        } else {

            $limit = $_GET['limit'] ?? 10;
            $data = Transaction::where('user_id', Auth::id())->orderBy('id', 'DESC');
            if (isset($_GET['search'])) {
                $data = $data->where('name', 'like', '%' . $_GET['search'] . '%');
            }
            if ($data->count() > 0) {
                $data = $data->paginate($limit);


                $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data,'timestamp' => now()->toIso8601String()]);
                $data = $custom->merge($data);
                return response()->json($data, 200);
            } else {
                $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
                return response()->json($custom, 404);
            }
        }
    }

    public function purchaseFood(Request $request)
    {

        $order_id = $this->generateTransactionCode();
        $total = 0;
        $tot = 0;

        foreach ($request->items as $key => $item) {
            $food = Food::findOrFail($item['food_id']);
            $tot += $item['qty'] * $food->price;
        }

        if ($tot > Auth::user()->balance_coin) {
            $custom = collect(['status' => 'error','statusCode' => 400, 'message' => 'Saldo tidak cukup', 'data' => null,'timestamp' => now()->toIso8601String()]);
            return response()->json($custom, 401);
        }

        $item_order = [];
        foreach ($request->items as $key => $item) {
            $food = Food::findOrFail($item['food_id']);
            $total += $item['qty'] * $food->price;
            $it = [
                'transaction_id' => $order_id,
                'food_id' => $item['food_id'],
                'qty' => $item['qty'],
                'total' => $item['qty'] * $food->price,
            ];
            DB::table('orders')->insert($it);
            $it['food'] = $food;
            $item_order[] = $it;
        }

        $data = [
            'transaction_code' => $order_id,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'pending',
            'total' => $total,
            'user_id' => Auth::id(),
            'driver_id' => null,
        ];
        $transaction = new Transaction($data);
        $transaction->save();
        $data['items'] = $item_order;



        $user = Auth::user();
        $user->balance_coin = $user->balance_coin - $total;
        $user->save();
        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data transaksi berhasil disimpan', 'data' => $data,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    public function purchaseProduct(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        // Validate input
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $quantity = $request->input('quantity');
        $totalAmount = $product->price * $quantity;

        $transaction = new Transaction([
            'transaction_code' => $this->generateTransactionCode(),
            'status' => 'pending',
            'total' => $totalAmount,
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'driver_id' => null, // Assuming there's no driver associated initially
        ]);

        $transaction->save();
        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data transaksi berhasil disimpan', 'data' => $data,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);

        // $midtransSnapToken = $this->generateMidtransSnapToken($transaction);

        // return response()->json(['snap_token' => $midtransSnapToken]);
    }

    public function changeStatus(Request $request, $id)
    {

        $wasteRequest = Transaction::findOrFail($id);
        $wasteRequest->status = $request->status;
        $wasteRequest->driver_id = Auth::id();
        $wasteRequest->save();


        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diupdate', 'data' => null,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    // private function generateMidtransSnapToken(Transaction $transaction)
    // {
    //     MidtransConfig::$serverKey = config('services.midtrans.server_key');
    //     MidtransConfig::$isProduction = config('services.midtrans.is_production');
    //     MidtransConfig::$isSanitized = true;
    //     MidtransConfig::$is3ds = true;

    //     $midtransParams = [
    //         'transaction_details' => [
    //             'order_id' => $transaction->id,
    //             'gross_amount' => $transaction->total_amount,
    //         ],
    //         'customer_details' => [
    //             'first_name' => Auth::user()->name,
    //             'email' => Auth::user()->email,
    //         ],
    //         'item_details' => [
    //             [
    //                 'id' => $transaction->food_id ?? $transaction->product_id,
    //                 'price' => $transaction->total_amount,
    //                 'quantity' => $transaction->quantity,
    //                 'name' => $transaction->food->name ?? $transaction->product->name,
    //                 'category' => $transaction->food->category->name ?? $transaction->product->category->name,
    //                 'merchant_name' => config('app.name'),
    //             ],
    //         ],
    //     ];

    //     $midtransSnapToken = MidtransSnap::getSnapToken($midtransParams);

    //     return $midtransSnapToken;
    // }

    // public function midtransCallback(Request $request)
    // {
    //     $transactionStatus = $request->input('transaction_status');
    //     $orderId = $request->input('order_id');
    //     if ($transactionStatus === 'capture') {
    //         $transaction = Transaction::find($orderId);
    //         $transaction->status = 'Success';
    //         $transaction->save();

    //     } elseif ($transactionStatus === 'settlement') {

    //     } elseif ($transactionStatus === 'deny') {

    //     } elseif ($transactionStatus === 'cancel') {

    //     } elseif ($transactionStatus === 'expire') {

    //     } elseif ($transactionStatus === 'pending') {

    //     }

    //     return response()->json(['status' => 'success']);
    // }

    private function generateTransactionCode()
    {
        return 'TRX-' . date('YmdHis') . '-' . Auth::id() . '-' . rand(1000, 9999);
    }
}
