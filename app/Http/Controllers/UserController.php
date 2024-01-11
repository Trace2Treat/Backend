<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (isset($_GET['id'])) {
            $data = User::where('id', $_GET['id'])->first();
            if ($data) {
                $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data]);
                $data = $custom->merge($data);
                return response()->json($data, 200);
            } else {
                $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
                $data = $custom->merge($data);
                return response()->json($data, 404);
            }
        } else {
            dd('ada');
            $limit = $_GET['limit'] ?? 10;
            $data = User::orderBy('id', 'DESC');
            if (isset($_GET['search'])) {
                $data = $data->where('name', 'like', '%' . $_GET['search'] . '%');
            }
            if ($data->count() > 0) {
                $data = $data->paginate($limit);
                $custom = collect(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diambil', 'data' => $data]);
                $data = $custom->merge($data);
                return response()->json($data, 200);
            } else {
                $custom = collect(['status' => 'error','statusCode' => 404, 'message' => 'Data tidak ditemukan', 'data' => null]);
                $data = $custom->merge($data);
                return response()->json($data, 404);
            }
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'nama' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $data = [
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];
        User::create($data);

        return redirect()->back()->with(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil ditambahkan !', 'data' => $data,'timestamp' => now()->toIso8601String()]);
    }


    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'nama' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $data = [
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];
        User::where('id',$id)->update($data);

        return redirect()->back()->with(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil diedit !', 'data' => $data,'timestamp' => now()->toIso8601String()]);
    }



    public function destroy($id)
    {
        User::where('id',$id)->delete();
        return redirect()->back()->with(['status' => 'success','statusCode' => 200, 'message' => 'Data berhasil didelete !', 'data' => $data,'timestamp' => now()->toIso8601String()]);
    }
}
