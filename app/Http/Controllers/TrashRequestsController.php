<?php

namespace App\Http\Controllers;

use App\Models\{TrashRequests,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TrashRequestsController extends Controller
{
    public function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        // Earth radius in kilometers (mean value)
        $r = 6371;

        // Calculate the result
        $distance = $c * $r;

        return $distance;
    }


    public function checkLocation()
    {
        $latitude = '-6.65476';
        $longitude = '106.7682632';
        $geolocation = $latitude . ',' . $longitude;
        $request = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $geolocation . '&sensor=false&key=AIzaSyBPQgUMf_TbTbbNxxNpYbtc_-5Ok3K6jgE';
        $file_contents = file_get_contents($request);
        $json_decode = json_decode($file_contents);

        if(isset($json_decode->results[0])) {
            $response = array();
            foreach($json_decode->results[0]->address_components as $addressComponet) {
                if(in_array('political', $addressComponet->types)) {
                    $response[] = $addressComponet->long_name;
                    print_r($response); // no error
                }
            }

            if(isset($response[0])) {
                $first  =  $response[0];
            } else {
                $first  = 'null';
            }
            if(isset($response[1])) {
                $second =  $response[1];
            } else {
                $second = 'null';
            }
            if(isset($response[2])) {
                $third  =  $response[2];
            } else {
                $third  = 'null';
            }
            if(isset($response[3])) {
                $fourth =  $response[3];
            } else {
                $fourth = 'null';
            }
            if(isset($response[4])) {
                $fifth  =  $response[4];
            } else {
                $fifth  = 'null';
            }

            if($first != 'null' && $second != 'null' && $third != 'null' && $fourth != 'null' && $fifth != 'null') {
                echo "<br/>Address:: " . $first;
                echo "<br/>City:: " . $second;
                echo "<br/>State:: " . $fourth;
                echo "<br/>Country:: " . $fifth;
                print_r($response);// No error
            } elseif ($first != 'null' && $second != 'null' && $third != 'null' && $fourth != 'null' && $fifth == 'null') {
                echo "<br/>Address:: " . $first;
                echo "<br/>City:: " . $second;
                echo "<br/>State:: " . $third;
                echo "<br/>Country:: " . $fourth;
            } elseif ($first != 'null' && $second != 'null' && $third != 'null' && $fourth == 'null' && $fifth == 'null') {
                echo "<br/>City:: " . $first;
                echo "<br/>State:: " . $second;
                echo "<br/>Country:: " . $third;
            } elseif ($first != 'null' && $second != 'null' && $third == 'null' && $fourth == 'null' && $fifth == 'null') {
                echo "<br/>State:: " . $first;
                echo "<br/>Country:: " . $second;
            } elseif ($first != 'null' && $second == 'null' && $third == 'null' && $fourth == 'null' && $fifth == 'null') {
                echo "<br/>Country:: " . $first;
            }
        }
        print_r($response);// error
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (isset($_GET['id'])) {
            $data = TrashRequests::with('user', 'driver')->where('id', $_GET['id'])->first();

            try {
                $distance = round($this->haversineDistance($_GET['lot'], $_GET['lang'], $data->latitude, $data->longitude));
            } catch (\Throwable $th) {
                //throw $th;
            }


            // if ($data->proof_payment != null) {
            //     $data['proof_payment_url'] = env('APP_URL') . '/api/getFile/ProofPayment/' . $data->proof_payment;
            // }
            try {
                $data['distance'] = $distance;
                $data['shipping'] = $distance * 5000;
            } catch (\Throwable $th) {
                //throw $th;
            }

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
            $data = TrashRequests::with('user', 'driver')->where('id', '!=', null);


            if (isset($_GET['user_id'])) {
                $data = TrashRequests::with('user', 'driver')->where('user_id', $_GET['user_id']);
            }
            if (isset($_GET['driver_id'])) {
                $data = $data->where('driver_id', $_GET['driver_id']);
            }

            if (isset($_GET['status'])) {
                $data = $data->where('status', $_GET['status']);
            }

            if (isset($_GET['search'])) {
                $data = $data->where('name', 'like', '%' . $_GET['search'] . '%');
            }
            if ($data->count() > 0) {
                $data = $data->paginate($limit);

                $data->getCollection()->transform(function ($value) {
                    $datas = $value;

                    $dateTime = Carbon::parse($value->updated_at);
                    $formattedDate = $dateTime->format('l, d F Y');
                    $datas['date'] = $formattedDate;

                    try {
                        $datas['distance'] = round($this->haversineDistance($_GET['lot'], $_GET['lang'], $value->latitude, $value->longitude));
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                    return $datas;
                });

                try {
                    $data->setCollection($data->getCollection()->sortBy('distance'));
                } catch (\Throwable $th) {
                    //throw $th;
                }

                $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data,'timestamp' => now()->toIso8601String()]);
                $data = $custom->merge($data);
                return response()->json($data, 200);
            } else {
                $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
                return response()->json($custom, 404);
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trash_type' => 'required|string',
            'trash_weight' => 'required|string',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'place_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // $file = $request->file('thumb');
        // $filename = time() . '-' . $file->getClientOriginalName();
        // Storage::disk('public')->put('WasteThumb/' . $filename, file_get_contents($file));

        $data = [
            'user_id' => Auth::id(),
            'trash_type' => $request->trash_type,
            'trash_weight' => $request->trash_weight,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'place_name' => $request->place_name,
            'status' => 'Pending',
            'driver_id' => null,
        ];

        $wasteRequest = TrashRequests::create($data);

        $custom = [
            'status' => 'success',
            'statusCode' => 200,
            'message' => 'Data berhasil disimpan',
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        return response()->json($custom, 200);
    }


    public function changeStatus(Request $request, $id)
    {

        if ($request->status == 'Approved') {
            // Rp 10.000 / Kg
            $wasteRequest = TrashRequests::findOrFail($id);
            $coin = 10000 * $wasteRequest->trash_weight;
            $wasteRequest->status = $request->status;
            $wasteRequest->driver_id = Auth::id();
            $wasteRequest->point = $coin;

            $wasteRequest->save();
            User::where('id', $wasteRequest->user_id)->update(['balance_coin' => $coin]);
        }
        if ($request->status == 'Finished') {

            // $file = $request->file('proof_payment');
            // $filename = time() . '-' . $file->getClientOriginalName();
            // Storage::disk('public')->put('ProofPayment/' . $filename, file_get_contents($file));

            $wasteRequest = TrashRequests::findOrFail($id);
            $wasteRequest->status = $request->status;
            $wasteRequest->proof_payment = $request->proof_payment;
            $wasteRequest->driver_id = Auth::id();

            $wasteRequest->save();
        } elseif ($request->status == 'Received') {
            // Rp 10.000 / Kg
            $wasteRequest = TrashRequests::findOrFail($id);
            $wasteRequest->status = $request->status;
            $wasteRequest->driver_id = Auth::id();

            $wasteRequest->save();
        } else {
            $wasteRequest = TrashRequests::findOrFail($id);
            $wasteRequest->status = $request->status;
            $wasteRequest->driver_id = Auth::id();
            $wasteRequest->save();
        }
        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diupdate', 'data' => null,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if ($request->hasFile('file')) {
            $validator = Validator::make($request->all(), [
                'trash_type' => 'required|string',
                'trash_weight' => 'required|string',
                'latitude' => 'required|string',
                'longitude' => 'required|string',
                'thumb' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $file = $request->file('thumb');
            $filename = time() . '-' . $file->getClientOriginalName();
            Storage::disk('public')->put('WasteThumb/' . $filename, file_get_contents($file));

            $data = [
                'user_id' => Auth::id(),
                'trash_type' => $request->trash_type,
                'trash_weight' => $request->trash_weight,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'Pending',
                'place_name' => $request->place_name,

                'thumb' => $filename,
                'driver_id' => null,
            ];

            $user = TrashRequests::where('id', $id)->update($data);
        } else {
            $validator = Validator::make($request->all(), [
                'trash_type' => 'required|string',
                'trash_weight' => 'required|string',
                'latitude' => 'required|string',
                'longitude' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $data = [
                'user_id' => Auth::id(),
                'trash_type' => $request->trash_type,
                'trash_weight' => $request->trash_weight,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'place_name' => $request->place_name,

                'status' => 'Pending',
                'driver_id' => null,
            ];

            $user = TrashRequests::where('id', $id)->update($data);
        }

        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diupdate', 'data' => $data,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $data = TrashRequests::where('id', $id)->firstOrFail();
        if ($data) {
            $data->delete();
            $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil dihapus', 'data' => $data,'timestamp' => now()->toIso8601String()]);
            return response()->json($custom, 200);
        } else {
            $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
            return response()->json($custom, 404);
        }
    }

    public function collectorRequests()
    {
        // Mendapatkan daftar kategori sampah
        $wasteCategories = WasteCategory::all();

        return view('collector_requests.index', compact('wasteCategories'));
    }

    /**
     * Store a newly created resource for waste_collector's request.
     */
    public function storeCollectorRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'waste_category_id' => 'required|exists:waste_categories,id',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = [
            'user_id' => Auth::id(),
            'waste_category_id' => $request->waste_category_id,
            'description' => $request->description,
            'status' => 'Pending', // Atau status lain sesuai kebutuhan
        ];

        $collectorRequest = WasteCollectorRequest::create($data);

        $custom = [
            'status' => 'success',
            'statusCode' => 200,
            'message' => 'Permintaan berhasil disimpan',
            'data' => $collectorRequest,
            'timestamp' => now()->toIso8601String(),
        ];

        return response()->json($custom, 200);
    }

}
