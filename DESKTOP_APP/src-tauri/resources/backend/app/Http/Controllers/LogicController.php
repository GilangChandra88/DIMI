<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class LogicController extends Controller
{
    protected $blacklist = [
        'users', 'password_reset_tokens', 'migrations', 'personal_access_tokens',
        'failed_jobs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'
    ];

    public function getTables()
    {
        $tables = [];
        $dbTables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
        
        foreach ($dbTables as $table) {
            if (!in_array(strtolower($table), $this->blacklist)) {
                $tables[] = $table;
            }
        }
        
        return response()->json($tables);
    }

    public function getHook($table, $event)
    {
        $hookPath = storage_path("app/hooks/{$table}_{$event}.php");
        
        if (File::exists($hookPath)) {
            return response()->json(['code' => File::get($hookPath)]);
        }
        
        return response()->json(['code' => '']);
    }

    public function saveHook(Request $request, $table, $event)
    {
        $request->validate([
            'code' => 'present|string'
        ]);

        $code = $request->input('code');
        $hooksDir = storage_path('app/hooks');

        if (!File::exists($hooksDir)) {
            File::makeDirectory($hooksDir, 0755, true);
        }

        $hookPath = $hooksDir . "/{$table}_{$event}.php";

        if (trim($code) === '') {
            if (File::exists($hookPath)) {
                File::delete($hookPath);
            }
            return response()->json(['message' => 'Hook berhasil dihapus.']);
        }

        // Validate basic PHP syntax (if possible) or just save it
        // Note: In a real production app, arbitrary PHP execution is dangerous.
        // Since DIMI is a local dev tool, this is acceptable.
        
        File::put($hookPath, $code);

        return response()->json(['message' => 'Hook berhasil disimpan.']);
    }
}
