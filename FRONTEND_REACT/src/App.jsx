import React, { useState, useEffect } from 'react';
import axios from 'axios';
import SyncModal from './components/SyncModal';
import UpdateModal from './components/UpdateModal';
import LogicModal from './components/LogicModal';

function App() {
  const [isSyncOpen, setIsSyncOpen] = useState(false);
  const [isLogicOpen, setIsLogicOpen] = useState(false);
  const [notification, setNotification] = useState(null);
  const [sysStatus, setSysStatus] = useState({
    php: 'Checking...',
    ngrok: 'Checking...'
  });

  const showNotification = (message, type = 'success') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 3000);
  };

  useEffect(() => {
    // Check PHP status
    axios.get('http://127.0.0.1:8000/api/system/ip')
      .then(() => setSysStatus(s => ({ ...s, php: 'Online' })))
      .catch(() => setSysStatus(s => ({ ...s, php: 'Offline' })));

    // Check Ngrok Status
    axios.get('http://127.0.0.1:4040/api/tunnels')
      .then(res => {
        if(res.data && res.data.tunnels && res.data.tunnels.length > 0) {
          setSysStatus(s => ({ ...s, ngrok: 'Online' }));
        } else {
          setSysStatus(s => ({ ...s, ngrok: 'Offline' }));
        }
      })
      .catch(() => setSysStatus(s => ({ ...s, ngrok: 'Offline' })));
  }, []);

  const openSettings = () => {
    if (window.__TAURI__) {
      window.__TAURI__.core.invoke('open_settings_window').catch(console.error);
    } else {
      alert("Pengaturan hanya tersedia di aplikasi Desktop.");
    }
  };

  const handleLogicClick = () => {
    setIsLogicOpen(true);
  };

  return (
    <div className="container" style={{ display: 'flex', flexDirection: 'column', height: '100vh', padding: '2rem', gap: '2rem' }}>
      
      {/* Notification Toast */}
      {notification && (
        <div style={{
          position: 'fixed', top: '20px', right: '20px', zIndex: 1100,
          background: notification.type === 'error' ? 'var(--danger)' : (notification.type === 'info' ? '#3b82f6' : 'var(--success)'),
          color: 'white', padding: '1rem 1.5rem', borderRadius: '8px',
          boxShadow: '0 4px 6px rgba(0,0,0,0.1)', animation: 'slideUp 0.3s ease'
        }}>
          {notification.message}
        </div>
      )}

      <header style={{ textAlign: 'center', marginBottom: '1rem' }}>
        <h1 style={{ fontSize: '2.5rem', marginBottom: '0.5rem', background: 'linear-gradient(135deg, #60a5fa, #818cf8)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>
          Dashboard DIMI
        </h1>
        <p style={{ color: 'var(--text-muted)', fontSize: '1.1rem' }}>
          Backend as a Service Platform &middot; Data Information Management Imigrasi
        </p>
      </header>

      <main style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '1.5rem', flex: 1, alignContent: 'center' }}>
        
        {/* Card: Modeler Database */}
        <div className="glass-panel" style={{ padding: '2rem', display: 'flex', flexDirection: 'column', alignItems: 'center', textAlign: 'center', cursor: 'pointer', transition: 'transform 0.2s', border: '1px solid var(--border)' }} 
             onClick={() => window.location.href = 'http://127.0.0.1:8000/super-admin'}
             onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
             onMouseLeave={(e) => e.currentTarget.style.transform = 'none'}>
          <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🗄️</div>
          <h2 style={{ color: 'var(--primary)', marginBottom: '0.5rem' }}>Modeler Database</h2>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Rancang arsitektur tabel database secara visual (ERD) dan dapatkan API seketika.</p>
        </div>

        {/* Card: Business Logic */}
        <div className="glass-panel" style={{ padding: '2rem', display: 'flex', flexDirection: 'column', alignItems: 'center', textAlign: 'center', cursor: 'pointer', transition: 'transform 0.2s', border: '1px solid var(--border)' }}
             onClick={handleLogicClick}
             onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
             onMouseLeave={(e) => e.currentTarget.style.transform = 'none'}>
          <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>⚡</div>
          <h2 style={{ color: 'var(--accent)', marginBottom: '0.5rem' }}>Business Logic</h2>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Kelola API Hooks, validasi data, dan script kalkulasi transaksi kustom (Segera Hadir).</p>
        </div>

        {/* Card: Sinkronisasi Ngrok */}
        <div className="glass-panel" style={{ padding: '2rem', display: 'flex', flexDirection: 'column', alignItems: 'center', textAlign: 'center', cursor: 'pointer', transition: 'transform 0.2s', border: '1px solid var(--border)' }}
             onClick={() => setIsSyncOpen(true)}
             onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
             onMouseLeave={(e) => e.currentTarget.style.transform = 'none'}>
          <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🌐</div>
          <h2 style={{ color: '#10b981', marginBottom: '0.5rem' }}>Jaringan Publik (Ngrok)</h2>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Ekspos API Anda ke internet agar dapat diakses dari aplikasi luar atau cabang lain.</p>
          <div style={{ marginTop: '1rem', padding: '0.2rem 0.8rem', borderRadius: '20px', fontSize: '0.8rem', background: sysStatus.ngrok === 'Online' ? '#10b98120' : '#47556950', color: sysStatus.ngrok === 'Online' ? '#10b981' : '#94a3b8' }}>
            Status: {sysStatus.ngrok}
          </div>
        </div>

        {/* Card: Pengaturan Sistem */}
        <div className="glass-panel" style={{ padding: '2rem', display: 'flex', flexDirection: 'column', alignItems: 'center', textAlign: 'center', cursor: 'pointer', transition: 'transform 0.2s', border: '1px solid var(--border)' }}
             onClick={openSettings}
             onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-5px)'}
             onMouseLeave={(e) => e.currentTarget.style.transform = 'none'}>
          <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>⚙️</div>
          <h2 style={{ color: 'var(--text)', marginBottom: '0.5rem' }}>Pengaturan Desktop</h2>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Atur versi aplikasi, pembaruan (OTA), dan preferensi sistem utama.</p>
        </div>

      </main>

      <footer style={{ textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.85rem' }}>
        <p>Status API Lokal: <strong style={{ color: sysStatus.php === 'Online' ? '#10b981' : '#ef4444' }}>{sysStatus.php}</strong></p>
      </footer>

      {isSyncOpen && (
        <SyncModal 
          onClose={() => setIsSyncOpen(false)}
          onSyncComplete={() => {}}
          showNotification={showNotification}
        />
      )}

      {isLogicOpen && (
        <LogicModal 
          onClose={() => setIsLogicOpen(false)}
          showNotification={showNotification}
        />
      )}

      <UpdateModal />
    </div>
  );
}

export default App;
