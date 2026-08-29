<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Pegawai;

class PegawaiController extends Controller
{
    // Mengambil semua data pegawai
    public function index()
    {
        $pegawais = Pegawai::all();
        return response()->json($pegawais);
    }

    // Menambah data pegawai baru
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nip' => 'required|string|unique:pegawais',
            'nama' => 'required|string',
            'jabatan' => 'required|string',
            'divisi' => 'required|string',
            'tanggal_bergabung' => 'required|date',
        ]);

        $pegawai = Pegawai::create($validatedData);

        return response()->json($pegawai, 201);
    }

    // Mengambil satu data pegawai berdasarkan ID
    public function show($id)
    {
        $pegawai = Pegawai::find($id);

        if (!$pegawai) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        return response()->json($pegawai);
    }

    // Mengubah data pegawai
    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::find($id);

        if (!$pegawai) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        $validatedData = $request->validate([
            'nip' => 'sometimes|required|string|unique:pegawais,nip,' . $id,
            'nama' => 'sometimes|required|string',
            'jabatan' => 'sometimes|required|string',
            'divisi' => 'sometimes|required|string',
            'tanggal_bergabung' => 'sometimes|required|date',
        ]);

        $pegawai->update($validatedData);

        return response()->json($pegawai);
    }

    // Menghapus data pegawai
    public function destroy($id)
    {
        $pegawai = Pegawai::find($id);

        if (!$pegawai) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        $pegawai->delete();

        return response()->json(['message' => 'Data pegawai berhasil dihapus']);
    }
}
