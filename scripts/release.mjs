import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const tauriConfPath = path.join(rootDir, 'DESKTOP_APP', 'src-tauri', 'tauri.conf.json');
const updaterJsonPath = path.join(rootDir, 'updater.json');

// 1. Baca versi saat ini dari tauri.conf.json
const tauriConf = JSON.parse(fs.readFileSync(tauriConfPath, 'utf8'));
const currentVersion = tauriConf.version;

// Auto bump patch version (misal 0.1.0 -> 0.1.1)
const parts = currentVersion.split('.');
parts[2] = parseInt(parts[2], 10) + 1;
const newVersion = parts.join('.');

console.log(`\n🚀 Memulai rilis otomatis untuk DIMI v${newVersion}...\n`);

// Update tauri.conf.json dengan versi baru
tauriConf.version = newVersion;
fs.writeFileSync(tauriConfPath, JSON.stringify(tauriConf, null, 2));
console.log(`✅ Versi diubah menjadi v${newVersion}`);

// 2. Build Aplikasi dengan Tauri & Inject Security Keys
console.log(`\n⚙️ Menjalankan build aplikasi... (Ini mungkin memakan waktu beberapa menit)`);
try {
  execSync('npm run tauri build', {
    cwd: path.join(rootDir, 'DESKTOP_APP'),
    stdio: 'inherit',
    env: {
      ...process.env,
      TAURI_SIGNING_PRIVATE_KEY_PATH: "C:\\Users\\Gilang Chandra\\.tauri\\dimi.key",
      TAURI_SIGNING_PRIVATE_KEY_PASSWORD: "dimi123"
    }
  });
} catch (error) {
  console.error("❌ Gagal saat build aplikasi.");
  process.exit(1);
}

// 3. Update updater.json dengan Signature Baru
console.log(`\n📝 Memperbarui updater.json...`);
const sigDir = path.join(rootDir, 'DESKTOP_APP', 'src-tauri', 'target', 'release', 'bundle', 'msi');
let sigFile = null;

// Cari file berakhiran .msi.zip.sig
if (fs.existsSync(sigDir)) {
  const files = fs.readdirSync(sigDir);
  sigFile = files.find(f => f.endsWith('.msi.zip.sig'));
}

if (!sigFile) {
  console.error("❌ File .msi.zip.sig tidak ditemukan! Pastikan kunci keamanan dimasukkan dengan benar.");
  process.exit(1);
}

const signature = fs.readFileSync(path.join(sigDir, sigFile), 'utf8').trim();
const updaterData = JSON.parse(fs.readFileSync(updaterJsonPath, 'utf8'));

updaterData.version = newVersion;
updaterData.pub_date = new Date().toISOString();
updaterData.platforms["windows-x86_64"].signature = signature;
updaterData.platforms["windows-x86_64"].url = `https://github.com/GilangChandra88/DIMI/releases/download/v${newVersion}/DIMI_${newVersion}_x64_en-US.msi.zip`;

fs.writeFileSync(updaterJsonPath, JSON.stringify(updaterData, null, 2));
console.log(`✅ updater.json berhasil diperbarui dengan versi v${newVersion} dan signature terbaru.`);

// 4. Git Commit dan Push
console.log(`\n🌐 Mendorong (Pushing) ke GitHub...`);
try {
  execSync('git add .', { cwd: rootDir, stdio: 'inherit' });
  execSync(`git commit -m "🚀 Release v${newVersion}"`, { cwd: rootDir, stdio: 'inherit' });
  execSync('git push origin main', { cwd: rootDir, stdio: 'inherit' });
  console.log(`✅ Push berhasil!`);
} catch (error) {
  console.error("⚠️ Push ke Git gagal (mungkin tidak ada perubahan yang di-commit, atau belum ada repo/koneksi internet).");
}

console.log(`\n🎉 SELESAI! Release v${newVersion} sukses diproses.`);
console.log(`\n⚠️ PENTING: Langkah terakhir Anda adalah mengunggah (upload) file installer berikut:`);
console.log(`👉 ${path.join(sigDir, sigFile.replace('.sig', ''))}`);
console.log(`Ke dalam halaman 'Releases' di GitHub: https://github.com/GilangChandra88/DIMI/releases/new dengan tag v${newVersion}`);
