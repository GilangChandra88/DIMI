import React from 'react';

const PegawaiList = ({ pegawais, onEdit, onDelete, isLoading }) => {
  if (isLoading) {
    return <div className="loading">Memuat data pegawai...</div>;
  }

  if (pegawais.length === 0) {
    return <div className="loading">Belum ada data pegawai.</div>;
  }

  return (
    <div className="table-container">
      <table className="data-table">
        <thead>
          <tr>
            <th>NIP</th>
            <th>Nama Lengkap</th>
            <th>Jabatan</th>
            <th>Divisi</th>
            <th>Tgl Bergabung</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          {pegawais.map((pegawai) => (
            <tr key={pegawai.id}>
              <td>
                <span className="badge">{pegawai.nip}</span>
              </td>
              <td style={{ fontWeight: 500 }}>{pegawai.nama}</td>
              <td>{pegawai.jabatan}</td>
              <td>{pegawai.divisi}</td>
              <td>{new Date(pegawai.tanggal_bergabung).toLocaleDateString('id-ID')}</td>
              <td className="actions">
                <button 
                  className="btn btn-secondary btn-sm" 
                  onClick={() => onEdit(pegawai)}
                >
                  ✎ Edit
                </button>
                <button 
                  className="btn btn-danger btn-sm" 
                  onClick={() => {
                    if (window.confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                      onDelete(pegawai.id);
                    }
                  }}
                >
                  ✖ Hapus
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default PegawaiList;
