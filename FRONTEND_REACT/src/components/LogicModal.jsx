import React, { useState, useEffect } from 'react';
import axios from 'axios';
import Editor from '@monaco-editor/react';

const API_BASE = 'http://127.0.0.1:8000/api/system';

const LogicModal = ({ onClose, showNotification }) => {
  const [tables, setTables] = useState([]);
  const [selectedTable, setSelectedTable] = useState('');
  const [selectedEvent, setSelectedEvent] = useState('before_insert');
  const [code, setCode] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  const events = [
    { value: 'before_insert', label: 'Before Insert (POST)' },
    { value: 'after_insert', label: 'After Insert (POST)' },
    { value: 'before_update', label: 'Before Update (PUT)' },
    { value: 'after_update', label: 'After Update (PUT)' },
    { value: 'before_delete', label: 'Before Delete (DELETE)' },
    { value: 'after_delete', label: 'After Delete (DELETE)' },
  ];

  const defaultCode = `<?php
// Hook: \${selectedEvent} pada tabel \${selectedTable}
// Akses data request melalui variabel $data (array asosiatif).
// Anda dapat melempar Exception untuk membatalkan proses:
// throw new \\Exception("Pesan Error");

`;

  useEffect(() => {
    // Ambil daftar tabel
    axios.get(`${API_BASE}/tables`)
      .then(res => {
        setTables(res.data);
        if (res.data.length > 0) {
          setSelectedTable(res.data[0]);
        }
      })
      .catch(err => {
        showNotification('Gagal memuat daftar tabel.', 'error');
      });
  }, []);

  useEffect(() => {
    if (selectedTable && selectedEvent) {
      loadHookCode();
    }
  }, [selectedTable, selectedEvent]);

  const loadHookCode = async () => {
    setIsLoading(true);
    try {
      const res = await axios.get(`${API_BASE}/hooks/${selectedTable}/${selectedEvent}`);
      if (res.data.code && res.data.code.trim() !== '') {
        setCode(res.data.code);
      } else {
        setCode(defaultCode.replace('${selectedEvent}', selectedEvent).replace('${selectedTable}', selectedTable));
      }
    } catch (e) {
      showNotification('Gagal memuat script hook.', 'error');
    } finally {
      setIsLoading(false);
    }
  };

  const handleSave = async () => {
    if (!selectedTable) return;
    setIsSaving(true);
    try {
      await axios.post(`${API_BASE}/hooks/${selectedTable}/${selectedEvent}`, { code });
      showNotification('Script logika berhasil disimpan!', 'success');
      onClose();
    } catch (e) {
      showNotification('Gagal menyimpan script.', 'error');
    } finally {
      setIsSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!confirm('Apakah Anda yakin ingin menghapus script logika ini?')) return;
    setIsSaving(true);
    try {
      await axios.post(`${API_BASE}/hooks/${selectedTable}/${selectedEvent}`, { code: '' });
      showNotification('Script logika berhasil dihapus!', 'success');
      onClose();
    } catch (e) {
      showNotification('Gagal menghapus script.', 'error');
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div style={{
      position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
      backgroundColor: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)',
      display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000
    }}>
      <div className="glass-panel" style={{ width: '90%', maxWidth: '900px', height: '85vh', display: 'flex', flexDirection: 'column' }}>
        
        <div style={{ padding: '1.5rem', borderBottom: '1px solid var(--border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div>
            <h2 style={{ fontSize: '1.5rem', color: 'var(--accent)', marginBottom: '0.2rem' }}>⚡ Editor Business Logic</h2>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Tulis script PHP kustom yang dieksekusi saat API dipanggil.</p>
          </div>
          <button onClick={onClose} style={{ background: 'transparent', border: 'none', color: 'var(--text-muted)', fontSize: '1.5rem', cursor: 'pointer' }}>&times;</button>
        </div>

        <div style={{ padding: '1rem 1.5rem', display: 'flex', gap: '1rem', background: '#0f172a' }}>
          <div style={{ flex: 1 }}>
            <label style={{ display: 'block', fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.4rem' }}>Pilih Tabel</label>
            <select className="form-input" value={selectedTable} onChange={(e) => setSelectedTable(e.target.value)} style={{ width: '100%', background: '#1e293b', color: 'white', border: '1px solid #334155', padding: '0.6rem', borderRadius: '6px' }}>
              {tables.map(t => <option key={t} value={t}>{t}</option>)}
            </select>
          </div>
          <div style={{ flex: 1 }}>
            <label style={{ display: 'block', fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.4rem' }}>Pilih Trigger Event</label>
            <select className="form-input" value={selectedEvent} onChange={(e) => setSelectedEvent(e.target.value)} style={{ width: '100%', background: '#1e293b', color: 'white', border: '1px solid #334155', padding: '0.6rem', borderRadius: '6px' }}>
              {events.map(e => <option key={e.value} value={e.value}>{e.label}</option>)}
            </select>
          </div>
        </div>

        <div style={{ flex: 1, position: 'relative' }}>
          {isLoading && (
            <div style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(15,23,42,0.8)', zIndex: 10, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <span>Memuat...</span>
            </div>
          )}
          <Editor
            height="100%"
            theme="vs-dark"
            language="php"
            value={code}
            onChange={(value) => setCode(value)}
            options={{
              minimap: { enabled: false },
              fontSize: 14,
              fontFamily: "'JetBrains Mono', 'Fira Code', monospace"
            }}
          />
        </div>

        <div style={{ padding: '1rem 1.5rem', borderTop: '1px solid var(--border)', display: 'flex', justifyContent: 'space-between' }}>
          <button className="btn" style={{ background: 'transparent', color: 'var(--danger)' }} onClick={handleDelete} disabled={isSaving}>
            Hapus Script
          </button>
          <div style={{ display: 'flex', gap: '10px' }}>
            <button className="btn btn-secondary" onClick={onClose}>Batal</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={isSaving || !selectedTable}>
              {isSaving ? 'Menyimpan...' : 'Simpan Logika'}
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};

export default LogicModal;
