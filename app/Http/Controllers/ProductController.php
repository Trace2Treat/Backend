<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (isset($_GET['id'])) {
            $data = Product::where('id', $_GET['id'])->first();
            if ($data) {
                $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data,'timestamp' => now()->toIso8601String()]);
                return response()->json($custom, 200);
            } else {
                $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
                return response()->json($custom, 404);
            }
        } else {
            $limit = $_GET['limit'] ?? 10;
            $data = Product::orderBy('id', 'DESC');
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
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
            'thumb' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|numeric',
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $file = $request->file('thumb');
        $filename = time() . '-' . $file->getClientOriginalName();
        Storage::disk('public')->put('ProductThumb/' . $filename, file_get_contents($file));

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'thumb' => $filename,
            'user_id' => Auth::id()
        ];
        $user = Product::create($data);

        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil disimpan', 'data' => $data,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if ($request->hasFile('file')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'price' => 'required|numeric',
                'stock' => 'required|numeric',
                'thumb' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'category_id' => 'required|numeric',
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $file = $request->file('thumb');
            $filename = time() . '-' . $file->getClientOriginalName();
            Storage::disk('public')->put('ProductThumb/' . $filename, file_get_contents($file));

            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'category_id' => $request->category_id,
                'thumb' => $filename,
            ];
            $user = Product::where('id',$id)->update($data);
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'price' => 'required|numeric',
                'stock' => 'required|numeric',
                'category_id' => 'required|numeric',
            ]);



            $data = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'category_id' => $request->category_id,
            ];
            $user = Product::where('id',$id)->update($data);
        }

        $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diupdate', 'data' => $data,'timestamp' => now()->toIso8601String()]);
        return response()->json($custom, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $data = Product::where('id',$id)->firstOrFail();
        if ($data) {
            $data->delete();
            $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil dihapus', 'data' => $data,'timestamp' => now()->toIso8601String()]);
            return response()->json($custom, 200);
        } else {
            $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
            return response()->json($custom, 404);
        }
    }
}
