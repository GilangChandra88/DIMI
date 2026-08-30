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

// Tentukan jenis release dari argumen (misal: "npm run release minor")
const releaseType = process.argv[2] || 'patch';

const parts = currentVersion.split('.').map(Number);
if (releaseType === 'major') {
  parts[0] += 1;
  parts[1] = 0;
  parts[2] = 0;
} else if (releaseType === 'minor') {
  parts[1] += 1;
  parts[2] = 0;
} else {
  // default: patch
  parts[2] += 1;
}
const newVersion = parts.join('.');

console.log(`\n🚀 Memulai rilis otomatis untuk DIMI v${newVersion}...\n`);

// Update tauri.conf.json dengan versi baru
tauriConf.version = newVersion;
fs.writeFileSync(tauriConfPath, JSON.stringify(tauriConf, null, 2));
console.log(`✅ Versi diubah menjadi v${newVersion}`);

// 2. Build Aplikasi dengan Tauri & Inject Security Keys
console.log(`\n⚙️ Menjalankan build aplikasi... (Ini mungkin memakan waktu beberapa menit)`);
const privateKeyPath = "C:\\Users\\Gilang Chandra\\.tauri\\dimi.key";
const privateKeyString = fs.readFileSync(privateKeyPath, 'utf8');

try {
  execSync('npm run tauri build', {
    cwd: path.join(rootDir, 'DESKTOP_APP'),
    stdio: 'inherit',
    env: {
      ...process.env,
      TAURI_SIGNING_PRIVATE_KEY: privateKeyString,
      TAURI_SIGNING_PRIVATE_KEY_PASSWORD: "dimi123"
    }
  });
} catch (error) {
  console.error("❌ Gagal saat build aplikasi.");
  process.exit(1);
}

// 3. Update updater.json dengan Signature Baru
console.log(`\n📝 Memperbarui updater.json...`);
const msiDir = path.join(rootDir, 'DESKTOP_APP', 'src-tauri', 'target', 'release', 'bundle', 'msi');
const nsisDir = path.join(rootDir, 'DESKTOP_APP', 'src-tauri', 'target', 'release', 'bundle', 'nsis');
let sigFile = null;
let sigDir = null;
let zipUrlName = null;

if (fs.existsSync(msiDir)) {
  const files = fs.readdirSync(msiDir);
  sigFile = files.find(f => f.endsWith('.msi.sig'));
  if (sigFile) {
    sigDir = msiDir;
    zipUrlName = sigFile.replace('.sig', '');
  }
}

if (!sigFile && fs.existsSync(nsisDir)) {
  const files = fs.readdirSync(nsisDir);
  sigFile = files.find(f => f.endsWith('.exe.sig'));
  if (sigFile) {
    sigDir = nsisDir;
    zipUrlName = sigFile.replace('.sig', '');
  }
}

if (!sigFile) {
  console.error("❌ File .sig tidak ditemukan! Pastikan kunci keamanan dimasukkan dengan benar.");
  process.exit(1);
}

const signature = fs.readFileSync(path.join(sigDir, sigFile), 'utf8').trim();
const updaterData = JSON.parse(fs.readFileSync(updaterJsonPath, 'utf8'));

updaterData.version = newVersion;
updaterData.pub_date = new Date().toISOString();
updaterData.platforms["windows-x86_64"].signature = signature;
updaterData.platforms["windows-x86_64"].url = `https://github.com/GilangChandra88/DIMI/releases/download/v${newVersion}/${zipUrlName}`;

fs.writeFileSync(updaterJsonPath, JSON.stringify(updaterData, null, 2));
console.log(`✅ updater.json berhasil diperbarui dengan versi v${newVersion} dan signature terbaru.`);

// 4. Git Commit dan Push
console.log(`\n🌐 Mendorong (Pushing) ke GitHub...`);
try {
  execSync('git add .', { cwd: rootDir, stdio: 'inherit' });
  execSync(`git commit -m "🚀 Release v${newVersion}"`, { cwd: rootDir, stdio: 'inherit' });
  execSync('git push origin HEAD', { cwd: rootDir, stdio: 'inherit' });
  console.log(`✅ Push berhasil!`);
} catch (error) {
  console.error("⚠️ Push ke Git gagal (mungkin tidak ada perubahan yang di-commit, atau belum ada repo/koneksi internet).");
}

// 5. Buat GitHub Release dan Upload Installer (Jika GITHUB_TOKEN tersedia)
const githubToken = process.env.GITHUB_TOKEN;
const githubRepo = "GilangChandra88/DIMI";

console.log(`\n🎉 SELESAI! Release v${newVersion} sukses diproses di sisi lokal.`);

if (githubToken) {
  console.log(`\n📦 Mengotomatisasi GitHub Release v${newVersion}...`);
  try {
    // Create Release
    const releaseRes = await fetch(`https://api.github.com/repos/${githubRepo}/releases`, {
      method: 'POST',
      headers: {
        'Accept': 'application/vnd.github.v3+json',
        'Authorization': `token ${githubToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        tag_name: `v${newVersion}`,
        name: `DIMI v${newVersion}`,
        body: `Release otomatis DIMI v${newVersion}`,
        draft: false,
        prerelease: false
      })
    });
    
    if (!releaseRes.ok) {
      const errorText = await releaseRes.text();
      throw new Error(`Gagal membuat release: ${releaseRes.status} - ${errorText}`);
    }
    const releaseData = await releaseRes.json();
    console.log(`✅ Release berhasil dibuat! URL: ${releaseData.html_url}`);
    
    // Upload Installer
    const assetPath = path.join(sigDir, zipUrlName);
    console.log(`📤 Mengunggah installer: ${zipUrlName}... (Mungkin butuh waktu beberapa menit)`);
    const assetSize = fs.statSync(assetPath).size;
    const fileBuffer = fs.readFileSync(assetPath);
    
    const uploadRes = await fetch(`https://uploads.github.com/repos/${githubRepo}/releases/${releaseData.id}/assets?name=${zipUrlName}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/vnd.github.v3+json',
        'Authorization': `token ${githubToken}`,
        'Content-Type': 'application/octet-stream',
        'Content-Length': assetSize.toString()
      },
      body: fileBuffer
    });
    
    if (!uploadRes.ok) {
      const errorText = await uploadRes.text();
      throw new Error(`Gagal mengunggah asset: ${uploadRes.status} - ${errorText}`);
    }
    
    console.log(`✅ Installer berhasil diunggah secara otomatis! 🚀`);
  } catch (error) {
    console.error(`❌ Terjadi kesalahan saat integrasi GitHub Release: ${error.message}`);
    console.log(`\n⚠️ Silakan upload secara manual file installer berikut:`);
    console.log(`👉 ${path.join(sigDir, zipUrlName)}`);
    console.log(`Ke: https://github.com/GilangChandra88/DIMI/releases/new dengan tag v${newVersion}`);
  }
} else {
  console.log(`\n⚠️ GITHUB_TOKEN tidak ditemukan di environment.`);
  console.log(`Untuk mengaktifkan unggahan rilis otomatis, buat Personal Access Token (PAT) di GitHub dengan akses 'repo' dan jalankan perintah dengan token tersebut.`);
  console.log(`Contoh di PowerShell:`);
  console.log(`$env:GITHUB_TOKEN="ghp_xxxx..." ; npm run release patch`);
  console.log(`\n👉 Sementara itu, unggah manual installer berikut:`);
  console.log(`👉 ${path.join(sigDir, zipUrlName)}`);
  console.log(`Ke: https://github.com/GilangChandra88/DIMI/releases/new dengan tag v${newVersion}`);
}
