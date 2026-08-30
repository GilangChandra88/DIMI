const { invoke } = window.__TAURI__.core;
const { getVersion } = window.__TAURI__.app;

const currentVersionEl = document.getElementById('current-version');
const updateStatusEl = document.getElementById('update-status');
const updateLoader = document.getElementById('update-loader');
const btnUpdate = document.getElementById('btn-update');
const autoUpdateToggle = document.getElementById('auto-update-toggle');

// Local storage untuk menyimpan pengaturan auto-update
const AUTO_UPDATE_KEY = 'dimi_auto_update_on_close';
const isAutoUpdateEnabled = localStorage.getItem(AUTO_UPDATE_KEY) !== 'false';
autoUpdateToggle.checked = isAutoUpdateEnabled;

autoUpdateToggle.addEventListener('change', (e) => {
  localStorage.setItem(AUTO_UPDATE_KEY, e.target.checked);
});

async function init() {
  try {
    const version = await getVersion();
    currentVersionEl.textContent = `v${version}`;
    
    await checkForUpdates();
  } catch (error) {
    console.error("Gagal inisialisasi:", error);
    updateStatusEl.textContent = "Gagal memuat pengaturan.";
  }
}

async function checkForUpdates() {
  try {
    updateLoader.style.display = 'block';
    updateStatusEl.textContent = 'Memeriksa pembaruan dari server...';
    
    // Tauri v2 invoke for updater
    const updateVersion = await invoke('check_update');
    
    updateLoader.style.display = 'none';
    
    if (updateVersion) {
      updateStatusEl.textContent = `Versi terbaru tersedia: v${updateVersion}`;
      
      // Jika auto-update aktif, kita bisa download di latar belakang
      if (autoUpdateToggle.checked) {
        updateStatusEl.textContent += " (Update akan berjalan otomatis saat aplikasi ditutup)";
      }
      
      btnUpdate.style.display = 'block';
      btnUpdate.onclick = async () => {
        btnUpdate.disabled = true;
        btnUpdate.textContent = 'Mengunduh & Memasang...';
        updateStatusEl.textContent = 'Harap tunggu... Aplikasi akan direstart otomatis.';
        try {
          // Install dan restart
          await invoke('install_update');
        } catch (e) {
          console.error("Gagal update:", e);
          updateStatusEl.textContent = "Gagal memperbarui aplikasi.";
          btnUpdate.style.display = 'none';
        }
      };
    } else {
      updateStatusEl.textContent = 'Aplikasi Anda sudah versi terbaru! 🎉';
      updateStatusEl.style.color = '#34d399';
    }
  } catch (error) {
    updateLoader.style.display = 'none';
    updateStatusEl.textContent = 'Gagal memeriksa pembaruan. Pastikan Anda terhubung ke internet.';
    console.error("Update error:", error);
  }
}

document.addEventListener('DOMContentLoaded', init);
