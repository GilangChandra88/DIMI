<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Dashboard V5 - Drag & Drop ERD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/leader-line-new@1.1.9/leader-line.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; overflow: hidden; margin: 0; }
        .erd-canvas {
            background-color: #f8fafc;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
            position: relative;
            cursor: grab;
            overflow: auto;
        }
        .erd-canvas:active { cursor: grabbing; }
        .table-card {
            position: absolute;
            width: 220px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            border: 1px solid #e2e8f0;
            z-index: 10;
        }
        .table-card-header {
            background: #0f172a;
            color: white;
            padding: 8px 12px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-card-body {
            padding: 8px 0;
            font-size: 12px;
            color: #475569;
        }
        .column-item {
            padding: 4px 12px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
        }
        .column-item:last-child { border-bottom: none; }
        .key-icon { color: #f59e0b; margin-right: 6px; font-weight: bold; }
        .relation-icon { color: #3b82f6; margin-right: 6px; font-weight: bold; font-size: 14px; }
        
        .selected-table-card {
            box-shadow: 0 0 0 2px #3b82f6, 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        .resizer {
            height: 8px;
            background: #e2e8f0;
            cursor: row-resize;
            width: 100%;
            z-index: 20;
        }
        .resizer:hover { background: #cbd5e1; }
        
        .leader-line { z-index: 5 !important; }
        
        /* Virtual node untuk animasi drag-and-drop */
        #virtual-mouse-node {
            position: fixed;
            width: 1px;
            height: 1px;
            background: transparent;
            pointer-events: none;
            z-index: 9999;
        }

        #erd-cards {
            width: 100%;
            height: 100%;
            position: absolute;
            transform-origin: 0 0;
            will-change: transform;
        }
    </style>
</head>
<body class="flex h-screen text-gray-800">

    <!-- Pointer Tracker -->
    <div id="virtual-mouse-node"></div>

    <!-- Panel Kiri: API Documentation -->
    <aside class="w-80 bg-slate-900 text-white flex flex-col z-30 shadow-xl relative shrink-0">
        <div class="h-14 flex items-center justify-between px-6 border-b border-slate-800 shrink-0">
            <div class="flex items-center gap-3">
                <a href="/" class="text-slate-400 hover:text-white transition-colors" title="Kembali ke Dashboard React">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h1 class="text-[15px] font-bold tracking-wider text-blue-400">API DOCS</h1>
            </div>
            <button id="toggle-sys-btn" onclick="toggleSystemTables()" class="text-[10px] bg-slate-800 hover:bg-slate-700 text-slate-300 px-2 py-1 rounded border border-slate-700 transition-colors flex items-center gap-1">
                <span>👁️</span> <span id="toggle-sys-text">Show System</span>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6" id="api-docs-container">
            <div class="flex flex-col items-center justify-center h-full text-slate-500 text-center">
                <svg class="w-16 h-16 mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                <p>Klik salah satu tabel di area ERD (kanan) untuk melihat dokumentasi API-nya secara detail di sini.</p>
            </div>
        </div>
    </aside>

    <!-- Panel Kanan: Atas (ERD) dan Bawah (Data) -->
    <main class="flex-1 flex flex-col h-screen bg-white min-w-0 relative">
        
        <!-- Panel Tengah Atas: Visual ERD -->
        <div id="erd-container" class="erd-canvas flex-1 relative outline-none" tabindex="0">
            <div id="erd-cards"></div>
            
            <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur border border-slate-200 shadow-lg rounded-lg p-3 text-xs text-slate-600 z-40 pointer-events-none">
                <p class="font-bold mb-1 text-slate-800 flex items-center"><span class="text-sm mr-1">✨</span> Panduan (V6):</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><b>Pan & Zoom</b>: Scroll/Pinch trackpad atau tahan klik kiri di area kosong</li>
                    <li><b>Drag Icon 🔗</b>: Tarik ke tabel lain untuk membuat Relasi!</li>
                    <li><b>Drag Nama Tabel</b>: Pindahkan posisi kartu</li>
                    <li><b>Klik Kanan</b>: Menu Tambah Tabel</li>
                </ul>
            </div>
        </div>

        <div class="resizer" id="resizer"></div>

        <!-- Panel Tengah Bawah: Data Viewer -->
        <div id="data-container" class="h-1/2 flex flex-col bg-white border-t border-gray-200 shrink-0 relative z-30">
            <div class="h-12 bg-gray-50 border-b border-gray-200 flex items-center px-6 justify-between shrink-0">
                <h3 class="font-semibold text-gray-800" id="data-title">Data Explorer</h3>
            </div>
            <div class="flex-1 overflow-auto relative p-4" id="data-content">
                <div class="h-full flex flex-col items-center justify-center text-gray-400">
                    <p>Data tabel akan muncul di sini setelah tabel dipilih.</p>
                </div>
            </div>
            
            <div id="loading-overlay" class="absolute inset-0 bg-white/80 flex items-center justify-center hidden z-10">
                <div class="text-blue-600 font-semibold animate-pulse">Menarik Data dari API...</div>
            </div>
        </div>
    </main>

    <!-- Context Menu Custom -->
    <div id="context-menu" class="hidden absolute bg-white border border-gray-200 shadow-xl rounded-lg overflow-hidden z-50 w-56">
        <div class="px-4 py-2 bg-slate-50 border-b border-gray-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi Database</div>
        <button onclick="openAddTableModal()" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center transition-colors">
            <span class="mr-3 text-lg">➕</span> Buat Tabel Baru
        </button>
        <button onclick="arrangeCards()" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-slate-50 flex items-center border-t border-gray-100 transition-colors">
            <span class="mr-3 text-lg">🔄</span> Reset & Rapikan Kartu
        </button>
    </div>

    <!-- Modal Tambah Tabel -->
    <div id="addTableModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100]">
        <!-- ... Sama seperti V4 ... -->
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">Buat Tabel Baru</h3>
                <button type="button" onclick="document.getElementById('addTableModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="{{ route('superadmin.createTable') }}" method="POST" class="flex-1 overflow-y-auto">
                @csrf
                <div class="p-6">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Tabel (huruf kecil, plural, cth: cutis)</label>
                        <input type="text" name="table_name" required pattern="^[a-zA-Z_]+$"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-2 flex justify-between items-center">
                        <label class="block text-sm font-medium text-gray-700">Daftar Kolom</label>
                        <button type="button" onclick="addColumn()" class="text-xs bg-blue-100 text-blue-700 px-3 py-1.5 rounded-md font-medium">
                            + Tambah Kolom
                        </button>
                    </div>
                    <div id="columns-container" class="space-y-3 bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <div class="flex gap-3 column-row">
                            <input type="text" name="columns[0][name]" placeholder="Nama Kolom" required
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <select name="columns[0][type]" class="w-1/3 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
                                <option value="string">String (Teks)</option>
                                <option value="integer">Integer (Angka)</option>
                                <option value="date">Date (Tanggal)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addTableModal').classList.add('hidden')" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">Buat Tabel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Cerdas: Pilihan Tipe Relasi (Drag & Drop Result) -->
    <div id="smartRelationModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100]">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800 flex items-center">
                    <span class="mr-2 text-blue-500">🔗</span> Smart Relation Builder
                </h3>
                <button type="button" onclick="document.getElementById('smartRelationModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6">
                <p class="text-slate-600 text-center mb-6">Kamu sedang menghubungkan <strong id="sr-source" class="text-emerald-600 text-lg">X</strong> ke <strong id="sr-target" class="text-blue-600 text-lg">Y</strong>.<br>Pilih jenis hubungan yang kamu inginkan:</p>
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Opsi One to Many -->
                    <form action="{{ route('superadmin.createRelation') }}" method="POST" class="border-2 border-slate-200 hover:border-emerald-400 hover:shadow-lg rounded-xl p-5 cursor-pointer transition-all group bg-white relative" onclick="this.querySelector('button').click()">
                        @csrf
                        <input type="hidden" name="source_table" id="sr-normal-source">
                        <input type="hidden" name="target_table" id="sr-normal-target">
                        <button type="submit" class="hidden"></button>
                        
                        <div class="text-emerald-500 mb-2">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                        </div>
                        <h4 class="text-lg font-bold text-slate-800 mb-1 group-hover:text-emerald-600">One-to-Many (Normal)</h4>
                        <p class="text-xs text-slate-500">Menambahkan kolom Foreign Key (<code class="bg-slate-100 text-pink-500 px-1 rounded" id="sr-normal-col">id</code>) ke dalam tabel <b id="sr-normal-srcname">X</b>.</p>
                        <div class="mt-4 flex items-center justify-center text-xs font-bold text-emerald-600 bg-emerald-50 rounded py-2 opacity-0 group-hover:opacity-100 transition-opacity">Buat Relasi Ini →</div>
                    </form>

                    <!-- Opsi Many to Many (Pivot) -->
                    <form action="{{ route('superadmin.createPivotRelation') }}" method="POST" class="border-2 border-slate-200 hover:border-blue-400 hover:shadow-lg rounded-xl p-5 cursor-pointer transition-all group bg-white relative" onclick="this.querySelector('button').click()">
                        @csrf
                        <input type="hidden" name="source_table" id="sr-pivot-source">
                        <input type="hidden" name="target_table" id="sr-pivot-target">
                        <button type="submit" class="hidden"></button>
                        
                        <div class="text-blue-500 mb-2">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <h4 class="text-lg font-bold text-slate-800 mb-1 group-hover:text-blue-600">Many-to-Many (Pivot)</h4>
                        <p class="text-xs text-slate-500">Menciptakan satu <b>Tabel Pivot Baru</b> secara otomatis (<code class="bg-slate-100 text-pink-500 px-1 rounded" id="sr-pivot-name">x_y</code>) untuk menghubungkan keduanya.</p>
                        <div class="mt-4 flex items-center justify-center text-xs font-bold text-blue-600 bg-blue-50 rounded py-2 opacity-0 group-hover:opacity-100 transition-opacity">Buat Pivot Tabel →</div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data & Script -->
    <script>
        let dbSchema = @json($schema);
        const blacklist = ['users', 'password_reset_tokens', 'migrations', 'personal_access_tokens', 'failed_jobs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'];
        
        let selectedCard = null;
        let activeLines = [];
        let showSystemTables = false;

        function toggleSystemTables() {
            showSystemTables = !showSystemTables;
            const btnText = document.getElementById('toggle-sys-text');
            btnText.innerText = showSystemTables ? 'Hide System' : 'Show System';
            
            const systemCards = document.querySelectorAll('.system-table-card');
            systemCards.forEach(card => {
                if (showSystemTables) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                    // Jika card ini sedang dipilih, deselect
                    const tableName = card.id.replace('card-', '');
                    if (selectedCard === tableName) {
                        card.classList.remove('selected-table-card');
                        selectedCard = null;
                        document.getElementById('api-docs-container').innerHTML = `
                            <div class="flex flex-col items-center justify-center h-full text-slate-500 text-center">
                                <svg class="w-16 h-16 mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <p>Klik salah satu tabel di area ERD (kanan) untuk melihat dokumentasi API-nya secara detail di sini.</p>
                            </div>
                        `;
                        document.getElementById('data-content').innerHTML = `
                            <div class="h-full flex flex-col items-center justify-center text-gray-400">
                                <p>Data tabel akan muncul di sini setelah tabel dipilih.</p>
                            </div>
                        `;
                        document.getElementById('data-title').innerText = 'Data Explorer';
                    }
                }
            });
            
            // Render ulang relasi agar garis yang menempel ke system table ikut hilang/muncul
            setTimeout(() => { drawRelations(); }, 50);
        }

        // --- Infinite Canvas State (Pan & Zoom) ---
        let canvasScale = 1;
        let panX = 0;
        let panY = 0;
        let isCanvasPanning = false;
        let startPanX = 0;
        let startPanY = 0;

        function updateCanvasTransform() {
            const erdCards = document.getElementById('erd-cards');
            const erdContainer = document.getElementById('erd-container');
            
            erdCards.style.transform = `translate(${panX}px, ${panY}px) scale(${canvasScale})`;
            erdContainer.style.backgroundPosition = `${panX}px ${panY}px`;
            erdContainer.style.backgroundSize = `${20 * canvasScale}px ${20 * canvasScale}px`;
            
            localStorage.setItem('erd_canvas_state', JSON.stringify({ panX, panY, scale: canvasScale }));
            
            // Perbarui posisi garis secara efisien menggunakan requestAnimationFrame
            requestAnimationFrame(() => {
                activeLines.forEach(line => line.position());
            });
        }

        // Inisialisasi ERD
        function initDashboard() {
            const erdCards = document.getElementById('erd-cards');
            
            // Muat state kanvas
            const savedState = JSON.parse(localStorage.getItem('erd_canvas_state')) || { panX: 0, panY: 0, scale: 1 };
            panX = savedState.panX || 0;
            panY = savedState.panY || 0;
            canvasScale = savedState.scale || 1;
            
            let defaultX = 40;
            let defaultY = 40;
            
            let savedPositions = JSON.parse(localStorage.getItem('erd_positions')) || {};
            
            Object.keys(dbSchema).forEach((tableName) => {
                const isSystemTable = blacklist.includes(tableName.toLowerCase());
                
                let posX = defaultX;
                let posY = defaultY;
                
                if (savedPositions[tableName]) {
                    posX = savedPositions[tableName].x;
                    posY = savedPositions[tableName].y;
                } else {
                    defaultX += 250;
                    if (defaultX > window.innerWidth - 600) { defaultX = 40; defaultY += 280; }
                }

                const card = document.createElement('div');
                card.className = `table-card select-none ${isSystemTable ? 'system-table-card' + (showSystemTables ? '' : ' hidden') : ''}`;
                card.id = `card-${tableName}`;
                card.style.left = `${posX}px`;
                card.style.top = `${posY}px`;

                let colsHtml = '';
                dbSchema[tableName].forEach(col => {
                    let icon = '<span class="mr-4"></span>';
                    if (col === 'id') icon = '<span class="key-icon">🗝️</span>';
                    else if (col.endsWith('_id')) icon = '<span class="relation-icon">🔗</span>';
                    
                    colsHtml += `<div class="column-item relative" id="col-${tableName}-${col}">${icon} ${col}</div>`;
                });

                // Perhatikan tombol drag-handle khusus
                card.innerHTML = `
                    <div class="table-card-header ${isSystemTable ? 'bg-slate-500' : 'bg-slate-900'}">
                        <div class="flex-1 cursor-move py-1" onmousedown="startDrag(event, '${tableName}')">
                            <span>${tableName}</span>
                            ${isSystemTable ? '<span class="text-[10px] bg-slate-600 px-1.5 py-0.5 rounded border border-slate-400 ml-2">System</span>' : ''}
                        </div>
                        ${!isSystemTable ? `<div class="w-7 h-7 rounded bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white flex items-center justify-center cursor-crosshair ml-2 transition-colors border border-emerald-500/30" onmousedown="startRelationDrag(event, '${tableName}')" title="Tarik ke tabel lain untuk membuat relasi">🔗</div>` : ''}
                    </div>
                    <div class="table-card-body" onclick="selectTable('${tableName}')">
                        ${colsHtml}
                    </div>
                `;
                
                erdCards.appendChild(card);
            });

            updateCanvasTransform();
            setTimeout(() => { drawRelations(); }, 100);
        }

        // Tampilkan Detail API di Sidebar Kiri
        function renderApiDocs(tableName) {
            const container = document.getElementById('api-docs-container');
            const isSystem = blacklist.includes(tableName);

            if (isSystem) {
                container.innerHTML = `
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center text-orange-800 font-bold mb-2"><span class="mr-2">⚠️</span> System Table</div>
                        <p class="text-sm text-orange-700">Tabel <b>${tableName}</b> dilindungi oleh sistem. Akses API diblokir.</p>
                    </div>
                `;
                return;
            }

            const baseUrl = window.location.origin;
            const endpoint = `/api/dynamic/${tableName}`;
            
            let payloadObj = {};
            dbSchema[tableName].forEach(col => {
                if(col !== 'id' && col !== 'created_at' && col !== 'updated_at') payloadObj[col] = "nilai";
            });
            const jsonPayload = JSON.stringify(payloadObj, null, 2);

            let relationsHtml = '';
            dbSchema[tableName].forEach(col => {
                if (col.endsWith('_id')) {
                    relationsHtml += `
                        <li class="flex justify-between items-center bg-slate-800 p-2 rounded mb-2 border border-slate-700">
                            <span class="text-sm font-mono text-blue-400 flex items-center"><span class="mr-2">🔗</span> ${col}</span>
                            <form action="/super-admin/relation/drop" method="POST" onsubmit="return confirm('Hapus relasi (kolom ${col}) dari tabel ${tableName}?');">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="table_name" value="${tableName}">
                                <input type="hidden" name="column_name" value="${col}">
                                <button type="submit" class="text-[10px] uppercase font-bold bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white px-2 py-1 rounded transition-colors border border-red-500/30">Putus</button>
                            </form>
                        </li>
                    `;
                }
            });

            if (relationsHtml !== '') {
                relationsHtml = `
                    <div class="mt-8 pt-6 border-t border-slate-800">
                        <h3 class="text-sm font-semibold text-blue-400 mb-3 flex items-center">🔗 Relasi Aktif (Foreign Keys)</h3>
                        <ul>${relationsHtml}</ul>
                    </div>
                `;
            }

            let deleteFormHtml = `
                <div class="mt-8 pt-6 border-t border-slate-800">
                    <h3 class="text-sm font-semibold text-red-400 mb-2 flex items-center"><span class="mr-2">⚠️</span> Danger Zone</h3>
                    <p class="text-xs text-slate-500 mb-4">Hapus tabel ini beserta isinya secara permanen.</p>
                    <form action="/super-admin/table/drop" method="POST" onsubmit="return confirm('Yakin hapus ${tableName}? Seluruh data akan hilang!');">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="table_name" value="${tableName}">
                        <button type="submit" class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 py-2 rounded-md text-sm font-medium">Hapus Tabel ${tableName}</button>
                    </form>
                </div>
            `;

            container.innerHTML = `
                <h2 class="text-xl font-bold text-blue-400 mb-1">${tableName}</h2>
                <div class="mb-6">
                    <p class="text-xs text-slate-400 font-mono mb-1"><span class="text-slate-500 font-semibold">LOCAL:</span> ${baseUrl}${endpoint}</p>
                    ${window.ngrokUrl ? `<p class="text-xs text-emerald-400 font-mono"><span class="text-emerald-600 font-semibold">PUBLIC:</span> ${window.ngrokUrl}${endpoint}</p>` : `<p class="text-[10px] text-slate-600 italic mt-1">API belum online. Gunakan "Sinkronisasi Node" di aplikasi untuk mengaktifkan Ngrok.</p>`}
                </div>
                <div class="mb-6"><div class="flex items-center mb-2"><span class="bg-emerald-500/20 text-emerald-400 text-xs font-bold px-2 py-1 rounded mr-2">GET</span><span class="text-sm text-slate-300 font-mono text-xs break-all">${endpoint}</span></div></div>
                <div class="mb-6">
                    <div class="flex items-center mb-2"><span class="bg-yellow-500/20 text-yellow-400 text-xs font-bold px-2 py-1 rounded mr-2">POST</span><span class="text-sm text-slate-300 font-mono text-xs break-all">${endpoint}</span></div>
                    <pre class="ml-10 bg-slate-950 p-3 rounded text-[10px] font-mono text-emerald-300 border border-slate-800 overflow-x-auto">${jsonPayload}</pre>
                </div>
                <div class="mb-6"><div class="flex items-center mb-2"><span class="bg-blue-500/20 text-blue-400 text-xs font-bold px-2 py-1 rounded mr-2">PUT</span><span class="text-sm text-slate-300 font-mono text-xs break-all">${endpoint}/{id}</span></div></div>
                <div class="mb-6"><div class="flex items-center mb-2"><span class="bg-red-500/20 text-red-400 text-xs font-bold px-2 py-1 rounded mr-2">DEL</span><span class="text-sm text-slate-300 font-mono text-xs break-all">${endpoint}/{id}</span></div></div>
                ${relationsHtml}
                ${deleteFormHtml}
            `;
        }

        // Interaksi Pilih Tabel
        function selectTable(tableName) {
            if (selectedCard) document.getElementById(`card-${selectedCard}`).classList.remove('selected-table-card');
            document.getElementById(`card-${tableName}`).classList.add('selected-table-card');
            selectedCard = tableName;
            document.getElementById('data-title').innerText = `Data Tabel: ${tableName}`;
            renderApiDocs(tableName);
            fetchData(tableName);
        }

        async function fetchData(tableName) {
            const dataContent = document.getElementById('data-content');
            const loading = document.getElementById('loading-overlay');
            loading.classList.remove('hidden');
            try {
                if (blacklist.includes(tableName)) {
                    dataContent.innerHTML = `<div class="h-full flex flex-col items-center justify-center text-orange-500"><p>Data tersembunyi (System Table).</p></div>`;
                    return;
                }
                const response = await fetch(`/api/dynamic/${tableName}`);
                if (!response.ok) throw new Error('API Error');
                const data = await response.json();
                if (data.length === 0) { dataContent.innerHTML = `<div class="h-full flex items-center justify-center text-gray-400">Tabel masih kosong.</div>`; return; }
                const columns = Object.keys(data[0]);
                let tableHtml = `<table class="min-w-full divide-y divide-gray-200 border"><thead class="bg-gray-50"><tr>`;
                columns.forEach(col => { tableHtml += `<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">${col}</th>`; });
                tableHtml += `</tr></thead><tbody class="bg-white divide-y divide-gray-200">`;
                data.forEach(row => {
                    tableHtml += `<tr class="hover:bg-gray-50">`;
                    columns.forEach(col => { tableHtml += `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${row[col] !== null ? row[col] : '-'}</td>`; });
                    tableHtml += `</tr>`;
                });
                tableHtml += `</tbody></table>`;
                dataContent.innerHTML = tableHtml;
            } catch (error) { dataContent.innerHTML = `<div class="h-full flex items-center justify-center text-red-500">Gagal mengambil data.</div>`; } 
            finally { loading.classList.add('hidden'); }
        }

        // --- LeaderLine Relasi ---
        function drawRelations() {
            activeLines.forEach(line => line.remove());
            activeLines = [];
            
            Object.keys(dbSchema).forEach(tableName => {
                dbSchema[tableName].forEach(col => {
                    if (col.endsWith('_id')) {
                        const baseName = col.replace('_id', '');
                        let targetTable = null;
                        if (dbSchema[baseName]) targetTable = baseName;
                        else if (dbSchema[baseName + 's']) targetTable = baseName + 's';
                        else if (dbSchema[baseName + 'es']) targetTable = baseName + 'es';
                        
                        if (targetTable) {
                            const el1 = document.getElementById(`col-${tableName}-${col}`);
                            const el2 = document.getElementById(`col-${targetTable}-id`);
                            
                            if (el1 && el2) {
                                const card1 = document.getElementById(`card-${tableName}`);
                                const card2 = document.getElementById(`card-${targetTable}`);
                                if (card1 && card2 && !card1.classList.contains('hidden') && !card2.classList.contains('hidden')) {
                                    const line = new LeaderLine(el1, el2, {
                                        color: '#3b82f6', 
                                        size: 3, 
                                        path: 'fluid', 
                                        startSocketGravity: 40, 
                                        endSocketGravity: 40,
                                        startSocket: 'auto', 
                                        endSocket: 'auto', 
                                        dropShadow: true, 
                                        dash: { animation: true }
                                    });
                                    activeLines.push(line);
                                }
                            }
                        }
                    }
                });
            });
        }

        // --- Kanvas Events (Pan & Zoom) ---
        const erdContainerDOM = document.getElementById('erd-container');
        
        erdContainerDOM.addEventListener('wheel', (e) => {
            if (e.ctrlKey || e.metaKey) {
                // Zoom
                e.preventDefault();
                const zoomSensitivity = 0.002;
                const delta = e.deltaY * -zoomSensitivity;
                
                const rect = erdContainerDOM.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;

                const newScale = Math.min(Math.max(0.2, canvasScale * (1 + delta)), 3); // Batas zoom 20% s/d 300%
                const scaleRatio = newScale / canvasScale;

                panX = mouseX - (mouseX - panX) * scaleRatio;
                panY = mouseY - (mouseY - panY) * scaleRatio;
                canvasScale = newScale;

                updateCanvasTransform();
            } else {
                // Pan (Scroll)
                e.preventDefault();
                panX -= e.deltaX;
                panY -= e.deltaY;
                updateCanvasTransform();
            }
        }, { passive: false });

        erdContainerDOM.addEventListener('mousedown', (e) => {
            // Drag background untuk Pan
            if (e.target === erdContainerDOM || e.target.id === 'erd-cards') {
                if (e.button === 0 || e.button === 1) { // Left or Middle click
                    isCanvasPanning = true;
                    startPanX = e.clientX - panX;
                    startPanY = e.clientY - panY;
                    erdContainerDOM.style.cursor = 'grabbing';
                }
            }
        });

        // --- Dragging Card Logic ---
        let isDragging = false;
        let dragElem = null;
        let startX, startY, initialLeft, initialTop;

        function startDrag(e, tableName) {
            isDragging = true;
            dragElem = document.getElementById(`card-${tableName}`);
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(dragElem.style.left || 0);
            initialTop = parseInt(dragElem.style.top || 0);
            document.body.style.userSelect = 'none';
        }

        // --- Visual Drag & Drop Relation Logic ---
        let isRelDragging = false;
        let relSourceTable = null;
        let tempRelLine = null;
        const virtualNode = document.getElementById('virtual-mouse-node');

        function startRelationDrag(e, tableName) {
            e.preventDefault();
            e.stopPropagation();
            
            isRelDragging = true;
            relSourceTable = tableName;
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'crosshair';

            const sourceEl = document.getElementById(`card-${tableName}`);
            virtualNode.style.left = `${e.clientX}px`;
            virtualNode.style.top = `${e.clientY}px`;
            
            tempRelLine = new LeaderLine(sourceEl, virtualNode, {
                color: '#10b981', // Emerald for drawing
                size: 4,
                path: 'fluid',
                dash: { animation: true }
            });
        }

        // Unified MouseMove
        document.addEventListener('mousemove', (e) => {
            if (isCanvasPanning) {
                panX = e.clientX - startPanX;
                panY = e.clientY - startPanY;
                updateCanvasTransform();
            }

            if (isDragging) {
                // Bagibagi dengan canvasScale agar pergerakan mouse sinkron dengan pergerakan elemen yang diperkecil/diperbesar!
                const dx = (e.clientX - startX) / canvasScale;
                const dy = (e.clientY - startY) / canvasScale;
                dragElem.style.left = `${initialLeft + dx}px`;
                dragElem.style.top = `${initialTop + dy}px`;
                activeLines.forEach(line => line.position());
            }

            if (isRelDragging) {
                virtualNode.style.left = `${e.clientX}px`;
                virtualNode.style.top = `${e.clientY}px`;
                tempRelLine.position();
            }
        });

        // Unified MouseUp
        document.addEventListener('mouseup', (e) => {
            if (isCanvasPanning) {
                isCanvasPanning = false;
                erdContainerDOM.style.cursor = 'grab';
            }

            if (isDragging && dragElem) {
                const tableName = dragElem.id.replace('card-', '');
                let savedPositions = JSON.parse(localStorage.getItem('erd_positions')) || {};
                savedPositions[tableName] = { x: parseInt(dragElem.style.left), y: parseInt(dragElem.style.top) };
                localStorage.setItem('erd_positions', JSON.stringify(savedPositions));
                isDragging = false;
                dragElem = null;
                document.body.style.userSelect = 'auto';
            }

            if (isRelDragging) {
                isRelDragging = false;
                document.body.style.cursor = 'default';
                document.body.style.userSelect = 'auto';
                
                tempRelLine.remove();
                tempRelLine = null;

                // Collision Detection: Did we drop on another card?
                virtualNode.style.display = 'none';
                const droppedOnEl = document.elementFromPoint(e.clientX, e.clientY);
                virtualNode.style.display = 'block';

                if (droppedOnEl) {
                    const cardEl = droppedOnEl.closest('.table-card');
                    if (cardEl) {
                        const targetTable = cardEl.id.replace('card-', '');
                        if (targetTable !== relSourceTable && !blacklist.includes(targetTable)) {
                            openSmartRelationModal(relSourceTable, targetTable);
                        }
                    }
                }
            }
        });

        // Smart Relation Modal Logic
        function openSmartRelationModal(source, target) {
            document.getElementById('sr-source').innerText = source;
            document.getElementById('sr-target').innerText = target;
            
            // Set inputs
            document.getElementById('sr-normal-source').value = source;
            document.getElementById('sr-normal-target').value = target;
            document.getElementById('sr-pivot-source').value = source;
            document.getElementById('sr-pivot-target').value = target;

            // Generate Preview Strings
            let targetSingular = target.replace(/s$/, ''); // basic
            let srcSingular = source.replace(/s$/, '');

            document.getElementById('sr-normal-srcname').innerText = source;
            document.getElementById('sr-normal-col').innerText = targetSingular + '_id';

            let pivotNames = [srcSingular, targetSingular].sort();
            document.getElementById('sr-pivot-name').innerText = pivotNames.join('_');

            document.getElementById('smartRelationModal').classList.remove('hidden');
        }

        // Context Menu (Klik Kanan)
        const erdContainer = document.getElementById('erd-container');
        const contextMenu = document.getElementById('context-menu');
        
        erdContainer.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.classList.remove('hidden');
        });
        document.addEventListener('click', () => { contextMenu.classList.add('hidden'); });

        function openAddTableModal() { document.getElementById('addTableModal').classList.remove('hidden'); }
        
        function arrangeCards() {
            localStorage.removeItem('erd_positions');
            localStorage.removeItem('erd_canvas_state');
            
            panX = 0;
            panY = 0;
            canvasScale = 1;
            updateCanvasTransform();
            
            let x = 40; let y = 40;
            Object.keys(dbSchema).forEach(tableName => {
                const card = document.getElementById(`card-${tableName}`);
                if (card) {
                    card.style.left = `${x}px`; card.style.top = `${y}px`;
                }
                x += 250; if (x > window.innerWidth - 600) { x = 40; y += 280; }
            });
            activeLines.forEach(line => line.position());
        }

        // Resizer Logic
        const resizer = document.getElementById('resizer');
        const topPanel = document.getElementById('erd-container');
        const bottomPanel = document.getElementById('data-container');
        let isResizing = false;

        resizer.addEventListener('mousedown', () => { isResizing = true; document.body.style.cursor = 'row-resize'; });
        document.addEventListener('mousemove', (e) => {
            if (!isResizing) return;
            const containerHeight = document.querySelector('main').clientHeight;
            if (e.clientY < 200 || e.clientY > containerHeight - 100) return;
            topPanel.style.height = `${e.clientY}px`; topPanel.style.flex = 'none';
            bottomPanel.style.height = `${containerHeight - e.clientY - 8}px`;
            activeLines.forEach(line => line.position());
        });
        document.addEventListener('mouseup', () => {
            if (isResizing) { isResizing = false; document.body.style.cursor = 'default'; activeLines.forEach(line => line.position()); }
        });

        // Tambah Kolom
        let colIndex = 1;
        function addColumn() {
            const container = document.getElementById('columns-container');
            const row = document.createElement('div');
            row.className = 'flex gap-3 column-row mt-3';
            row.innerHTML = `
                <input type="text" name="columns[${colIndex}][name]" placeholder="Nama Kolom" required class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                <select name="columns[${colIndex}][type]" class="w-1/3 px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
                    <option value="string">String (Teks)</option><option value="integer">Integer (Angka)</option><option value="date">Date (Tanggal)</option>
                </select>
                <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-red-50 text-red-600 rounded-md border border-red-200">X</button>
            `;
            container.appendChild(row);
            colIndex++;
        }

        window.ngrokUrl = null;
        async function fetchNgrokUrl() {
            try {
                const res = await fetch('/api/system/ngrok');
                if (res.ok) {
                    const data = await res.json();
                    if (data.url) {
                        window.ngrokUrl = data.url;
                    }
                }
            } catch(e) {
                console.error("Failed to fetch Ngrok URL", e);
            }
            initDashboard();
        }

        fetchNgrokUrl();
        // --- Custom Toast Notification ---
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-xl text-sm font-medium text-white transition-all duration-300 z-[9999] opacity-0 transform translate-y-4 flex items-center ${type === 'success' ? 'bg-emerald-600' : 'bg-red-600'}`;
            toast.innerHTML = `<span class="mr-2">${type === 'success' ? '✅' : '⚠️'}</span> ${message}`;
            document.body.appendChild(toast);
            
            // Animasi masuk
            setTimeout(() => { toast.classList.remove('opacity-0', 'translate-y-4'); }, 10);
            
            // Animasi keluar dan hapus
            setTimeout(() => { 
                toast.classList.add('opacity-0', 'translate-y-4'); 
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // --- Global Form AJAX Interceptor (Tanpa Blink) ---
        document.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.innerText || submitBtn.innerHTML;
                submitBtn.innerText = 'Loading...';
            }

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (response.ok && data.status === 'success') {
                    showToast(data.message, 'success');
                    
                    // Tutup semua modal
                    document.querySelectorAll('[id$="Modal"]').forEach(m => m.classList.add('hidden'));
                    
                    // Update Schema global dengan data terbaru dari server
                    dbSchema = data.schema;
                    
                    // Bersihkan ERD Canvas
                    document.getElementById('erd-cards').innerHTML = '';
                    if (activeLines) {
                        activeLines.forEach(l => l.remove());
                        activeLines = [];
                    }
                    
                    // Reset Sidebar & Data Explorer
                    selectedCard = null;
                    document.getElementById('api-docs-container').innerHTML = `
                        <div class="flex flex-col items-center justify-center h-full text-slate-500 text-center">
                            <svg class="w-16 h-16 mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <p>Klik salah satu tabel di area ERD (kanan) untuk melihat dokumentasi API-nya secara detail di sini.</p>
                        </div>
                    `;
                    document.getElementById('data-content').innerHTML = `
                        <div class="h-full flex flex-col items-center justify-center text-gray-400">
                            <p>Data tabel akan muncul di sini setelah tabel dipilih.</p>
                        </div>
                    `;
                    document.getElementById('data-title').innerText = 'Data Explorer';
                    
                    // Render ulang ERD dengan schema baru
                    initDashboard();
                    
                    // Reset form
                    form.reset();
                    
                } else {
                    showToast(data.message || 'Terjadi kesalahan.', 'error');
                }
            } catch (err) {
                console.error('AJAX Error:', err);
                showToast('Gagal memproses permintaan (Cek Console).', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerText = submitBtn.dataset.originalText; // Kembalikan text asli
                }
            }
        });

    </script>
</body>
</html>
