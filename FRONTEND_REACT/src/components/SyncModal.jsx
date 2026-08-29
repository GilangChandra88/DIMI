import React, { useState, useEffect } from 'react';
import axios from 'axios';

const SYNC_URL = 'http://127.0.0.1:8000/api/sync';
const IP_URL = 'http://127.0.0.1:8000/api/system/ip';
const NGROK_API_URL = 'http://127.0.0.1:8000/api/system/ngrok';

const SyncModal = ({ onClose, onSyncComplete, showNotification }) => {
  const [localIps, setLocalIps] = useState([]);
  const [masterIp, setMasterIp] = useState('');
  const [isSyncing, setIsSyncing] = useState(false);
  const [isLoadingIp, setIsLoadingIp] = useState(true);

  // State untuk Ngrok
  const [ngrokToken, setNgrokToken] = useState(localStorage.getItem('dimi_ngrok_token') || '');
  const [ngrokUrl, setNgrokUrl] = useState('');
  const [isStartingNgrok, setIsStartingNgrok] = useState(false);

  useEffect(() => {
    // Fetch local IP when modal opens
    const fetchIp = async () => {
      try {
        const response = await axios.get(IP_URL);
        if (response.data && response.data.ips) {
          setLocalIps(response.data.ips);
        }
      } catch (error) {
        console.error('Failed to get IP', error);
      } finally {
        setIsLoadingIp(false);
      }
    };

    // Cek apakah Ngrok sudah menyala sebelumnya
    const checkNgrok = async () => {
      try {
        const res = await axios.get(NGROK_API_URL);
        if (res.data && res.data.url) {
          setNgrokUrl(res.data.url);
        }
      } catch (e) {
        // Abaikan jika tidak aktif
      }
    };

    fetchIp();
    checkNgrok();
  }, []);

  const handleStartNgrok = async () => {
    if (!ngrokToken.trim()) {
      showNotification('Silakan masukkan Ngrok Auth Token terlebih dahulu', 'error');
      return;
    }

    if (!window.__TAURI__) {
      showNotification('Fitur ini hanya tersedia di aplikasi Desktop', 'error');
      return;
    }

    setIsStartingNgrok(true);
    localStorage.setItem('dimi_ngrok_token', ngrokToken.trim());

    try {
      // Panggil API Backend Laravel untuk menjalankan Ngrok (Bypass Tauri ACL)
      await fetch('/api/system/start-ngrok', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: ngrokToken.trim() })
      });
      
      // Polling untuk mendapatkan URL Publik Ngrok
      let attempts = 0;
      const pollUrl = setInterval(async () => {
        attempts++;
        try {
          const res = await axios.get(NGROK_API_URL);
          if (res.data && res.data.url) {
            setNgrokUrl(res.data.url);
            clearInterval(pollUrl);
            setIsStartingNgrok(false);
            showNotification('Server Internet berhasil diaktifkan!', 'success');
          }
        } catch (e) {
          if (attempts > 5) {
            clearInterval(pollUrl);
            setIsStartingNgrok(false);
            showNotification('Gagal mengambil URL Ngrok. Coba lagi.', 'error');
          }
        }
      }, 2000);
    } catch (error) {
      console.error('Ngrok error:', error);
      setIsStartingNgrok(false);
      showNotification(`Gagal memulai Ngrok: ${error}`, 'error');
    }
  };

  const handleSync = async () => {
    if (!masterIp.trim()) {
      showNotification('Silakan masukkan IP atau URL Master terlebih dahulu', 'error');
      return;
    }

    setIsSyncing(true);
    try {
      let payload = { master_ip: masterIp.trim() };
      
      const response = await axios.post(SYNC_URL, payload);
      showNotification(`${response.data.message} (${response.data.count} data)`, 'success');
      onSyncComplete(); // Trigger refresh data di App.jsx
      onClose(); // Tutup modal
    } catch (error) {
      console.error('Sync error:', error);
      const errorMsg = error.response?.data?.message || 'Gagal terhubung ke Master Server';
      showNotification(errorMsg, 'error');
    } finally {
      setIsSyncing(false);
    }
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content" style={{ maxWidth: '600px', maxHeight: '90vh', overflowY: 'auto' }}>
        <div className="modal-header">
          <h2>🔄 Sinkronisasi Jaringan (Node to Node)</h2>
          <button className="btn-close" onClick={onClose}>&times;</button>
        </div>
        
        <div className="modal-body">
          <div style={{ marginBottom: '20px', padding: '15px', backgroundColor: 'var(--surface)', borderRadius: '8px', border: '1px solid var(--border)' }}>
            <h4 style={{ margin: '0 0 10px 0', color: 'var(--primary)' }}>📡 Jadikan Laptop Ini Sebagai Master</h4>
            <p style={{ margin: '0 0 10px 0', fontSize: '0.9rem' }}>
              <strong>Jaringan Lokal (Wi-Fi):</strong> Minta laptop lain memasukkan IP di bawah ini:
            </p>
            {isLoadingIp ? (
              <div style={{ padding: '10px', background: 'rgba(0,0,0,0.2)', borderRadius: '4px' }}>Loading IP...</div>
            ) : (
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', marginBottom: '15px' }}>
                {localIps.map((ip, idx) => (
                  <span key={idx} style={{ 
                    padding: '8px 12px', 
                    background: 'var(--primary)', 
                    color: 'white', 
                    borderRadius: '6px', 
                    fontWeight: 'bold',
                    letterSpacing: '1px'
                  }}>
                    {ip}
                  </span>
                ))}
              </div>
            )}

            <div style={{ borderTop: '1px solid var(--border)', paddingTop: '15px' }}>
              <p style={{ margin: '0 0 10px 0', fontSize: '0.9rem' }}>
                <strong>Jaringan Internet (Ngrok):</strong> Untuk sinkronisasi jarak jauh beda kota/jaringan.
              </p>
              
              {!ngrokUrl ? (
                <div style={{ display: 'flex', gap: '10px' }}>
                  <input 
                    type="password" 
                    className="form-control" 
                    placeholder="Masukkan Auth Token Ngrok" 
                    value={ngrokToken}
                    onChange={(e) => setNgrokToken(e.target.value)}
                  />
                  <button className="btn btn-primary" onClick={handleStartNgrok} disabled={isStartingNgrok} style={{ whiteSpace: 'nowrap' }}>
                    {isStartingNgrok ? 'Memulai...' : 'Online-kan Server'}
                  </button>
                </div>
              ) : (
                <div>
                  <span style={{ 
                    display: 'inline-block',
                    padding: '8px 12px', 
                    background: '#818cf8', 
                    color: 'white', 
                    borderRadius: '6px', 
                    fontWeight: 'bold',
                    letterSpacing: '1px'
                  }}>
                    {ngrokUrl}
                  </span>
                  <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginTop: '5px' }}>
                    Berikan URL di atas ke laptop Client.
                  </p>
                </div>
              )}
            </div>
          </div>

          <div style={{ padding: '15px', backgroundColor: 'var(--surface)', borderRadius: '8px', border: '1px solid var(--border)' }}>
            <h4 style={{ margin: '0 0 10px 0', color: 'var(--accent)' }}>📥 Tarik Data dari Master (Client)</h4>
            <p style={{ margin: '0 0 10px 0', fontSize: '0.9rem', color: 'var(--text-muted)' }}>
              PERINGATAN: Menarik data akan menimpa seluruh data lokal di laptop ini dengan data dari Master.
            </p>
            <div className="form-group">
              <label>IP Lokal / URL Ngrok Master</label>
              <input 
                type="text" 
                className="form-control" 
                placeholder="Contoh: 192.168.1.15 atau https://abc.ngrok.app" 
                value={masterIp}
                onChange={(e) => setMasterIp(e.target.value)}
              />
            </div>
          </div>
        </div>

        <div className="modal-footer" style={{ marginTop: '20px' }}>
          <button className="btn btn-secondary" onClick={onClose} disabled={isSyncing}>
            Batal
          </button>
          <button 
            className="btn btn-primary" 
            onClick={handleSync} 
            disabled={isSyncing}
            style={{ display: 'flex', alignItems: 'center', gap: '8px' }}
          >
            {isSyncing ? (
              <>
                <span className="spinner"></span> Menyinkronkan...
              </>
            ) : 'Mulai Sinkronisasi'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default SyncModal;
