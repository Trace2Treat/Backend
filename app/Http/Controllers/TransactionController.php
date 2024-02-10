<?php

namespace App\Http\Controllers;

use App\Models\{Transaction,Food,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        if (isset($_GET['id'])) {
            $data = Transaction::where('id', $_GET['id'])->first();
            $data['user'] = DB::table('users')->select('name','phone')->where('id', $data->user_id)->first();

            $data['item'] = DB::table('orders')->where('transaction_id', $data->transaction_code)->get();

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
            $data = Transaction::orderBy('id', 'DESC');

            if (isset($_GET['user_id'])) {
                $data = $data->where('user_id', $_GET['user_id']);
            }
            if (isset($_GET['driver_id'])) {
                $data = $data->where('driver_id', $_GET['driver_id']);
            }
            if (isset($_GET['status'])) {
                $stat = explode(',', $_GET['status']);
                $data = $data->whereIn('status', $stat);
            }

            if (isset($_GET['restaurant_id'])) {
                $data = $data->where('restaurant_id', $_GET['restaurant_id']);
            }
            if (isset($_GET['owner_id'])) {
                $data = $data->where('owner_id', $_GET['owner_id']);
            }
            if (isset($_GET['search'])) {
                $data = $data->where('name', 'like', '%' . $_GET['search'] . '%');
            }

            if ($data->count() > 0) {
                $data = $data->paginate($limit);

                $data->getCollection()->transform(function ($value) {
                    $datas = $value;
                    $datas['items'] = DB::table('orders')->where('transaction_id', $value->transaction_code)->get();
                    $datas['user'] = DB::table('users')->select('name','phone')->where('id', $value->user_id)->first();

                    // get food from food id in orders
                    $datas['items']->transform(function ($value) {
                        $food = Food::where('id', $value->food_id)->first();
                        $value->food = $food;
                        return $value;
                    });

                    return $datas;
                });

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

        $items = json_decode($request->items, true);

        foreach ($items as $key => $item) {
            $food = Food::findOrFail($item['food_id']);
            $tot += $item['qty'] * $food->price;
        }

        if ($tot > Auth::user()->balance_coin) {
            $custom = collect(['status' => 'error','statusCode' => 400, 'message' => 'Saldo tidak cukup', 'data' => null,'timestamp' => now()->toIso8601String()]);
            return response()->json($custom, 401);
        }

        $item_order = [];
        foreach ($items as $key => $item) {
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
            'restaurant_id' => $request->restaurant_id,
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


    private function generateTransactionCode()
    {
        return 'TRX-' . date('YmdHis') . '-' . Auth::id() . '-' . rand(1000, 9999);
    }

    // generate qr code
    public function generateQrCode(Request $request, $transaction_code)
    {
            $qrCodeText = route('transaction.pay', $transaction_code);
            $qrCode = QrCode::format('png')->size(200)->generate($qrCodeText);
            $filename = uniqid('qrcode_') . '.png';
            $publicPath = public_path('qrcodes');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }
            $file = $publicPath . '/' . $filename;
            file_put_contents($file, $qrCode);
            return response()->file($file);
    }

    public function pay(Request $request, $transaction_code)
    {
        $transaction = Transaction::where('transaction_code', $transaction_code)->first();
        if ($transaction) {
            Auth::user()->balance_coin = Auth::user()->balance_coin - $transaction->total;
            Auth::user()->save();

            $resto = User::where('id',$transaction->user_id)->first();
            $resto->balance_coin = $resto->balance_coin + $transaction->total;
            $resto->save();

            $transaction->status = 'success';
            $transaction->save();
            $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diupdate', 'data' => null,'timestamp' => now()->toIso8601String()]);
            return response()->json($custom, 200);
        } else {
            $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
            return response()->json($custom, 404);
        }
    }
}
