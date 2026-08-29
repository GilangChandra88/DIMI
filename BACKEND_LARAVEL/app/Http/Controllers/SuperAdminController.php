<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    private function getSchemaData()
    {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $schema = [];
        foreach ($tables as $table) {
            $schema[$table->name] = Schema::getColumnListing($table->name);
        }
        return $schema;
    }

    private function respond($request, $status, $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'schema' => $this->getSchemaData()
            ], $status === 'success' ? 200 : 400);
        }

        return $status === 'success' 
            ? redirect()->route('superadmin.index')->with('success', $message)
            : back()->with('error', $message);
    }

    public function index()
    {
        return view('super-admin', ['schema' => $this->getSchemaData()]);
    }

    // Fungsi untuk membuat tabel baru dinamis
    public function createTable(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string|regex:/^[a-zA-Z_]+$/',
            'columns' => 'required|array'
        ]);

        $tableName = strtolower($request->table_name);

        if (Schema::hasTable($tableName)) {
            return $this->respond($request, 'error', "Tabel {$tableName} sudah ada!");
        }

        try {
            Schema::create($tableName, function (Blueprint $table) use ($request) {
                $table->id();
                
                foreach ($request->columns as $col) {
                    if (empty($col['name'])) continue;
                    $colName = strtolower(str_replace(' ', '_', $col['name']));
                    
                    switch ($col['type']) {
                        case 'integer':
                            $table->integer($colName)->nullable();
                            break;
                        case 'date':
                            $table->date($colName)->nullable();
                            break;
                        case 'text':
                            $table->text($colName)->nullable();
                            break;
                        default:
                            $table->string($colName)->nullable();
                            break;
                    }
                }
                
                $table->timestamps();
            });

            return $this->respond($request, 'success', "Tabel {$tableName} berhasil dibuat!");
        } catch (\Exception $e) {
            return $this->respond($request, 'error', 'Gagal membuat tabel: ' . $e->getMessage());
        }
    }

    // Fungsi untuk membuat relasi normal (One-to-Many)
    public function createRelation(Request $request)
    {
        $request->validate([
            'source_table' => 'required|string',
            'target_table' => 'required|string'
        ]);

        $source = $request->source_table;
        $target = $request->target_table;

        if (!Schema::hasTable($source) || !Schema::hasTable($target)) {
            return $this->respond($request, 'error', 'Tabel asal atau tujuan tidak ditemukan.');
        }

        // Nama kolom FK biasanya bentuk singular dari tabel target ditambah _id
        $foreignKeyCol = rtrim($target, 's') . '_id';

        if (Schema::hasColumn($source, $foreignKeyCol)) {
            return $this->respond($request, 'error', "Relasi sudah ada! Kolom {$foreignKeyCol} sudah ada di tabel {$source}.");
        }

        try {
            Schema::table($source, function (Blueprint $table) use ($foreignKeyCol) {
                $table->integer($foreignKeyCol)->nullable();
            });

            return $this->respond($request, 'success', "Relasi berhasil dibuat! Garis penghubung akan muncul otomatis.");
        } catch (\Exception $e) {
            return $this->respond($request, 'error', 'Gagal membuat relasi: ' . $e->getMessage());
        }
    }

    // Fungsi untuk membuat Tabel Pivot (Many-to-Many)
    public function createPivotRelation(Request $request)
    {
        $request->validate([
            'source_table' => 'required|string',
            'target_table' => 'required|string'
        ]);

        $table1 = $request->source_table;
        $table2 = $request->target_table;

        if (!Schema::hasTable($table1) || !Schema::hasTable($table2)) {
            return $this->respond($request, 'error', 'Tabel asal atau tujuan tidak ditemukan.');
        }

        $model1 = rtrim($table1, 's');
        $model2 = rtrim($table2, 's');

        $names = [$model1, $model2];
        sort($names);
        $pivotTableName = implode('_', $names);

        if (Schema::hasTable($pivotTableName)) {
            return $this->respond($request, 'error', "Tabel Pivot {$pivotTableName} sudah ada!");
        }

        try {
            Schema::create($pivotTableName, function (Blueprint $table) use ($model1, $model2) {
                $table->id();
                $table->integer($model1 . '_id')->nullable();
                $table->integer($model2 . '_id')->nullable();
                $table->timestamps();
            });

            return $this->respond($request, 'success', "Tabel Pivot {$pivotTableName} berhasil dibuat!");
        } catch (\Exception $e) {
            return $this->respond($request, 'error', 'Gagal membuat tabel pivot: ' . $e->getMessage());
        }
    }

    // Fungsi untuk menghapus relasi (menghapus kolom foreign key)
    public function dropRelation(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'column_name' => 'required|string'
        ]);

        $tableName = $request->table_name;
        $columnName = $request->column_name;

        if (!Schema::hasColumn($tableName, $columnName)) {
            return $this->respond($request, 'error', 'Relasi/Kolom tidak ditemukan.');
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                $table->dropColumn($columnName);
            });
            return $this->respond($request, 'success', "Relasi {$columnName} pada tabel {$tableName} berhasil diputus!");
        } catch (\Exception $e) {
            return $this->respond($request, 'error', 'Gagal memutus relasi: ' . $e->getMessage());
        }
    }

    // Fungsi untuk menghapus tabel (Drop Table)
    public function dropTable(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string'
        ]);

        $tableName = $request->table_name;
        
        $blacklist = ['users', 'password_reset_tokens', 'migrations', 'personal_access_tokens', 'failed_jobs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'];

        if (in_array(strtolower($tableName), $blacklist)) {
            return $this->respond($request, 'error', 'Tabel sistem tidak boleh dihapus!');
        }

        if (!Schema::hasTable($tableName)) {
            return $this->respond($request, 'error', 'Tabel tidak ditemukan.');
        }

        try {
            Schema::drop($tableName);
            return $this->respond($request, 'success', "Tabel {$tableName} berhasil dihapus permanen.");
        } catch (\Exception $e) {
            return $this->respond($request, 'error', 'Gagal menghapus tabel: ' . $e->getMessage());
        }
    }
}
