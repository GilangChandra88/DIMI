import React, { useState, useEffect } from 'react';
import axios from 'axios';
import PegawaiList from './components/PegawaiList';
import PegawaiForm from './components/PegawaiForm';
import SyncModal from './components/SyncModal';
import UpdateModal from './components/UpdateModal';

// Konfigurasi URL API Backend Laravel
const API_URL = 'http://127.0.0.1:8000/api/pegawai';

function App() {
  const [pegawais, setPegawais] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [isSyncOpen, setIsSyncOpen] = useState(false);
  const [editingPegawai, setEditingPegawai] = useState(null);
  const [notification, setNotification] = useState(null);

  // Fungsi untuk mengambil data dari Backend
  const fetchPegawais = async () => {
    setIsLoading(true);
    try {
      const response = await axios.get(API_URL);
      setPegawais(response.data);
    } catch (error) {
      console.error('Error fetching data:', error);
      showNotification('Gagal memuat data dari server', 'error');
    } finally {
      setIsLoading(false);
    }
  };

  // Panggil fetchPegawais saat komponen pertama kali dimuat
  useEffect(() => {
    fetchPegawais();
  }, []);

  const showNotification = (message, type = 'success') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 3000);
  };

  const handleAddClick = () => {
    setEditingPegawai(null);
    setIsFormOpen(true);
  };

  const handleEditClick = (pegawai) => {
    setEditingPegawai(pegawai);
    setIsFormOpen(true);
  };

  const handleFormClose = () => {
    setIsFormOpen(false);
    setEditingPegawai(null);
  };

  const handleFormSubmit = async (formData) => {
    try {
      if (editingPegawai) {
        // Update Data (PUT)
        await axios.put(`${API_URL}/${editingPegawai.id}`, formData);
        showNotification('Data pegawai berhasil diperbarui!');
      } else {
        // Tambah Data (POST)
        await axios.post(API_URL, formData);
        showNotification('Pegawai baru berhasil ditambahkan!');
      }
      handleFormClose();
      fetchPegawais(); // Muat ulang data
    } catch (error) {
      console.error('Error saving data:', error);
      showNotification('Gagal menyimpan data', 'error');
    }
  };

  const handleDelete = async (id) => {
    try {
      // Hapus Data (DELETE)
      await axios.delete(`${API_URL}/${id}`);
      showNotification('Data pegawai berhasil dihapus!');
      fetchPegawais(); // Muat ulang data
    } catch (error) {
      console.error('Error deleting data:', error);
      showNotification('Gagal menghapus data', 'error');
    }
  };

  return (
    <div className="container">
      {/* Notification Toast */}
      {notification && (
        <div style={{
          position: 'fixed', top: '20px', right: '20px', zIndex: 1100,
          background: notification.type === 'error' ? 'var(--danger)' : 'var(--success)',
          color: 'white', padding: '1rem 1.5rem', borderRadius: '8px',
          boxShadow: '0 4px 6px rgba(0,0,0,0.1)', animation: 'slideUp 0.3s ease'
        }}>
          {notification.message}
        </div>
      )}

      <header className="app-header">
        <div>
          <h1 className="app-title">
            <span style={{fontSize: '2rem'}}>🛂</span> Sistem Informasi Kepegawaian
          </h1>
          <p className="app-subtitle">Kantor Imigrasi Kelas I TPI - Dashboard Admin HR</p>
        </div>
        <div style={{ display: 'flex', gap: '10px' }}>
          <button className="btn btn-secondary" onClick={() => window.location.href = 'http://127.0.0.1:8000/super-admin'} style={{ backgroundColor: 'var(--surface)', color: 'var(--text-muted)' }}>
            🔧 Mode Admin (ERD)
          </button>
          <button className="btn btn-secondary" onClick={() => {
            if (window.__TAURI__) {
              window.__TAURI__.core.invoke('open_settings_window').catch(console.error);
            } else {
              alert("Pengaturan hanya tersedia di aplikasi Desktop.");
            }
          }}>
            ⚙️ Pengaturan
          </button>
          <button className="btn btn-secondary" onClick={() => setIsSyncOpen(true)}>
            🔄 Sinkronisasi Node
          </button>
          <button className="btn btn-primary" onClick={handleAddClick}>
            ➕ Tambah Pegawai
          </button>
        </div>
      </header>

      <main className="glass-panel">
        <PegawaiList 
          pegawais={pegawais} 
          isLoading={isLoading}
          onEdit={handleEditClick}
          onDelete={handleDelete}
        />
      </main>

      {isFormOpen && (
        <PegawaiForm 
          initialData={editingPegawai}
          onSubmit={handleFormSubmit}
          onCancel={handleFormClose}
        />
      )}

      {isSyncOpen && (
        <SyncModal 
          onClose={() => setIsSyncOpen(false)}
          onSyncComplete={fetchPegawais}
          showNotification={showNotification}
        />
      )}

      <UpdateModal />
    </div>
  );
}

export default App;
