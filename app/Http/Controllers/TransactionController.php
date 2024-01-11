<?php

namespace App\Http\Controllers;

use App\Models\{Transaction,Food};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function purchaseFood(Request $request, $foodId)
    {
        $food = Food::findOrFail($foodId);

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $quantity = $request->input('quantity');
        $totalAmount = $food->price * $quantity;

        $data = [
            'transaction_code' => $this->generateTransactionCode(),
            'status' => 'pending',
            'total' => $totalAmount,
            'user_id' => Auth::id(),
            'food_id' => $food->id,
            'driver_id' => null,
        ];
        $transaction = new Transaction($data);

        $transaction->save();

        $transaction->save();
        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data transaksi berhasil disimpan', 'data' => $data,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);

        // $midtransSnapToken = $this->generateMidtransSnapToken($transaction);

        // return response()->json(['snap_token' => $midtransSnapToken]);
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

    private function generateMidtransSnapToken(Transaction $transaction)
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = config('services.midtrans.is_production');
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;

        $midtransParams = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => $transaction->total_amount,
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ],
            'item_details' => [
                [
                    'id' => $transaction->food_id ?? $transaction->product_id,
                    'price' => $transaction->total_amount,
                    'quantity' => $transaction->quantity,
                    'name' => $transaction->food->name ?? $transaction->product->name,
                    'category' => $transaction->food->category->name ?? $transaction->product->category->name,
                    'merchant_name' => config('app.name'),
                ],
            ],
        ];

        $midtransSnapToken = MidtransSnap::getSnapToken($midtransParams);

        return $midtransSnapToken;
    }

    public function midtransCallback(Request $request)
    {
        $transactionStatus = $request->input('transaction_status');
        $orderId = $request->input('order_id');
        if ($transactionStatus === 'capture') {
            $transaction = Transaction::find($orderId);
            $transaction->status = 'Success';
            $transaction->save();

        } elseif ($transactionStatus === 'settlement') {

        } elseif ($transactionStatus === 'deny') {

        } elseif ($transactionStatus === 'cancel') {

        } elseif ($transactionStatus === 'expire') {

        } elseif ($transactionStatus === 'pending') {

        }

        return response()->json(['status' => 'success']);
    }

    private function generateTransactionCode()
    {
        return 'TRX-' . date('YmdHis') . '-' . Auth::id() . '-' . rand(1000, 9999);
    }
}
