use tauri::{Manager, AppHandle, State};
use std::process::Command;
use std::sync::Mutex;

struct PhpProcess(Mutex<Option<std::process::Child>>);
struct NgrokProcess(Mutex<Option<std::process::Child>>);

#[tauri::command]
fn startngrok(app: AppHandle, state: State<'_, NgrokProcess>, token: String) -> Result<String, String> {
    let mut guard = state.0.lock().unwrap();
    if guard.is_some() {
        return Ok("Ngrok is already running".to_string());
    }

    let resource_path = app.path().resource_dir().map_err(|e| e.to_string())?;
    let mut ngrok_path = resource_path.join("ngrok").join("ngrok.exe");
    if !ngrok_path.exists() {
        ngrok_path = resource_path.join("resources").join("ngrok").join("ngrok.exe");
    }

    if !ngrok_path.exists() {
        return Err("ngrok.exe not found".to_string());
    }

    // Auth token configuration
    let mut auth_cmd = Command::new(&ngrok_path);
    auth_cmd.args(["config", "add-authtoken", &token])
            .stdout(std::process::Stdio::null())
            .stderr(std::process::Stdio::null())
            .stdin(std::process::Stdio::null());
    
    #[cfg(target_os = "windows")]
    {
        use std::os::windows::process::CommandExt;
        auth_cmd.creation_flags(0x08000000); // CREATE_NO_WINDOW
    }
    let _ = auth_cmd.output();

    // Start ngrok tunnel
    let mut start_cmd = Command::new(&ngrok_path);
    start_cmd.args(["http", "8000", "--log", "stdout"])
             .stdout(std::process::Stdio::null())
             .stderr(std::process::Stdio::null())
             .stdin(std::process::Stdio::null());
             
    #[cfg(target_os = "windows")]
    {
        use std::os::windows::process::CommandExt;
        start_cmd.creation_flags(0x08000000); // CREATE_NO_WINDOW
    }

    match start_cmd.spawn() {
        Ok(child) => {
            *guard = Some(child);
            Ok("Ngrok started".to_string())
        }
        Err(e) => Err(e.to_string())
    }
}

#[tauri::command]
fn stopngrok(state: State<'_, NgrokProcess>) -> Result<String, String> {
    let mut guard = state.0.lock().unwrap();
    if let Some(mut child) = guard.take() {
        let _ = child.kill();
        let _ = child.wait();
        Ok("Ngrok stopped".to_string())
    } else {
        Ok("Ngrok was not running".to_string())
    }
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_opener::init())
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_process::init())
        .plugin(tauri_plugin_updater::Builder::new().build())
        .setup(|app| {
            // Tentukan path ke Laravel backend (relatif dari executable)
            let resource_path = app.path().resource_dir()
                .expect("Gagal mendapatkan resource dir");
            
            let mut laravel_path = resource_path.join("backend");
            let mut php_path = resource_path.join("php").join("php.exe");
            
            if !php_path.exists() {
                // Coba cari di dalam folder "resources" jika di Windows ter-nested
                laravel_path = resource_path.join("resources").join("backend");
                php_path = resource_path.join("resources").join("php").join("php.exe");
            }
            
            // Variabel untuk menampung path yang akan disuntikkan ke PHP (hanya saat production)
            let mut env_storage_path = None;
            let mut env_db_path = None;

            // Fallback untuk development: gunakan path sistem
            let (php_bin, laravel_dir) = if php_path.exists() {
                println!("[DIMI] Production Mode Detected.");
                
                // Siapkan path di AppData untuk storage dan database
                let app_data_dir = app.path().app_data_dir().expect("Gagal mendapatkan app data dir");
                let storage_dir = app_data_dir.join("storage");
                let db_dir = app_data_dir.join("database");
                let db_file = db_dir.join("database.sqlite");

                // Buat struktur direktori untuk Laravel storage agar tidak error
                std::fs::create_dir_all(storage_dir.join("framework").join("views")).unwrap();
                std::fs::create_dir_all(storage_dir.join("framework").join("cache")).unwrap();
                std::fs::create_dir_all(storage_dir.join("framework").join("sessions")).unwrap();
                std::fs::create_dir_all(storage_dir.join("logs")).unwrap();
                std::fs::create_dir_all(&db_dir).unwrap();

                // Buat file database jika belum ada
                if !db_file.exists() {
                    std::fs::File::create(&db_file).unwrap();
                }

                env_storage_path = Some(storage_dir.to_string_lossy().to_string());
                env_db_path = Some(db_file.to_string_lossy().to_string());

                // Jalankan artisan migrate --force
                println!("[DIMI] Running database migrations...");
                #[cfg(target_os = "windows")]
                use std::os::windows::process::CommandExt;
                
                let mut migrate_cmd = Command::new(&php_path);
                migrate_cmd.args(["artisan", "migrate", "--force"])
                    .current_dir(&laravel_path)
                    .env("APP_STORAGE_PATH", env_storage_path.as_ref().unwrap())
                    .env("DB_DATABASE", env_db_path.as_ref().unwrap())
                    .env("APP_KEY", "base64:XRgSGiKm4H9+Nyg/ulmvRBM95rtNe3P3abBrzz/VO3s=")
                    .env("APP_ENV", "production")
                    .stdout(std::process::Stdio::null())
                    .stderr(std::process::Stdio::null())
                    .stdin(std::process::Stdio::null());

                #[cfg(target_os = "windows")]
                migrate_cmd.creation_flags(0x08000000); // CREATE_NO_WINDOW

                let _ = migrate_cmd.output();

                (php_path.to_string_lossy().to_string(), laravel_path.to_string_lossy().to_string())
            } else {
                // Development mode: gunakan PHP dan Laravel dari path sistem
                let cwd = std::env::current_dir().unwrap();
                let mut search_dir = cwd.as_path();
                let mut laravel_dir_path = cwd.clone();
                let mut found = false;
                for _ in 0..5 {
                    let candidate = search_dir.join("BACKEND_LARAVEL");
                    if candidate.exists() {
                        laravel_dir_path = candidate.canonicalize().unwrap();
                        found = true;
                        break;
                    }
                    match search_dir.parent() {
                        Some(parent) => search_dir = parent,
                        None => break,
                    }
                }
                if !found {
                    println!("[DIMI] WARNING: BACKEND_LARAVEL not found! Searched from: {}", cwd.display());
                }
                ("php".to_string(), laravel_dir_path.to_string_lossy().to_string())
            };
            
            println!("[DIMI] Starting PHP server...");
            println!("[DIMI] PHP: {}", php_bin);
            println!("[DIMI] Laravel: {}", laravel_dir);
            
            // Jalankan php artisan serve
            let mut cmd = Command::new(&php_bin);
            cmd.args(["artisan", "serve", "--host=0.0.0.0", "--port=8000"])
               .current_dir(&laravel_dir)
               .stdout(std::process::Stdio::null())
               .stderr(std::process::Stdio::null())
               .stdin(std::process::Stdio::null());

            #[cfg(target_os = "windows")]
            {
                use std::os::windows::process::CommandExt;
                cmd.creation_flags(0x08000000); // CREATE_NO_WINDOW
            }

            // Jika production, inject environment variables
            if let Some(storage) = env_storage_path {
                cmd.env("APP_STORAGE_PATH", storage);
                cmd.env("APP_ENV", "production");
                cmd.env("APP_DEBUG", "false");
                cmd.env("APP_KEY", "base64:XRgSGiKm4H9+Nyg/ulmvRBM95rtNe3P3abBrzz/VO3s=");
            }
            if let Some(db) = env_db_path {
                cmd.env("DB_DATABASE", db);
                cmd.env("DB_CONNECTION", "sqlite");
            }

            let child = cmd.spawn();

            // Simpan process handle agar bisa di-kill saat app ditutup
            match child {
                Ok(process) => {
                    println!("[DIMI] PHP server started successfully.");
                    app.manage(PhpProcess(Mutex::new(Some(process))));
                }
                Err(e) => {
                    println!("[DIMI] WARNING: Could not start PHP server: {}. Assuming it's already running.", e);
                    app.manage(PhpProcess(Mutex::new(None)));
                }
            }

            // Tunggu sebentar agar PHP server siap, lalu buka URL
            let window = app.get_webview_window("main").unwrap();
            std::thread::spawn(move || {
                std::thread::sleep(std::time::Duration::from_secs(2));
                let _ = window.navigate("http://127.0.0.1:8000/".parse().unwrap());
            });

            Ok(())
        })
        .on_window_event(|window, event| {
            // Matikan PHP server saat window ditutup
            if let tauri::WindowEvent::Destroyed = event {
                let app = window.app_handle();
                if let Some(state) = app.try_state::<PhpProcess>() {
                    if let Ok(mut guard) = state.0.lock() {
                        if let Some(mut child) = guard.take() {
                            println!("[DIMI] Stopping PHP server...");
                            let _ = child.kill();
                            let _ = child.wait();
                            println!("[DIMI] PHP server stopped.");
                        }
                    }
                }
                if let Some(state) = app.try_state::<NgrokProcess>() {
                    if let Ok(mut guard) = state.0.lock() {
                        if let Some(mut child) = guard.take() {
                            println!("[DIMI] Stopping Ngrok...");
                            let _ = child.kill();
                            let _ = child.wait();
                            println!("[DIMI] Ngrok stopped.");
                        }
                    }
                }
            }
        })
        .invoke_handler(tauri::generate_handler![startngrok, stopngrok])
        .manage(NgrokProcess(Mutex::new(None)))
        .run(tauri::generate_context!())
        .expect("error while running DIMI");
}
