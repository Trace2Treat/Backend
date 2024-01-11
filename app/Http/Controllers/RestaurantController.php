<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class RestaurantController extends Controller
{
    /**
      * Display a listing of the resource.
      */
    public function index()
    {
        if (isset($_GET['id'])) {
            $data = Restaurant::with('owner')->where('id', $_GET['id'])->first();
            if ($data) {
                $custom = collect(['status' => 'success', 'statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data, 'timestamp' => now()->toIso8601String()]);
                return response()->json($custom, 200);
            } else {
                $custom = collect(['status' => 'error', 'statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
                return response()->json($custom, 404);
            }
        } else {
            $limit = $_GET['limit'] ?? 10;
            $data = Restaurant::with('owner')->orderBy('id', 'DESC');
            if (isset($_GET['search'])) {
                $data = $data->where('name', 'like', '%' . $_GET['search'] . '%');
            }
            if ($data->count() > 0) {
                $data = $data->paginate($limit);
                $custom = collect(['status' => 'success', 'statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data, 'timestamp' => now()->toIso8601String()]);
                $data = $custom->merge($data);
                return response()->json($data, 200);
            } else {
                $custom = collect(['status' => 'error', 'statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
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
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $file = $request->file('logo');
        $filename = time() . '-' . $file->getClientOriginalName();
        Storage::disk('public')->put('RestaurantLogo/' . $filename, file_get_contents($file));

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'phone' => $request->phone,
            'logo' => $filename,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'owner_id' => Auth::id(),
        ];

        $restaurant = Restaurant::create($data);

        $custom = collect(['status' => 'success', 'statusCode' => 200, 'message' => 'Data berhasil disimpan', 'data' => $data, 'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }


        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'phone' => $request->phone,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        $file = $request->file('logo');
        if ($file) {
            // If a new logo is provided, update the logo
            $filename = time() . '-' . $file->getClientOriginalName();
            Storage::disk('public')->put('RestaurantLogo/' . $filename, file_get_contents($file));
            $data['logo'] = $filename;
        }

        Restaurant::where('id',$id)->update($data);
        $custom = collect(['status' => 'success', 'statusCode' => 200, 'message' => 'Data berhasil diupdate', 'data' => $data, 'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->delete();

        $custom = collect(['status' => 'success', 'statusCode' => 200, 'message' => 'Data berhasil dihapus', 'data' => $restaurant, 'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }
}
