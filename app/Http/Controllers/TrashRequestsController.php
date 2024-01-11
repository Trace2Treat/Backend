<?php

namespace App\Http\Controllers;

use App\Models\{TrashRequests,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TrashRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (isset($_GET['id'])) {
            $data = TrashRequests::where('id', $_GET['id'])->first();
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
            $data = TrashRequests::orderBy('id', 'DESC');
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
            'thumb' => $filename,
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


    public function changeStatus(Request $request,$id)
    {

        if ($request->status == 'Received') {
            // Rp 10.000 / Kg
            $wasteRequest = TrashRequests::findOrFail($id);
            $coin = 10000 * $wasteRequest->trash_weight;
            $wasteRequest->status = $request->status;
            $wasteRequest->driver_id = Auth::id();
            $wasteRequest->save();
            User::where('id', $wasteRequest->user_id)->update(['balance_coin' => $coin]);
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
