<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;

class SyncController extends Controller
{
    // Mengambil IP Address Lokal Laptop
    public function getLocalIp()
    {
        $ips = [];
        
        // Coba ambil dari ipconfig untuk Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('ipconfig', $output);
            foreach ($output as $line) {
                // Filter hanya IP lokal / LAN (192.168.x.x, 10.x.x.x, 172.16.x.x)
                if (preg_match('/IPv4 Address.*: (192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+|172\.(1[6-9]|2[0-9]|3[0-1])\.\d+\.\d+)/', $line, $matches)) {
                    $ips[] = $matches[1];
                }
            }
        }

        // Fallback jika kosong
        if (empty($ips)) {
            $ips[] = gethostbyname(gethostname());
        }

        return response()->json(['ips' => $ips]);
    }

    // Memulai Ngrok via PHP
    public function startNgrok(Request $request)
    {
        $token = $request->input('token');
        if (!$token) {
            return response()->json(['error' => 'Token required'], 400);
        }

        $ngrokPath = base_path('../ngrok/ngrok.exe'); // Path produksi jika backend dan ngrok sebelahan di resources
        if (!file_exists($ngrokPath)) {
            $ngrokPath = base_path('../resources/ngrok/ngrok.exe'); // Fallback path produksi
        }
        if (!file_exists($ngrokPath)) {
            $ngrokPath = base_path('resources/ngrok/ngrok.exe'); // Path saat masa development (BACKEND_LARAVEL/resources/ngrok)
        }
        
        if (!file_exists($ngrokPath)) {
            return response()->json(['error' => 'ngrok.exe not found at ' . $ngrokPath], 404);
        }

        // Set Auth Token
        exec('"' . $ngrokPath . '" config add-authtoken ' . escapeshellarg($token));

        // Jalankan Ngrok di background
        pclose(popen('start /B "" "' . $ngrokPath . '" http 8000', 'r'));

        return response()->json(['message' => 'Ngrok started']);
    }

    // Mengambil URL Publik Ngrok jika aktif
    public function getNgrokUrl()
    {
        try {
            $response = Http::timeout(3)->get('http://127.0.0.1:4040/api/tunnels');
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['tunnels']) && count($data['tunnels']) > 0) {
                    // Filter untuk mencari URL HTTP/HTTPS
                    foreach ($data['tunnels'] as $tunnel) {
                        if (strpos($tunnel['public_url'], 'https://') === 0 || strpos($tunnel['public_url'], 'http://') === 0) {
                            return response()->json(['url' => $tunnel['public_url']]);
                        }
                    }
                }
            }
            return response()->json(['url' => null], 404);
        } catch (\Exception $e) {
            return response()->json(['url' => null, 'error' => $e->getMessage()], 404);
        }
    }

    // Melakukan sinkronisasi (Menarik data dari Master)
    public function pullSync(Request $request)
    {
        $request->validate([
            'master_ip' => 'required|string'
        ]);

        $masterIp = $request->input('master_ip');
        
        // Cek apakah input mengandung http atau https (berarti Ngrok URL)
        if (strpos($masterIp, 'http://') === 0 || strpos($masterIp, 'https://') === 0) {
            // Hilangkan trailing slash jika ada
            $masterIp = rtrim($masterIp, '/');
            $url = "{$masterIp}/api/pegawai";
        } else {
            // Format IP lokal biasa (192.168.x.x)
            $url = "http://{$masterIp}:8000/api/pegawai";
        }

        try {
            $response = Http::timeout(15)->get($url);

            if ($response->successful()) {
                $masterData = $response->json();

                DB::beginTransaction();
                try {
                    // Hapus data lama agar persis sama dengan Master (Satu Arah)
                    Pegawai::truncate();
                    
                    // Insert batch data baru
                    foreach ($masterData as $pegawai) {
                        Pegawai::insert([
                            'id' => $pegawai['id'],
                            'nip' => $pegawai['nip'],
                            'nama' => $pegawai['nama'],
                            'jabatan' => $pegawai['jabatan'],
                            'divisi' => $pegawai['divisi'],
                            'tanggal_bergabung' => $pegawai['tanggal_bergabung'],
                            'created_at' => $pegawai['created_at'],
                            'updated_at' => $pegawai['updated_at'],
                        ]);
                    }
                    
                    DB::commit();
                    return response()->json(['message' => 'Sinkronisasi berhasil!', 'count' => count($masterData)]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['message' => 'Gagal menyimpan sinkronisasi', 'error' => $e->getMessage()], 500);
                }
            } else {
                return response()->json(['message' => 'Gagal menghubungi Server Master. Coba periksa IP dan koneksi Wi-Fi.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Koneksi ke Master Timeout/Gagal. Pastikan IP benar.', 'error' => $e->getMessage()], 500);
        }
    }
}
