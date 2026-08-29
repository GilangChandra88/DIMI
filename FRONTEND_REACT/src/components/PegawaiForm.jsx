import React, { useState, useEffect } from 'react';

const PegawaiForm = ({ onSubmit, onCancel, initialData }) => {
  const [formData, setFormData] = useState({
    nip: '',
    nama: '',
    jabatan: '',
    divisi: '',
    tanggal_bergabung: ''
  });

  useEffect(() => {
    if (initialData) {
      setFormData({
        nip: initialData.nip,
        nama: initialData.nama,
        jabatan: initialData.jabatan,
        divisi: initialData.divisi,
        tanggal_bergabung: initialData.tanggal_bergabung
      });
    }
  }, [initialData]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(formData);
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <div className="modal-header">
          <h2 className="modal-title">{initialData ? 'Edit Data Pegawai' : 'Tambah Pegawai Baru'}</h2>
          <button className="btn btn-secondary btn-sm" onClick={onCancel} style={{ padding: '0.2rem 0.6rem' }}>✖</button>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="modal-body">
            <div className="form-group">
              <label className="form-label">NIP (Nomor Induk Pegawai)</label>
              <input 
                type="text" 
                className="form-control" 
                name="nip"
                value={formData.nip}
                onChange={handleChange}
                required 
                placeholder="Misal: 198001012010011001"
              />
            </div>
            
            <div className="form-group">
              <label className="form-label">Nama Lengkap</label>
              <input 
                type="text" 
                className="form-control" 
                name="nama"
                value={formData.nama}
                onChange={handleChange}
                required 
                placeholder="Nama lengkap pegawai"
              />
            </div>

            <div className="form-group">
              <label className="form-label">Jabatan</label>
              <input 
                type="text" 
                className="form-control" 
                name="jabatan"
                value={formData.jabatan}
                onChange={handleChange}
                required 
                placeholder="Misal: Analis Keimigrasian Ahli Pertama"
              />
            </div>

            <div className="form-group">
              <label className="form-label">Divisi / Seksi</label>
              <select 
                className="form-control" 
                name="divisi"
                value={formData.divisi}
                onChange={handleChange}
                required
              >
                <option value="" disabled>Pilih Divisi</option>
                <option value="Seksi Lalu Lintas Keimigrasian">Seksi Lalu Lintas Keimigrasian</option>
                <option value="Seksi Izin Tinggal dan Status Keimigrasian">Seksi Izin Tinggal dan Status</option>
                <option value="Seksi Intelijen dan Penindakan">Seksi Intelijen dan Penindakan</option>
                <option value="Seksi Informasi dan Komunikasi">Seksi Informasi dan Komunikasi</option>
                <option value="Subbagian Tata Usaha">Subbagian Tata Usaha</option>
              </select>
            </div>

            <div className="form-group" style={{ marginBottom: 0 }}>
              <label className="form-label">Tanggal Bergabung</label>
              <input 
                type="date" 
                className="form-control" 
                name="tanggal_bergabung"
                value={formData.tanggal_bergabung}
                onChange={handleChange}
                required 
              />
            </div>
          </div>
          
          <div className="modal-footer">
            <button type="button" className="btn btn-secondary" onClick={onCancel}>Batal</button>
            <button type="submit" className="btn btn-primary">
              {initialData ? 'Simpan Perubahan' : 'Simpan Pegawai'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default PegawaiForm;
