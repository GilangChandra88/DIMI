<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DynamicApiController extends Controller
{
    // Daftar tabel sistem yang DIBLOKIR dari akses API
    protected $blacklist = [
        'users', 'password_reset_tokens', 'migrations', 'personal_access_tokens',
        'failed_jobs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'
    ];

    // Fungsi utilitas untuk memvalidasi keamanan tabel
    private function validateTable($table)
    {
        if (in_array(strtolower($table), $this->blacklist)) {
            abort(403, "Akses ke tabel '{$table}' ditolak karena alasan keamanan sistem.");
        }

        if (!Schema::hasTable($table)) {
            abort(404, "Tabel '{$table}' tidak ditemukan di database.");
        }
    }

    // GET: Ambil Semua Data
    public function index($table)
    {
        $this->validateTable($table);
        $data = DB::table($table)->get();
        return response()->json($data);
    }

    // POST: Tambah Data Baru
    public function store(Request $request, $table)
    {
        $this->validateTable($table);
        
        $data = $request->all();
        
        // Otomatis mengisi timestamp jika kolomnya ada
        if (Schema::hasColumn($table, 'created_at')) {
            $data['created_at'] = Carbon::now();
        }
        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = Carbon::now();
        }

        try {
            $id = DB::table($table)->insertGetId($data);
            $record = DB::table($table)->find($id);
            return response()->json($record, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan data', 'error' => $e->getMessage()], 400);
        }
    }

    // GET: Ambil Satu Data
    public function show($table, $id)
    {
        $this->validateTable($table);
        
        $record = DB::table($table)->find($id);
        if (!$record) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        
        return response()->json($record);
    }

    // PUT/PATCH: Ubah Data
    public function update(Request $request, $table, $id)
    {
        $this->validateTable($table);
        
        $record = DB::table($table)->find($id);
        if (!$record) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data = $request->all();
        
        // Otomatis mengisi kolom updated_at jika ada
        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = Carbon::now();
        }

        try {
            DB::table($table)->where('id', $id)->update($data);
            $updatedRecord = DB::table($table)->find($id);
            return response()->json($updatedRecord);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengubah data', 'error' => $e->getMessage()], 400);
        }
    }

    // DELETE: Hapus Data
    public function destroy($table, $id)
    {
        $this->validateTable($table);
        
        $record = DB::table($table)->find($id);
        if (!$record) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        DB::table($table)->where('id', $id)->delete();
        
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
