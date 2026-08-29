import React, { useState, useEffect } from 'react';
import { check } from '@tauri-apps/plugin-updater';
import { relaunch } from '@tauri-apps/plugin-process';

const UpdateModal = () => {
  const [updateInfo, setUpdateInfo] = useState(null);
  const [isUpdating, setIsUpdating] = useState(false);
  const [downloadProgress, setDownloadProgress] = useState(0);

  useEffect(() => {
    // Only run in Tauri environment
    if (window.__TAURI__) {
      checkForUpdates();
    }
  }, []);

  const checkForUpdates = async () => {
    try {
      const update = await check();
      if (update) {
        setUpdateInfo(update);
      }
    } catch (error) {
      console.error('Failed to check for updates', error);
    }
  };

  const handleInstallUpdate = async () => {
    setIsUpdating(true);
    let downloaded = 0;
    let contentLength = 0;

    try {
      await updateInfo.downloadAndInstall((event) => {
        switch (event.event) {
          case 'Started':
            contentLength = event.contentLength;
            break;
          case 'Progress':
            downloaded += event.chunkLength;
            if (contentLength) {
              setDownloadProgress(Math.round((downloaded / contentLength) * 100));
            }
            break;
          case 'Finished':
            break;
        }
      });
      // Restart the app after installing
      await relaunch();
    } catch (error) {
      console.error('Failed to install update', error);
      setIsUpdating(false);
    }
  };

  if (!updateInfo) return null;

  return (
    <div style={{
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1000,
      display: 'flex', alignItems: 'center', justifyContent: 'center'
    }}>
      <div className="glass-panel" style={{
        width: '450px', background: 'white', padding: '2rem',
        borderRadius: '16px', textAlign: 'center',
        boxShadow: '0 20px 40px rgba(0,0,0,0.2)'
      }}>
        <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🎁</div>
        <h2 style={{ marginBottom: '1rem' }}>Pembaruan Tersedia!</h2>
        <p style={{ color: 'var(--text-muted)', marginBottom: '2rem' }}>
          Versi terbaru <strong>v{updateInfo.version}</strong> telah dirilis.
          <br/>
          {updateInfo.body && <span><br/>Catatan: {updateInfo.body}</span>}
        </p>

        {isUpdating ? (
          <div>
            <p>Mengunduh pembaruan... {downloadProgress}%</p>
            <div style={{
              width: '100%', background: '#eee', borderRadius: '8px',
              height: '10px', overflow: 'hidden', marginTop: '0.5rem'
            }}>
              <div style={{
                height: '100%', background: 'var(--primary)',
                width: `${downloadProgress}%`, transition: 'width 0.2s'
              }}></div>
            </div>
          </div>
        ) : (
          <div style={{ display: 'flex', gap: '10px', justifyContent: 'center' }}>
            <button className="btn btn-secondary" onClick={() => setUpdateInfo(null)}>
              Nanti Saja
            </button>
            <button className="btn btn-primary" onClick={handleInstallUpdate}>
              Unduh & Instal Sekarang
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default UpdateModal;
