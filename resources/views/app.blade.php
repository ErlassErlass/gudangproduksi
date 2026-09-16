<!DOCTYPE html>
<html lang="id" data-theme="winter">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>ErlassGudangApp — Warehouse Management System</title>
<meta name="description" content="Sistem Inventaris Multi-Warehouse Erlass Institute">
<meta name="theme-color" content="#2563eb">
<link rel="manifest" href="/manifest.json">
<link rel="icon" href="/icons/icon-192.png" type="image/png">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
  if (typeof tailwind !== 'undefined') {
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Plus Jakarta Sans', 'sans-serif'],
            mono: ['JetBrains Mono', 'monospace'],
          }
        }
      }
    }
  }
</script>
<style>
/* Disable Double-Tap Zoom & Tap Highlight */
html, body, button, select, input, textarea, a, .btn, .sb-item, .pin-dot {
  touch-action: manipulation;
}
button, .btn, .sb-item, .pin-dot {
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}

/* Custom Scrollbars */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.4); }

/* QR Scanner Animation overlay */
.scan-line {
  animation: scanMove 2.5s ease-in-out infinite;
}
@keyframes scanMove {
  0%, 100% { top: 20%; opacity: 0.8; }
  50% { top: 80%; opacity: 0.3; }
}

/* Print Overrides */
@media print {
  body { background: #fff !important; color: #000 !important; margin: 0 !important; padding: 0 !important; }
  #app-layout, #login-screen, .toast, .no-print { display: none !important; }
  #print-area { 
    display: flex !important; 
    flex-wrap: wrap; 
    gap: 8px; 
    padding: 0 !important; 
    margin: 0 !important; 
    background: #fff; 
    justify-content: flex-start !important; 
    align-items: flex-start !important;
  }
}

/* PIN DOT Fallback Styles */
.pin-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  transition: all 0.2s cubic-bezier(0.23, 1, 0.32, 1);
}
.pin-dot.filled {
  background-color: #2563eb;
  border-color: #2563eb;
  transform: scale(1.15);
}
.pin-dot.error {
  background-color: #dc2626;
  border-color: #dc2626;
}
/* Page display switching */
.page {
  display: none;
}
.page.active {
  display: block;
}
</style>
</head>
<body class="font-sans bg-base-200 text-base-content min-h-screen overflow-hidden">

<!-- ═══ LOGIN SCREEN ═══ -->
<div id="login-screen" class="fixed inset-0 bg-base-300 z-[9999] flex items-center justify-center">
  <div class="w-full max-w-sm p-8 bg-base-100 rounded-3xl border border-base-300 shadow-2xl">
    <div class="text-center mb-6">
      <div class="text-6xl mb-4">📦</div>
      <h1 class="text-3xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-primary to-accent mb-1">ErlassGudangApp</h1>
      <p class="text-sm text-base-content/60">Warehouse Management System — Erlass</p>
    </div>
    
    <div class="text-xs text-error font-semibold text-center h-5 mb-3" id="login-err"></div>
    
    <form onsubmit="handleLoginSubmitForm(event)" class="space-y-4">
      <div class="form-control">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">NIK Karyawan *</span></label>
        <input type="text" id="login-nik" class="input input-bordered input-sm w-full font-semibold" placeholder="Masukkan NIK (cth: 12345 / admin)" required>
      </div>
      <div class="form-control">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Password *</span></label>
        <input type="password" id="login-password" class="input input-bordered input-sm w-full" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md mt-4">Login</button>
    </form>
  </div>
</div>

<!-- ═══ MAIN APP LAYOUT ═══ -->
<div class="flex h-screen overflow-hidden" id="app-layout" style="display:none">
  <!-- Overlay for mobile sidebar -->
  <div class="fixed inset-0 bg-black/40 z-40 hidden" id="sb-overlay" onclick="closeSidebar()"></div>

  <!-- SIDEBAR -->
  <aside class="w-64 bg-base-100 border-r border-base-300 flex flex-col shrink-0 transition-transform duration-300 z-50 fixed inset-y-0 left-0 -translate-x-full lg:static lg:translate-x-0" id="sidebar">
    <div class="p-6 border-b border-base-200 flex items-center gap-3 shrink-0">
      <div class="w-10 h-10 bg-gradient-to-tr from-primary to-accent rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-md shadow-primary/20">📦</div>
      <div>
        <h2 class="font-extrabold text-base tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-primary to-accent">ErlassGudangApp</h2>
        <span class="text-[9px] font-bold text-base-content/40 font-mono tracking-wider">MULTI-WAREHOUSE v3.5</span>
      </div>
    </div>
    <nav class="flex-1 py-4 px-3 overflow-y-auto space-y-1" role="menu">
      <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-2">Overview</div>
      <div id="sb-link-dashboard" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50 active" onclick="goPage('dashboard')"><span>📊</span> Dashboard Utama</div>
      <div id="sb-link-sewadash" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('sewadash')"><span>📈</span> Dashboard Sewa</div>

      <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-base-content/40 mt-6 mb-2">Operasional Reguler</div>
      <div id="sb-link-scan" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('scan')"><span>📷</span> Scan Barang</div>
      <div id="sb-link-stock" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('stock')"><span>📦</span> Daftar Stok (Biasa)</div>
      <div id="sb-link-transactions" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('transactions')"><span>📋</span> Riwayat Transaksi <span class="badge badge-error badge-sm font-extrabold ml-auto text-white" id="sb-pending" style="display:none">0</span></div>
      <div id="sb-link-stockcard" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('stockcard')"><span>📄</span> Kartu Stok (Biasa)</div>

      <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-base-content/40 mt-6 mb-2">Operasional Sewa & Bekas</div>
      <div id="sb-link-sewamutasi" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('sewamutasi')"><span>🔄</span> Transaksi Sewa</div>
      <div id="sb-link-sewaunits" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('sewaunits')"><span>🔍</span> Daftar Unit Asset</div>
      <div id="sb-link-sewacard" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('sewacard')"><span>📄</span> Riwayat & Kartu Asset</div>

      <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-base-content/40 mt-6 mb-2">Modul MIKMS Lapangan</div>
      <div id="sb-link-mikms" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('mikms')"><span>🤖</span> Operasional MIKMS</div>
      <div id="sb-link-mikmsguide" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('mikmsguide')"><span>📖</span> SOP & Panduan (How-To)</div>

      <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-base-content/40 mt-6 mb-2">Tools</div>
      <div id="sb-link-print" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50" onclick="goPage('print')"><span>🖨️</span> Cetak QR Stiker</div>

      <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-base-content/40 mt-6 mb-2 admin-section" style="display:none">Administrasi</div>
      <div id="sb-link-master" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50 admin-section" style="display:none" onclick="goPage('master')"><span>🛠️</span> Master Barang</div>
      <div id="sb-link-locations" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50 admin-section" style="display:none" onclick="goPage('locations')"><span>🏢</span> Master Lokasi</div>
      <div id="sb-link-vendors" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50 admin-section" style="display:none" onclick="goPage('vendors')"><span>🤝</span> Master Vendor</div>
      <div id="sb-link-customers" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50 admin-section" style="display:none" onclick="goPage('customers')"><span>🏫</span> Master Customer</div>
      <div id="sb-link-categories" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50 admin-section" style="display:none" onclick="goPage('categories')"><span>🏷️</span> Master Kategori</div>
      <div id="sb-link-users" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold hover:bg-base-200 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-base-200/50 admin-section" style="display:none" onclick="goPage('users')"><span>👥</span> Master User</div>
    </nav>
    <div class="p-4 border-t border-base-200 shrink-0">
      <div tabindex="0" role="button" aria-label="Logout" class="flex items-center gap-3 p-3 rounded-2xl bg-base-200/50 hover:bg-base-200 cursor-pointer transition duration-200 focus:outline-none focus:ring-2 focus:ring-error/50" onclick="logoutUser()">
        <div class="w-9 h-9 bg-primary text-primary-content rounded-xl flex items-center justify-center font-bold" id="sb-avatar">P</div>
        <div class="overflow-hidden flex-1">
          <div class="text-xs font-bold text-base-content truncate" id="sb-uname">—</div>
          <div class="text-[9px] font-semibold text-base-content/50 uppercase tracking-wide" id="sb-urole">—</div>
        </div>
        <div class="text-base-content/40 text-xs">🚪</div>
      </div>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
    <!-- TOPBAR -->
    <header class="h-16 bg-base-100 border-b border-base-300 flex items-center justify-between px-6 shrink-0 no-print">
      <div class="flex items-center gap-2 sm:gap-4">
        <!-- Hamburger Menu Button (Dashboard only) -->
        <button id="tb-menu-btn" class="btn btn-ghost btn-square lg:hidden" onclick="toggleSidebar()">
          <span class="text-xl">☰</span>
        </button>
        <!-- Back Navigation Button (Sub-pages only) -->
        <button id="tb-back-btn" class="btn btn-ghost btn-square lg:hidden" onclick="goPage('dashboard')" style="display:none">
          <span class="text-xl">⬅</span>
        </button>
        <h1 class="text-lg sm:text-xl font-bold tracking-tight text-base-content" id="tb-page-title">Dashboard</h1>
      </div>
      <div class="flex items-center gap-1.5 sm:gap-3">
        <button class="btn btn-primary btn-xs sm:btn-sm rounded-full shadow-sm text-[10px] sm:text-xs font-bold" id="pwa-install-btn" onclick="installPWA()">📲 Install</button>
        <button class="btn btn-ghost btn-circle btn-sm text-lg" onclick="toggleTheme()" id="theme-btn">🌙</button>
        <div class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-4 py-1.5 sm:py-2 rounded-full bg-base-200 border border-base-300 text-[10px] sm:text-xs font-bold text-base-content/70 cursor-pointer hover:bg-base-300/50 transition shrink-0" onclick="syncPending()">
          <div class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-success shadow-sm" id="sync-dot"></div>
          <span id="sync-lbl" class="hidden sm:inline">Online</span>
        </div>
      </div>
    </header>

    <!-- SCROLL CONTENT CONTAINER -->
    <main class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6" id="main-scroll">

      <!-- ═══ DASHBOARD PAGE ═══ -->
      <div class="page active" id="pg-dashboard">
        <!-- STATS SECTION -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4" id="dash-stats"></div>

        <!-- CHARTS SECTION -->
        <div class="hidden lg:grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5 col-span-1 xl:col-span-2">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-base-content/50 mb-4">📈 Tren Masuk vs Keluar (30 Hari Terakhir)</h3>
            <div class="h-64 relative">
              <canvas id="chart-trend"></canvas>
            </div>
          </div>
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5 col-span-1">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-base-content/50 mb-4">📊 Distribusi per Gudang</h3>
            <div class="h-64 relative">
              <canvas id="chart-gudang"></canvas>
            </div>
          </div>
        </div>

        <!-- MOBILE QUICK LINKS -->
        <div class="lg:hidden mt-6 flex flex-col gap-3">
          <button onclick="goPage('scan')" class="btn btn-primary w-full shadow-lg rounded-2xl py-4 h-auto text-base font-extrabold flex items-center justify-center gap-3">
            <span>📷</span> Mulai Scan QR Barang
          </button>
          <button onclick="goPage('stock')" class="btn btn-neutral btn-outline w-full rounded-2xl py-3 h-auto text-sm font-bold flex items-center justify-center gap-2">
            <span>📦</span> Lihat Daftar Stok
          </button>
        </div>

        <!-- RECENT TRANSACTIONS TABLE -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mt-6 hidden lg:block">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-base-content/50">Transaksi Terakhir</h3>
          </div>
          <div class="overflow-x-auto rounded-xl border border-base-200">
            <table class="table table-zebra table-sm">
              <thead class="bg-base-200"><tr>
                <th>Tanggal</th><th>Kode</th><th>Nama Barang</th><th class="text-center">Tipe</th><th class="text-right">Qty</th><th>Gudang</th><th>Petugas</th>
              </tr></thead>
              <tbody id="dash-recent-tbody">
                <tr><td colspan="7" class="text-center py-8 text-base-content/40">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══ SCAN PAGE ═══ -->
      <div class="page" id="pg-scan">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Video Scanner Column -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xs font-extrabold uppercase tracking-wider text-base-content/50">📷 QR Scanner</h3>
            </div>
            <div class="relative w-full max-w-xs aspect-square rounded-2xl overflow-hidden bg-black mx-auto border border-base-300 shadow-inner">
              <video id="scanner-video" class="w-full h-full object-cover" playsinline></video>
              <canvas id="scanner-canvas" class="hidden"></canvas>
              <!-- Scan lines and target frame overlay -->
              <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                <div class="w-4/5 h-4/5 border-2 border-white/50 rounded-2xl relative">
                  <div class="scan-line absolute left-4 right-4 h-[2px] bg-gradient-to-r from-transparent via-primary to-transparent shadow-lg shadow-primary"></div>
                </div>
              </div>
              <!-- Camera activation cover -->
              <div class="absolute inset-0 bg-base-100/90 backdrop-blur-sm flex flex-col items-center justify-center gap-4 text-center p-6" id="cam-overlay">
                <div class="text-5xl">📷</div>
                <button class="btn btn-primary btn-sm rounded-xl font-bold shadow-md shadow-primary/20" onclick="startCamera()">Aktifkan Kamera</button>
                <p class="text-[10px] text-base-content/50">Atau masukkan kode barang secara manual di formulir</p>
              </div>
            </div>
            <div class="alert alert-info mt-6 text-xs justify-center font-bold" id="scan-status">
              Siap memindai QR Code barang...
            </div>
          </div>

          <!-- Transaction Form Column -->
          <div class="flex flex-col gap-4">
            <div class="join w-full grid grid-cols-2 bg-base-300 p-1.5 rounded-2xl shadow-inner">
              <button class="btn btn-sm rounded-xl join-item border-none text-xs font-extrabold" id="mode-masuk-btn" onclick="setMode('masuk')">↓ Barang Masuk</button>
              <button class="btn btn-sm rounded-xl join-item border-none text-xs font-extrabold btn-ghost text-error" id="mode-keluar-btn" onclick="setMode('keluar')">↑ Barang Keluar</button>
            </div>
            
            <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-extrabold text-base-content" id="scan-form-title">Form Barang Masuk</h3>
                <span class="badge badge-success text-white font-extrabold text-[10px]" id="scan-form-badge">↓ MASUK</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Gudang / Lokasi *</span></label>
                  <select id="sf-lokasi" class="select select-bordered select-sm w-full"><option>Gudang Utama</option><option>Gudang Raw Material</option><option>Gudang Work in Process</option></select>
                </div>
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kode Barang *</span></label>
                  <input type="text" id="sf-kode" class="input input-bordered input-sm w-full font-mono uppercase font-bold" placeholder="Scan atau Ketik..." oninput="autoFillScan()" list="sf-dl" autocomplete="off">
                  <datalist id="sf-dl"></datalist>
                </div>
                <div class="form-control w-full md:col-span-2">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Barang</span></label>
                  <input type="text" id="sf-nama" class="input input-bordered input-sm w-full bg-base-200 text-base-content/50" readonly placeholder="Otomatis terisi">
                </div>
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Jumlah Unit *</span></label>
                  <input type="number" id="sf-qty" class="input input-bordered input-sm w-full font-semibold" value="1" min="1">
                </div>
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Satuan</span></label>
                  <select id="sf-satuan" class="select select-bordered select-sm w-full">
                    <option>pcs</option><option>unit</option><option>set</option><option>box</option><option>lembar</option>
                  </select>
                </div>
                <div class="form-control w-full md:col-span-2" id="sf-sumber-wrap">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Sumber / Pengirim *</span></label>
                  <input type="text" id="sf-sumber" class="input input-bordered input-sm w-full font-semibold" placeholder="Ketik atau pilih vendor..." list="sf-sumber-dl" autocomplete="off">
                  <datalist id="sf-sumber-dl"></datalist>
                </div>
                <div class="form-control w-full md:col-span-2" id="sf-penerima-wrap" style="display:none">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Penerima / Divisi *</span></label>
                  <input type="text" id="sf-penerima" class="input input-bordered input-sm w-full font-semibold" placeholder="Ketik atau pilih customer..." list="sf-penerima-dl" autocomplete="off">
                  <datalist id="sf-penerima-dl"></datalist>
                </div>
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. PO</span></label>
                  <input type="text" id="sf-po" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="PO-xxx">
                </div>
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. PRN</span></label>
                  <input type="text" id="sf-prn" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="PRN-xxx">
                </div>
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Job Number</span></label>
                  <input type="text" id="sf-job" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="JOB-xxx">
                </div>
                <div class="form-control w-full">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Transfer Order</span></label>
                  <input type="text" id="sf-to" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="TO-xxx">
                </div>
                <div class="form-control w-full md:col-span-2" id="sf-petugas-wrap">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60" id="sf-petugas-lbl">Petugas</span></label>
                  <input type="text" id="sf-petugas" class="input input-bordered input-sm w-full font-semibold" placeholder="Nama Petugas...">
                </div>
                <div class="form-control w-full md:col-span-2">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan</span></label>
                  <input type="text" id="sf-catatan" class="input input-bordered input-sm w-full" placeholder="Keterangan opsional">
                </div>
                <div class="form-control w-full md:col-span-2 mt-4">
                  <button class="btn btn-primary w-full shadow-md text-xs font-bold" id="sf-submit-btn" onclick="submitScan()">↓ Simpan Barang Masuk</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ STOCK PAGE ═══ -->
      <div class="page" id="pg-stock">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div class="flex flex-wrap gap-2 items-center">
            <select id="stk-gudang" class="select select-bordered select-sm" onchange="loadStock()">
              <option value="Semua">Semua Gudang</option>
              <option>Gudang Utama</option>
              <option>Gudang Raw Material</option>
              <option>Gudang Work in Process</option>
            </select>
            <select id="stk-produk" class="select select-bordered select-sm" onchange="renderStockTable()">
              <option value="Semua">Semua Kategori</option>
            </select>
            <input type="text" id="stk-search" class="input input-bordered input-sm w-48 md:w-64" placeholder="Cari Kode atau Nama..." oninput="renderStockTable()">
          </div>
          <div class="flex gap-2">
            <button class="btn btn-sm btn-outline" onclick="exportExcelFile()">📥 Export Excel</button>
          </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden p-0">
          <div class="overflow-x-auto max-h-[calc(100vh-270px)]">
            <table class="table table-zebra table-pin-rows table-sm">
              <thead class="bg-base-200"><tr>
                <th>Kode</th><th>Nama Barang</th><th>Kategori</th><th class="text-right">Total Masuk</th><th class="text-right">Total Keluar</th><th class="text-right">Sisa Stok</th><th>Satuan</th><th class="text-center">Aksi</th>
              </tr></thead>
              <tbody id="stk-tbody">
                <tr><td colspan="8" class="text-center py-8 text-base-content/40">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══ TRANSACTIONS PAGE ═══ -->
      <div class="page" id="pg-transactions">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div class="flex flex-wrap gap-2 items-center">
            <select id="tx-gudang" class="select select-bordered select-sm" onchange="loadTransactions()">
              <option value="Semua">Semua Gudang</option>
              <option>Gudang Utama</option>
              <option>Gudang Raw Material</option>
              <option>Gudang Work in Process</option>
            </select>
            <select id="tx-tipe" class="select select-bordered select-sm" onchange="loadTransactions()">
              <option value="">Semua Mutasi</option>
              <option value="masuk">Masuk</option>
              <option value="keluar">Keluar</option>
            </select>
            <input type="text" id="tx-search" class="input input-bordered input-sm w-48 md:w-64" placeholder="Cari Kode, Nama, Petugas..." oninput="renderTxTable()">
          </div>
          <div class="flex gap-2">
            <button class="btn btn-sm btn-outline" onclick="exportExcelFile()">📥 Export Excel</button>
            <button class="btn btn-sm btn-ghost border border-base-300" onclick="syncPending()">🔄 Sync Antrian</button>
          </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden p-0">
          <div class="overflow-x-auto max-h-[calc(100vh-270px)]">
            <table class="table table-zebra table-pin-rows table-sm">
              <thead class="bg-base-200"><tr>
                <th>Tanggal</th><th>Kode</th><th>Nama Barang</th><th class="text-center">Mutasi</th><th class="text-right">Qty</th><th>Gudang</th><th>Asal / Penerima</th><th>Referensi PO</th><th>Petugas</th>
              </tr></thead>
              <tbody id="tx-tbody">
                <tr><td colspan="9" class="text-center py-8 text-base-content/40">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══ STOCK CARD PAGE ═══ -->
      <div class="page" id="pg-stockcard">
        <div class="flex flex-wrap gap-2 items-center mb-6">
          <div class="join max-w-xs w-full">
            <input type="text" id="sc-item-search" class="input input-bordered input-sm join-item w-full" placeholder="Cari Kode atau Nama Barang..." list="sc-item-dl" oninput="onScItemSearchInput()" autocomplete="off">
            <datalist id="sc-item-dl"></datalist>
            <input type="hidden" id="sc-item" value="">
            <button class="btn btn-sm btn-ghost join-item border border-base-300" onclick="clearScSearch()">✕</button>
          </div>
          <select id="sc-gudang" class="select select-bordered select-sm" onchange="loadStockCard()">
            <option value="Semua">Semua Gudang</option>
            <option>Gudang Utama</option>
            <option>Gudang Raw Material</option>
            <option>Gudang Work in Process</option>
          </select>
          <input type="number" id="sc-tahun" class="input input-bordered input-sm w-24" value="2026" min="2020" max="2030" onchange="loadStockCard()">
          <button class="btn btn-sm btn-primary text-xs font-bold" onclick="loadStockCard()">🔍 Tampilkan</button>
          <button class="btn btn-sm btn-outline text-xs" id="sc-pdf-btn" onclick="downloadPdfCard()" style="display:none">📄 Cetak Kartu PDF</button>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden p-0">
          <div class="overflow-x-auto max-h-[calc(100vh-280px)]">
            <table class="table table-zebra table-pin-rows table-sm">
              <thead class="bg-base-200"><tr>
                <th>Tanggal</th><th>Keterangan</th><th>P.O / P.R.N</th><th>Gudang</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Sisa Akhir</th><th>PIC</th>
              </tr></thead>
              <tbody id="sc-tbody">
                <tr><td colspan="8" class="text-center py-8 text-base-content/40">Pilih barang di atas untuk melihat Kartu Stok.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══ PRINT PAGE ═══ -->
      <div class="page" id="pg-print">
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 max-w-2xl mx-auto">
          <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-extrabold text-base-content">🖨️ Cetak Stiker QR Code</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Barang</span></label>
              <div class="join w-full">
                <input type="text" id="pr-item-search" class="input input-bordered input-sm join-item w-full" placeholder="Cari Kode atau Nama Barang..." list="pr-item-dl" oninput="onPrItemSearchInput()" autocomplete="off">
                <datalist id="pr-item-dl"></datalist>
                <input type="hidden" id="pr-item" value="">
                <button class="btn btn-sm btn-ghost join-item border border-base-300" onclick="clearPrSearch()">✕</button>
              </div>
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Jumlah Copy Stiker</span></label>
              <input type="number" id="pr-qty" class="input input-bordered input-sm w-full font-bold" value="1" min="1" max="100" onchange="updatePrintPreview()">
            </div>
          </div>
          
          <div class="flex gap-2 mt-4">
            <button class="btn btn-outline btn-sm btn-secondary flex-1 text-xs font-bold" onclick="addToQueue()">+ Tambah ke Antrean</button>
            <button class="btn btn-primary btn-sm flex-1 text-xs font-bold" onclick="doPrint()">🖨️ Cetak Langsung</button>
          </div>

          <!-- Antrean Cetak Table -->
          <div id="pr-queue-section" class="mt-6 hidden">
            <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 mb-3">Antrean Cetak Barang:</div>
            <div class="overflow-x-auto border border-base-300 rounded-2xl max-h-[250px]">
              <table class="table table-zebra table-sm w-full">
                <thead class="bg-base-200">
                  <tr>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th class="text-right">Qty Copy</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody id="pr-queue-tbody"></tbody>
              </table>
            </div>
            <button id="pr-queue-print-btn" class="btn btn-primary btn-sm w-full mt-4 text-xs font-extrabold" onclick="doPrint()">🖨️ Cetak Semua Antrean</button>
          </div>
          
          <div class="mt-6 p-6 bg-base-200 border border-base-300 rounded-2xl">
            <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 mb-3">Pratinjau Stiker:</div>
            <div class="flex flex-wrap gap-3 justify-center min-h-[100px]" id="pr-preview"></div>
          </div>
        </div>
      </div>

      <!-- ═══ MASTER PAGE (admin only) ═══ -->
      <div class="page" id="pg-master">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <input type="text" id="ms-search" class="input input-bordered input-sm w-full md:w-72" placeholder="Cari Kode atau Nama Master..." oninput="renderMasterTable()">
          <button class="btn btn-sm btn-primary text-xs font-bold" onclick="openItemModal()">+ Tambah Master Barang</button>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden p-0">
          <div class="overflow-x-auto max-h-[calc(100vh-270px)]">
            <table class="table table-zebra table-pin-rows table-sm">
              <thead class="bg-base-200"><tr>
                <th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Komponen</th><th>Satuan</th><th class="text-right">Min. Stok Warning</th><th class="text-center">Aksi</th>
              </tr></thead>
              <tbody id="ms-tbody">
                <tr><td colspan="7" class="text-center py-8 text-base-content/40">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══ SEWA DASHBOARD PAGE ═══ -->
      <div class="page" id="pg-sewadash">
        <!-- STATS SECTION -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="sewa-stats"></div>

        <!-- RENTED ITEMS SECTION -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mt-6">
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-base-content/50 mb-4">📋 Unit yang Sedang Disewakan</h3>
          <div class="overflow-x-auto">
            <table class="table table-sm table-zebra w-full">
              <thead><tr>
                <th>Serial Number</th><th>Nama Barang</th><th>Penyewa (Customer)</th><th>Tanggal Mulai Sewa</th><th>Keperluan</th>
              </tr></thead>
              <tbody id="sewa-rented-tbody">
                <tr><td colspan="5" class="text-center py-4 text-base-content/40">Tidak ada unit yang sedang disewa.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══ TRANSAKSI SEWA PAGE ═══ -->
      <div class="page" id="pg-sewamutasi">
        <div class="tabs tabs-boxed mb-6 bg-base-200 p-1 flex justify-start gap-1">
          <button class="tab tab-active font-bold text-xs" id="tab-btn-sewain" onclick="switchSewaTab('sewain')">📥 Penerimaan Asset Baru</button>
          <button class="tab font-bold text-xs" id="tab-btn-sewaout" onclick="switchSewaTab('sewaout')">📤 Sewa Keluar</button>
          <button class="tab font-bold text-xs" id="tab-btn-sewaret" onclick="switchSewaTab('sewaret')">↩️ Sewa Kembali</button>
        </div>

        <!-- TAB: PENERIMAAN ASSET BARU -->
        <div id="sewa-tab-sewain" class="sewa-tab-content">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 max-w-2xl">
            <h3 class="font-extrabold text-base mb-4">Penerimaan & Registrasi Unit Baru</h3>
            <form onsubmit="handleNewAssetSubmit(event)" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Master Barang *</span></label>
                  <select id="sewain-item-id" class="select select-bordered select-sm w-full font-semibold" required></select>
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tipe Kepemilikan *</span></label>
                  <select id="sewain-tipe" class="select select-bordered select-sm w-full font-semibold" required>
                    <option value="sewa">Sewa</option>
                    <option value="bekas">Bekas</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Serial Number / Asset Tag *</span></label>
                  <input type="text" id="sewain-serial" class="input input-bordered input-sm w-full font-mono font-bold" placeholder="SEWA-RE-xxx" required>
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kondisi Awal *</span></label>
                  <select id="sewain-status" class="select select-bordered select-sm w-full font-semibold" required>
                    <option value="lengkap">Lengkap</option>
                    <option value="good">Good</option>
                    <option value="not_good">Not Good</option>
                    <option value="tidak_lengkap">Tidak Lengkap</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Vendor / Supplier</span></label>
                  <select id="sewain-vendor-id" class="select select-bordered select-sm w-full font-semibold"></select>
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Lokasi Penempatan Rak *</span></label>
                  <select id="sewain-location-id" class="select select-bordered select-sm w-full font-semibold" required></select>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. PO</span></label>
                  <input type="text" id="sewain-po" class="input input-bordered input-sm w-full" placeholder="PO-2026-xxx">
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. Surat Jalan / Dokumen</span></label>
                  <input type="text" id="sewain-dokumen" class="input input-bordered input-sm w-full" placeholder="SJ-xxx">
                </div>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan</span></label>
                <textarea id="sewain-catatan" class="textarea textarea-bordered textarea-sm w-full h-20" placeholder="Keterangan tambahan..."></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-full font-bold">Daftarkan Unit & Catat Transaksi Masuk</button>
            </form>
          </div>
        </div>

        <!-- TAB: SEWA KELUAR -->
        <div id="sewa-tab-sewaout" class="sewa-tab-content hidden">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 max-w-2xl">
            <h3 class="font-extrabold text-base mb-4">Distribusi Sewa Keluar</h3>
            <form onsubmit="handleSewaOutSubmit(event)" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Penyewa (Customer) *</span></label>
                  <select id="sewaout-customer-id" class="select select-bordered select-sm w-full font-semibold" required></select>
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. Dokumen / Kontrak</span></label>
                  <input type="text" id="sewaout-dokumen" class="input input-bordered input-sm w-full" placeholder="KTR-2026-xxx">
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Keperluan</span></label>
                  <input type="text" id="sewaout-keperluan" class="input input-bordered input-sm w-full" placeholder="Sewa Kegiatan Pembelajaran, dll.">
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan</span></label>
                  <input type="text" id="sewaout-catatan" class="input input-bordered input-sm w-full" placeholder="Keterangan tambahan">
                </div>
              </div>

              <!-- Pindai / Scan QR Asset -->
              <div class="bg-base-200 p-4 rounded-xl space-y-3">
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50">Scanner Unit Asset</div>
                <div class="flex gap-2">
                  <input type="text" id="sewaout-scan-input" class="input input-bordered input-sm flex-1 font-mono" placeholder="Masukkan / Scan QR Code Unit Asset...">
                  <button type="button" class="btn btn-neutral btn-sm" onclick="addAssetToSewaOutList()">Tambah</button>
                </div>
              </div>

              <!-- List of scanned assets -->
              <div class="border border-base-300 rounded-xl p-4 bg-base-50 max-h-60 overflow-y-auto">
                <div class="text-xs font-bold text-base-content/60 mb-2">📋 Daftar Unit yang akan Disewakan:</div>
                <table class="table table-sm table-zebra w-full">
                  <thead><tr>
                    <th>Serial Number</th><th>Nama Barang</th><th>Kondisi</th><th class="text-center">Aksi</th>
                  </tr></thead>
                  <tbody id="sewaout-list-tbody">
                    <tr><td colspan="4" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>
                  </tbody>
                </table>
              </div>

              <button type="submit" class="btn btn-primary btn-sm w-full font-bold">Proses Transaksi Sewa Keluar</button>
            </form>
          </div>
        </div>

        <!-- TAB: SEWA KEMBALI -->
        <div id="sewa-tab-sewaret" class="sewa-tab-content hidden">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 max-w-2xl">
            <h3 class="font-extrabold text-base mb-4">Pengembalian Unit Sewa (Sewa Kembali)</h3>
            <form onsubmit="handleSewaReturnSubmit(event)" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. Surat Jalan / Dokumen Kembali</span></label>
                  <input type="text" id="sewaret-dokumen" class="input input-bordered input-sm w-full" placeholder="SJK-2026-xxx">
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan Pengembalian</span></label>
                  <input type="text" id="sewaret-catatan" class="input input-bordered input-sm w-full" placeholder="Keterangan kondisi dll">
                </div>
              </div>

              <div class="bg-base-200 p-4 rounded-xl space-y-3">
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50">Cari Unit Asset yang Kembali</div>
                <div class="flex gap-2">
                  <input type="text" id="sewaret-scan-input" class="input input-bordered input-sm flex-1 font-mono" placeholder="Masukkan / Scan QR Code Unit Asset...">
                  <button type="button" class="btn btn-neutral btn-sm" onclick="addAssetToSewaReturnList()">Cari & Tambah</button>
                </div>
              </div>

              <!-- List of returning assets -->
              <div class="border border-base-300 rounded-xl p-4 bg-base-50 max-h-60 overflow-y-auto">
                <div class="text-xs font-bold text-base-content/60 mb-2">📋 Daftar Unit yang Kembali:</div>
                <table class="table table-sm table-zebra w-full">
                  <thead><tr>
                    <th>Serial Number</th><th>Nama Barang</th><th>Kondisi Pengembalian</th><th>Rak Penempatan Kembali</th><th class="text-center">Aksi</th>
                  </tr></thead>
                  <tbody id="sewaret-list-tbody">
                    <tr><td colspan="5" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>
                  </tbody>
                </table>
              </div>

              <button type="submit" class="btn btn-primary btn-sm w-full font-bold">Proses Pengembalian (Sewa Kembali)</button>
            </form>
          </div>
        </div>
      </div>

      <!-- ═══ DAFTAR UNIT ASSET PAGE ═══ -->
      <div class="page" id="pg-sewaunits">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div class="flex flex-wrap gap-2">
            <select id="sf-tipe" class="select select-bordered select-sm w-36 font-semibold" onchange="loadSewaUnitsPage()">
              <option value="">Semua Tipe</option>
              <option value="sewa">Sewa</option>
              <option value="bekas">Bekas</option>
            </select>
            <select id="sf-status" class="select select-bordered select-sm w-44 font-semibold" onchange="loadSewaUnitsPage()">
              <option value="">Semua Kondisi</option>
              <option value="lengkap">Lengkap</option>
              <option value="good">Good</option>
              <option value="not_good">Not Good</option>
              <option value="tidak_lengkap">Tidak Lengkap</option>
            </select>
          </div>
          <button class="btn btn-sm btn-primary text-xs font-bold" onclick="switchSewaTab('sewain'); goPage('sewamutasi');">+ Registrasi Asset Baru</button>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden p-0">
          <div class="overflow-x-auto max-h-[calc(100vh-270px)]">
            <table class="table table-zebra table-sm w-full">
              <thead class="bg-base-200"><tr>
                <th>Serial Number</th><th>Nama Barang</th><th>Kategori Master</th><th>Tipe</th><th>Kondisi</th><th>Lokasi Saat Ini</th><th>Status</th><th class="text-center">Aksi</th>
              </tr></thead>
              <tbody id="sewaunits-tbody">
                <tr><td colspan="8" class="text-center py-8 text-base-content/40">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══ RIWAYAT & KARTU ASSET PAGE ═══ -->
      <div class="page" id="pg-sewacard">
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="form-control w-full md:w-96">
            <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Unit Asset (Serial Number / QR)</span></label>
            <select id="sewacard-asset-select" class="select select-bordered select-sm font-semibold w-full" onchange="renderSewaCard()"></select>
          </div>
        </div>

        <!-- CARD DETAILS AND TIMELINE -->
        <div id="sewacard-details" style="display:none" class="space-y-6">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
            <h3 class="font-extrabold text-base mb-4">ℹ️ Detail Keterangan Asset</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div><span class="text-base-content/50">Nama Barang:</span> <div id="sc-detail-nama" class="font-bold">-</div></div>
              <div><span class="text-base-content/50">Tipe Kepemilikan:</span> <div id="sc-detail-tipe" class="font-semibold uppercase">-</div></div>
              <div><span class="text-base-content/50">Kondisi Terkini:</span> <div id="sc-detail-kondisi" class="font-semibold">-</div></div>
              <div><span class="text-base-content/50">Status Ketersediaan:</span> <div id="sc-detail-sewa" class="font-bold">-</div></div>
            </div>
          </div>

          <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
            <h3 class="font-extrabold text-base mb-4">⏳ Log Kronologi Perpindahan & Penggunaan</h3>
            <div class="overflow-x-auto">
              <table class="table table-sm table-zebra w-full">
                <thead><tr>
                  <th>Tanggal</th><th>Tipe Mutasi</th><th>Detail Kegiatan</th><th>Lokasi/Penyewa</th><th>Dokumen</th><th>Catatan</th><th>Petugas</th>
                </tr></thead>
                <tbody id="sewacard-tbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ MASTER LOKASI PAGE (admin only) ═══ -->
      <div class="page" id="pg-locations">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- FORM ADD LOCATION -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 h-fit">
            <h3 class="font-extrabold text-base mb-4">➕ Tambah Lokasi Baru</h3>
            <form onsubmit="handleLocationSubmit(event)" class="space-y-4">
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tipe Lokasi *</span></label>
                <select id="loc-tipe" class="select select-bordered select-sm font-semibold" onchange="adjustLocationParentDropdown()" required>
                  <option value="gedung">Gedung (Level 1)</option>
                  <option value="lantai">Lantai (Level 2)</option>
                  <option value="ruangan">Gudang / Ruangan (Level 3)</option>
                  <option value="rak">Rak / Slot (Level 4)</option>
                </select>
              </div>
              <div class="form-control" id="loc-parent-container" style="display:none">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Parent Lokasi *</span></label>
                <select id="loc-parent-id" class="select select-bordered select-sm font-semibold"></select>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Lokasi *</span></label>
                <input type="text" id="loc-nama" class="input input-bordered input-sm font-semibold" placeholder="Nama Lokasi (misal: Rak A-01)" required>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kode Lokasi Unik *</span></label>
                <input type="text" id="loc-kode" class="input input-bordered input-sm font-mono font-bold" placeholder="KODE-LOKASI" required>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-full font-bold">Simpan Lokasi</button>
            </form>
          </div>

          <!-- LIST OF LOCATIONS -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 col-span-2">
            <h3 class="font-extrabold text-base mb-4">🏢 Hierarki Struktur Lokasi</h3>
            <div class="max-h-[500px] overflow-y-auto" id="locations-tree-container">
              <div class="text-center text-base-content/40 py-8">Memuat lokasi...</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ VENDOR & CUSTOMER PAGE (admin only) ═══ -->
      <!-- ═══ VENDOR PAGE (admin only) ═══ -->
      <div class="page" id="pg-vendors">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 h-fit">
            <h3 class="font-extrabold text-base mb-4">🤝 Tambah Vendor (Supplier)</h3>
            <form onsubmit="handleVendorSubmit(event)" class="space-y-3">
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Perusahaan / Vendor *</span></label>
                <input type="text" id="vend-nama" class="input input-bordered input-sm font-semibold" placeholder="Nama Perusahaan *" required>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Kontak PIC</span></label>
                <input type="text" id="vend-kontak" class="input input-bordered input-sm" placeholder="Nama PIC">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. Telepon / WA</span></label>
                <input type="text" id="vend-telepon" class="input input-bordered input-sm" placeholder="No. Telepon / WA">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Alamat Vendor</span></label>
                <textarea id="vend-alamat" class="textarea textarea-bordered textarea-sm h-20" placeholder="Alamat lengkap Vendor"></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-full font-bold">Simpan Vendor</button>
            </form>
          </div>

          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 lg:col-span-2">
            <h3 class="font-bold text-xs uppercase tracking-wider text-base-content/50 mb-4">📋 Daftar Vendor (Supplier) Pemasok</h3>
            <div class="overflow-x-auto max-h-[500px]">
              <table class="table table-sm table-zebra w-full text-xs">
                <thead><tr>
                  <th>Nama Vendor</th><th>PIC</th><th>Telepon</th><th>Alamat</th><th class="text-center">Aksi</th>
                </tr></thead>
                <tbody id="vendors-tbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CUSTOMER PAGE (admin only) ═══ -->
      <div class="page" id="pg-customers">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 h-fit">
            <h3 class="font-extrabold text-base mb-4">🏫 Tambah Customer (Penyewa)</h3>
            <form onsubmit="handleCustomerSubmit(event)" class="space-y-3">
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Customer / Sekolah *</span></label>
                <input type="text" id="cust-nama" class="input input-bordered input-sm font-semibold" placeholder="Nama Customer *" required>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Kontak PIC</span></label>
                <input type="text" id="cust-kontak" class="input input-bordered input-sm" placeholder="Nama PIC">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. Telepon / WA</span></label>
                <input type="text" id="cust-telepon" class="input input-bordered input-sm" placeholder="No. Telepon / WA">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Alamat Lengkap</span></label>
                <textarea id="cust-alamat" class="textarea textarea-bordered textarea-sm h-20" placeholder="Alamat lengkap Customer"></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-full font-bold">Simpan Customer</button>
            </form>
          </div>

          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 lg:col-span-2">
            <h3 class="font-bold text-xs uppercase tracking-wider text-base-content/50 mb-4">📋 Daftar Customer (Sekolah / Penyewa)</h3>
            <div class="overflow-x-auto max-h-[500px]">
              <table class="table table-sm table-zebra w-full text-xs">
                <thead><tr>
                  <th>Nama Customer</th><th>PIC</th><th>Telepon</th><th>Alamat</th><th class="text-center">Aksi</th>
                </tr></thead>
                <tbody id="customers-tbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ CATEGORIES PAGE (admin only) ═══ -->
      <div class="page" id="pg-categories">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- FORM -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 h-fit">
            <h3 class="font-extrabold text-base mb-4">🏷️ Tambah Kategori</h3>
            <form onsubmit="handleCategorySubmit(event)" class="space-y-4">
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Kategori *</span></label>
                <input type="text" id="cat-nama" class="input input-bordered input-sm font-semibold" placeholder="Contoh: Elektronik, Modul, ATK" required>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Sub Kategori dari</span></label>
                <select id="cat-parent-id" class="select select-bordered select-sm">
                  <option value="">-- Tidak Ada (Kategori Utama) --</option>
                </select>
                <label class="label"><span class="label-text-alt text-base-content/40">Kosongkan jika ini kategori utama</span></label>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-full font-bold">Simpan Kategori</button>
            </form>
          </div>

          <!-- TABLE -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 col-span-2">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-bold text-xs uppercase tracking-wider text-base-content/50">📄 Daftar Kategori</h3>
              <span class="badge badge-ghost badge-sm" id="cat-count">0 kategori</span>
            </div>
            <div class="overflow-x-auto max-h-[520px]">
              <table class="table table-sm table-zebra w-full text-xs">
                <thead class="sticky top-0 bg-base-200 z-10">
                  <tr>
                    <th>Nama</th>
                    <th>Sub Kategori dari</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody id="categories-tbody">
                  <tr><td colspan="3" class="text-center py-8 text-base-content/40">Memuat...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- MODAL EDIT KATEGORI -->
      <div class="modal" id="cat-edit-modal">
        <div class="modal-box max-w-sm bg-base-100 border border-base-300 shadow-2xl p-6">
          <div class="flex justify-between items-center mb-5">
            <h3 class="font-extrabold text-base">✏️ Edit Kategori</h3>
            <button class="btn btn-sm btn-circle btn-ghost" onclick="closeCatModal()">✕</button>
          </div>
          <input type="hidden" id="cat-edit-id">
          <div class="space-y-4">
            <div class="form-control">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Kategori *</span></label>
              <input type="text" id="cat-edit-nama" class="input input-bordered input-sm font-semibold" required>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Sub Kategori dari</span></label>
              <select id="cat-edit-parent-id" class="select select-bordered select-sm">
                <option value="">-- Tidak Ada (Kategori Utama) --</option>
              </select>
            </div>
            <button class="btn btn-primary btn-sm w-full font-bold" onclick="submitCatEdit()">Simpan Perubahan</button>
          </div>
        </div>
        <div class="modal-backdrop" onclick="closeCatModal()"></div>
      </div>


      <!-- ═══ MASTER USER PAGE (admin only) ═══ -->
      <div class="page" id="pg-users">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
            <input type="text" id="us-search" class="input input-bordered input-sm w-full md:w-72" placeholder="Cari NIK atau Nama User..." oninput="renderUsersTable()">
          </div>
          <div class="flex gap-2">
            <button class="btn btn-sm btn-outline btn-neutral text-xs font-bold" onclick="openUserImportModal()">📥 Import CSV</button>
            <button class="btn btn-sm btn-primary text-xs font-bold" onclick="openUserModal()">+ Tambah User</button>
          </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden p-0">
          <div class="overflow-x-auto max-h-[calc(100vh-270px)]">
            <table class="table table-zebra table-pin-rows table-sm">
              <thead class="bg-base-200">
                <tr>
                  <th>NIK (Username)</th>
                  <th>Nama Lengkap</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Dibuat Pada</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="us-tbody">
                <tr><td colspan="6" class="text-center py-8 text-base-content/40">Memuat data pengguna...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════
           MIKMS OPERASIONAL PAGE (MODUL LAPANGAN)
           ═════════════════════════════════════════════ -->
      <div class="page" id="pg-mikms">
        <!-- HEADER HERO CARD -->
        <div class="card bg-gradient-to-r from-primary/10 via-base-100 to-accent/10 border border-primary/20 shadow-sm p-6 mb-6">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-primary text-primary-content flex items-center justify-center text-3xl font-extrabold shadow-md shadow-primary/30 shrink-0">
                🤖
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h2 class="text-xl font-extrabold text-base-content tracking-tight">Operasional Kit MIKMS Lapangan</h2>
                  <span class="badge badge-primary badge-sm font-extrabold text-[10px] tracking-wider uppercase">BOM AUTO-SYNC</span>
                </div>
                <p class="text-xs text-base-content/70 mt-1 max-w-2xl leading-relaxed">
                  Manajemen terintegrasi Modul Micro:bit, Perakitan BOM otomatis, QC kelayakan, Distribusi peminjaman sekolah, Retur cek fisik, dan Pemeliharaan (Repair).
                </p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <button class="btn btn-sm btn-outline border-base-300 bg-base-100 text-xs font-bold shadow-sm hover:bg-base-200" onclick="exportMikmsExcel()">
                📥 Unduh Excel Lapangan
              </button>
              <button class="btn btn-sm btn-accent text-xs font-bold shadow-sm" onclick="goPage('mikmsguide')">
                📖 Panduan & SOP
              </button>
              <button class="btn btn-sm btn-primary text-xs font-bold shadow-sm" onclick="openMikmsBoxModal()">
                + Register Boks Baru
              </button>
            </div>
          </div>

          <!-- METRICS ROW -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-base-200">
            <div class="bg-base-100/80 backdrop-blur rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-info/10 text-info flex items-center justify-center text-xl font-bold">📦</div>
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Total Boks Kit</div>
                <div class="text-lg font-extrabold text-base-content" id="mk-stat-total-boxes">0</div>
              </div>
            </div>
            <div class="bg-base-100/80 backdrop-blur rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-success/10 text-success flex items-center justify-center text-xl font-bold">✅</div>
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Boks Siap (Ready)</div>
                <div class="text-lg font-extrabold text-success" id="mk-stat-ready-boxes">0</div>
              </div>
            </div>
            <div class="bg-base-100/80 backdrop-blur rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-warning/10 text-warning flex items-center justify-center text-xl font-bold">🚚</div>
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Di Sekolah (Loan)</div>
                <div class="text-lg font-extrabold text-warning" id="mk-stat-loan-boxes">0</div>
              </div>
            </div>
            <div class="bg-base-100/80 backdrop-blur rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-error/10 text-error flex items-center justify-center text-xl font-bold">🔧</div>
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Perlu Repair</div>
                <div class="text-lg font-extrabold text-error" id="mk-stat-repair-boxes">0</div>
              </div>
            </div>
          </div>
        </div>

        <!-- TABS BAR (MOBILE-OPTIMIZED HORIZONTAL SCROLL) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-6 scrollbar-none border-b border-base-300">
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn active bg-primary text-primary-content hover:bg-primary" data-tab="boxes" onclick="switchMikmsTab('boxes')">
            📦 Daftar Boks Kit
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="bomlist" onclick="switchMikmsTab('bomlist')">
            📑 List Komponen Kit (BOM)
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="production" onclick="switchMikmsTab('production')">
            ⚙️ Perakitan & BOM
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="qc" onclick="switchMikmsTab('qc')">
            ✅ Quality Control (QC)
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="shipment" onclick="switchMikmsTab('shipment')">
            🚚 Kirim ke Sekolah
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="return" onclick="switchMikmsTab('return')">
            ↩️ Retur / Kembali
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="repair" onclick="switchMikmsTab('repair')">
            🔧 Maintenance & Repair
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="opname" onclick="switchMikmsTab('opname')">
            📋 Stock Opname Fisik
          </button>
          <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="logs" onclick="switchMikmsTab('logs')">
            📜 Riwayat Aktivitas
          </button>
        </div>

        <!-- ═══ TAB 1: DAFTAR BOKS KIT ═══ -->
        <div class="mikms-tab-content" id="mk-panel-boxes">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
              <input type="text" id="mk-box-search" class="input input-bordered input-sm w-full sm:w-60 font-semibold" placeholder="Cari Kode Boks / Sekolah..." oninput="renderMikmsBoxes()">
              <select id="mk-box-filter-cat" class="select select-bordered select-sm font-semibold" onchange="renderMikmsBoxes()">
                <option value="">Semua Kategori Boks</option>
                <option value="BOX 1 – Beginner Kit">BOX 1 – Beginner Kit</option>
                <option value="BOX 2 – Supporting Equipment">BOX 2 – Supporting Equipment</option>
                <option value="BOX 3 – Bricks">BOX 3 – Bricks</option>
              </select>
              <select id="mk-box-filter-status" class="select select-bordered select-sm font-semibold" onchange="renderMikmsBoxes()">
                <option value="">Semua Status</option>
                <option value="READY">READY (Tersedia)</option>
                <option value="ON_LOAN">ON_LOAN (Di Sekolah)</option>
                <option value="REPAIR">REPAIR (Perbaikan)</option>
              </select>
            </div>
            <span class="text-xs font-bold text-base-content/60" id="mk-box-count-lbl">Memuat boks...</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="mk-boxes-grid">
            <div class="card bg-base-100 border border-base-200 p-8 text-center text-base-content/50 col-span-full">
              Memuat data boks kit...
            </div>
          </div>
        </div>

        <!-- ═══ TAB: STANDAR LIST KOMPONEN KIT (BOM) ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-bomlist">
          <!-- PROGRAM SELECTOR & SIMULATOR CARD -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                    <span>📑</span> Standar Bill of Materials (BOM) Program Kit
                  </h3>
                  <span class="badge badge-primary badge-sm font-extrabold">SHEET 2 EXCEL</span>
                </div>
                <p class="text-xs text-base-content/60 mt-1">
                  Struktur resmi komponen per boks dan modul sesuai kurikulum lapangan. Simulasikan kesiapan stok sebelum perakitan masal.
                </p>
              </div>

              <!-- PROGRAM SWITCHER BUTTONS -->
              <div class="join bg-base-200 p-1 rounded-2xl shrink-0">
                <button class="btn btn-sm join-item font-bold text-xs mk-prog-btn active bg-primary text-primary-content hover:bg-primary" id="mk-prog-btn-microbit" onclick="switchBomProgram('microbit')">
                  🤖 Microbit Kit (95 Pcs)
                </button>
                <button class="btn btn-sm join-item font-bold text-xs mk-prog-btn btn-ghost" id="mk-prog-btn-robotic" onclick="switchBomProgram('robotic')">
                  🏎️ Robotic Explorer
                </button>
                <button class="btn btn-sm join-item font-bold text-xs mk-prog-btn btn-ghost" id="mk-prog-btn-finishgood" onclick="switchBomProgram('finishgood')">
                  📦 Finish Good
                </button>
              </div>
            </div>

            <!-- SIMULATOR KESIAPAN PAKET -->
            <div class="bg-gradient-to-r from-base-200/60 to-primary/5 rounded-2xl p-4 border border-base-300">
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-primary text-primary-content flex items-center justify-center text-xl font-bold">🧮</div>
                  <div>
                    <div class="text-xs font-extrabold text-base-content">Simulator Kesiapan Perakitan Paket</div>
                    <div class="text-[11px] text-base-content/60">Hitung kebutuhan total & cek kesiapan stok fisik gudang saat ini</div>
                  </div>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                  <div class="flex items-center gap-2 bg-base-100 px-3 py-1.5 rounded-xl border border-base-300">
                    <span class="text-xs font-bold text-base-content/70">Rencana Paket:</span>
                    <input type="number" id="mk-sim-package-qty" min="1" max="500" value="1" 
                      class="input input-bordered input-xs font-mono font-extrabold w-16 text-center" 
                      oninput="renderBomListTable()">
                    <span class="text-xs font-semibold text-base-content/50">Paket</span>
                  </div>
                  <button class="btn btn-xs btn-outline font-bold" onclick="exportMikmsExcel()">
                    📥 Unduh Excel Sheet
                  </button>
                </div>
              </div>

              <!-- STATS BADGES -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-3 border-t border-base-300/50">
                <div class="bg-base-100 p-2.5 rounded-xl border border-base-200">
                  <div class="text-[10px] font-bold text-base-content/50 uppercase">Item per Paket</div>
                  <div class="text-base font-extrabold text-base-content" id="mk-sim-items-per-pkg">95 Pcs</div>
                </div>
                <div class="bg-base-100 p-2.5 rounded-xl border border-base-200">
                  <div class="text-[10px] font-bold text-base-content/50 uppercase">Total Kebutuhan</div>
                  <div class="text-base font-extrabold text-primary" id="mk-sim-total-needed">95 Pcs</div>
                </div>
                <div class="bg-base-100 p-2.5 rounded-xl border border-base-200">
                  <div class="text-[10px] font-bold text-base-content/50 uppercase">Maks. Paket Siap Rakit</div>
                  <div class="text-base font-extrabold text-success" id="mk-sim-max-possible">0 Paket</div>
                </div>
                <div class="bg-base-100 p-2.5 rounded-xl border border-base-200">
                  <div class="text-[10px] font-bold text-base-content/50 uppercase">Bottleneck (Part Terkecil)</div>
                  <div class="text-xs font-extrabold text-error truncate" id="mk-sim-bottleneck">—</div>
                </div>
              </div>
            </div>
          </div>

          <!-- TABLE CARD -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-sm uppercase tracking-wider text-base-content/70" id="mk-bom-table-title">Daftar Komponen: MICROBIT LEARNING KIT</span>
                <span class="badge badge-sm badge-neutral font-mono font-bold" id="mk-bom-table-badge">22 Komponen</span>
              </div>
              <div class="flex items-center gap-2">
                <input type="text" id="mk-bom-search" class="input input-bordered input-sm font-semibold w-full sm:w-60" placeholder="Cari komponen / modul..." oninput="renderBomListTable()">
              </div>
            </div>

            <div class="overflow-x-auto border border-base-200 rounded-xl max-h-[600px]">
              <table class="table table-xs table-zebra table-pin-rows w-full">
                <thead class="bg-base-200">
                  <tr>
                    <th class="w-10 text-center">No</th>
                    <th>Box / Kategori</th>
                    <th>Modul</th>
                    <th>Nama Komponen</th>
                    <th class="text-center">Jumlah / Pkt</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-center">Total Butuh</th>
                    <th class="text-center">Stok Gudang</th>
                    <th class="text-center">Status Kesiapan</th>
                  </tr>
                </thead>
                <tbody id="mk-bomlist-tbody">
                  <tr><td colspan="9" class="text-center py-8 text-base-content/40">Memuat rincian BOM...</td></tr>
                </tbody>
              </table>
            </div>

            <!-- FOOTER SUMMARY -->
            <div class="mt-4 p-4 rounded-xl bg-base-200/50 flex flex-col sm:flex-row justify-between items-center gap-3">
              <div class="text-xs text-base-content/70">
                Data terintegrasi real-time dengan <strong>MIKMS_Form_Lapangan.xlsx (Sheet: List Komponen)</strong>.
              </div>
              <div class="flex items-center gap-2">
                <button class="btn btn-sm btn-primary font-bold text-xs" onclick="switchMikmsTab('production')">
                  ⚙️ Buka Form Perakitan Modul
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ TAB 2: PERAKITAN MODUL & OTOMASI BOM ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-production">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form Perakitan -->
            <div class="lg:col-span-6 space-y-4">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>⚙️</span> Formulir Perakitan Modul MIKMS
                </h3>
                <form id="mk-form-production" onsubmit="submitMikmsProduction(event)" class="space-y-4">
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Modul yang Dirakit *</span></label>
                    <select id="mk-prod-module-id" class="select select-bordered select-sm font-bold w-full" required onchange="onModuleSelectChanged()">
                      <option value="">Pilih Modul MIKMS...</option>
                    </select>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Jumlah Unit Perakitan *</span></label>
                      <input type="number" id="mk-prod-qty" min="1" value="1" class="input input-bordered input-sm font-extrabold text-base w-full" required oninput="onProdQtyChanged()">
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Target Boks Kemasan</span></label>
                      <select id="mk-prod-box-id" class="select select-bordered select-sm font-bold w-full">
                        <option value="">(Simpan di Gudang / Tanpa Boks)</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Teknisi / PIC Perakitan *</span></label>
                    <input type="text" id="mk-prod-pic" class="input input-bordered input-sm font-semibold w-full" placeholder="Nama Teknisi perakit" required>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan Produksi (Batch / Kondisi)</span></label>
                    <textarea id="mk-prod-notes" rows="2" class="textarea textarea-bordered textarea-sm w-full font-medium" placeholder="Contoh: Perakitan batch persiapan semester ganjil..."></textarea>
                  </div>
                  <div class="p-3 rounded-xl bg-info/10 border border-info/20 text-xs text-info-content flex items-start gap-2">
                    <span class="text-sm">💡</span>
                    <span>Saat Anda menyimpan perakitan modul, sistem akan <strong>secara otomatis memotong saldo stok komponen (BOM)</strong> di gudang secara real-time.</span>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md" id="mk-prod-submit-btn">
                    ⚙️ Proses Perakitan & Potong Stok Komponen
                  </button>
                </form>
              </div>
            </div>

            <!-- Live BOM Breakdown Panel -->
            <div class="lg:col-span-6 space-y-4">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                  <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2">
                    <span>📋</span> Bill of Materials (BOM) Preview
                  </h3>
                  <span class="badge badge-sm font-mono font-bold" id="mk-bom-code-badge">PILIH MODUL</span>
                </div>
                <div class="text-xs text-base-content/70 mb-4" id="mk-bom-desc">
                  Silakan pilih modul di sebelah kiri untuk melihat daftar kebutuhan komponen dan pengecekan ketersediaan stok fisik gudang.
                </div>
                
                <div class="overflow-x-auto border border-base-200 rounded-xl max-h-[380px]">
                  <table class="table table-xs table-zebra w-full">
                    <thead class="bg-base-200">
                      <tr>
                        <th>Kode</th>
                        <th>Komponen</th>
                        <th class="text-center">Kebutuhan</th>
                        <th class="text-center">Total Kebutuhan</th>
                        <th class="text-center">Stok Gudang</th>
                        <th class="text-center">Status</th>
                      </tr>
                    </thead>
                    <tbody id="mk-bom-tbody">
                      <tr><td colspan="6" class="text-center py-6 text-base-content/40">Belum ada modul yang dipilih</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ TAB 3: QUALITY CONTROL (QC) ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-qc">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-5">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>✅</span> Form Inspeksi Kelayakan (QC)
                </h3>
                <form id="mk-form-qc" onsubmit="submitMikmsQc(event)" class="space-y-4">
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Boks Kit yang Diuji</span></label>
                    <select id="mk-qc-box-id" class="select select-bordered select-sm font-bold w-full">
                      <option value="">Pilih Boks Kit...</option>
                    </select>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Atau Pilih Modul Spesifik</span></label>
                    <select id="mk-qc-module-id" class="select select-bordered select-sm font-bold w-full">
                      <option value="">Pilih Modul (Opsional)...</option>
                    </select>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Hasil Pengujian QC *</span></label>
                    <select id="mk-qc-status" class="select select-bordered select-sm font-extrabold w-full" required>
                      <option value="PASS" class="text-success font-bold">✅ PASS (Lolos QC - Siap Digunakan)</option>
                      <option value="FAIL" class="text-error font-bold">❌ FAIL (Gagal QC - Perlu Repair)</option>
                    </select>
                  </div>
                  <div class="p-3 bg-base-200/50 rounded-xl space-y-2 text-xs">
                    <div class="font-bold text-[10px] uppercase text-base-content/60">Checklist Pengecekan Standar:</div>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" id="mk-qc-chk-visual" class="checkbox checkbox-xs checkbox-primary" checked>
                      <span>Cek fisik & visual (bebas retak/patah)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" id="mk-qc-chk-elec" class="checkbox checkbox-xs checkbox-primary" checked>
                      <span>Cek fungsional kelistrikan & konektivitas</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="checkbox" id="mk-qc-chk-complete" class="checkbox checkbox-xs checkbox-primary" checked>
                      <span>Cek kelengkapan jumlah komponen sesuai BOM</span>
                    </label>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Petugas QA/QC *</span></label>
                    <input type="text" id="mk-qc-pic" class="input input-bordered input-sm font-semibold w-full" placeholder="Nama penguji QC" required>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan Temuan Uji</span></label>
                    <textarea id="mk-qc-notes" rows="2" class="textarea textarea-bordered textarea-sm w-full font-medium" placeholder="Kondisi komponen, tegangan baterai, hasil burn test..."></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md">
                    Simpan Hasil Quality Control
                  </button>
                </form>
              </div>
            </div>

            <div class="lg:col-span-7">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>📜</span> Log Riwayat Pengujian Terakhir
                </h3>
                <div class="overflow-x-auto max-h-[480px]">
                  <table class="table table-xs table-zebra w-full">
                    <thead class="bg-base-200">
                      <tr>
                        <th>Waktu</th>
                        <th>Target Uji</th>
                        <th>Hasil</th>
                        <th>Catatan</th>
                        <th>PIC</th>
                      </tr>
                    </thead>
                    <tbody id="mk-qc-log-tbody">
                      <tr><td colspan="5" class="text-center py-6 text-base-content/40">Belum ada riwayat QC</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ TAB 4: PENGIRIMAN KE SEKOLAH ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-shipment">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-6">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>🚚</span> Surat Jalan / Pengiriman Boks ke Sekolah
                </h3>
                <form id="mk-form-shipment" onsubmit="submitMikmsShipment(event)" class="space-y-4">
                  <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nomor Pengiriman / Surat Jalan *</span></label>
                      <input type="text" id="mk-ship-number" class="input input-bordered input-sm font-mono font-bold w-full" placeholder="SJ-MIKMS-001" required>
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Sekolah Penerima *</span></label>
                      <select id="mk-ship-customer-id" class="select select-bordered select-sm font-bold w-full" required>
                        <option value="">Pilih Customer / Sekolah...</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Boks Kit yang Dikirim (Status READY) *</span></label>
                    <div class="max-h-40 overflow-y-auto border border-base-200 rounded-xl p-2 space-y-1 bg-base-200/30" id="mk-ship-boxes-container">
                      <span class="text-xs text-base-content/50">Memuat boks siap kirim...</span>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tanggal Kirim *</span></label>
                      <input type="date" id="mk-ship-date" class="input input-bordered input-sm font-semibold w-full" required>
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Estimasi Pengembalian</span></label>
                      <input type="date" id="mk-ship-return-plan" class="input input-bordered input-sm font-semibold w-full">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">PIC Sekolah (Guru/PJ)</span></label>
                      <input type="text" id="mk-ship-pic-school" class="input input-bordered input-sm font-medium w-full" placeholder="Bpk/Ibu Guru...">
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Petugas Logistik Gudang *</span></label>
                      <input type="text" id="mk-ship-pic-staff" class="input input-bordered input-sm font-semibold w-full" placeholder="Nama staf pengirim" required>
                    </div>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan Pengiriman</span></label>
                    <input type="text" id="mk-ship-notes" class="input input-bordered input-sm font-medium w-full" placeholder="Diserahkan via kurir internal / ekspedisi...">
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md">
                    🚚 Proses Pengiriman & Update Status Boks (ON_LOAN)
                  </button>
                </form>
              </div>
            </div>

            <!-- List Active Shipments -->
            <div class="lg:col-span-6">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2">
                    <span>🏫</span> Boks yang Sedang Dipinjam Sekolah
                  </h3>
                  <span class="badge badge-warning badge-sm font-bold" id="mk-ship-active-badge">0 Boks</span>
                </div>
                <div class="overflow-x-auto max-h-[480px]">
                  <table class="table table-xs table-zebra w-full">
                    <thead class="bg-base-200">
                      <tr>
                        <th>Boks</th>
                        <th>Sekolah</th>
                        <th>Tgl Kirim</th>
                        <th>PIC</th>
                        <th class="text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody id="mk-ship-active-tbody">
                      <tr><td colspan="5" class="text-center py-6 text-base-content/40">Tidak ada boks yang sedang dipinjam</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ TAB 5: PENGEMBALIAN DARI SEKOLAH ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-return">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-6">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>↩️</span> Form Serah Terima Kembali (Check-in Retur)
                </h3>
                <form id="mk-form-return" onsubmit="submitMikmsReturn(event)" class="space-y-4">
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Boks yang Dikembalikan *</span></label>
                    <select id="mk-ret-box-id" class="select select-bordered select-sm font-bold w-full" required onchange="onReturnBoxChanged()">
                      <option value="">Pilih Boks yang Sedang Dipinjam...</option>
                    </select>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tanggal Diterima Kembali *</span></label>
                      <input type="date" id="mk-ret-date" class="input input-bordered input-sm font-semibold w-full" required>
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kondisi Fisik Boks *</span></label>
                      <select id="mk-ret-condition" class="select select-bordered select-sm font-bold w-full" required>
                        <option value="Lengkap & Normal" class="text-success font-bold">Lengkap & Normal (Kembali READY)</option>
                        <option value="Komponen Kurang / Hilang" class="text-warning font-bold">Komponen Kurang / Hilang (Masuk REPAIR)</option>
                        <option value="Ada Kerusakan Komponen" class="text-error font-bold">Ada Kerusakan Komponen (Masuk REPAIR)</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Rincian Komponen Kurang / Rusak (Jika Ada)</span></label>
                    <textarea id="mk-ret-missing" rows="2" class="textarea textarea-bordered textarea-sm w-full font-medium" placeholder="Contoh: Kabel jumper M-M hilang 2 pcs, Servo SG90 kabel putus..."></textarea>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Petugas Penerima Gudang *</span></label>
                    <input type="text" id="mk-ret-pic" class="input input-bordered input-sm font-semibold w-full" placeholder="Nama staf pemeriksa" required>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md">
                    ↩️ Simpan Pengembalian & Perbarui Status Boks
                  </button>
                </form>
              </div>
            </div>

            <div class="lg:col-span-6">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>📜</span> Riwayat Pengembalian Terakhir
                </h3>
                <div class="overflow-x-auto max-h-[480px]">
                  <table class="table table-xs table-zebra w-full">
                    <thead class="bg-base-200">
                      <tr>
                        <th>Tgl Kembali</th>
                        <th>Boks</th>
                        <th>Kondisi</th>
                        <th>Temuan / Part Hilang</th>
                        <th>PIC</th>
                      </tr>
                    </thead>
                    <tbody id="mk-return-log-tbody">
                      <tr><td colspan="5" class="text-center py-6 text-base-content/40">Belum ada riwayat pengembalian</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ TAB 6: MAINTENANCE & REPAIR ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-repair">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-5">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>🔧</span> Formulir Perbaikan (Repair & Rekondisi)
                </h3>
                <form id="mk-form-repair" onsubmit="submitMikmsRepair(event)" class="space-y-4">
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Boks yang Diperbaiki</span></label>
                    <select id="mk-rep-box-id" class="select select-bordered select-sm font-bold w-full">
                      <option value="">Pilih Boks...</option>
                    </select>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Komponen / Bagian yang Rusak *</span></label>
                    <input type="text" id="mk-rep-part" class="input input-bordered input-sm font-semibold w-full" placeholder="Contoh: Sensor Ultrasonik HC-SR04, Kabel USB" required>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Gejala Kerusakan *</span></label>
                    <textarea id="mk-rep-issue" rows="2" class="textarea textarea-bordered textarea-sm w-full font-medium" placeholder="Tidak terdeteksi di software, konektor longgar..." required></textarea>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tindakan Perbaikan *</span></label>
                    <textarea id="mk-rep-action" rows="2" class="textarea textarea-bordered textarea-sm w-full font-medium" placeholder="Ganti modul baru, solder ulang kabel, isolasi..." required></textarea>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Status Hasil Perbaikan *</span></label>
                      <select id="mk-rep-status" class="select select-bordered select-sm font-extrabold w-full" required>
                        <option value="DONE" class="text-success font-bold">SELESAI (Kembali READY)</option>
                        <option value="IN_PROGRESS" class="text-warning font-bold">DALAM PENGERJAAN</option>
                        <option value="SCRAPPED" class="text-error font-bold">AFKIR (Rusak Total)</option>
                      </select>
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Teknisi PIC *</span></label>
                      <input type="text" id="mk-rep-pic" class="input input-bordered input-sm font-semibold w-full" placeholder="Nama teknisi" required>
                    </div>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md">
                    🔧 Simpan Laporan Perbaikan
                  </button>
                </form>
              </div>
            </div>

            <div class="lg:col-span-7">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-4 flex items-center gap-2">
                  <span>📋</span> Daftar Antrean & Riwayat Perbaikan
                </h3>
                <div class="overflow-x-auto max-h-[480px]">
                  <table class="table table-xs table-zebra w-full">
                    <thead class="bg-base-200">
                      <tr>
                        <th>Waktu</th>
                        <th>Boks/Item</th>
                        <th>Gejala</th>
                        <th>Tindakan</th>
                        <th>Status</th>
                        <th>Teknisi</th>
                      </tr>
                    </thead>
                    <tbody id="mk-repair-log-tbody">
                      <tr><td colspan="6" class="text-center py-6 text-base-content/40">Belum ada data repair</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ TAB 7: STOCK OPNAME FISIK MIKMS ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-opname">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
              <div>
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2">
                  <span>📋</span> Pengecekan Fisik 41 Komponen MIKMS (Stock Opname)
                </h3>
                <p class="text-xs text-base-content/60 mt-0.5">Bandingkan saldo di sistem dengan fisik nyata di rak gudang untuk menghindari selisih.</p>
              </div>
              <div class="flex items-center gap-2 w-full sm:w-auto">
                <input type="text" id="mk-opname-search" class="input input-bordered input-sm w-full sm:w-64 font-semibold" placeholder="Cari kode atau nama komponen..." oninput="renderMikmsOpnameTable()">
                <button class="btn btn-sm btn-outline text-xs font-bold" onclick="renderMikmsOpnameTable()">🔄 Refresh</button>
              </div>
            </div>

            <div class="overflow-x-auto max-h-[550px] border border-base-200 rounded-xl">
              <table class="table table-xs table-zebra table-pin-rows w-full">
                <thead class="bg-base-200">
                  <tr>
                    <th>Kode</th>
                    <th>Nama Komponen</th>
                    <th>Kategori</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-center">Stok Sistem</th>
                    <th class="text-center w-28">Stok Fisik</th>
                    <th class="text-center">Selisih</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody id="mk-opname-tbody">
                  <tr><td colspan="8" class="text-center py-8 text-base-content/40">Memuat komponen MIKMS...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ═══ TAB 8: RIWAYAT AKTIVITAS LENGKAP ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-logs">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
              <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2">
                <span>📜</span> Log Transaksi Terpadu Modul MIKMS Lapangan
              </h3>
              <div class="flex items-center gap-2">
                <select id="mk-logs-filter-type" class="select select-bordered select-sm font-semibold" onchange="renderMikmsLogsTable()">
                  <option value="">Semua Tipe Aktivitas</option>
                  <option value="production">Produksi / Perakitan</option>
                  <option value="qc">Quality Control</option>
                  <option value="shipment">Pengiriman Sekolah</option>
                  <option value="return">Pengembalian</option>
                  <option value="repair">Perbaikan / Repair</option>
                </select>
                <button class="btn btn-sm btn-ghost" onclick="loadMikmsPage()">🔄</button>
              </div>
            </div>

            <div class="overflow-x-auto max-h-[550px] border border-base-200 rounded-xl">
              <table class="table table-xs table-zebra table-pin-rows w-full">
                <thead class="bg-base-200">
                  <tr>
                    <th>Waktu</th>
                    <th>Tipe Aktivitas</th>
                    <th>Ref / Target</th>
                    <th>Detail Operasional</th>
                    <th>Status</th>
                    <th>Petugas PIC</th>
                  </tr>
                </thead>
                <tbody id="mk-logs-tbody">
                  <tr><td colspan="6" class="text-center py-8 text-base-content/40">Memuat log aktivitas...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════
           MIKMS PANDUAN & SOP ALUR KERJA (HOW-TO)
           ═════════════════════════════════════════════ -->
      <div class="page" id="pg-mikmsguide">
        <!-- HEADER -->
        <div class="card bg-gradient-to-r from-accent/10 via-base-100 to-primary/10 border border-accent/20 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-accent text-accent-content flex items-center justify-center text-3xl font-extrabold shadow-md shadow-accent/30 shrink-0">
                📖
              </div>
              <div>
                <h2 class="text-xl font-extrabold text-base-content tracking-tight">SOP & Panduan Penggunaan Modul MIKMS</h2>
                <p class="text-xs text-base-content/70 mt-1 max-w-2xl leading-relaxed">
                  Standar Operasional Prosedur (SOP) alur perakitan kit Micro:bit lapangan, kontrol kualitas (QC), distribusi sekolah, hingga penanganan retur dan repair.
                </p>
              </div>
            </div>
            <button class="btn btn-sm btn-primary text-xs font-bold" onclick="goPage('mikms')">
              🤖 Buka Operasional Lapangan
            </button>
          </div>
        </div>

        <!-- SECTION 1: VISUAL WORKFLOW LIFECYCLE -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <h3 class="font-extrabold text-base text-base-content mb-2 flex items-center gap-2">
            <span>🔄</span> Alur Kerja Siklus Hidup Komponen & Kit (Item Lifecycle)
          </h3>
          <p class="text-xs text-base-content/60 mb-6">Setiap barang dan modul melalui 7 tahapan proses terkontrol untuk memastikan kit yang sampai di sekolah dalam kondisi prima.</p>

          <!-- PIPELINE STEPS (HORIZONTAL / STACKED ON MOBILE) -->
          <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
            <!-- Step 1 -->
            <div class="p-3.5 rounded-2xl bg-base-200/60 border border-base-300 relative group hover:border-primary/50 transition">
              <div class="flex items-center justify-between mb-2">
                <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">1</span>
                <span class="badge badge-xs badge-neutral font-mono font-bold">RAW</span>
              </div>
              <div class="font-bold text-xs text-base-content">Penerimaan Komponen</div>
              <p class="text-[10px] text-base-content/60 mt-1">Komponen mentah dari supplier dicek dan dicatat ke master stok gudang.</p>
            </div>

            <!-- Step 2 -->
            <div class="p-3.5 rounded-2xl bg-base-200/60 border border-base-300 relative group hover:border-primary/50 transition">
              <div class="flex items-center justify-between mb-2">
                <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">2</span>
                <span class="badge badge-xs badge-info font-mono font-bold">BOM AUTO</span>
              </div>
              <div class="font-bold text-xs text-base-content">Perakitan Modul</div>
              <p class="text-[10px] text-base-content/60 mt-1">Teknisi merakit modul (M01-M11). Sistem otomatis memotong stok BOM.</p>
            </div>

            <!-- Step 3 -->
            <div class="p-3.5 rounded-2xl bg-base-200/60 border border-base-300 relative group hover:border-primary/50 transition">
              <div class="flex items-center justify-between mb-2">
                <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">3</span>
                <span class="badge badge-xs badge-secondary font-mono font-bold">IN_BOX</span>
              </div>
              <div class="font-bold text-xs text-base-content">Pengemasan Boks</div>
              <p class="text-[10px] text-base-content/60 mt-1">Modul ditata rapi ke dalam Box 1, Box 2, atau Box 3 sesuai standar kit.</p>
            </div>

            <!-- Step 4 -->
            <div class="p-3.5 rounded-2xl bg-base-200/60 border border-base-300 relative group hover:border-primary/50 transition">
              <div class="flex items-center justify-between mb-2">
                <span class="w-6 h-6 rounded-full bg-success text-success-content font-extrabold text-xs flex items-center justify-center">4</span>
                <span class="badge badge-xs badge-success font-mono font-bold">READY</span>
              </div>
              <div class="font-bold text-xs text-base-content">Quality Control (QC)</div>
              <p class="text-[10px] text-base-content/60 mt-1">Pengujian fungsi sensor, servo, LED & micro:bit. Jika lolos: status READY.</p>
            </div>

            <!-- Step 5 -->
            <div class="p-3.5 rounded-2xl bg-base-200/60 border border-base-300 relative group hover:border-primary/50 transition">
              <div class="flex items-center justify-between mb-2">
                <span class="w-6 h-6 rounded-full bg-warning text-warning-content font-extrabold text-xs flex items-center justify-center">5</span>
                <span class="badge badge-xs badge-warning font-mono font-bold">ON_LOAN</span>
              </div>
              <div class="font-bold text-xs text-base-content">Kirim ke Sekolah</div>
              <p class="text-[10px] text-base-content/60 mt-1">Boks dikirim dengan surat jalan & berita acara serah terima guru.</p>
            </div>

            <!-- Step 6 -->
            <div class="p-3.5 rounded-2xl bg-base-200/60 border border-base-300 relative group hover:border-primary/50 transition">
              <div class="flex items-center justify-between mb-2">
                <span class="w-6 h-6 rounded-full bg-info text-info-content font-extrabold text-xs flex items-center justify-center">6</span>
                <span class="badge badge-xs badge-info font-mono font-bold">RETURNED</span>
              </div>
              <div class="font-bold text-xs text-base-content">Pengembalian</div>
              <p class="text-[10px] text-base-content/60 mt-1">Boks kembali dari sekolah, staf gudang mencocokkan kelengkapan part.</p>
            </div>

            <!-- Step 7 -->
            <div class="p-3.5 rounded-2xl bg-base-200/60 border border-base-300 relative group hover:border-primary/50 transition">
              <div class="flex items-center justify-between mb-2">
                <span class="w-6 h-6 rounded-full bg-error text-error-content font-extrabold text-xs flex items-center justify-center">7</span>
                <span class="badge badge-xs badge-error font-mono font-bold">TRIAGE</span>
              </div>
              <div class="font-bold text-xs text-base-content">Triage & Repair</div>
              <p class="text-[10px] text-base-content/60 mt-1">Jika lengkap -> READY. Jika part rusak/hilang -> antrean REPAIR.</p>
            </div>
          </div>
        </div>

        <!-- SECTION 2: PERAN & TANGGUNG JAWAB (ROLE-BASED SOP) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Role 1: Staf Gudang -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl font-bold">👷</div>
              <div>
                <h4 class="font-extrabold text-sm text-base-content">Staf Gudang & Inventaris</h4>
                <span class="text-[10px] text-base-content/50 font-semibold uppercase">Penerimaan, Penyimpanan & Packaging</span>
              </div>
            </div>
            <ul class="text-xs text-base-content/80 space-y-2 list-disc list-inside">
              <li><strong>Penerimaan Barang:</strong> Periksa kesesuaian fisik invoice/PO vendor dengan kode barang (misal: <code>CT-001</code> untuk Micro:bit V2).</li>
              <li><strong>Pemberian Label:</strong> Pastikan setiap kemasan dan boks tertempel stiker QR code dengan jelas menggunakan menu <em>Cetak QR Stiker</em>.</li>
              <li><strong>Stock Opname Mingguan:</strong> Buka tab <em>Stock Opname Fisik</em> di sistem untuk verifikasi 41 komponen secara periodik.</li>
              <li><strong>Pengemasan Boks:</strong> Susun modul yang sudah lolos QC ke boks terkait (Box 1 Beginner, Box 2 Supporting, Box 3 Bricks).</li>
            </ul>
          </div>

          <!-- Role 2: Teknisi Perakitan & QC -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center text-xl font-bold">🛠️</div>
              <div>
                <h4 class="font-extrabold text-sm text-base-content">Teknisi Perakitan & Quality Control</h4>
                <span class="text-[10px] text-base-content/50 font-semibold uppercase">Assembly & Standar Kelayakan</span>
              </div>
            </div>
            <ul class="text-xs text-base-content/80 space-y-2 list-disc list-inside">
              <li><strong>Otomasi BOM:</strong> Pilih modul (misal: <code>M01 Controller Kit</code>) pada form perakitan. Sistem menghitung bahan mentah dan langsung memotong stok.</li>
              <li><strong>Standar Uji Micro:bit:</strong> Sambungkan kabel data Type C / Micro USB, tes flash program dasar (LED display menyala & Bluetooth berfungsi).</li>
              <li><strong>Standar Uji Servo & Sensor:</strong> Pastikan putaran 180° / 360° mulus tanpa hambatan roda gigi, dan sensor ultrasonik merespon jarak.</li>
              <li><strong>Pencatatan QC:</strong> Catat hasil uji di tab <em>Quality Control</em> (status <code>PASS</code> atau <code>FAIL</code>).</li>
            </ul>
          </div>

          <!-- Role 3: Koordinator Pengiriman Sekolah -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl bg-warning/10 text-warning flex items-center justify-center text-xl font-bold">🚚</div>
              <div>
                <h4 class="font-extrabold text-sm text-base-content">Tim Pengiriman & Lapangan</h4>
                <span class="text-[10px] text-base-content/50 font-semibold uppercase">Handover, Peminjaman & Retur</span>
              </div>
            </div>
            <ul class="text-xs text-base-content/80 space-y-2 list-disc list-inside">
              <li><strong>Hanya Boks READY:</strong> Pastikan hanya boks dengan status <code>READY</code> yang boleh dipilih untuk dikirim ke sekolah.</li>
              <li><strong>Formulir Surat Jalan:</strong> Input nomor surat jalan, sekolah tujuan, dan tanggal estimasi kembali pada tab <em>Kirim ke Sekolah</em>.</li>
              <li><strong>Check-in Retur Sekolah:</strong> Saat boks kembali dari sekolah, segera lakukan cek fisik bersama tim lapangan dan input di tab <em>Retur / Kembali</em>.</li>
              <li><strong>Discrepancy Reporting:</strong> Jika ada komponen yang tertinggal atau rusak di sekolah, catat rinciannya agar terarsip di sistem.</li>
            </ul>
          </div>

          <!-- Role 4: Teknisi Perbaikan & Maintenance -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center text-xl font-bold">🔧</div>
              <div>
                <h4 class="font-extrabold text-sm text-base-content">Teknisi Maintenance & Repair</h4>
                <span class="text-[10px] text-base-content/50 font-semibold uppercase">Rekondisi, Penggantian Part & Kalibrasi</span>
              </div>
            </div>
            <ul class="text-xs text-base-content/80 space-y-2 list-disc list-inside">
              <li><strong>Antrean Perbaikan:</strong> Cek boks atau modul yang masuk status <code>REPAIR</code> setelah proses pengembalian sekolah.</li>
              <li><strong>Penggantian Komponen:</strong> Ambil komponen pengganti dari stok spare-part (misal: kabel alligator baru, breadboard mini, atau servo).</li>
              <li><strong>Dokumentasi Perbaikan:</strong> Tulis tindakan yang dilakukan (misal: solder ulang pin kabel, ganti baterai) di tab <em>Maintenance & Repair</em>.</li>
              <li><strong>Re-QC:</strong> Boks yang telah selesai diperbaiki wajib melalui proses QC ulang sebelum kembali berstatus <code>READY</code>.</li>
            </ul>
          </div>
        </div>

        <!-- SECTION 3: TABEL REFERENSI & STRUKTUR KIT MIKMS -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <h3 class="font-extrabold text-base text-base-content mb-2 flex items-center gap-2">
            <span>📦</span> Standar Pembagian Boks Kit Lapangan
          </h3>
          <p class="text-xs text-base-content/60 mb-4">Struktur pengelompokan modul ke dalam 3 boks utama sesuai dokumen kurikulum lapangan.</p>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Box 1 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300">
              <div class="badge badge-primary font-bold text-[10px] mb-2">BOKS 1</div>
              <h5 class="font-extrabold text-sm text-base-content">BOX 1 – Beginner Kit</h5>
              <p class="text-xs text-base-content/60 mt-1 mb-3">Peralatan dasar pemrograman, output visual & konektor interaktif.</p>
              <div class="space-y-1.5 text-xs">
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>M01 - Controller Kit</span>
                  <span class="badge badge-xs badge-neutral">Micro:bit V2</span>
                </div>
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>M02 - LED Kit</span>
                  <span class="badge badge-xs badge-neutral">LED & Resistor</span>
                </div>
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>M07 - Connection Kit</span>
                  <span class="badge badge-xs badge-neutral">Breadboard & Kabel</span>
                </div>
              </div>
            </div>

            <!-- Box 2 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300">
              <div class="badge badge-accent font-bold text-[10px] mb-2">BOKS 2</div>
              <h5 class="font-extrabold text-sm text-base-content">BOX 2 – Supporting Equipment</h5>
              <p class="text-xs text-base-content/60 mt-1 mb-3">Aktuator gerak mekanis, sensorik, dan sumber daya baterai.</p>
              <div class="space-y-1.5 text-xs">
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>M03 - Motion Kit</span>
                  <span class="badge badge-xs badge-neutral">Servo SG90 / MG996R</span>
                </div>
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>M04 - Sensor Kit</span>
                  <span class="badge badge-xs badge-neutral">Sensor HC-SR04</span>
                </div>
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>M05 - Power Kit</span>
                  <span class="badge badge-xs badge-neutral">Holder & Baterai AAA</span>
                </div>
              </div>
            </div>

            <!-- Box 3 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300">
              <div class="badge badge-secondary font-bold text-[10px] mb-2">BOKS 3</div>
              <h5 class="font-extrabold text-sm text-base-content">BOX 3 – Bricks & Mechanics</h5>
              <p class="text-xs text-base-content/60 mt-1 mb-3">Struktur konstruksi mekanik, roda, sambungan, dan plate lego.</p>
              <div class="space-y-1.5 text-xs">
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>M06 - Mechanical Kit</span>
                  <span class="badge badge-xs badge-neutral">Lego & Separator</span>
                </div>
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>Base Plate Lego</span>
                  <span class="badge badge-xs badge-neutral">Base Plate</span>
                </div>
                <div class="p-2 rounded-lg bg-base-100 border border-base-200 font-semibold flex justify-between items-center">
                  <span>Set Lego Assembling</span>
                  <span class="badge badge-xs badge-neutral">Bricks</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 4: FAQ & TROUBLESHOOTING LAPANGAN -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
          <h3 class="font-extrabold text-base text-base-content mb-3 flex items-center gap-2">
            <span>❓</span> Tanya Jawab (FAQ) & Solusi Kendala Lapangan
          </h3>
          <div class="space-y-3">
            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Apa yang harus dilakukan jika Micro:bit V2 tidak terdeteksi saat dicolokkan ke laptop?
              </div>
              <div class="collapse-content text-xs text-base-content/70">
                <p>1. Coba ganti kabel Micro USB (kode <code>CT-003</code>) dengan kabel data lain, bukan kabel charge-only.<br>
                2. Tekan dan tahan tombol Reset di belakang Micro:bit selama 5 detik hingga masuk mode MAINTENANCE.<br>
                3. Jika tetap tidak terdeteksi, catat di modul repair untuk penggantian unit cadangan.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Bagaimana jika ada komponen yang hilang di sekolah saat pengembalian boks?
              </div>
              <div class="collapse-content text-xs text-base-content/70">
                <p>Saat pengembalian di tab <em>Retur / Kembali</em>, pilih kondisi <strong>"Komponen Kurang / Hilang"</strong>. Rincikan nama komponen dan jumlahnya. Status boks akan otomatis diubah menjadi <code>REPAIR</code> sampai staf gudang mengisi kembali komponen yang hilang dari stok konsumabel gudang.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Bagaimana cara mengekspor data laporan lapangan ke Excel yang formatnya sama dengan formulir resmi?
              </div>
              <div class="collapse-content text-xs text-base-content/70">
                <p>Klik tombol <strong>"Unduh Excel Lapangan"</strong> di header atas halaman MIKMS. Sistem secara otomatis menyuntikkan seluruh transaksi terbaru ke dalam file template Excel 10-sheet (Legenda, BOM, Stok Akhir, Penerimaan, Produksi, QC, Pengiriman, Pengembalian, Repair, dan Opname) yang siap dicetak atau dilaporkan ke manajemen.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════
           MODAL REGISTER BOX MIKMS BARU
           ═════════════════════════════════════════════ -->
      <div class="modal" id="mikms-box-modal">
        <div class="modal-box max-w-md bg-base-100 border border-base-300 shadow-2xl p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="font-extrabold text-lg text-base-content">Daftarkan Boks Kit MIKMS Baru</h3>
            <button class="btn btn-sm btn-circle btn-ghost" onclick="closeMikmsBoxModal()">✕</button>
          </div>
          <form id="mk-box-reg-form" onsubmit="submitMikmsNewBox(event)" class="space-y-4">
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kode Boks Kit * (Unik)</span></label>
              <input type="text" id="mk-reg-box-code" class="input input-bordered input-sm font-mono font-bold w-full" placeholder="Contoh: BOX-007" required>
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kategori Boks *</span></label>
              <select id="mk-reg-box-category" class="select select-bordered select-sm font-bold w-full" required>
                <option value="BOX 1 – Beginner Kit">BOX 1 – Beginner Kit</option>
                <option value="BOX 2 – Supporting Equipment">BOX 2 – Supporting Equipment</option>
                <option value="BOX 3 – Bricks">BOX 3 – Bricks</option>
                <option value="BOX Master Complete Kit">BOX Master Complete Kit</option>
              </select>
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Program Belajar *</span></label>
              <select id="mk-reg-box-program" class="select select-bordered select-sm font-bold w-full" required>
                <option value="MB-BEG">Micro:bit Beginner (MB-BEG)</option>
                <option value="MB-ADV">Micro:bit Intermediate / Advanced (MB-ADV)</option>
                <option value="ROBOTIC">Robotics Learning Track</option>
              </select>
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Status Awal *</span></label>
              <select id="mk-reg-box-status" class="select select-bordered select-sm font-bold w-full" required>
                <option value="READY">READY (Tersedia di Rak)</option>
                <option value="REPAIR">REPAIR (Dalam Perakitan / Perbaikan)</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md mt-2">
              Daftarkan Boks Kit
            </button>
          </form>
        </div>
      </div>
      <div class="modal" id="user-modal">
        <div class="modal-box max-w-md bg-base-100 border border-base-300 shadow-2xl p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="font-extrabold text-lg text-base-content" id="um-title">Tambah User</h3>
            <button class="btn btn-sm btn-circle btn-ghost" onclick="closeUserModal()">✕</button>
          </div>
          <input type="hidden" id="um-id">
          <div class="space-y-4">
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">NIK Karyawan * (Wajib Unik)</span></label>
              <input type="text" id="um-nik" class="input input-bordered input-sm w-full font-bold" placeholder="Contoh: 12345 / admin">
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Lengkap *</span></label>
              <input type="text" id="um-name" class="input input-bordered input-sm w-full font-semibold" placeholder="Nama Lengkap User">
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Email (Opsional)</span></label>
              <input type="email" id="um-email" class="input input-bordered input-sm w-full" placeholder="email@domain.com (bisa kosong)">
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Password *</span></label>
              <input type="password" id="um-password" class="input input-bordered input-sm w-full" placeholder="Password default: password">
              <span class="text-[9px] text-base-content/40 mt-1 font-semibold block" id="um-pass-tip">Kosongkan jika tidak ingin mengubah password saat edit</span>
            </div>
            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Role Akses *</span></label>
              <select id="um-role" class="select select-bordered select-sm w-full font-bold">
                <option value="petugas">Petugas Gudang</option>
                <option value="admin">Admin System</option>
              </select>
            </div>
          </div>
          <div class="flex gap-3 mt-8">
            <button class="btn btn-ghost flex-1 btn-sm rounded-xl font-bold" onclick="closeUserModal()">Batal</button>
            <button class="btn btn-primary flex-1 btn-sm rounded-xl font-bold shadow-md shadow-primary/20" onclick="saveUser()">Simpan</button>
          </div>
        </div>
      </div>

      <!-- MODAL IMPORT BULK USER (CSV) -->
      <div class="modal" id="user-import-modal">
        <div class="modal-box max-w-xl bg-base-100 border border-base-300 shadow-2xl p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="font-extrabold text-lg text-base-content">📥 Import Pengguna via CSV</h3>
            <button class="btn btn-sm btn-circle btn-ghost" onclick="closeUserImportModal()">✕</button>
          </div>
          
          <div class="space-y-4">
            <p class="text-xs text-base-content/70">
              Format CSV: **NIK,Nama,Password**. Baris pertama tidak dianggap header (langsung data). NIK wajib unik.
            </p>
            
            <div class="form-control">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tempel Data CSV / Teks Terpisah Koma</span></label>
              <textarea id="us-import-csv" class="textarea textarea-bordered h-40 font-mono text-xs" placeholder="12345,Budi Santoso,password123&#10;12346,Siti Aminah,password456" oninput="previewUserCSV()"></textarea>
            </div>

            <div class="form-control">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Atau Pilih File CSV (.csv)</span></label>
              <input type="file" id="us-import-file" accept=".csv,text/csv" class="file-input file-input-bordered file-input-sm w-full" onchange="handleUserCSVFile(event)">
            </div>

            <!-- Previews Table -->
            <div id="us-import-preview-container" class="hidden max-h-48 overflow-y-auto border border-base-300 rounded-lg p-2 bg-base-200">
              <table class="table table-xs w-full">
                <thead>
                  <tr><th>NIK</th><th>Nama</th><th>Password</th></tr>
                </thead>
                <tbody id="us-import-preview-tbody"></tbody>
              </table>
            </div>

            <div class="text-xs text-error font-semibold" id="us-import-err"></div>
          </div>

          <div class="flex gap-3 mt-8">
            <button class="btn btn-ghost flex-1 btn-sm rounded-xl font-bold" onclick="closeUserImportModal()">Batal</button>
            <button class="btn btn-primary flex-1 btn-sm rounded-xl font-bold shadow-md shadow-primary/20" onclick="processUserImport()">Mulai Impor</button>
          </div>
        </div>
      </div>

      <div class="modal" id="item-modal">
  <div class="modal-box max-w-lg bg-base-100 border border-base-300 shadow-2xl p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="font-extrabold text-lg text-base-content" id="im-title">Tambah Barang</h3>
      <button class="btn btn-sm btn-circle btn-ghost" onclick="closeItemModal()">✕</button>
    </div>
    <input type="hidden" id="im-id">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kode Barang *</span></label>
        <input type="text" id="im-kode" class="input input-bordered input-sm w-full uppercase font-bold font-mono" placeholder="KODE10">
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Barang *</span></label>
        <input type="text" id="im-nama" class="input input-bordered input-sm w-full font-semibold" placeholder="Nama lengkap barang">
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kategori Barang *</span></label>
        <select id="im-category-id" class="select select-bordered select-sm w-full font-semibold">
          <!-- Populated dynamically -->
        </select>
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Jenis Barang *</span></label>
        <select id="im-jenis-barang" class="select select-bordered select-sm w-full font-bold">
          <option value="INV">Inventory (Barang Fisik)</option>
          <option value="SVC">Service (Jasa)</option>
        </select>
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Barcode UPC/EAN</span></label>
        <input type="text" id="im-upc-barcode" class="input input-bordered input-sm w-full" placeholder="Barcode pabrik (opsional)">
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Satuan *</span></label>
        <select id="im-satuan" class="select select-bordered select-sm w-full">
          <option>pcs</option><option>unit</option><option>set</option><option>box</option><option>lembar</option>
        </select>
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Min. Stok Warning</span></label>
        <input type="number" id="im-minstok" class="input input-bordered input-sm w-full font-bold" value="5" min="0">
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Produk Group (Legacy)</span></label>
        <input type="text" id="im-produk" class="input input-bordered input-sm w-full" placeholder="Modul, Kit, dll">
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Komponen</span></label>
        <input type="text" id="im-komponen" class="input input-bordered input-sm w-full" placeholder="Shared, Jimu, dll">
      </div>
    </div>
    <div class="flex gap-3 mt-8">
      <button class="btn btn-ghost flex-1 btn-sm rounded-xl font-bold" onclick="closeItemModal()">Batal</button>
      <button class="btn btn-primary flex-1 btn-sm rounded-xl font-bold shadow-md shadow-primary/20" onclick="saveItem()">Simpan</button>
    </div>
  </div>
</div>

<!-- ═══ ASSET MUTATE MODAL ═══ -->
<div class="modal" id="asset-mutate-modal">
  <div class="modal-box max-w-sm bg-base-100 border border-base-300 shadow-2xl p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="font-extrabold text-base text-base-content">Mutasi Lokasi Asset</h3>
      <button class="btn btn-sm btn-circle btn-ghost" onclick="closeAssetMutateModal()">✕</button>
    </div>
    <input type="hidden" id="am-id">
    <div class="space-y-4">
      <div class="form-control w-full">
        <span class="text-xs text-base-content/60">Serial Number:</span>
        <div id="am-serial" class="font-bold text-sm"></div>
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Lokasi Tujuan Baru *</span></label>
        <select id="am-location-id" class="select select-bordered select-sm w-full font-semibold" required></select>
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan Mutasi</span></label>
        <input type="text" id="am-catatan" class="input input-bordered input-sm w-full" placeholder="Alasan pemindahan">
      </div>
      <button class="btn btn-primary btn-sm w-full font-bold" onclick="submitAssetMutation()">Proses Mutasi Lokasi</button>
    </div>
  </div>
</div>

<!-- ═══ ASSET STATUS MODAL ═══ -->
<div class="modal" id="asset-status-modal">
  <div class="modal-box max-w-sm bg-base-100 border border-base-300 shadow-2xl p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="font-extrabold text-base text-base-content">Update Kondisi Asset</h3>
      <button class="btn btn-sm btn-circle btn-ghost" onclick="closeAssetStatusModal()">✕</button>
    </div>
    <input type="hidden" id="as-id">
    <div class="space-y-4">
      <div class="form-control w-full">
        <span class="text-xs text-base-content/60">Serial Number:</span>
        <div id="as-serial" class="font-bold text-sm"></div>
      </div>
      <div class="form-control w-full">
        <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kondisi Baru *</span></label>
        <select id="as-status" class="select select-bordered select-sm w-full font-semibold" required>
          <option value="lengkap">Lengkap</option>
          <option value="good">Good</option>
          <option value="not_good">Not Good</option>
          <option value="tidak_lengkap">Tidak Lengkap</option>
        </select>
      </div>
      <button class="btn btn-primary btn-sm w-full font-bold" onclick="submitAssetStatusUpdate()">Simpan Perubahan Kondisi</button>
    </div>
  </div>
</div>

<!-- ═══ TOAST NOTIFICATION ═══ -->
<div id="toast" class="toast toast-end toast-bottom z-[9999] opacity-0 pointer-events-none transition-all duration-300 max-w-sm">
  <div class="alert alert-neutral shadow-lg border border-base-300 flex text-xs font-bold" id="toast-alert">
    <span id="toast-msg">Toast message here</span>
  </div>
</div>

<!-- printable barcode container -->
<div id="print-area" class="hidden"></div>

<script>
/* ═════════════════════════════════════════════
   STATE & THEME
   ═════════════════════════════════════════════ */
let token = '';
let user = null;
let master = [];
let txLog = [];
let pending = [];

try {
  token = localStorage.getItem('gs_token') || '';
} catch (e) {}

try {
  const u = localStorage.getItem('gs_user');
  user = (u && u !== 'undefined') ? JSON.parse(u) : null;
} catch (e) {
  console.error("Failed to parse user", e);
}

try {
  const p = localStorage.getItem('gs_pending');
  pending = (p && p !== 'undefined') ? JSON.parse(p) : [];
} catch (e) {
  console.error("Failed to parse pending transactions", e);
}

let currentMode = 'masuk', cameraActive = false, lastScanned = '', lastScanTime = 0, isSyncing = false;
let chartTrend = null, chartGudang = null;
let deferredPrompt = null;

let currentTheme = 'winter';
try {
  currentTheme = localStorage.getItem('gs_theme') || 'winter';
} catch (e) {}
document.documentElement.setAttribute('data-theme', currentTheme);

function toggleTheme() {
  currentTheme = currentTheme === 'winter' ? 'dim' : 'winter';
  document.documentElement.setAttribute('data-theme', currentTheme);
  localStorage.setItem('gs_theme', currentTheme);
  updateThemeButton();
  if (document.getElementById('pg-dashboard').classList.contains('active')) {
    renderCharts();
  }
}
function updateThemeButton() {
  const btn = document.getElementById('theme-btn');
  if (btn) btn.textContent = currentTheme === 'winter' ? '🌙' : '☀️';
}

/* ═════════════════════════════════════════════
   PWA INSTALL
   ═════════════════════════════════════════════ */
window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); deferredPrompt = e; });
function installPWA() {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(r => {
      if (r.outcome === 'accepted') showToast('✓ Aplikasi terinstall!');
      deferredPrompt = null;
    });
  } else {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    if (isIOS) {
      alert("Instalasi PWA ErlassGudangApp di iOS:\n1. Ketuk tombol 'Bagikan' (Share) di Safari 📤\n2. Gulir ke bawah lalu ketuk 'Tambah ke Layar Utama' (Add to Home Screen) ➕");
    } else {
      alert("Instalasi PWA ErlassGudangApp:\n1. Ketuk tombol menu browser (titik tiga di kanan atas) ░\n2. Pilih 'Instal Aplikasi' atau 'Tambahkan ke Layar Utama' 📲\n\n(Jika aplikasi sudah terinstal, silakan luncurkan langsung dari beranda/layar utama HP Anda)");
    }
  }
}
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => {});

/* ═════════════════════════════════════════════
   AUTH / NIK & PASSWORD
   ═════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  updateThemeButton();
  if (token && user) { document.getElementById('login-screen').style.display = 'none'; document.getElementById('app-layout').style.display = 'flex'; initApp(); }

  // Keyboard accessibility for sidebar menu items & button roles (Enter/Space to click)
  document.querySelectorAll('.sb-item, [role="button"]').forEach(el => {
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        el.click();
      }
    });
  });
});

async function handleLoginSubmitForm(e) {
  e.preventDefault();
  const nik = document.getElementById('login-nik').value.trim();
  const password = document.getElementById('login-password').value;
  const errEl = document.getElementById('login-err');

  errEl.textContent = 'Memverifikasi...';
  
  try {
    const r = await fetch('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ nik, password })
    });
    const d = await r.json();
    if (d.success) {
      token = d.token;
      user = d.user;
      localStorage.setItem('gs_token', token);
      localStorage.setItem('gs_user', JSON.stringify(user));
      
      document.getElementById('login-screen').style.display = 'none';
      document.getElementById('app-layout').style.display = 'flex';
      
      initApp();
      showToast('✓ Login berhasil');
    } else {
      errEl.textContent = d.message || 'NIK atau Password salah';
    }
  } catch (err) {
    errEl.textContent = 'Koneksi gagal';
  }
}

function logoutUser() { if (!confirm('Logout dari ErlassGudangApp?')) return; fetch('/api/auth/logout', { method: 'POST', headers: { Authorization: 'Bearer ' + token } }).finally(() => { localStorage.removeItem('gs_token'); localStorage.removeItem('gs_user'); token = ''; user = null; location.reload(); }); }


/* ═════════════════════════════════════════════
   INIT
   ═════════════════════════════════════════════ */
async function initApp() {
  document.getElementById('sb-uname').textContent = user.name;
  document.getElementById('sb-urole').textContent = user.role;
  document.getElementById('sb-avatar').textContent = user.name.charAt(0).toUpperCase();
  if (user.role === 'admin') document.querySelectorAll('.admin-section').forEach(e => e.style.display = '');

  // Dynamic label & placeholder based on user role
  const petugasLbl = document.getElementById('sf-petugas-lbl');
  const petugasInput = document.getElementById('sf-petugas');
  if (petugasLbl && petugasInput) {
    if (user.role === 'admin') {
      petugasLbl.textContent = 'Admin';
      petugasInput.placeholder = 'Nama Admin (opsional)...';
    } else {
      petugasLbl.textContent = 'Petugas';
      petugasInput.placeholder = 'Nama Petugas (opsional)...';
    }
  }

  updatePendingBadge();
  await Promise.all([loadStock(), loadTransactions(), loadMasterDataDependents()]);
  renderDashboard();
  buildSelects();
}
async function api(url, opts = {}) {
  const r = await fetch(url, {
    ...opts,
    headers: {
      Authorization: 'Bearer ' + token,
      Accept: 'application/json',
      ...opts.headers
    }
  });
  if (r.status === 401) {
    localStorage.removeItem('gs_token');
    localStorage.removeItem('gs_user');
    token = '';
    user = null;
    document.getElementById('login-screen').style.display = 'flex';
    document.getElementById('app-layout').style.display = 'none';
    showToast('Sesi berakhir, silakan login kembali', true);
  }
  return r;
}

/* ═════════════════════════════════════════════
   NAVIGATION
   ═════════════════════════════════════════════ */
function goPage(name) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('pg-' + name).classList.add('active');
  
  // Clear highlight on all items
  document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active', 'bg-primary/10', 'text-primary', 'border', 'border-primary/20'));
  
  // Highlight the active item by ID
  const activeLink = document.getElementById('sb-link-' + name);
  if (activeLink) activeLink.classList.add('active', 'bg-primary/10', 'text-primary', 'border', 'border-primary/20');
  
  // Toggle Hamburger menu vs Back button on mobile topbar
  const isDashboard = name === 'dashboard';
  const menuBtn = document.getElementById('tb-menu-btn');
  const backBtn = document.getElementById('tb-back-btn');
  if (menuBtn && backBtn) {
    if (isDashboard) {
      menuBtn.style.display = '';
      backBtn.style.display = 'none';
    } else {
      menuBtn.style.display = 'none';
      backBtn.style.display = '';
    }
  }

  const titles = { 
    dashboard: 'Dashboard', 
    scan: 'Scan Barang', 
    stock: 'Daftar Stok', 
    transactions: 'Riwayat Transaksi', 
    stockcard: 'Kartu Stok', 
    print: 'Cetak QR Stiker', 
    master: 'Master Barang',
    sewadash: 'Dashboard Sewa & Bekas',
    sewamutasi: 'Operasional Transaksi Sewa',
    sewaunits: 'Daftar Unit Asset',
    sewacard: 'Riwayat & Kartu Asset',
    locations: 'Manajemen Lokasi Penyimpanan',
    vendors: 'Manajemen Master Vendor',
    customers: 'Manajemen Master Customer',
    categories: 'Master Kategori Barang',
    users: 'Manajemen Master User',
    mikms: 'Operasional MIKMS Lapangan',
    mikmsguide: 'Panduan & SOP Alur Kerja MIKMS'
  };
  document.getElementById('tb-page-title').textContent = titles[name] || name;
  document.getElementById('main-scroll').scrollTop = 0;
  closeSidebar();
  if (name === 'stock') loadStock();
  if (name === 'transactions') loadTransactions();
  if (name === 'dashboard') renderDashboard();
  if (name === 'master') renderMasterTable();
  if (name === 'sewadash') loadSewaDashboard();
  if (name === 'sewamutasi') loadSewaMutasiPage();
  if (name === 'sewaunits') loadSewaUnitsPage();
  if (name === 'sewacard') loadSewaCardPage();
  if (name === 'locations') loadLocationsPage();
  if (name === 'vendors' || name === 'customers') loadPartnersPage();
  if (name === 'categories') loadCategoriesPage();
  if (name === 'users') loadUsersPage();
  if (name === 'mikms') loadMikmsPage();
  if (name === 'mikmsguide') renderMikmsGuide();
}
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sb-overlay');
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
}
function closeSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sb-overlay');
  sidebar.classList.add('-translate-x-full');
  overlay.classList.add('hidden');
}

/* ═════════════════════════════════════════════
   DATA LOADING
   ═════════════════════════════════════════════ */
async function loadMasterDataDependents() {
  try {
    const [resLoc, resVend, resCust] = await Promise.all([
      api('/api/locations'),
      api('/api/vendors'),
      api('/api/customers')
    ]);
    const [dLoc, dVend, dCust] = await Promise.all([
      resLoc.json(),
      resVend.json(),
      resCust.json()
    ]);
    if (dLoc.success) locations = dLoc.data;
    if (dVend.success) vendors = dVend.data;
    if (dCust.success) customers = dCust.data;
    
    populateTransactionFormSelects();
  } catch (e) {
    console.error("Gagal memuat master data dependents", e);
  }
}

function populateTransactionFormSelects() {
  const sfLokasi = document.getElementById('sf-lokasi');
  if (sfLokasi && locations.length > 0) {
    sfLokasi.innerHTML = locations.map(l => `<option value="${escHtml(l.nama)}">${escHtml(l.nama)}</option>`).join('');
  }

  const sfSumberDl = document.getElementById('sf-sumber-dl');
  if (sfSumberDl) {
    const defaultOptions = ['Transfer antar gudang', 'Retur dari karyawan', 'Produksi internal', 'Lainnya'];
    const vendorOptions = vendors.map(v => v.nama);
    const combined = [...new Set([...vendorOptions, ...defaultOptions])];
    sfSumberDl.innerHTML = combined.map(o => `<option value="${escHtml(o)}"></option>`).join('');
  }

  const sfPenerimaDl = document.getElementById('sf-penerima-dl');
  if (sfPenerimaDl) {
    const defaultOptions = ['Transfer antar gudang', 'Pembuangan / Disposal', 'Karyawan internal', 'Lainnya'];
    const customerOptions = customers.map(c => c.nama);
    const combined = [...new Set([...customerOptions, ...defaultOptions])];
    sfPenerimaDl.innerHTML = combined.map(o => `<option value="${escHtml(o)}"></option>`).join('');
  }
}

async function loadStock() {
  const g = document.getElementById('stk-gudang')?.value || 'Semua';
  try {
    const r = await api(`/api/stock?gudang=${encodeURIComponent(g)}`);
    const d = await r.json();
    if (d.success) { master = d.data.items; localStorage.setItem('gs_master', JSON.stringify(master)); }
  } catch {
    try {
      const mStr = localStorage.getItem('gs_master');
      master = (mStr && mStr !== 'undefined') ? JSON.parse(mStr) : [];
    } catch(e) { master = []; }
  }
  renderStockTable();
  buildSelects();
}
async function loadTransactions() {
  const g = document.getElementById('tx-gudang')?.value || 'Semua';
  const t = document.getElementById('tx-tipe')?.value || '';
  let url = `/api/transactions?limit=500`;
  if (g !== 'Semua') url += `&gudang=${encodeURIComponent(g)}`;
  if (t) url += `&tipe=${t}`;
  try {
    const r = await api(url);
    const d = await r.json();
    if (d.success) { txLog = d.data; localStorage.setItem('gs_log', JSON.stringify(txLog)); }
  } catch {
    try {
      const lStr = localStorage.getItem('gs_log');
      txLog = (lStr && lStr !== 'undefined') ? JSON.parse(lStr) : [];
    } catch(e) { txLog = []; }
  }
  renderTxTable();
}

/* ═════════════════════════════════════════════
   DASHBOARD
   ═════════════════════════════════════════════ */
function renderDashboard() {
  const totalItems = master.length;
  const totalStok = master.reduce((a, m) => a + m.stok, 0);
  const kosong = master.filter(m => m.stok <= 0).length;
  const menipis = master.filter(m => m.stok > 0 && m.stok < 5).length;
  const todayStr = new Date().toISOString().split('T')[0];
  const txToday = txLog.filter(t => (t.transaction_date || '').startsWith(todayStr)).length;

  document.getElementById('dash-stats').innerHTML = [
    { icon: '📦', val: totalItems, lbl: 'Total Item', cls: 'text-primary' },
    { icon: '🏷️', val: totalStok, lbl: 'Total Unit', cls: 'text-success' },
    { icon: '⚠️', val: menipis, lbl: 'Stok Menipis', cls: 'text-warning' },
    { icon: '🚫', val: kosong, lbl: 'Stok Kosong', cls: 'text-error' },
    { icon: '📝', val: txToday, lbl: 'Mutasi Hari Ini', cls: 'text-info' },
  ].map(s => `
    <div class="stats bg-base-100 shadow-sm border border-base-300">
      <div class="stat p-4">
        <div class="stat-figure ${s.cls} text-2xl">${s.icon}</div>
        <div class="stat-title text-[10px] font-bold uppercase tracking-wider text-base-content/50">${s.lbl}</div>
        <div class="stat-value text-2xl font-black ${s.cls} mt-1">${s.val}</div>
      </div>
    </div>
  `).join('');

  // Recent transactions table
  const recent = txLog.slice(0, 10);
  document.getElementById('dash-recent-tbody').innerHTML = recent.length ? recent.map(t => {
    const isMasuk = t.tipe === 'masuk';
    return `<tr>
      <td class="whitespace-nowrap">${fmtDate(t.transaction_date || t.created_at)}</td>
      <td class="font-mono font-bold text-xs text-primary">${t.item?.kode || ''}</td>
      <td class="font-semibold text-base-content">${t.item?.nama || ''}</td>
      <td class="text-center">
        <span class="badge ${isMasuk ? 'badge-success text-white' : 'badge-error text-white'} badge-xs font-bold text-[9px] px-2 py-1.5">${isMasuk ? 'Masuk' : 'Keluar'}</span>
      </td>
      <td class="font-mono font-bold text-right ${isMasuk ? 'text-success' : 'text-error'}">${isMasuk ? '+' : '-'}${t.qty}</td>
      <td><span class="badge badge-outline border-base-300 text-[10px]">${t.lokasi || '—'}</span></td>
      <td class="text-xs text-base-content/60">${t.user?.name || t.petugas || '—'}</td>
    </tr>`;
  }).join('') : '<tr><td colspan="7" class="text-center py-8 text-base-content/40">Belum ada transaksi</td></tr>';

  renderCharts();
}

function renderCharts() {
  if (typeof Chart === 'undefined') return; // Skip if library fails to load
  
  const ctx1 = document.getElementById('chart-trend');
  if (!ctx1 || ctx1.offsetParent === null) return; // Skip rendering if hidden (e.g. mobile)

  const isDark = currentTheme === 'dim';
  const textColor = isDark ? '#94a3b8' : '#475569';
  const gridColor = isDark ? 'rgba(148,163,184,0.06)' : 'rgba(0,0,0,0.06)';

  // Trend chart (last 30 days)
  const days = [];
  for (let i = 29; i >= 0; i--) { const d = new Date(); d.setDate(d.getDate() - i); days.push(d.toISOString().split('T')[0]); }
  const masukByDay = {}, keluarByDay = {};
  days.forEach(d => { masukByDay[d] = 0; keluarByDay[d] = 0; });
  txLog.forEach(t => {
    const d = (t.transaction_date || '').substring(0, 10);
    if (t.tipe === 'masuk' && masukByDay[d] !== undefined) masukByDay[d] += t.qty;
    if (t.tipe === 'keluar' && keluarByDay[d] !== undefined) keluarByDay[d] += t.qty;
  });

  try {
    if (chartTrend) chartTrend.destroy();
    chartTrend = new Chart(ctx1, {
      type: 'line',
      data: {
        labels: days.map(d => { const dt = new Date(d); return dt.getDate() + '/' + (dt.getMonth() + 1); }),
        datasets: [
          { label: 'Masuk', data: days.map(d => masukByDay[d]), borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.05)', tension: 0.3, fill: true, pointRadius: 1 },
          { label: 'Keluar', data: days.map(d => keluarByDay[d]), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.05)', tension: 0.3, fill: true, pointRadius: 1 },
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: textColor, font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' } } } },
        scales: {
          x: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor } },
          y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } }
        }
      }
    });
  } catch (e) {
    console.error("Error creating trend chart", e);
  }

  // Gudang distribution chart
  const gudangs = ['Gudang Utama', 'Gudang Raw Material', 'Gudang Work in Process'];
  const masukPerGudang = gudangs.map(g => txLog.filter(t => t.tipe === 'masuk' && t.lokasi === g).reduce((a, t) => a + t.qty, 0));
  const keluarPerGudang = gudangs.map(g => txLog.filter(t => t.tipe === 'keluar' && t.lokasi === g).reduce((a, t) => a + t.qty, 0));

  try {
    if (chartGudang) chartGudang.destroy();
    const ctx2 = document.getElementById('chart-gudang');
    chartGudang = new Chart(ctx2, {
      type: 'bar',
      data: {
        labels: ['Utama', 'Raw', 'WIP'],
        datasets: [
          { label: 'Masuk', data: masukPerGudang, backgroundColor: 'rgba(5,150,105,0.85)', borderRadius: 4 },
          { label: 'Keluar', data: keluarPerGudang, backgroundColor: 'rgba(220,38,38,0.85)', borderRadius: 4 },
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: textColor, font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' } } } },
        scales: {
          x: { ticks: { color: textColor, font: { size: 10, weight: 'bold' } }, grid: { display: false } },
          y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } }
        }
      }
    });
  } catch (e) {
    console.error("Error creating gudang chart", e);
  }
}

/* ═════════════════════════════════════════════
   STOCK TABLE
   ═════════════════════════════════════════════ */
function renderStockTable() {
  const q = (document.getElementById('stk-search')?.value || '').toLowerCase();
  const produk = document.getElementById('stk-produk')?.value || 'Semua';
  const filtered = master.filter(m =>
    (produk === 'Semua' || m.produk === produk) &&
    (!q || m.kode.toLowerCase().includes(q) || m.nama.toLowerCase().includes(q))
  );
  // Update produk filter options
  const prods = ['Semua', ...new Set(master.map(m => m.produk).filter(Boolean))];
  const sel = document.getElementById('stk-produk');
  if (sel) { const cur = sel.value; sel.innerHTML = prods.map(p => `<option${p === cur ? ' selected' : ''}>${p}</option>`).join(''); }

  document.getElementById('stk-tbody').innerHTML = filtered.length ? filtered.map(m => {
    const isEmpty = m.is_empty ?? (m.stok <= 0);
    const isWarning = m.is_warning ?? (m.stok > 0 && m.stok <= (m.min_stok ?? 5));
    const minStok = m.min_stok ?? 5;
    
    const stokBadgeClass = isEmpty ? 'text-error font-black' : isWarning ? 'text-warning font-black' : 'text-success font-black';
    const rowClass = isEmpty ? 'bg-error/5' : isWarning ? 'bg-warning/5' : '';
    
    let badge = '';
    if (isEmpty) {
      badge = `<span class="badge badge-error badge-xs text-white font-bold ml-1.5">HABIS</span>`;
    } else if (isWarning) {
      badge = `<span class="badge badge-warning badge-xs font-bold ml-1.5">⚠ MENIPIS</span>`;
    }

    return `<tr class="${rowClass}">
      <td class="font-mono font-bold text-xs text-primary">${m.kode}</td>
      <td class="font-semibold text-base-content">${m.nama}</td>
      <td class="text-xs text-base-content/60">${m.produk || '—'}</td>
      <td class="text-right font-mono font-semibold text-success">+${m.masuk}</td>
      <td class="text-right font-mono font-semibold text-error">-${m.keluar}</td>
      <td class="text-right font-mono text-base ${stokBadgeClass}">
        ${m.stok} <span class="text-[9px] text-base-content/30 font-normal">(min:${minStok})</span>${badge}
      </td>
      <td class="text-xs text-base-content/50">${m.satuan}</td>
      <td class="text-center">
        <button class="btn btn-xs btn-ghost border border-base-300 text-xs" onclick="viewStockCard(${m.id})">📄 Detail</button>
      </td>
    </tr>`;
  }).join('') : '<tr><td colspan="8" class="text-center py-8 text-base-content/40">Tidak ada data</td></tr>';
}

/* ═════════════════════════════════════════════
   TRANSACTION TABLE
   ═════════════════════════════════════════════ */
function renderTxTable() {
  const q = (document.getElementById('tx-search')?.value || '').toLowerCase();
  const filtered = txLog.filter(t => {
    if (!q) return true;
    return (t.item?.kode || '').toLowerCase().includes(q) || 
           (t.item?.nama || '').toLowerCase().includes(q) || 
           (t.user?.name || '').toLowerCase().includes(q) || 
           (t.petugas || '').toLowerCase().includes(q) || 
           (t.sumber || '').toLowerCase().includes(q) || 
           (t.penerima || '').toLowerCase().includes(q);
  });
  document.getElementById('tx-tbody').innerHTML = filtered.length ? filtered.map(t => {
    const isMasuk = t.tipe === 'masuk';
    return `<tr>
      <td class="whitespace-nowrap">${fmtDate(t.transaction_date || t.created_at)}</td>
      <td class="font-mono font-bold text-xs text-primary">${t.item?.kode || ''}</td>
      <td class="font-semibold text-base-content">${t.item?.nama || ''}</td>
      <td class="text-center">
        <span class="badge ${isMasuk ? 'badge-success text-white' : 'badge-error text-white'} badge-xs font-bold text-[9px] px-2 py-1.5">${isMasuk ? 'Masuk' : 'Keluar'}</span>
      </td>
      <td class="font-mono font-bold text-right ${isMasuk ? 'text-success' : 'text-error'}">${isMasuk ? '+' : '-'}${t.qty}</td>
      <td><span class="badge badge-outline border-base-300 text-[10px]">${t.lokasi || '—'}</span></td>
      <td class="text-xs text-base-content/70">${isMasuk ? (t.sumber || '—') : (t.penerima || '—')}</td>
      <td class="font-mono text-[10px] text-base-content/50">${[t.no_po, t.no_prn].filter(Boolean).join(' / ') || '—'}</td>
      <td class="text-xs text-base-content/60">${t.user?.name || t.petugas || '—'}</td>
    </tr>`;
  }).join('') : '<tr><td colspan="9" class="text-center py-8 text-base-content/40">Tidak ada transaksi</td></tr>';
}

/* ═════════════════════════════════════════════
   STOCK CARD
   ═════════════════════════════════════════════ */
function buildSelects() {
  const scItem = document.getElementById('sc-item'); if (scItem) scItem.value = '';
  const scDl = document.getElementById('sc-item-dl');
  if (scDl) {
    scDl.innerHTML = master.map(m => `<option value="${m.kode} — ${m.nama}"></option>`).join('');
  }
  
  const prItem = document.getElementById('pr-item'); if (prItem) prItem.value = '';
  const prDl = document.getElementById('pr-item-dl');
  if (prDl) {
    prDl.innerHTML = master.map(m => `<option value="${m.kode} — ${m.nama}"></option>`).join('');
  }
  
  const dl = document.getElementById('sf-dl'); if (dl) dl.innerHTML = master.map(m => `<option value="${m.kode}">${m.kode} — ${m.nama}</option>`).join('');
}
function viewStockCard(itemId) {
  goPage('stockcard');
  document.getElementById('sc-item').value = itemId;
  const item = master.find(m => m.id == itemId);
  if (item) {
    document.getElementById('sc-item-search').value = `${item.kode} — ${item.nama}`;
  } else {
    document.getElementById('sc-item-search').value = '';
  }
  loadStockCard();
}
function onScItemSearchInput() {
  const searchVal = document.getElementById('sc-item-search').value.trim();
  const hiddenInput = document.getElementById('sc-item');
  if (searchVal === '') {
    hiddenInput.value = '';
    document.getElementById('sc-tbody').innerHTML = '<tr><td colspan="8" class="text-center py-8 text-base-content/40">Pilih barang di atas untuk melihat Kartu Stok.</td></tr>';
    document.getElementById('sc-pdf-btn').style.display = 'none';
    return;
  }
  
  const match = master.find(m => 
    `${m.kode} — ${m.nama}`.toLowerCase() === searchVal.toLowerCase() ||
    m.kode.toLowerCase() === searchVal.toLowerCase() ||
    m.nama.toLowerCase() === searchVal.toLowerCase()
  );
  
  if (match) {
    hiddenInput.value = match.id;
    loadStockCard();
  } else {
    hiddenInput.value = '';
  }
}

function clearScSearch() {
  document.getElementById('sc-item-search').value = '';
  document.getElementById('sc-item').value = '';
  document.getElementById('sc-tbody').innerHTML = '<tr><td colspan="8" class="text-center py-8 text-base-content/40">Pilih barang di atas untuk melihat Kartu Stok.</td></tr>';
  document.getElementById('sc-pdf-btn').style.display = 'none';
}
async function loadStockCard() {
  const itemId = document.getElementById('sc-item').value;
  if (!itemId) return;
  const gudang = document.getElementById('sc-gudang').value;
  const tahun = document.getElementById('sc-tahun').value;
  const gp = gudang !== 'Semua' ? `&gudang=${encodeURIComponent(gudang)}` : '';
  try {
    const r = await api(`/api/stock/${itemId}/card?tahun=${tahun}${gp}`);
    const d = await r.json();
    if (d.success && d.data?.rows) {
      document.getElementById('sc-pdf-btn').style.display = 'inline-flex';
      document.getElementById('sc-pdf-btn').onclick = () => downloadPdf(itemId);
      document.getElementById('sc-tbody').innerHTML = d.data.rows.map(row => {
        const isAwal = (row.keterangan || '').startsWith('SALDO');
        return `<tr class="${isAwal ? 'font-bold bg-base-200/50' : ''}">
          <td class="font-mono text-xs whitespace-nowrap">${row.tanggal_formatted}</td>
          <td class="text-xs max-w-xs truncate" title="${row.keterangan || ''}">${row.keterangan || (row.masuk ? 'Masuk' : 'Keluar')}</td>
          <td class="font-mono text-[10px] text-base-content/50">${[row.no_po, row.no_prn].filter(Boolean).join(' / ') || '—'}</td>
          <td><span class="badge badge-outline border-base-300 text-[10px]">${row.lokasi || '—'}</span></td>
          <td class="text-right font-mono font-semibold ${row.masuk > 0 ? 'text-success' : ''}">${row.masuk > 0 ? '+' + row.masuk : ''}</td>
          <td class="text-right font-mono font-semibold ${row.keluar > 0 ? 'text-error' : ''}">${row.keluar > 0 ? '-' + row.keluar : ''}</td>
          <td class="text-right font-mono font-black text-sm">${row.sisa_akhir}</td>
          <td class="text-[10px] uppercase font-bold text-base-content/60">${row.pic || '—'}</td>
        </tr>`;
      }).join('');
    }
  } catch { showToast('Gagal memuat kartu stok', true); }
}

/* ═════════════════════════════════════════════
   SCAN & FORM
   ═════════════════════════════════════════════ */
function setMode(m) {
  currentMode = m;
  const masukBtn = document.getElementById('mode-masuk-btn');
  const keluarBtn = document.getElementById('mode-keluar-btn');
  
  if (m === 'masuk') {
    masukBtn.className = 'btn btn-sm rounded-xl join-item border-none text-xs font-extrabold btn-primary';
    keluarBtn.className = 'btn btn-sm rounded-xl join-item border-none text-xs font-extrabold btn-ghost text-error';
    
    document.getElementById('scan-form-badge').className = 'badge badge-success text-white font-extrabold text-[10px]';
    document.getElementById('scan-form-badge').textContent = '↓ MASUK';
    document.getElementById('sf-submit-btn').className = 'btn btn-primary w-full shadow-md text-xs font-bold mt-4';
    document.getElementById('sf-submit-btn').textContent = '↓ Simpan Barang Masuk';
  } else {
    masukBtn.className = 'btn btn-sm rounded-xl join-item border-none text-xs font-extrabold btn-ghost text-primary';
    keluarBtn.className = 'btn btn-sm rounded-xl join-item border-none text-xs font-extrabold btn-error text-white';
    
    document.getElementById('scan-form-badge').className = 'badge badge-error text-white font-extrabold text-[10px]';
    document.getElementById('scan-form-badge').textContent = '↑ KELUAR';
    document.getElementById('sf-submit-btn').className = 'btn btn-error text-white w-full shadow-md text-xs font-bold mt-4';
    document.getElementById('sf-submit-btn').textContent = '↑ Simpan Barang Keluar';
  }
  
  document.getElementById('scan-form-title').textContent = m === 'masuk' ? 'Form Barang Masuk' : 'Form Barang Keluar';
  document.getElementById('sf-sumber-wrap').style.display = m === 'masuk' ? '' : 'none';
  document.getElementById('sf-penerima-wrap').style.display = m === 'keluar' ? '' : 'none';
}
function autoFillScan() {
  const v = document.getElementById('sf-kode').value.trim().toUpperCase();
  const f = master.find(m => m.kode === v);
  if (f) { document.getElementById('sf-nama').value = f.nama; document.getElementById('sf-satuan').value = f.satuan || 'pcs'; }
}
async function startCamera() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 640 } } });
    const v = document.getElementById('scanner-video'); v.srcObject = stream; v.play();
    document.getElementById('cam-overlay').style.display = 'none'; cameraActive = true;
    document.getElementById('scan-status').textContent = 'Kamera Aktif — Arahkan ke QR Code';
    document.getElementById('scan-status').className = 'alert alert-success mt-6 text-xs justify-center font-bold text-white';
    (function tick() { if (!cameraActive) return; const c = document.getElementById('scanner-canvas'), ctx = c.getContext('2d'); if (v.readyState === v.HAVE_ENOUGH_DATA) { c.width = v.videoWidth; c.height = v.videoHeight; ctx.drawImage(v, 0, 0); const img = ctx.getImageData(0, 0, c.width, c.height); const code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' }); if (code) { const now = Date.now(); if (code.data !== lastScanned || now - lastScanTime > 3000) { lastScanned = code.data; lastScanTime = now; onQRDetected(code.data); } } } requestAnimationFrame(tick); })();
  } catch {
    document.getElementById('scan-status').textContent = 'Izin kamera ditolak';
    document.getElementById('scan-status').className = 'alert alert-error mt-6 text-xs justify-center font-bold text-white';
  }
}
function onQRDetected(raw) {
  let kode = raw.includes('|') ? raw.split('|')[0] : raw;
  kode = kode.trim().toUpperCase();
  const f = master.find(m => m.kode === kode);
  if (f) { document.getElementById('sf-kode').value = f.kode; document.getElementById('sf-nama').value = f.nama; document.getElementById('sf-satuan').value = f.satuan || 'pcs'; document.getElementById('scan-status').textContent = `✓ Terdeteksi: ${f.kode}`; document.getElementById('scan-status').className = 'alert alert-success mt-6 text-xs justify-center font-bold text-white'; try { navigator.vibrate?.([80, 40, 80]); } catch {} }
  else { document.getElementById('scan-status').textContent = `⚠ Kode tidak ditemukan: ${kode}`; document.getElementById('scan-status').className = 'alert alert-warning mt-6 text-xs justify-center font-bold text-white'; }
}
function submitScan() {
  const kode = document.getElementById('sf-kode').value.trim().toUpperCase();
  const nama = document.getElementById('sf-nama').value.trim();
  const qty = parseInt(document.getElementById('sf-qty').value) || 0;
  const lokasi = document.getElementById('sf-lokasi').value;
  const satuan = document.getElementById('sf-satuan').value;
  if (!kode || !nama || qty < 1) { showToast('Lengkapi kolom wajib *', true); return; }
  if (currentMode === 'masuk' && !document.getElementById('sf-sumber').value) { showToast('Pilih sumber barang', true); return; }
  if (currentMode === 'keluar' && !document.getElementById('sf-penerima').value.trim()) { showToast('Isi penerima barang', true); return; }

  const petugasManual = document.getElementById('sf-petugas').value.trim();
  const petugas = petugasManual || user.name;

  const tx = { client_id: 'GS-' + Date.now(), kode, nama, tipe: currentMode, qty, satuan, lokasi, sumber: document.getElementById('sf-sumber').value, penerima: document.getElementById('sf-penerima').value.trim(), no_po: document.getElementById('sf-po').value.trim(), no_prn: document.getElementById('sf-prn').value.trim(), job_number: document.getElementById('sf-job').value.trim(), transfer_order: document.getElementById('sf-to').value.trim(), catatan: document.getElementById('sf-catatan').value.trim(), petugas, transaction_date: new Date().toISOString().split('T')[0] };

  // Save locally
  txLog.unshift({ id: tx.client_id, item: { kode: tx.kode, nama: tx.nama, satuan: tx.satuan, produk: '' }, tipe: tx.tipe, qty: tx.qty, transaction_date: tx.transaction_date, sumber: tx.sumber, penerima: tx.penerima, lokasi: tx.lokasi, petugas: tx.petugas, created_at: new Date().toISOString(), no_po: tx.no_po, no_prn: tx.no_prn });
  const idx = master.findIndex(m => m.kode === tx.kode);
  if (idx !== -1) master[idx].stok += (tx.tipe === 'masuk' ? tx.qty : -tx.qty);
  pending.push(tx);
  localStorage.setItem('gs_log', JSON.stringify(txLog));
  localStorage.setItem('gs_master', JSON.stringify(master));
  localStorage.setItem('gs_pending', JSON.stringify(pending));
  updatePendingBadge();
  showToast(`✓ ${tx.kode} ${tx.tipe === 'masuk' ? '+' : '-'}${tx.qty} tersimpan`);
  // Reset
  ['sf-kode', 'sf-nama', 'sf-sumber', 'sf-penerima', 'sf-po', 'sf-prn', 'sf-job', 'sf-to', 'sf-petugas', 'sf-catatan'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('sf-qty').value = 1;
  syncPending();
}

/* ═════════════════════════════════════════════
   MASTER CRUD (admin)
   ═════════════════════════════════════════════ */
async function ensureCategoriesLoaded() {
  if (categoriesCache && categoriesCache.length > 0) return;
  try {
    const r = await api('/api/categories');
    const d = await r.json();
    if (d.success) { categoriesCache = d.data; }
  } catch (e) {
    console.error("Failed to load categories", e);
  }
}

function renderMasterTable() {
  const q = (document.getElementById('ms-search')?.value || '').toLowerCase();
  const filtered = master.filter(m => m.kode.toLowerCase().includes(q) || m.nama.toLowerCase().includes(q));
  document.getElementById('ms-tbody').innerHTML = filtered.length ? filtered.map(m => {
    const catName = m.category?.nama || m.produk || '—';
    const typeLabel = m.jenis_barang === 'SVC' ? 'SVC' : 'INV';
    const typeClass = m.jenis_barang === 'SVC' ? 'badge-neutral' : 'badge-primary';
    
    return `<tr>
      <td class="font-mono font-bold text-xs text-primary">${m.kode}</td>
      <td class="font-semibold text-base-content">
        ${m.nama}
        <span class="badge ${typeClass} badge-outline text-[9px] scale-90 ml-1 font-extrabold" title="Jenis Barang: ${typeLabel}">${typeLabel}</span>
      </td>
      <td class="text-xs text-base-content/60 font-semibold">${catName}</td>
      <td class="text-xs text-base-content/60">${m.komponen || '—'}</td>
      <td class="text-xs">${m.satuan}</td>
      <td class="text-right font-mono font-bold">${m.min_stok ?? 5}</td>
      <td class="text-center">
        <div class="flex gap-1 justify-center">
          <button class="btn btn-xs btn-neutral font-bold text-[10px]" onclick="openItemModal(${m.id})">Edit</button>
          <button class="btn btn-xs btn-error btn-outline font-bold text-[10px]" onclick="deleteItem(${m.id})">Hapus</button>
        </div>
      </td>
    </tr>`;
  }).join('') : '<tr><td colspan="7" class="text-center py-8 text-base-content/40">Tidak ada data</td></tr>';
}

async function openItemModal(id) {
  await ensureCategoriesLoaded();

  const selCat = document.getElementById('im-category-id');
  if (selCat) {
    selCat.innerHTML = '<option value="">-- Tanpa Kategori --</option>' + 
      categoriesCache.map(c => `<option value="${c.id}">${c.parent ? '↳ ' : ''}${escHtml(c.nama)}</option>`).join('');
  }

  const m = id ? master.find(x => x.id === id) : null;
  document.getElementById('im-title').textContent = m ? 'Edit Barang' : 'Tambah Barang';
  document.getElementById('im-id').value = m ? m.id : '';
  document.getElementById('im-kode').value = m ? m.kode : ''; 
  document.getElementById('im-kode').readOnly = !!m;
  document.getElementById('im-nama').value = m ? m.nama : '';
  
  // Set category select
  document.getElementById('im-category-id').value = (m && m.category_id) ? m.category_id : '';
  
  // Set jenis barang select
  document.getElementById('im-jenis-barang').value = (m && m.jenis_barang) ? m.jenis_barang : 'INV';
  
  // Set upc barcode
  document.getElementById('im-upc-barcode').value = (m && m.upc_barcode) ? m.upc_barcode : '';
  
  document.getElementById('im-satuan').value = m ? m.satuan : 'pcs';
  document.getElementById('im-minstok').value = m ? (m.min_stok ?? 5) : 5;
  document.getElementById('im-produk').value = m ? (m.produk || '') : '';
  document.getElementById('im-komponen').value = m ? (m.komponen || '') : '';
  document.getElementById('item-modal').classList.add('modal-open');
}

function closeItemModal() { document.getElementById('item-modal').classList.remove('modal-open'); }

async function saveItem() {
  const id = document.getElementById('im-id').value;
  const body = { 
    kode: document.getElementById('im-kode').value.trim().toUpperCase(), 
    nama: document.getElementById('im-nama').value.trim(), 
    category_id: document.getElementById('im-category-id').value || null,
    jenis_barang: document.getElementById('im-jenis-barang').value,
    upc_barcode: document.getElementById('im-upc-barcode').value.trim() || null,
    satuan: document.getElementById('im-satuan').value, 
    min_stok: parseInt(document.getElementById('im-minstok').value) || 0, 
    produk: document.getElementById('im-produk').value.trim(), 
    komponen: document.getElementById('im-komponen').value.trim() 
  };
  
  if (!body.kode || !body.nama) { showToast('Lengkapi kode dan nama', true); return; }
  
  try {
    const r = await api(id ? `/api/items/${id}` : '/api/items', { 
      method: id ? 'PUT' : 'POST', 
      headers: { 'Content-Type': 'application/json' }, 
      body: JSON.stringify(body) 
    });
    const d = await r.json();
    if (d.success) { 
      showToast(d.message); 
      closeItemModal(); 
      await loadStock(); 
      renderMasterTable(); 
    } else {
      showToast('Gagal: ' + (d.message || 'Error'), true); 
    }
  } catch { 
    showToast('Koneksi gagal', true); 
  }
}

async function deleteItem(id) {
  if (!confirm('Hapus barang ini dari master?')) return;
  try { 
    const r = await api(`/api/items/${id}`, { method: 'DELETE' }); 
    const d = await r.json(); 
    if (d.success) { 
      showToast(d.message); 
      await loadStock(); 
      renderMasterTable(); 
    } else {
      showToast('Gagal menghapus', true); 
    }
  } catch { 
    showToast('Koneksi gagal', true); 
  }
}

/* ═════════════════════════════════════════════
   SYNC & EXPORTS
   ═════════════════════════════════════════════ */
async function syncPending() {
  if (!pending.length || isSyncing) return;
  isSyncing = true; setSyncDot('syncing');
  try {
    const r = await api('/api/transactions', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ transactions: pending }) });
    const d = await r.json();
    if (d.success) { pending = []; localStorage.setItem('gs_pending', '[]'); setSyncDot('on'); showToast('✓ Sinkronisasi berhasil'); await Promise.all([loadStock(), loadTransactions()]); renderDashboard(); } else setSyncDot('off');
  } catch { setSyncDot('off'); } finally { isSyncing = false; updatePendingBadge(); }
}
function updatePendingBadge() { const b = document.getElementById('sb-pending'); if (pending.length) { b.style.display = ''; b.textContent = pending.length; } else b.style.display = 'none'; }
function setSyncDot(state) {
  const d = document.getElementById('sync-dot');
  const l = document.getElementById('sync-lbl');
  
  if (state === 'on') {
    d.className = 'w-2.5 h-2.5 rounded-full bg-success shadow-sm';
    l.textContent = 'Online';
  } else if (state === 'syncing') {
    d.className = 'w-2.5 h-2.5 rounded-full bg-primary animate-pulse shadow-sm';
    l.textContent = 'Syncing...';
  } else {
    d.className = 'w-2.5 h-2.5 rounded-full bg-error shadow-sm';
    l.textContent = 'Offline';
  }
}
function downloadPdf(itemId) { const t = document.getElementById('sc-tahun').value; const g = document.getElementById('sc-gudang').value; const gp = g !== 'Semua' ? `&gudang=${encodeURIComponent(g)}` : ''; window.open(`/api/export/pdf/${itemId}?tahun=${t}&token=${token}${gp}`, '_blank'); }
function downloadPdfCard() { const itemId = document.getElementById('sc-item').value; if (itemId) downloadPdf(itemId); }
function exportExcelFile() { const g = document.getElementById('stk-gudang')?.value || 'Semua'; const gp = g !== 'Semua' ? `&gudang=${encodeURIComponent(g)}` : ''; window.open(`/api/export/excel?token=${token}${gp}`, '_blank'); }

/* ═════════════════════════════════════════════
   PRINT QR
   ═════════════════════════════════════════════ */
let printQueue = [];

function addToQueue() {
  const selIdx = document.getElementById('pr-item').value;
  if (selIdx === '') {
    showToast('Pilih barang terlebih dahulu', true);
    return;
  }
  const item = master[parseInt(selIdx)];
  const qty = parseInt(document.getElementById('pr-qty').value) || 1;
  
  const existing = printQueue.find(q => q.item.id === item.id);
  if (existing) {
    existing.qty += qty;
  } else {
    printQueue.push({ item, qty });
  }
  
  clearPrSearch();
  document.getElementById('pr-qty').value = 1;
  renderQueue();
}

function removeFromQueue(index) {
  printQueue.splice(index, 1);
  renderQueue();
}

function updateQueueQty(index, change) {
  printQueue[index].qty += change;
  if (printQueue[index].qty <= 0) {
    printQueue.splice(index, 1);
  }
  renderQueue();
}

function renderQueue() {
  const queueContainer = document.getElementById('pr-queue-section');
  const tableBody = document.getElementById('pr-queue-tbody');
  
  if (printQueue.length === 0) {
    queueContainer.classList.add('hidden');
    updatePrintPreview();
    return;
  }
  
  queueContainer.classList.remove('hidden');
  tableBody.innerHTML = printQueue.map((q, idx) => `
    <tr>
      <td class="font-mono text-xs font-bold text-primary">${q.item.kode}</td>
      <td class="text-xs font-semibold">${q.item.nama}</td>
      <td class="text-right">
        <div class="flex items-center justify-end gap-1.5">
          <button class="btn btn-xs btn-circle btn-ghost border border-base-300" onclick="updateQueueQty(${idx}, -1)">-</button>
          <span class="font-mono text-xs font-bold w-8 text-center">${q.qty}</span>
          <button class="btn btn-xs btn-circle btn-ghost border border-base-300" onclick="updateQueueQty(${idx}, 1)">+</button>
        </div>
      </td>
      <td class="text-center">
        <button class="btn btn-xs btn-error btn-ghost" onclick="removeFromQueue(${idx})">✕</button>
      </td>
    </tr>
  `).join('');
  
  const qBtn = document.getElementById('pr-queue-print-btn');
  if (qBtn) {
    qBtn.textContent = `🖨️ Cetak Semua Antrean (${printQueue.length} Barang)`;
  }
  
  updatePrintPreview();
}

function updatePrintPreview() {
  const preview = document.getElementById('pr-preview'); if (!preview) return;
  preview.innerHTML = '';
  
  let itemsToPreview = [];
  if (printQueue.length > 0) {
    printQueue.forEach(q => {
      for (let i = 0; i < q.qty; i++) {
        itemsToPreview.push(q.item);
      }
    });
  } else {
    const selIdx = document.getElementById('pr-item').value;
    if (selIdx !== '') {
      const item = master[parseInt(selIdx)];
      const qty = parseInt(document.getElementById('pr-qty').value) || 1;
      for (let i = 0; i < qty; i++) {
        itemsToPreview.push(item);
      }
    } else {
      itemsToPreview = master.slice(0, 6);
    }
  }
  
  itemsToPreview.forEach(m => {
    if (!m) return;
    const wrap = document.createElement('div');
    wrap.style.cssText = 'background:#fff;border-radius:12px;padding:12px;display:flex;align-items:center;gap:12px;min-width:210px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border:1px solid #e2e8f0';
    const qrDiv = document.createElement('div');
    qrDiv.style.cssText = 'flex-shrink:0';
    new QRCode(qrDiv, { text: m.kode + '|' + m.nama, width: 64, height: 64, correctLevel: QRCode.CorrectLevel.H });
    const info = document.createElement('div');
    info.innerHTML = `<div style="font-size:11px;font-weight:800;color:#0f172a">${m.nama}</div><div style="font-size:10px;font-weight:700;color:#2563eb;font-family:monospace;margin-top:2px">${m.kode}</div><div style="font-size:8px;color:#94a3b8;margin-top:2px">Erlass Inventory</div>`;
    wrap.appendChild(qrDiv); wrap.appendChild(info); preview.appendChild(wrap);
  });
}

function doPrint() {
  const area = document.getElementById('print-area');
  area.innerHTML = ''; 
  area.style.cssText = 'display:flex !important;flex-wrap:wrap;gap:8px;padding:0;margin:0;background:#fff;justify-content:flex-start;align-items:flex-start;';
  
  let itemsToPrint = [];
  if (printQueue.length > 0) {
    printQueue.forEach(q => {
      for (let i = 0; i < q.qty; i++) {
        itemsToPrint.push(q.item);
      }
    });
  } else {
    const selIdx = document.getElementById('pr-item').value;
    if (selIdx === '') { showToast('Pilih barang atau tambah ke antrean', true); return; }
    const item = master[parseInt(selIdx)];
    const qty = parseInt(document.getElementById('pr-qty').value) || 1;
    for (let i = 0; i < qty; i++) {
      itemsToPrint.push(item);
    }
  }
  
  if (itemsToPrint.length === 0) {
    showToast('Tidak ada barang untuk dicetak', true);
    return;
  }
  
  itemsToPrint.forEach(item => {
    const wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;align-items:center;gap:8px;padding:6px;border:1px solid #ddd;border-radius:4px;background:#fff;break-inside:avoid;box-sizing:border-box;margin:2px;';
    const qrDiv = document.createElement('div');
    new QRCode(qrDiv, { text: item.kode + '|' + item.nama, width: 80, height: 80, correctLevel: QRCode.CorrectLevel.H });
    const info = document.createElement('div');
    info.innerHTML = `<div style="font-size:10px;font-weight:700">${item.nama}</div><div style="font-size:9px;font-weight:700;color:#3b82f6">${item.kode}</div><div style="font-size:7px;color:#999">Erlass Inventory</div>`;
    wrap.appendChild(qrDiv); wrap.appendChild(info); area.appendChild(wrap);
  });
  
  window.print();
}

function onPrItemSearchInput() {
  const searchVal = document.getElementById('pr-item-search').value.trim();
  const hiddenInput = document.getElementById('pr-item');
  if (searchVal === '') {
    hiddenInput.value = '';
    updatePrintPreview();
    return;
  }
  
  const matchIndex = master.findIndex(m => 
    `${m.kode} — ${m.nama}`.toLowerCase() === searchVal.toLowerCase() ||
    m.kode.toLowerCase() === searchVal.toLowerCase() ||
    m.nama.toLowerCase() === searchVal.toLowerCase()
  );
  
  if (matchIndex !== -1) {
    hiddenInput.value = matchIndex;
  } else {
    hiddenInput.value = '';
  }
  updatePrintPreview();
}

function clearPrSearch() {
  document.getElementById('pr-item-search').value = '';
  document.getElementById('pr-item').value = '';
  updatePrintPreview();
}

/* ═════════════════════════════════════════════
   UTILS
   ═════════════════════════════════════════════ */
function fmtDate(s) { if (!s) return '—'; try { const d = new Date(s); return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return s; } }
let toastTimer;
function showToast(msg, err) {
  const t = document.getElementById('toast');
  const msgEl = document.getElementById('toast-msg');
  const alertEl = document.getElementById('toast-alert');
  
  msgEl.textContent = msg;
  alertEl.className = 'alert shadow-lg border flex text-xs font-bold text-white ' + (err ? 'alert-error' : 'alert-neutral bg-neutral');
  
  t.classList.remove('opacity-0', 'pointer-events-none');
  t.classList.add('opacity-100');
  
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    t.classList.add('opacity-0', 'pointer-events-none');
    t.classList.remove('opacity-100');
  }, 3000);
}
window.addEventListener('online', () => { setSyncDot('on'); syncPending(); });
window.addEventListener('offline', () => setSyncDot('off'));

/* ═════════════════════════════════════════════
   SEWA & BEKAS - STATE & CORE LOGIC
   ═════════════════════════════════════════════ */
let sewaAssets = [];
let locations = [];
let vendors = [];
let customers = [];
let sewaOutList = [];
let sewaReturnList = [];

async function loadSewaAssets() {
  try {
    const r = await api('/api/assets');
    const d = await r.json();
    if (d.success) sewaAssets = d.data;
  } catch (e) {
    console.error("Gagal memuat assets", e);
  }
}

/* ── TAB SWITCHER ── */
function switchSewaTab(tab) {
  document.querySelectorAll('.sewa-tab-content').forEach(c => c.classList.add('hidden'));
  document.getElementById('sewa-tab-' + tab).classList.remove('hidden');
  
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-active'));
  document.getElementById('tab-btn-' + tab).classList.add('tab-active');
}

/* ── DASHBOARD SEWA ── */
async function loadSewaDashboard() {
  await loadSewaAssets();
  
  const total = sewaAssets.length;
  const ready = sewaAssets.filter(a => !a.is_rented && (a.status === 'lengkap' || a.status === 'good')).length;
  const rented = sewaAssets.filter(a => a.is_rented).length;
  const issue = sewaAssets.filter(a => a.status === 'not_good' || a.status === 'tidak_lengkap').length;
  
  document.getElementById('sewa-stats').innerHTML = [
    { icon: '📦', val: total, lbl: 'Total Unit Asset', cls: 'text-primary' },
    { icon: '✅', val: ready, lbl: 'Unit Ready / Tersedia', cls: 'text-success' },
    { icon: '🔄', val: rented, lbl: 'Sedang Disewa', cls: 'text-warning' },
    { icon: '🛠️', val: issue, lbl: 'Bermasalah / Servis', cls: 'text-error' }
  ].map(s => `
    <div class="card bg-base-100 border border-base-300 shadow-sm p-5 flex flex-row items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-base-200 flex items-center justify-center text-2xl shadow-inner">${s.icon}</div>
      <div>
        <div class="text-xl sm:text-2xl font-black ${s.cls}">${s.val}</div>
        <div class="text-[10px] font-bold text-base-content/50 uppercase tracking-wide">${s.lbl}</div>
      </div>
    </div>
  `).join('');

  const tbody = document.getElementById('sewa-rented-tbody');
  const rentedAssets = sewaAssets.filter(a => a.is_rented);
  
  if (rentedAssets.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-base-content/40">Tidak ada unit yang sedang disewa.</td></tr>';
  } else {
    try {
      const r = await api('/api/transactions?limit=200');
      const d = await r.json();
      if (d.success) {
        const txs = d.data;
        tbody.innerHTML = rentedAssets.map(asset => {
          const latestRentTx = txs.find(t => t.asset_id === asset.id && t.tipe_detail === 'sewa_keluar');
          const customerName = latestRentTx ? (latestRentTx.penerima || 'Customer') : 'Sekolah / Customer';
          const rentDate = latestRentTx ? fmtDate(latestRentTx.transaction_date) : fmtDate(new Date());
          const keperluan = latestRentTx ? (latestRentTx.keperluan || 'Kegiatan Sewa') : 'Workshop';
          
          return `
            <tr>
              <td class="font-mono font-bold text-primary">${asset.serial_number}</td>
              <td class="font-semibold">${asset.item?.nama || 'Item'}</td>
              <td>${customerName}</td>
              <td>${rentDate}</td>
              <td>${keperluan}</td>
            </tr>
          `;
        }).join('');
      }
    } catch {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-error">Gagal memuat detail penyewaan.</td></tr>';
    }
  }
}

/* ── TRANSAKSI SEWA PAGE ── */
async function loadSewaMutasiPage() {
  await Promise.all([loadStock(), loadMasterDataDependents()]);
  
  // Populate Master Items select
  const itemSelect = document.getElementById('sewain-item-id');
  itemSelect.innerHTML = master.map(m => `
    <option value="${m.id}">${m.kode} — ${m.nama}</option>
  `).join('');
  
  // Populate Vendors select
  const vendorSelect = document.getElementById('sewain-vendor-id');
  vendorSelect.innerHTML = '<option value="">Pilih Vendor...</option>' + vendors.map(v => `
    <option value="${v.id}">${v.nama}</option>
  `).join('');
  
  // Populate Locations select (only type=rak)
  const locSelect = document.getElementById('sewain-location-id');
  const rakLocations = locations.filter(l => l.tipe === 'rak');
  locSelect.innerHTML = rakLocations.map(l => `
    <option value="${l.id}">${l.nama} (${l.kode})</option>
  `).join('');
  
  // Populate Customers select
  const custSelect = document.getElementById('sewaout-customer-id');
  custSelect.innerHTML = customers.map(c => `
    <option value="${c.id}">${c.nama}</option>
  `).join('');

  // Clear lists
  sewaOutList = [];
  sewaReturnList = [];
  renderSewaOutListTable();
  renderSewaReturnListTable();
}

/* Penerimaan Asset Baru Submit */
async function handleNewAssetSubmit(e) {
  e.preventDefault();
  const body = {
    item_id: document.getElementById('sewain-item-id').value,
    tipe_kepemilikan: document.getElementById('sewain-tipe').value,
    serial_number: document.getElementById('sewain-serial').value.trim(),
    status: document.getElementById('sewain-status').value,
    location_id: document.getElementById('sewain-location-id').value,
    vendor_id: document.getElementById('sewain-vendor-id').value || null,
    no_po: document.getElementById('sewain-po').value.trim() || null,
    no_dokumen: document.getElementById('sewain-dokumen').value.trim() || null,
    catatan: document.getElementById('sewain-catatan').value.trim() || null,
  };

  try {
    const r = await api('/api/assets', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      document.getElementById('sewain-serial').value = '';
      document.getElementById('sewain-po').value = '';
      document.getElementById('sewain-dokumen').value = '';
      document.getElementById('sewain-catatan').value = '';
      loadSewaMutasiPage();
    } else {
      showToast(d.message || 'Gagal menyimpan asset', true);
    }
  } catch (err) {
    showToast('Koneksi bermasalah atau serial number duplikat', true);
  }
}

/* Sewa Keluar List Management */
function addAssetToSewaOutList() {
  const serial = document.getElementById('sewaout-scan-input').value.trim();
  if (!serial) return;
  
  const asset = sewaAssets.find(a => a.serial_number.toLowerCase() === serial.toLowerCase());
  if (!asset) {
    showToast('Asset dengan serial number tersebut tidak terdaftar!', true);
    return;
  }
  if (asset.is_rented) {
    showToast('Asset ini sedang berstatus disewa!', true);
    return;
  }
  if (sewaOutList.some(a => a.id === asset.id)) {
    showToast('Asset sudah masuk dalam daftar', true);
    return;
  }

  sewaOutList.push(asset);
  document.getElementById('sewaout-scan-input').value = '';
  renderSewaOutListTable();
}

function removeAssetFromSewaOutList(id) {
  sewaOutList = sewaOutList.filter(a => a.id !== id);
  renderSewaOutListTable();
}

function renderSewaOutListTable() {
  const tbody = document.getElementById('sewaout-list-tbody');
  if (sewaOutList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>';
    return;
  }
  tbody.innerHTML = sewaOutList.map(a => `
    <tr>
      <td class="font-mono font-bold">${a.serial_number}</td>
      <td>${a.item?.nama || 'Asset'}</td>
      <td><span class="badge badge-sm badge-neutral font-bold">${a.status}</span></td>
      <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-error font-bold" onclick="removeAssetFromSewaOutList(${a.id})">Batal</button></td>
    </tr>
  `).join('');
}

async function handleSewaOutSubmit(e) {
  e.preventDefault();
  if (sewaOutList.length === 0) {
    showToast('Tambahkan minimal 1 unit asset untuk disewa!', true);
    return;
  }

  const body = {
    customer_id: document.getElementById('sewaout-customer-id').value,
    no_dokumen: document.getElementById('sewaout-dokumen').value.trim() || null,
    keperluan: document.getElementById('sewaout-keperluan').value.trim() || null,
    catatan: document.getElementById('sewaout-catatan').value.trim() || null,
    asset_ids: sewaOutList.map(a => a.id)
  };

  try {
    const r = await api('/api/assets/rent-out', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      document.getElementById('sewaout-dokumen').value = '';
      document.getElementById('sewaout-keperluan').value = '';
      document.getElementById('sewaout-catatan').value = '';
      sewaOutList = [];
      await loadSewaAssets();
      renderSewaOutListTable();
    } else {
      showToast(d.message || 'Transaksi gagal diproses', true);
    }
  } catch (err) {
    showToast('Terjadi kesalahan pengiriman transaksi', true);
  }
}

/* Sewa Kembali List Management */
function addAssetToSewaReturnList() {
  const serial = document.getElementById('sewaret-scan-input').value.trim();
  if (!serial) return;
  
  const asset = sewaAssets.find(a => a.serial_number.toLowerCase() === serial.toLowerCase());
  if (!asset) {
    showToast('Asset tidak terdaftar!', true);
    return;
  }
  if (!asset.is_rented) {
    showToast('Asset ini tidak sedang dalam status disewa!', true);
    return;
  }
  if (sewaReturnList.some(a => a.id === asset.id)) {
    showToast('Asset sudah ada di daftar pengembalian', true);
    return;
  }

  sewaReturnList.push(asset);
  document.getElementById('sewaret-scan-input').value = '';
  renderSewaReturnListTable();
}

function removeAssetFromSewaReturnList(id) {
  sewaReturnList = sewaReturnList.filter(a => a.id !== id);
  renderSewaReturnListTable();
}

function renderSewaReturnListTable() {
  const tbody = document.getElementById('sewaret-list-tbody');
  if (sewaReturnList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>';
    return;
  }
  
  const rakLocations = locations.filter(l => l.tipe === 'rak');
  const locOptions = rakLocations.map(l => `<option value="${l.id}">${l.nama}</option>`).join('');

  tbody.innerHTML = sewaReturnList.map((a, idx) => `
    <tr>
      <td class="font-mono font-bold">${a.serial_number}</td>
      <td>${a.item?.nama || 'Asset'}</td>
      <td>
        <select id="ret-status-${idx}" class="select select-bordered select-xs w-28 font-semibold">
          <option value="lengkap">Lengkap</option>
          <option value="good">Good</option>
          <option value="not_good">Not Good</option>
          <option value="tidak_lengkap">Tidak Lengkap</option>
        </select>
      </td>
      <td>
        <select id="ret-loc-${idx}" class="select select-bordered select-xs w-44 font-semibold">
          ${locOptions}
        </select>
      </td>
      <td class="text-center"><button type="button" class="btn btn-ghost btn-xs text-error font-bold" onclick="removeAssetFromSewaReturnList(${a.id})">Batal</button></td>
    </tr>
  `).join('');
}

async function handleSewaReturnSubmit(e) {
  e.preventDefault();
  if (sewaReturnList.length === 0) {
    showToast('Tambahkan minimal 1 unit asset yang kembali!', true);
    return;
  }

  const returns = sewaReturnList.map((a, idx) => ({
    asset_id: a.id,
    status: document.getElementById(`ret-status-${idx}`).value,
    location_id: document.getElementById(`ret-loc-${idx}`).value,
  }));

  const body = {
    returns: returns,
    no_dokumen: document.getElementById('sewaret-dokumen').value.trim() || null,
    catatan: document.getElementById('sewaret-catatan').value.trim() || null,
  };

  try {
    const r = await api('/api/assets/rent-return', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      document.getElementById('sewaret-dokumen').value = '';
      document.getElementById('sewaret-catatan').value = '';
      sewaReturnList = [];
      await loadSewaAssets();
      renderSewaReturnListTable();
    } else {
      showToast(d.message || 'Transaksi pengembalian gagal', true);
    }
  } catch (err) {
    showToast('Terjadi kesalahan pengiriman data', true);
  }
}

/* ── DAFTAR UNIT ASSET PAGE ── */
async function loadSewaUnitsPage() {
  await Promise.all([loadSewaAssets(), loadMasterDataDependents()]);
  
  const tipeFilter = document.getElementById('sf-tipe').value;
  const statusFilter = document.getElementById('sf-status').value;
  
  let filtered = sewaAssets;
  if (tipeFilter) filtered = filtered.filter(a => a.tipe_kepemilikan === tipeFilter);
  if (statusFilter) filtered = filtered.filter(a => a.status === statusFilter);

  const tbody = document.getElementById('sewaunits-tbody');
  
  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-base-content/40">Tidak ada unit asset yang cocok dengan kriteria filter.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(a => {
    const isRentedLabel = a.is_rented 
      ? '<span class="badge badge-warning font-bold text-[10px] text-warning-content">Disewakan</span>' 
      : '<span class="badge badge-success font-bold text-[10px] text-success-content">Tersedia</span>';
    
    // Build location path
    let locPath = '—';
    if (a.location) {
      locPath = a.location.nama;
      let parent = locations.find(l => l.id === a.location.parent_id);
      while(parent) {
        locPath = parent.nama + ' ➔ ' + locPath;
        parent = locations.find(l => l.id === parent.parent_id);
      }
    }
    
    const conditionBadge = {
      lengkap: 'badge-success',
      good: 'badge-primary',
      not_good: 'badge-error text-white',
      tidak_lengkap: 'badge-warning'
    }[a.status] || 'badge-neutral';

    return `
      <tr>
        <td class="font-mono font-bold text-primary">${a.serial_number}</td>
        <td class="font-semibold">${a.item?.nama || '—'}</td>
        <td class="text-xs">${a.item?.produk || '—'}</td>
        <td class="font-bold text-xs uppercase">${a.tipe_kepemilikan}</td>
        <td><span class="badge badge-sm ${conditionBadge} font-extrabold text-[10px]">${a.status}</span></td>
        <td class="text-xs max-w-xs truncate" title="${locPath}">${locPath}</td>
        <td>${isRentedLabel}</td>
        <td class="text-center">
          <div class="flex gap-1.5 justify-center">
            <button class="btn btn-neutral btn-xs font-bold" onclick="openAssetMutateModal(${a.id}, '${a.serial_number}')" ${a.is_rented ? 'disabled' : ''}>Mutasi</button>
            <button class="btn btn-neutral btn-xs font-bold" onclick="openAssetStatusModal(${a.id}, '${a.serial_number}', '${a.status}')">Kondisi</button>
            <button class="btn btn-primary btn-xs font-bold text-white shadow-sm" onclick="showAssetHistoryCard(${a.id})">Riwayat</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function showAssetHistoryCard(id) {
  goPage('sewacard');
  document.getElementById('sewacard-asset-select').value = id;
  renderSewaCard();
}

/* Asset Mutate Modal */
function openAssetMutateModal(id, serial) {
  document.getElementById('am-id').value = id;
  document.getElementById('am-serial').textContent = serial;
  document.getElementById('am-catatan').value = '';
  
  // Populate select
  const rakLocations = locations.filter(l => l.tipe === 'rak');
  document.getElementById('am-location-id').innerHTML = rakLocations.map(l => `
    <option value="${l.id}">${l.nama} (${l.kode})</option>
  `).join('');

  document.getElementById('asset-mutate-modal').classList.add('modal-open');
}

function closeAssetMutateModal() {
  document.getElementById('asset-mutate-modal').classList.remove('modal-open');
}

async function submitAssetMutation() {
  const body = {
    asset_id: document.getElementById('am-id').value,
    location_id: document.getElementById('am-location-id').value,
    catatan: document.getElementById('am-catatan').value.trim() || null,
  };

  try {
    const r = await api('/api/assets/mutate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      closeAssetMutateModal();
      loadSewaUnitsPage();
    } else {
      showToast(d.message || 'Gagal memindahkan asset', true);
    }
  } catch (err) {
    showToast('Terjadi kesalahan koneksi', true);
  }
}

/* Asset Status Modal */
function openAssetStatusModal(id, serial, currentStatus) {
  document.getElementById('as-id').value = id;
  document.getElementById('as-serial').textContent = serial;
  document.getElementById('as-status').value = currentStatus;
  document.getElementById('asset-status-modal').classList.add('modal-open');
}

function closeAssetStatusModal() {
  document.getElementById('asset-status-modal').classList.remove('modal-open');
}

async function submitAssetStatusUpdate() {
  const id = document.getElementById('as-id').value;
  const body = {
    status: document.getElementById('as-status').value
  };

  try {
    const r = await api(`/api/assets/${id}/status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      closeAssetStatusModal();
      loadSewaUnitsPage();
    } else {
      showToast(d.message || 'Gagal mengupdate kondisi', true);
    }
  } catch (err) {
    showToast('Terjadi kesalahan koneksi', true);
  }
}

/* ── RIWAYAT & KARTU ASSET PAGE ── */
async function loadSewaCardPage() {
  await loadSewaAssets();
  const select = document.getElementById('sewacard-asset-select');
  select.innerHTML = '<option value="">Pilih Serial Number...</option>' + sewaAssets.map(a => `
    <option value="${a.id}">${a.serial_number} [${a.item?.nama || ''}]</option>
  `).join('');
  document.getElementById('sewacard-details').style.display = 'none';
}

async function renderSewaCard() {
  const id = document.getElementById('sewacard-asset-select').value;
  if (!id) {
    document.getElementById('sewacard-details').style.display = 'none';
    return;
  }

  try {
    const r = await api(`/api/assets/${id}/card`);
    const d = await r.json();
    if (d.success) {
      const asset = d.data.asset;
      const txs = d.data.transactions;

      document.getElementById('sc-detail-nama').textContent = asset.item?.nama || '—';
      document.getElementById('sc-detail-tipe').textContent = asset.tipe_kepemilikan || '—';
      document.getElementById('sc-detail-kondisi').textContent = asset.status.toUpperCase();
      document.getElementById('sc-detail-sewa').textContent = asset.is_rented ? 'SEWA OUT' : 'READY DI RAK';

      const tbody = document.getElementById('sewacard-tbody');
      if (txs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-base-content/40">Tidak ada riwayat mutasi untuk asset ini.</td></tr>';
      } else {
        tbody.innerHTML = txs.map(t => {
          let activity = 'Mutasi Reguler';
          let partner = '—';
          
          if (t.tipe_detail === 'pembelian') {
            activity = '📥 Penerimaan Unit Baru';
            partner = t.vendor?.nama || 'Vendor Pemasok';
          } else if (t.tipe_detail === 'sewa_keluar') {
            activity = '📤 Distribusi Sewa Keluar';
            partner = t.customer?.nama || 'Sekolah / Renter';
          } else if (t.tipe_detail === 'sewa_kembali') {
            activity = '↩️ Pengembalian Sewa';
            partner = t.location?.nama || 'Rak Penyimpanan';
          } else if (t.tipe_detail === 'mutasi_lokasi') {
            activity = '🔄 Pemindahan Internal (Mutasi)';
            partner = t.location?.nama || 'Rak Tujuan';
          }

          return `
            <tr>
              <td>${fmtDate(t.transaction_date)}</td>
              <td class="font-bold text-xs uppercase">${t.tipe}</td>
              <td class="font-semibold text-xs">${activity}</td>
              <td class="font-bold text-xs text-primary">${partner}</td>
              <td class="font-mono text-[10px]">${t.no_dokumen || t.no_po || '—'}</td>
              <td class="text-xs text-base-content/70">${t.catatan || '—'}</td>
              <td class="text-xs font-semibold">${t.user?.name || t.petugas || '—'}</td>
            </tr>
          `;
        }).join('');
      }

      document.getElementById('sewacard-details').style.display = '';
    }
  } catch (err) {
    showToast('Gagal memuat kartu riwayat asset', true);
  }
}

/* ── MASTER LOKASI PAGE (admin only) ── */
async function loadLocationsPage() {
  await loadMasterDataDependents();
  renderLocationsTree();
  adjustLocationParentDropdown();
}

function renderLocationsTree() {
  const container = document.getElementById('locations-tree-container');
  if (locations.length === 0) {
    container.innerHTML = '<div class="text-center text-base-content/40 py-8">Belum ada lokasi terdaftar.</div>';
    return;
  }

  const roots = locations.filter(l => !l.parent_id);
  
  let html = '<div class="space-y-1.5">';
  
  function buildNodeHtml(node, depth = 0) {
    const children = locations.filter(l => l.parent_id === node.id);
    const indent = depth * 20; // Indentation width
    const icons = { gedung: '🏢', lantai: '🪜', ruangan: '🚪', rak: '📥' };
    const icon = icons[node.tipe] || '📍';
    
    let nodeHtml = `
      <div class="flex items-center justify-between p-2 hover:bg-base-200/50 rounded-lg transition" style="margin-left: ${indent}px">
        <div class="flex items-center gap-2">
          <span class="text-sm">${icon}</span>
          <span class="font-bold text-xs font-mono text-primary">${node.kode}</span>
          <span class="text-xs font-semibold">— ${node.nama}</span>
          <span class="badge badge-sm badge-outline text-[9px] uppercase font-bold">${node.tipe}</span>
        </div>
        <button class="btn btn-ghost btn-xs text-error font-bold text-[10px]" onclick="deleteLocation(${node.id})">✕ Hapus</button>
      </div>
    `;
    
    children.forEach(child => {
      nodeHtml += buildNodeHtml(child, depth + 1);
    });
    
    return nodeHtml;
  }
  
  roots.forEach(root => {
    html += buildNodeHtml(root, 0);
  });
  
  html += '</div>';
  container.innerHTML = html;
}

function adjustLocationParentDropdown() {
  const tipe = document.getElementById('loc-tipe').value;
  const parentContainer = document.getElementById('loc-parent-container');
  const parentSelect = document.getElementById('loc-parent-id');
  
  if (tipe === 'gedung') {
    parentContainer.style.display = 'none';
    parentSelect.innerHTML = '';
    parentSelect.required = false;
    return;
  }
  
  parentContainer.style.display = '';
  parentSelect.required = true;
  
  const targetParentTipe = {
    lantai: 'gedung',
    ruangan: 'lantai',
    rak: 'ruangan'
  }[tipe];
  
  const eligibleParents = locations.filter(l => l.tipe === targetParentTipe);
  
  if (eligibleParents.length === 0) {
    parentSelect.innerHTML = `<option value="">Harap buat lokasi tipe ${targetParentTipe} terlebih dahulu</option>`;
  } else {
    parentSelect.innerHTML = eligibleParents.map(p => `
      <option value="${p.id}">${p.nama} (${p.kode})</option>
    `).join('');
  }
}

async function handleLocationSubmit(e) {
  e.preventDefault();
  const body = {
    tipe: document.getElementById('loc-tipe').value,
    nama: document.getElementById('loc-nama').value.trim(),
    kode: document.getElementById('loc-kode').value.trim().toUpperCase(),
    parent_id: document.getElementById('loc-parent-id').value || null
  };

  try {
    const r = await api('/api/locations', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      document.getElementById('loc-nama').value = '';
      document.getElementById('loc-kode').value = '';
      await loadLocationsPage();
    } else {
      showToast(d.message || 'Gagal menyimpan lokasi', true);
    }
  } catch (err) {
    showToast('Koneksi error atau kode lokasi sudah terdaftar', true);
  }
}

async function deleteLocation(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus lokasi ini? Semua sub-lokasi di bawahnya juga akan terpengaruh.')) return;
  try {
    const r = await api(`/api/locations/${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      await loadLocationsPage();
    } else {
      showToast(d.message || 'Gagal menghapus', true);
    }
  } catch {
    showToast('Gagal menghapus lokasi karena terikat data lain', true);
  }
}

/* ── VENDOR & CUSTOMER PAGE (admin only) ── */
async function loadPartnersPage() {
  await loadMasterDataDependents();
  renderPartnersTables();
}

function renderPartnersTables() {
  const vTbody = document.getElementById('vendors-tbody');
  if (vendors.length === 0) {
    vTbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-base-content/40">Belum ada vendor terdaftar.</td></tr>';
  } else {
    vTbody.innerHTML = vendors.map(v => `
      <tr>
        <td class="font-semibold text-xs">${v.nama}</td>
        <td>${v.kontak || '—'}</td>
        <td class="font-mono text-[10px]">${v.telepon || '—'}</td>
        <td class="text-center"><button class="btn btn-ghost btn-xs text-error font-bold text-[10px]" onclick="deleteVendor(${v.id})">Hapus</button></td>
      </tr>
    `).join('');
  }

  const cTbody = document.getElementById('customers-tbody');
  if (customers.length === 0) {
    cTbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-base-content/40">Belum ada customer terdaftar.</td></tr>';
  } else {
    cTbody.innerHTML = customers.map(c => `
      <tr>
        <td class="font-semibold text-xs">${c.nama}</td>
        <td>${c.kontak || '—'}</td>
        <td class="font-mono text-[10px]">${c.telepon || '—'}</td>
        <td class="text-center"><button class="btn btn-ghost btn-xs text-error font-bold text-[10px]" onclick="deleteCustomer(${c.id})">Hapus</button></td>
      </tr>
    `).join('');
  }
}

async function handleVendorSubmit(e) {
  e.preventDefault();
  const body = {
    nama: document.getElementById('vend-nama').value.trim(),
    kontak: document.getElementById('vend-kontak').value.trim() || null,
    telepon: document.getElementById('vend-telepon').value.trim() || null,
    alamat: document.getElementById('vend-alamat').value.trim() || null,
  };

  try {
    const r = await api('/api/vendors', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      document.getElementById('vend-nama').value = '';
      document.getElementById('vend-kontak').value = '';
      document.getElementById('vend-telepon').value = '';
      document.getElementById('vend-alamat').value = '';
      await loadPartnersPage();
    } else {
      showToast(d.message || 'Gagal menyimpan vendor', true);
    }
  } catch {
    showToast('Terjadi kesalahan koneksi', true);
  }
}

async function deleteVendor(id) {
  if (!confirm('Hapus vendor ini?')) return;
  try {
    const r = await api(`/api/vendors/${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      await loadPartnersPage();
    }
  } catch {
    showToast('Gagal menghapus vendor karena terikat data lain', true);
  }
}

async function handleCustomerSubmit(e) {
  e.preventDefault();
  const body = {
    nama: document.getElementById('cust-nama').value.trim(),
    kontak: document.getElementById('cust-kontak').value.trim() || null,
    telepon: document.getElementById('cust-telepon').value.trim() || null,
    alamat: document.getElementById('cust-alamat').value.trim() || null,
  };

  try {
    const r = await api('/api/customers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      document.getElementById('cust-nama').value = '';
      document.getElementById('cust-kontak').value = '';
      document.getElementById('cust-telepon').value = '';
      document.getElementById('cust-alamat').value = '';
      await loadPartnersPage();
    } else {
      showToast(d.message || 'Gagal menyimpan customer', true);
    }
  } catch {
    showToast('Terjadi kesalahan koneksi', true);
  }
}

async function deleteCustomer(id) {
  if (!confirm('Hapus customer ini?')) return;
  try {
    const r = await api(`/api/customers/${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      await loadPartnersPage();
    }
  } catch {
    showToast('Gagal menghapus customer karena terikat data lain', true);
  }
}

/* ═══════════════════════════════════════════
   MASTER KATEGORI
   ═══════════════════════════════════════════ */

function escHtml(str) {
  if (!str) return '';
  return str.toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

let categoriesCache = [];

async function loadCategoriesPage() {
  try {
    const r = await api('/api/categories');
    const d = await r.json();
    if (!d.success) return;
    categoriesCache = d.data;
    renderCategoriesTable(categoriesCache);
    populateCatParentDropdown('cat-parent-id', null);
  } catch {
    showToast('Gagal memuat data kategori', true);
  }
}

function renderCategoriesTable(categories) {
  const tbody = document.getElementById('categories-tbody');
  const counter = document.getElementById('cat-count');
  counter.textContent = categories.length + ' kategori';

  if (!categories.length) {
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-8 text-base-content/40">Belum ada kategori. Tambahkan melalui form di sebelah kiri.</td></tr>';
    return;
  }

  tbody.innerHTML = categories.map(cat => `
    <tr>
      <td class="font-semibold">${escHtml(cat.nama)}</td>
      <td>${cat.parent ? '<span class="badge badge-ghost badge-sm font-semibold">' + escHtml(cat.parent.nama) + '</span>' : '<span class="text-base-content/30 text-[10px]">— Kategori Utama</span>'}</td>
      <td class="text-center">
        <button class="btn btn-xs btn-ghost text-info" onclick="openCatEdit(${cat.id})">✏️ Edit</button>
        <button class="btn btn-xs btn-ghost text-error" onclick="deleteCategory(${cat.id})">🗑️</button>
      </td>
    </tr>
  `).join('');
}

function populateCatParentDropdown(selectId, excludeId) {
  const sel = document.getElementById(selectId);
  const currentVal = sel.value;
  // Hanya tampilkan kategori utama (parent_id = null) sebagai opsi parent
  // untuk menghindari kedalaman hierarki yang terlalu dalam
  const opts = categoriesCache
    .filter(c => c.id !== excludeId)
    .map(c => `<option value="${c.id}" ${currentVal == c.id ? 'selected' : ''}>${c.parent ? '↳ ' : ''}${escHtml(c.nama)}</option>`)
    .join('');
  sel.innerHTML = '<option value="">-- Tidak Ada (Kategori Utama) --</option>' + opts;
}

async function handleCategorySubmit(e) {
  e.preventDefault();
  const nama = document.getElementById('cat-nama').value.trim();
  const parentId = document.getElementById('cat-parent-id').value || null;

  try {
    const r = await api('/api/categories', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nama, parent_id: parentId })
    });
    const d = await r.json();
    if (d.success) {
      showToast('Kategori berhasil ditambahkan');
      document.getElementById('cat-nama').value = '';
      document.getElementById('cat-parent-id').value = '';
      await loadCategoriesPage();
    } else {
      showToast(d.message || 'Gagal menyimpan kategori', true);
    }
  } catch {
    showToast('Terjadi kesalahan koneksi', true);
  }
}

function openCatEdit(id) {
  const cat = categoriesCache.find(c => c.id === id);
  if (!cat) return;
  document.getElementById('cat-edit-id').value = cat.id;
  document.getElementById('cat-edit-nama').value = cat.nama;
  // Populate dropdown tapi exclude dirinya sendiri (tidak boleh jadi parent sendiri)
  populateCatParentDropdown('cat-edit-parent-id', cat.id);
  document.getElementById('cat-edit-parent-id').value = cat.parent_id || '';
  document.getElementById('cat-edit-modal').classList.add('modal-open');
}

function closeCatModal() {
  document.getElementById('cat-edit-modal').classList.remove('modal-open');
}

async function submitCatEdit() {
  const id = document.getElementById('cat-edit-id').value;
  const nama = document.getElementById('cat-edit-nama').value.trim();
  const parentId = document.getElementById('cat-edit-parent-id').value || null;

  if (!nama) { showToast('Nama kategori tidak boleh kosong', true); return; }

  try {
    const r = await api(`/api/categories/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nama, parent_id: parentId })
    });
    const d = await r.json();
    if (d.success) {
      showToast('Kategori berhasil diperbarui');
      closeCatModal();
      await loadCategoriesPage();
    } else {
      showToast(d.message || 'Gagal memperbarui kategori', true);
    }
  } catch {
    showToast('Terjadi kesalahan koneksi', true);
  }
}

async function deleteCategory(id) {
  const cat = categoriesCache.find(c => c.id === id);
  const childCount = categoriesCache.filter(c => c.parent_id === id).length;
  let msg = `Hapus kategori "${cat?.nama}"?`;
  if (childCount > 0) msg += `\n\nPeringatan: ${childCount} sub-kategori yang menunjuk ke kategori ini akan menjadi Kategori Utama.`;
  if (!confirm(msg)) return;

  try {
    const r = await api(`/api/categories/${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      await loadCategoriesPage();
    } else {
      showToast(d.message || 'Gagal menghapus kategori', true);
    }
  } catch {
    showToast('Gagal menghapus kategori', true);
  }
}

/* ═══════════════════════════════════════════
   MASTER USER (Manajemen Pengguna)
   ═══════════════════════════════════════════ */
let usersCache = [];

async function loadUsersPage() {
  try {
    const r = await api('/api/users');
    const d = await r.json();
    if (!d.success) return;
    usersCache = d.data;
    renderUsersTable();
  } catch {
    showToast('Gagal memuat data pengguna', true);
  }
}

function renderUsersTable() {
  const tbody = document.getElementById('us-tbody');
  if (!tbody) return;

  const q = (document.getElementById('us-search')?.value || '').toLowerCase();
  const filtered = usersCache.filter(u => 
    (u.nik && u.nik.toLowerCase().includes(q)) || 
    (u.name && u.name.toLowerCase().includes(q)) || 
    (u.email && u.email.toLowerCase().includes(q))
  );

  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-base-content/40">Tidak ada data pengguna</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(u => {
    const roleBadge = u.role === 'admin' ? 'badge-primary' : 'badge-neutral';
    const createdAt = u.created_at ? new Date(u.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
    
    return `
      <tr>
        <td class="font-bold text-xs font-mono">${escHtml(u.nik || '—')}</td>
        <td class="font-semibold">${escHtml(u.name)}</td>
        <td>${escHtml(u.email || '—')}</td>
        <td><span class="badge ${roleBadge} badge-sm font-bold uppercase text-[9px]">${u.role}</span></td>
        <td class="text-xs text-base-content/50">${createdAt}</td>
        <td class="text-center">
          <button class="btn btn-xs btn-ghost text-info" onclick="openUserModal(${u.id})">✏️ Edit</button>
          <button class="btn btn-xs btn-ghost text-error" onclick="deleteUser(${u.id})">🗑️</button>
        </td>
      </tr>
    `;
  }).join('');
}

function openUserModal(id = null) {
  const u = id ? usersCache.find(x => x.id === id) : null;
  document.getElementById('um-title').textContent = u ? 'Edit Pengguna' : 'Tambah Pengguna';
  document.getElementById('um-id').value = u ? u.id : '';
  document.getElementById('um-nik').value = u ? u.nik : '';
  document.getElementById('um-nik').readOnly = !!u;
  document.getElementById('um-name').value = u ? u.name : '';
  document.getElementById('um-email').value = u ? (u.email || '') : '';
  document.getElementById('um-password').value = '';
  document.getElementById('um-role').value = u ? u.role : 'petugas';

  const tip = document.getElementById('um-pass-tip');
  if (tip) tip.style.display = u ? 'block' : 'none';

  document.getElementById('user-modal').classList.add('modal-open');
}

function closeUserModal() {
  document.getElementById('user-modal').classList.remove('modal-open');
}

async function saveUser() {
  const id = document.getElementById('um-id').value;
  const nik = document.getElementById('um-nik').value.trim();
  const name = document.getElementById('um-name').value.trim();
  const email = document.getElementById('um-email').value.trim() || null;
  const password = document.getElementById('um-password').value;
  const role = document.getElementById('um-role').value;

  if (!nik || !name || (!id && !password)) {
    showToast('Lengkapi semua field wajib (*)', true);
    return;
  }

  const body = { nik, name, email, role };
  if (password) body.password = password;

  try {
    const url = id ? `/api/users/${id}` : '/api/users';
    const method = id ? 'PUT' : 'POST';
    const r = await api(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      closeUserModal();
      await loadUsersPage();
    } else {
      showToast(d.message || 'Gagal menyimpan pengguna', true);
    }
  } catch {
    showToast('Koneksi gagal', true);
  }
}

async function deleteUser(id) {
  if (user && user.id === id) {
    showToast('Anda tidak dapat menghapus diri sendiri', true);
    return;
  }

  if (!confirm('Hapus pengguna ini? Semua hak akses dan data login akan dinonaktifkan.')) return;

  try {
    const r = await api(`/api/users/${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      await loadUsersPage();
    } else {
      showToast(d.message || 'Gagal menghapus pengguna', true);
    }
  } catch {
    showToast('Koneksi gagal', true);
  }
}

// --- IMPORT CSV LOGIC ---
let userImportList = [];

function openUserImportModal() {
  document.getElementById('us-import-csv').value = '';
  document.getElementById('us-import-file').value = '';
  document.getElementById('us-import-preview-container').classList.add('hidden');
  document.getElementById('us-import-err').textContent = '';
  userImportList = [];
  document.getElementById('user-import-modal').classList.add('modal-open');
}

function closeUserImportModal() {
  document.getElementById('user-import-modal').classList.remove('modal-open');
}

function previewUserCSV() {
  const text = document.getElementById('us-import-csv').value;
  parseUserCSVText(text);
}

function handleUserCSVFile(e) {
  const file = e.target.files[0];
  if (!file) return;

  const r = new FileReader();
  r.onload = function(evt) {
    const text = evt.target.result;
    document.getElementById('us-import-csv').value = text;
    parseUserCSVText(text);
  };
  r.readAsText(file);
}

function parseUserCSVText(text) {
  const lines = text.split('\n');
  userImportList = [];
  
  for (let line of lines) {
    line = line.trim();
    if (!line) continue;
    
    // NIK, Nama, Password
    const parts = line.split(',');
    if (parts.length >= 3) {
      userImportList.push({
        nik: parts[0].trim(),
        name: parts[1].trim(),
        password: parts[2].trim(),
        role: 'petugas' // Default role for imports
      });
    }
  }

  const container = document.getElementById('us-import-preview-container');
  const tbody = document.getElementById('us-import-preview-tbody');
  
  if (userImportList.length > 0) {
    tbody.innerHTML = userImportList.map(u => `
      <tr>
        <td class="font-mono font-semibold">${escHtml(u.nik)}</td>
        <td>${escHtml(u.name)}</td>
        <td class="font-mono">${escHtml(u.password)}</td>
      </tr>
    `).join('');
    container.classList.remove('hidden');
  } else {
    container.classList.add('hidden');
  }
}

async function processUserImport() {
  if (!userImportList.length) {
    showToast('Masukkan data CSV terlebih dahulu', true);
    return;
  }

  const errEl = document.getElementById('us-import-err');
  errEl.textContent = 'Memproses impor...';

  try {
    const r = await api('/api/users/import', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ users: userImportList })
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message);
      closeUserImportModal();
      await loadUsersPage();
    } else {
      errEl.innerHTML = (d.message || 'Gagal impor') + '<br>' + (d.errors ? d.errors.join('<br>') : '');
    }
  } catch {
    errEl.textContent = 'Koneksi gagal';
  }
}

/* ═════════════════════════════════════════════
   MIKMS (MICRO:BIT KIT MANAGEMENT) JAVASCRIPT
   ═════════════════════════════════════════════ */
let mikmsModules = [];
let mikmsBoxes = [];
let mikmsLogs = [];
let currentMikmsTab = 'boxes';
let mikmsDashboardData = null;

function switchMikmsTab(tab) {
  currentMikmsTab = tab;
  document.querySelectorAll('.mikms-tab-btn').forEach(btn => {
    if (btn.dataset.tab === tab) {
      btn.classList.add('active', 'bg-primary', 'text-primary-content');
      btn.classList.remove('btn-ghost');
    } else {
      btn.classList.remove('active', 'bg-primary', 'text-primary-content');
      btn.classList.add('btn-ghost');
    }
  });

  document.querySelectorAll('.mikms-tab-content').forEach(p => p.classList.add('hidden'));
  const target = document.getElementById('mk-panel-' + tab);
  if (target) target.classList.remove('hidden');

  if (tab === 'boxes') renderMikmsBoxes();
  if (tab === 'bomlist') renderBomListTable();
  if (tab === 'production') renderMikmsProductionTab();
  if (tab === 'qc') renderMikmsQcTab();
  if (tab === 'shipment') renderMikmsShipmentTab();
  if (tab === 'return') renderMikmsReturnTab();
  if (tab === 'repair') renderMikmsRepairTab();
  if (tab === 'opname') renderMikmsOpnameTable();
  if (tab === 'logs') renderMikmsLogsTable();
}

/* ── STANDAR BOM & LIST KOMPONEN DATA (SHEET 2) ── */
const BOMS_DATA = {
  microbit: [
    { no: 1, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-001', name: 'Micro:bit V2', qty: 1, unit: 'Pcs' },
    { no: 2, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-003', name: 'Kabel Micro USB', qty: 1, unit: 'Pcs' },
    { no: 3, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-002', name: 'Kabel Micro Type-C', qty: 1, unit: 'Pcs' },
    { no: 4, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-001', name: 'LED Merah', qty: 4, unit: 'Pcs' },
    { no: 5, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-003', name: 'LED Hijau', qty: 4, unit: 'Pcs' },
    { no: 6, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-002', name: 'LED Kuning', qty: 4, unit: 'Pcs' },
    { no: 7, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'CN-006', name: 'Resistor', qty: 12, unit: 'Pcs' },
    { no: 8, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-001', name: 'Breadboard Mini (170 TP)', qty: 1, unit: 'Pcs' },
    { no: 9, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-007', name: 'Blok Konektor', qty: 2, unit: 'Pcs' },
    { no: 10, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-005', name: 'Kabel Alligator', qty: 10, unit: 'Pcs' },
    { no: 11, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-002', name: 'Kabel Jumper Male to Male', qty: 20, unit: 'Pcs' },
    { no: 12, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-004', name: 'Kabel Jumper Male to Female', qty: 10, unit: 'Pcs' },
    { no: 13, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-003', name: 'Kabel Jumper Female to Female', qty: 10, unit: 'Pcs' },
    { no: 14, box: 'BOX 2 – Supporting Equipment', mod: 'M03 - Motion Kit', code: 'MT-001', name: 'Servo 180°', qty: 2, unit: 'Pcs' },
    { no: 15, box: 'BOX 2 – Supporting Equipment', mod: 'M03 - Motion Kit', code: 'MT-002', name: 'Servo 360°', qty: 2, unit: 'Pcs' },
    { no: 16, box: 'BOX 2 – Supporting Equipment', mod: 'M03 - Motion Kit', code: 'MT-003', name: 'Servo MG996R', qty: 1, unit: 'Pcs' },
    { no: 17, box: 'BOX 2 – Supporting Equipment', mod: 'M04 - Sensor Kit', code: 'SN-001', name: 'Sensor Ultrasonik', qty: 1, unit: 'Pcs' },
    { no: 18, box: 'BOX 2 – Supporting Equipment', mod: 'M05 - Power Kit', code: 'PW-002', name: 'Battery Holder', qty: 1, unit: 'Pcs' },
    { no: 19, box: 'BOX 2 – Supporting Equipment', mod: 'M05 - Power Kit', code: 'PW-001', name: 'Baterai AAA', qty: 4, unit: 'Pcs' },
    { no: 20, box: 'BOX 3 – Bricks', mod: 'M06 - Mechanical Kit', code: 'MC-004', name: 'Lego (150 gr)', qty: 1, unit: 'Paket' },
    { no: 21, box: 'BOX 3 – Bricks', mod: 'M06 - Mechanical Kit', code: 'MC-003', name: 'Separator', qty: 1, unit: 'Pcs' },
    { no: 22, box: 'BOX 3 – Bricks', mod: 'M06 - Mechanical Kit', code: 'MC-005', name: 'Papan Lego / Base Plate', qty: 2, unit: 'Pcs' },
  ],
  robotic: [
    { no: 1, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-001', name: 'Micro:bit V2', qty: 1, unit: 'Pcs' },
    { no: 2, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-003', name: 'Kabel Micro USB', qty: 1, unit: 'Pcs' },
    { no: 3, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-002', name: 'Kabel Micro Type-C', qty: 1, unit: 'Pcs' },
    { no: 4, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-001', name: 'LED Merah', qty: 4, unit: 'Pcs' },
    { no: 5, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-003', name: 'LED Hijau', qty: 4, unit: 'Pcs' },
    { no: 6, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-002', name: 'LED Kuning', qty: 4, unit: 'Pcs' },
    { no: 7, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'CN-006', name: 'Resistor', qty: 12, unit: 'Pcs' },
    { no: 8, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-001', name: 'Breadboard Mini (170 TP)', qty: 1, unit: 'Pcs' },
    { no: 9, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-007', name: 'Blok Konektor', qty: 2, unit: 'Pcs' },
    { no: 10, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-005', name: 'Kabel Alligator', qty: 10, unit: 'Pcs' },
    { no: 11, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-002', name: 'Kabel Jumper Male to Male', qty: 20, unit: 'Pcs' },
    { no: 12, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-004', name: 'Kabel Jumper Male to Female', qty: 10, unit: 'Pcs' },
    { no: 13, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-003', name: 'Kabel Jumper Female to Female', qty: 10, unit: 'Pcs' },
    { no: 14, box: 'Jimu Trackbot', mod: 'JM01 - Jimu', code: 'RJ01', name: 'Jimu Trackbot', qty: 1, unit: 'Pcs' },
  ],
  finishgood: [
    { no: 1, box: 'Finish Good Kit', mod: 'Finish Good', code: 'RJ01', name: 'Jimu Trackbot', qty: 1, unit: 'Unit' },
    { no: 2, box: 'Finish Good Kit', mod: 'Finish Good', code: 'ERB01', name: 'Erboblox', qty: 1, unit: 'Unit' },
    { no: 3, box: 'Finish Good Kit', mod: 'Finish Good', code: 'ALK01', name: 'Arduino Learning Kit', qty: 1, unit: 'Pcs' },
  ]
};

let currentBomProgram = 'microbit';

function switchBomProgram(prog) {
  currentBomProgram = prog;
  document.querySelectorAll('.mk-prog-btn').forEach(btn => {
    btn.classList.remove('active', 'bg-primary', 'text-primary-content');
    btn.classList.add('btn-ghost');
  });
  const activeBtn = document.getElementById('mk-prog-btn-' + prog);
  if (activeBtn) {
    activeBtn.classList.add('active', 'bg-primary', 'text-primary-content');
    activeBtn.classList.remove('btn-ghost');
  }
  renderBomListTable();
}

function renderBomListTable() {
  const q = (document.getElementById('mk-bom-search')?.value || '').toLowerCase();
  const list = BOMS_DATA[currentBomProgram] || [];
  const multiplier = Math.max(1, parseInt(document.getElementById('mk-sim-package-qty')?.value || '1', 10));

  // Update titles
  const titleEl = document.getElementById('mk-bom-table-title');
  const badgeEl = document.getElementById('mk-bom-table-badge');
  if (currentBomProgram === 'microbit') {
    if (titleEl) titleEl.textContent = 'Daftar Komponen: MICROBIT LEARNING KIT';
    if (badgeEl) badgeEl.textContent = '22 Komponen (95 Pcs/Paket)';
  } else if (currentBomProgram === 'robotic') {
    if (titleEl) titleEl.textContent = 'Daftar Komponen: ROBOTIC EXPLORER';
    if (badgeEl) badgeEl.textContent = '14 Komponen (81 Pcs/Paket)';
  } else {
    if (titleEl) titleEl.textContent = 'Daftar Komponen: FINISH GOOD';
    if (badgeEl) badgeEl.textContent = '3 Produk Siap Pakai';
  }

  // Calculate totals and bottleneck
  const totalPcsPerPkg = list.reduce((sum, item) => sum + item.qty, 0);
  const totalNeededAll = totalPcsPerPkg * multiplier;

  let minPossible = 999999;
  let bottleneckItem = null;

  const itemsWithStock = list.map(row => {
    const stockItem = (typeof master !== 'undefined' ? master : []).find(m => 
      (m.kode && m.kode.toLowerCase() === row.code.toLowerCase()) ||
      (m.nama && m.nama.toLowerCase().includes(row.name.toLowerCase()))
    );
    const currentStock = stockItem ? (stockItem.stok || 0) : 0;
    const possibleForThis = Math.floor(currentStock / row.qty);
    if (possibleForThis < minPossible) {
      minPossible = possibleForThis;
      bottleneckItem = { name: row.name, stock: currentStock, neededPerPkg: row.qty };
    }
    return { ...row, currentStock };
  });

  if (minPossible === 999999) minPossible = 0;

  // Update simulator badges
  const itemsPerPkgEl = document.getElementById('mk-sim-items-per-pkg');
  const totalNeededEl = document.getElementById('mk-sim-total-needed');
  const maxPossibleEl = document.getElementById('mk-sim-max-possible');
  const bottleneckEl = document.getElementById('mk-sim-bottleneck');

  if (itemsPerPkgEl) itemsPerPkgEl.textContent = `${totalPcsPerPkg} Pcs`;
  if (totalNeededEl) totalNeededEl.textContent = `${totalNeededAll} Pcs (${multiplier} Paket)`;
  if (maxPossibleEl) maxPossibleEl.textContent = `${minPossible} Paket`;
  if (bottleneckEl) {
    bottleneckEl.textContent = bottleneckItem ? `${bottleneckItem.name} (Stok: ${bottleneckItem.stock})` : 'Semua Cukup';
  }

  // Filter
  const filtered = itemsWithStock.filter(row => {
    return !q || row.name.toLowerCase().includes(q) || 
      row.mod.toLowerCase().includes(q) || 
      row.box.toLowerCase().includes(q) || 
      row.code.toLowerCase().includes(q);
  });

  const tbody = document.getElementById('mk-bomlist-tbody');
  if (!tbody) return;

  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-6 text-base-content/40">Tidak ada komponen yang cocok dengan pencarian</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(r => {
    const needed = r.qty * multiplier;
    const isSufficient = r.currentStock >= needed;
    const diff = r.currentStock - needed;

    let boxBadgeClass = 'badge-primary text-primary-content';
    if (r.box.includes('BOX 2')) boxBadgeClass = 'badge-accent text-accent-content';
    if (r.box.includes('BOX 3')) boxBadgeClass = 'badge-warning text-warning-content font-bold';

    return `
      <tr>
        <td class="text-center font-mono font-bold text-base-content/50">${r.no}</td>
        <td><span class="badge badge-xs ${boxBadgeClass}">${escHtml(r.box)}</span></td>
        <td class="font-mono text-[11px] font-bold text-base-content/80">${escHtml(r.mod)}</td>
        <td>
          <div class="font-bold text-base-content">${escHtml(r.name)}</div>
          <div class="font-mono text-[10px] text-base-content/40">${escHtml(r.code)}</div>
        </td>
        <td class="text-center font-mono font-bold">${r.qty}</td>
        <td class="text-center text-xs font-semibold text-base-content/60">${escHtml(r.unit)}</td>
        <td class="text-center font-mono font-extrabold text-primary">${needed}</td>
        <td class="text-center font-mono font-bold ${r.currentStock <= 0 ? 'text-error' : 'text-base-content'}">${r.currentStock}</td>
        <td class="text-center">
          ${isSufficient ? `
            <span class="badge badge-xs badge-success font-bold text-white">✅ CUKUP (+${diff})</span>
          ` : `
            <span class="badge badge-xs badge-error font-bold text-white">⚠️ KURANG (${Math.abs(diff)})</span>
          `}
        </td>
      </tr>
    `;
  }).join('');
}


async function loadMikmsPage() {
  try {
    const [dashRes, modRes, boxRes, logRes] = await Promise.all([
      api('/api/mikms/dashboard'),
      api('/api/mikms/modules'),
      api('/api/mikms/boxes'),
      api('/api/mikms/logs')
    ]);

    if (dashRes.ok) {
      const d = await dashRes.json();
      if (d.success) {
        mikmsDashboardData = d.data;
        document.getElementById('mk-stat-total-boxes').textContent = d.data.total_boxes || 0;
        document.getElementById('mk-stat-ready-boxes').textContent = d.data.ready_boxes || 0;
        document.getElementById('mk-stat-loan-boxes').textContent = d.data.loan_boxes || 0;
        document.getElementById('mk-stat-repair-boxes').textContent = d.data.repair_boxes || 0;
      }
    }

    if (modRes.ok) {
      const d = await modRes.json();
      if (d.success) mikmsModules = d.data || [];
    }

    if (boxRes.ok) {
      const d = await boxRes.json();
      if (d.success) mikmsBoxes = d.data || [];
    }

    if (logRes.ok) {
      const d = await logRes.json();
      if (d.success) mikmsLogs = d.data || [];
    }

    // Populate module dropdowns
    const prodModSelect = document.getElementById('mk-prod-module-id');
    const qcModSelect = document.getElementById('mk-qc-module-id');
    if (prodModSelect) {
      prodModSelect.innerHTML = '<option value="">Pilih Modul MIKMS...</option>' + mikmsModules.map(m => `
        <option value="${m.id}">${m.code} — ${escHtml(m.name)}</option>
      `).join('');
    }
    if (qcModSelect) {
      qcModSelect.innerHTML = '<option value="">Pilih Modul (Opsional)...</option>' + mikmsModules.map(m => `
        <option value="${m.id}">${m.code} — ${escHtml(m.name)}</option>
      `).join('');
    }

    // Populate box dropdowns
    populateMikmsBoxDropdowns();

    // Populate customer dropdown for shipment
    const shipCustSelect = document.getElementById('mk-ship-customer-id');
    if (shipCustSelect && typeof customers !== 'undefined') {
      shipCustSelect.innerHTML = '<option value="">Pilih Customer / Sekolah...</option>' + customers.map(c => `
        <option value="${c.id}">${escHtml(c.nama)}</option>
      `).join('');
    }

    // Render current active tab
    switchMikmsTab(currentMikmsTab);
  } catch (err) {
    console.error('Error loading MIKMS page:', err);
    showToast('Gagal memuat data MIKMS', true);
  }
}

function populateMikmsBoxDropdowns() {
  const prodBox = document.getElementById('mk-prod-box-id');
  const qcBox = document.getElementById('mk-qc-box-id');
  const retBox = document.getElementById('mk-ret-box-id');
  const repBox = document.getElementById('mk-rep-box-id');

  const readyBoxes = mikmsBoxes.filter(b => b.status === 'READY');
  const loanBoxes = mikmsBoxes.filter(b => b.status === 'ON_LOAN');

  if (prodBox) {
    prodBox.innerHTML = '<option value="">(Simpan di Gudang / Tanpa Boks)</option>' + mikmsBoxes.map(b => `
      <option value="${b.id}">${b.box_code} (${escHtml(b.category)})</option>
    `).join('');
  }

  if (qcBox) {
    qcBox.innerHTML = '<option value="">Pilih Boks Kit...</option>' + mikmsBoxes.map(b => `
      <option value="${b.id}">${b.box_code} — ${escHtml(b.category)} [${b.status}]</option>
    `).join('');
  }

  if (retBox) {
    retBox.innerHTML = '<option value="">Pilih Boks yang Sedang Dipinjam...</option>' + loanBoxes.map(b => `
      <option value="${b.id}">${b.box_code} — Dipinjam: ${escHtml(b.current_school || 'Sekolah')} (${escHtml(b.category)})</option>
    `).join('');
  }

  if (repBox) {
    repBox.innerHTML = '<option value="">Pilih Boks...</option>' + mikmsBoxes.map(b => `
      <option value="${b.id}">${b.box_code} [${b.status}] — ${escHtml(b.category)}</option>
    `).join('');
  }
}

/* ── TAB 1: BOXES ── */
function renderMikmsBoxes() {
  const q = (document.getElementById('mk-box-search')?.value || '').toLowerCase();
  const cat = document.getElementById('mk-box-filter-cat')?.value || '';
  const st = document.getElementById('mk-box-filter-status')?.value || '';

  const filtered = mikmsBoxes.filter(b => {
    const matchQ = !q || b.box_code.toLowerCase().includes(q) || 
      (b.current_school && b.current_school.toLowerCase().includes(q)) ||
      (b.category && b.category.toLowerCase().includes(q));
    const matchCat = !cat || b.category === cat;
    const matchSt = !st || b.status === st;
    return matchQ && matchCat && matchSt;
  });

  const countLbl = document.getElementById('mk-box-count-lbl');
  if (countLbl) countLbl.textContent = `Menampilkan ${filtered.length} dari ${mikmsBoxes.length} boks`;

  const container = document.getElementById('mk-boxes-grid');
  if (!container) return;

  if (!filtered.length) {
    container.innerHTML = `
      <div class="card bg-base-100 border border-base-200 p-8 text-center text-base-content/50 col-span-full">
        Tidak ditemukan boks yang sesuai kriteria pencarian.
      </div>`;
    return;
  }

  container.innerHTML = filtered.map(b => {
    let badgeClass = 'badge-success text-white';
    let statusIcon = '✅';
    if (b.status === 'ON_LOAN') { badgeClass = 'badge-warning text-black font-extrabold'; statusIcon = '🚚'; }
    if (b.status === 'REPAIR') { badgeClass = 'badge-error text-white font-extrabold'; statusIcon = '🔧'; }

    return `
      <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition p-5 flex flex-col justify-between">
        <div>
          <div class="flex items-start justify-between gap-2 mb-2">
            <div>
              <span class="font-mono font-extrabold text-base text-primary">${escHtml(b.box_code)}</span>
              <span class="badge badge-xs badge-neutral font-mono ml-2">${escHtml(b.program_code || 'MB')}</span>
            </div>
            <span class="badge badge-sm ${badgeClass}">${statusIcon} ${b.status}</span>
          </div>
          <h4 class="font-bold text-sm text-base-content">${escHtml(b.category)}</h4>
          
          ${b.status === 'ON_LOAN' ? `
            <div class="mt-3 p-2.5 rounded-xl bg-warning/10 border border-warning/20 text-xs">
              <div class="text-[10px] uppercase font-bold text-warning-content/70">Peminjam Saat Ini:</div>
              <div class="font-extrabold text-base-content mt-0.5">🏫 ${escHtml(b.current_school || 'Sekolah')}</div>
              <div class="text-[11px] text-base-content/60">PIC: ${escHtml(b.borrower_name || '—')}</div>
            </div>
          ` : ''}

          ${b.status === 'REPAIR' ? `
            <div class="mt-3 p-2.5 rounded-xl bg-error/10 border border-error/20 text-xs">
              <div class="text-[10px] uppercase font-bold text-error-content/70">Kondisi Perbaikan:</div>
              <div class="font-semibold text-error mt-0.5">⚠️ Perlu Rekondisi Part / QC Ulang</div>
            </div>
          ` : ''}

          <div class="mt-3 pt-3 border-t border-base-200 text-xs text-base-content/60 flex justify-between">
            <span>QC Terakhir:</span>
            <span class="font-bold ${b.qc_status === 'PASS' ? 'text-success' : 'text-base-content/70'}">${b.qc_status || 'Belum QC'}</span>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-base-200 flex items-center justify-between gap-2">
          ${b.status === 'READY' ? `
            <button class="btn btn-xs btn-primary font-bold flex-1" onclick="quickShipBox('${b.id}')">🚚 Kirim</button>
            <button class="btn btn-xs btn-outline font-bold" onclick="quickQcBox('${b.id}')">✅ QC</button>
          ` : ''}
          ${b.status === 'ON_LOAN' ? `
            <button class="btn btn-xs btn-warning font-bold flex-1" onclick="quickReturnBox('${b.id}')">↩️ Terima Kembali</button>
          ` : ''}
          ${b.status === 'REPAIR' ? `
            <button class="btn btn-xs btn-error font-bold flex-1 text-white" onclick="quickRepairBox('${b.id}')">🔧 Selesaikan Repair</button>
          ` : ''}
        </div>
      </div>
    `;
  }).join('');
}

/* ── TAB 2: PRODUCTION & BOM ── */
function renderMikmsProductionTab() {
  populateMikmsBoxDropdowns();
  onModuleSelectChanged();
}

function onModuleSelectChanged() {
  const modId = document.getElementById('mk-prod-module-id')?.value;
  const mod = mikmsModules.find(m => String(m.id) === String(modId));
  const badge = document.getElementById('mk-bom-code-badge');
  const desc = document.getElementById('mk-bom-desc');
  const tbody = document.getElementById('mk-bom-tbody');

  if (!mod) {
    if (badge) badge.textContent = 'PILIH MODUL';
    if (desc) desc.textContent = 'Silakan pilih modul di sebelah kiri untuk melihat daftar kebutuhan komponen dan pengecekan ketersediaan stok fisik gudang.';
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-base-content/40">Belum ada modul yang dipilih</td></tr>';
    return;
  }

  if (badge) badge.textContent = mod.code;
  if (desc) desc.textContent = `${escHtml(mod.name)} — ${escHtml(mod.description || 'Tidak ada deskripsi')}`;

  const qty = parseInt(document.getElementById('mk-prod-qty')?.value || '1', 10);
  renderBomTable(mod.boms || [], qty);
}

function onProdQtyChanged() {
  const modId = document.getElementById('mk-prod-module-id')?.value;
  const mod = mikmsModules.find(m => String(m.id) === String(modId));
  if (mod) {
    const qty = parseInt(document.getElementById('mk-prod-qty')?.value || '1', 10);
    renderBomTable(mod.boms || [], qty);
  }
}

function renderBomTable(boms, multiplier) {
  const tbody = document.getElementById('mk-bom-tbody');
  if (!tbody) return;

  if (!boms.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-warning font-semibold">Modul ini belum memiliki konfigurasi BOM komponen.</td></tr>';
    return;
  }

  tbody.innerHTML = boms.map(b => {
    const item = b.item || {};
    const stockItem = (typeof master !== 'undefined' ? master : []).find(m => m.id === b.item_id);
    const currentStock = stockItem ? (stockItem.stok || 0) : 0;
    const totalNeeded = b.quantity * multiplier;
    const isSufficient = currentStock >= totalNeeded;

    return `
      <tr>
        <td class="font-mono font-bold">${escHtml(item.kode || '—')}</td>
        <td>
          <div class="font-bold">${escHtml(item.nama || 'Komponen')}</div>
          <div class="text-[10px] text-base-content/50">${escHtml(b.box_category || 'Box Lapangan')}</div>
        </td>
        <td class="text-center font-mono">${b.quantity} ${escHtml(item.satuan || 'pcs')}</td>
        <td class="text-center font-mono font-extrabold text-primary">${totalNeeded}</td>
        <td class="text-center font-mono font-bold ${currentStock <= 0 ? 'text-error' : 'text-base-content'}">${currentStock}</td>
        <td class="text-center">
          ${isSufficient ? `
            <span class="badge badge-success badge-xs font-bold text-white">CUKUP</span>
          ` : `
            <span class="badge badge-error badge-xs font-bold text-white">KURANG</span>
          `}
        </td>
      </tr>
    `;
  }).join('');
}

async function submitMikmsProduction(e) {
  e.preventDefault();
  const btn = document.getElementById('mk-prod-submit-btn');
  if (btn) btn.disabled = true;

  const payload = {
    module_id: document.getElementById('mk-prod-module-id').value,
    quantity: parseInt(document.getElementById('mk-prod-qty').value, 10),
    box_id: document.getElementById('mk-prod-box-id').value || null,
    technician_name: document.getElementById('mk-prod-pic').value.trim(),
    notes: document.getElementById('mk-prod-notes').value.trim() || null,
  };

  try {
    const r = await api('/api/mikms/productions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message || 'Perakitan modul berhasil dicatat & stok terpotong!');
      document.getElementById('mk-prod-notes').value = '';
      await Promise.all([loadStock(), loadMikmsPage()]);
      switchMikmsTab('production');
    } else {
      showToast(d.message || 'Gagal menyimpan perakitan', true);
    }
  } catch (err) {
    showToast('Terjadi kesalahan koneksi', true);
  } finally {
    if (btn) btn.disabled = false;
  }
}

/* ── TAB 3: QC ── */
function renderMikmsQcTab() {
  populateMikmsBoxDropdowns();
  const tbody = document.getElementById('mk-qc-log-tbody');
  if (!tbody) return;

  const qcLogs = (mikmsDashboardData?.recent_qc || []).concat(
    mikmsLogs.filter(l => l.action_type === 'qc')
  ).slice(0, 15);

  if (!qcLogs.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-base-content/40">Belum ada riwayat QC</td></tr>';
    return;
  }

  tbody.innerHTML = qcLogs.map(q => `
    <tr>
      <td class="font-mono text-[10px]">${formatDateTime(q.created_at || q.checked_at)}</td>
      <td>
        <span class="font-mono font-bold">${escHtml(q.box?.box_code || q.module?.code || 'BOKS')}</span>
        <span class="text-[10px] text-base-content/50 ml-1">(${escHtml(q.box?.category || q.module?.name || '')})</span>
      </td>
      <td>
        <span class="badge badge-xs font-bold ${q.status === 'PASS' ? 'badge-success text-white' : 'badge-error text-white'}">${q.status}</span>
      </td>
      <td class="max-w-[180px] truncate text-[11px]">${escHtml(q.notes || '—')}</td>
      <td class="font-medium text-[11px]">${escHtml(q.technician_name || 'Teknisi')}</td>
    </tr>
  `).join('');
}

async function submitMikmsQc(e) {
  e.preventDefault();
  const payload = {
    box_id: document.getElementById('mk-qc-box-id').value || null,
    module_id: document.getElementById('mk-qc-module-id').value || null,
    status: document.getElementById('mk-qc-status').value,
    technician_name: document.getElementById('mk-qc-pic').value.trim(),
    notes: document.getElementById('mk-qc-notes').value.trim() || null,
  };

  if (!payload.box_id && !payload.module_id) {
    showToast('Pilih Boks atau Modul yang di-QC', true);
    return;
  }

  try {
    const r = await api('/api/mikms/qc-logs', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message || 'Hasil QC berhasil dicatat!');
      document.getElementById('mk-qc-notes').value = '';
      await loadMikmsPage();
      switchMikmsTab('qc');
    } else {
      showToast(d.message || 'Gagal menyimpan hasil QC', true);
    }
  } catch (err) {
    showToast('Koneksi gagal', true);
  }
}

/* ── TAB 4: SHIPMENT ── */
function renderMikmsShipmentTab() {
  const readyBoxes = mikmsBoxes.filter(b => b.status === 'READY');
  const boxContainer = document.getElementById('mk-ship-boxes-container');
  if (boxContainer) {
    if (!readyBoxes.length) {
      boxContainer.innerHTML = '<span class="text-xs text-error font-bold">Tidak ada boks dengan status READY saat ini.</span>';
    } else {
      boxContainer.innerHTML = readyBoxes.map(b => `
        <label class="flex items-center gap-2 p-2 rounded-lg bg-base-100 hover:bg-base-200/60 cursor-pointer border border-base-200">
          <input type="checkbox" name="mk-ship-box-cb" value="${b.id}" class="checkbox checkbox-xs checkbox-primary">
          <span class="font-mono font-bold text-xs">${escHtml(b.box_code)}</span>
          <span class="text-xs text-base-content/70">${escHtml(b.category)}</span>
        </label>
      `).join('');
    }
  }

  // Set default shipment date to today
  const dateInput = document.getElementById('mk-ship-date');
  if (dateInput && !dateInput.value) {
    dateInput.value = new Date().toISOString().split('T')[0];
  }

  // Render active shipments list
  const activeShipments = mikmsBoxes.filter(b => b.status === 'ON_LOAN');
  const activeBadge = document.getElementById('mk-ship-active-badge');
  if (activeBadge) activeBadge.textContent = `${activeShipments.length} Boks`;

  const tbody = document.getElementById('mk-ship-active-tbody');
  if (tbody) {
    if (!activeShipments.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-base-content/40">Tidak ada boks yang sedang dipinjam</td></tr>';
    } else {
      tbody.innerHTML = activeShipments.map(b => `
        <tr>
          <td class="font-mono font-bold text-primary">${escHtml(b.box_code)}</td>
          <td>
            <div class="font-bold">${escHtml(b.current_school || 'Sekolah')}</div>
            <div class="text-[10px] text-base-content/50">${escHtml(b.category)}</div>
          </td>
          <td class="font-mono text-[11px]">${b.updated_at ? b.updated_at.substring(0, 10) : '—'}</td>
          <td class="text-[11px]">${escHtml(b.borrower_name || '—')}</td>
          <td class="text-center">
            <button class="btn btn-xs btn-warning font-bold" onclick="quickReturnBox('${b.id}')">↩️ Retur</button>
          </td>
        </tr>
      `).join('');
    }
  }
}

async function submitMikmsShipment(e) {
  e.preventDefault();
  const checkedBoxes = Array.from(document.querySelectorAll('input[name="mk-ship-box-cb"]:checked')).map(cb => cb.value);
  if (!checkedBoxes.length) {
    showToast('Pilih minimal 1 boks kit yang akan dikirim', true);
    return;
  }

  const payload = {
    shipment_number: document.getElementById('mk-ship-number').value.trim(),
    customer_id: document.getElementById('mk-ship-customer-id').value,
    box_ids: checkedBoxes,
    shipment_date: document.getElementById('mk-ship-date').value,
    expected_return_date: document.getElementById('mk-ship-return-plan').value || null,
    school_pic: document.getElementById('mk-ship-pic-school').value.trim() || null,
    staff_pic: document.getElementById('mk-ship-pic-staff').value.trim(),
    notes: document.getElementById('mk-ship-notes').value.trim() || null,
  };

  try {
    const r = await api('/api/mikms/shipments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message || 'Pengiriman berhasil dicatat!');
      document.getElementById('mk-ship-number').value = '';
      document.getElementById('mk-ship-notes').value = '';
      await loadMikmsPage();
      switchMikmsTab('shipment');
    } else {
      showToast(d.message || 'Gagal mencatat pengiriman', true);
    }
  } catch {
    showToast('Koneksi gagal', true);
  }
}

/* ── TAB 5: RETURNS ── */
function renderMikmsReturnTab() {
  populateMikmsBoxDropdowns();
  const retDate = document.getElementById('mk-ret-date');
  if (retDate && !retDate.value) {
    retDate.value = new Date().toISOString().split('T')[0];
  }

  const tbody = document.getElementById('mk-return-log-tbody');
  if (!tbody) return;

  const returnLogs = mikmsLogs.filter(l => l.action_type === 'return').slice(0, 15);
  if (!returnLogs.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-base-content/40">Belum ada riwayat pengembalian</td></tr>';
    return;
  }

  tbody.innerHTML = returnLogs.map(r => `
    <tr>
      <td class="font-mono text-[10px]">${formatDateTime(r.created_at)}</td>
      <td class="font-mono font-bold">${escHtml(r.reference_code || 'BOKS')}</td>
      <td><span class="badge badge-xs badge-info font-bold">${escHtml(r.status || 'Kembali')}</span></td>
      <td class="max-w-[180px] truncate text-[11px]">${escHtml(r.details || '—')}</td>
      <td class="text-[11px]">${escHtml(r.pic_name || 'Petugas')}</td>
    </tr>
  `).join('');
}

function onReturnBoxChanged() {
  // Can trigger additional info if needed
}

async function submitMikmsReturn(e) {
  e.preventDefault();
  const payload = {
    box_id: document.getElementById('mk-ret-box-id').value,
    return_date: document.getElementById('mk-ret-date').value,
    condition: document.getElementById('mk-ret-condition').value,
    missing_items: document.getElementById('mk-ret-missing').value.trim() || null,
    staff_pic: document.getElementById('mk-ret-pic').value.trim(),
  };

  try {
    const r = await api('/api/mikms/returns', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message || 'Pengembalian boks berhasil diproses!');
      document.getElementById('mk-ret-missing').value = '';
      await loadMikmsPage();
      switchMikmsTab('return');
    } else {
      showToast(d.message || 'Gagal menyimpan pengembalian', true);
    }
  } catch {
    showToast('Koneksi gagal', true);
  }
}

/* ── TAB 6: REPAIRS ── */
function renderMikmsRepairTab() {
  populateMikmsBoxDropdowns();
  const tbody = document.getElementById('mk-repair-log-tbody');
  if (!tbody) return;

  const repairLogs = mikmsLogs.filter(l => l.action_type === 'repair').slice(0, 15);
  if (!repairLogs.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-base-content/40">Belum ada data repair</td></tr>';
    return;
  }

  tbody.innerHTML = repairLogs.map(r => `
    <tr>
      <td class="font-mono text-[10px]">${formatDateTime(r.created_at)}</td>
      <td class="font-mono font-bold">${escHtml(r.reference_code || 'BOKS')}</td>
      <td class="max-w-[150px] truncate text-[11px]">${escHtml(r.details || '—')}</td>
      <td class="max-w-[150px] truncate text-[11px]">${escHtml(r.notes || '—')}</td>
      <td>
        <span class="badge badge-xs font-bold ${r.status === 'DONE' ? 'badge-success text-white' : 'badge-warning text-black'}">${r.status || 'DONE'}</span>
      </td>
      <td class="text-[11px]">${escHtml(r.pic_name || 'Teknisi')}</td>
    </tr>
  `).join('');
}

async function submitMikmsRepair(e) {
  e.preventDefault();
  const payload = {
    box_id: document.getElementById('mk-rep-box-id').value || null,
    damaged_part: document.getElementById('mk-rep-part').value.trim(),
    issue_description: document.getElementById('mk-rep-issue').value.trim(),
    action_taken: document.getElementById('mk-rep-action').value.trim(),
    status: document.getElementById('mk-rep-status').value,
    technician_name: document.getElementById('mk-rep-pic').value.trim(),
  };

  try {
    const r = await api('/api/mikms/repairs', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message || 'Laporan perbaikan berhasil disimpan!');
      document.getElementById('mk-rep-part').value = '';
      document.getElementById('mk-rep-issue').value = '';
      document.getElementById('mk-rep-action').value = '';
      await loadMikmsPage();
      switchMikmsTab('repair');
    } else {
      showToast(d.message || 'Gagal menyimpan perbaikan', true);
    }
  } catch {
    showToast('Koneksi gagal', true);
  }
}

/* ── TAB 7: STOCK OPNAME FISIK ── */
function renderMikmsOpnameTable() {
  const q = (document.getElementById('mk-opname-search')?.value || '').toLowerCase();
  const tbody = document.getElementById('mk-opname-tbody');
  if (!tbody) return;

  const mikmsPrefixes = ['CT', 'OP', 'SN', 'MT', 'PW', 'CN', 'MC', 'CS', 'TL', 'PC'];
  const items = (typeof master !== 'undefined' ? master : []).filter(item => {
    const isMikms = mikmsPrefixes.some(p => item.kode.startsWith(p));
    const matchQ = !q || item.kode.toLowerCase().includes(q) || item.nama.toLowerCase().includes(q);
    return isMikms && matchQ;
  });

  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-base-content/40">Tidak ditemukan komponen MIKMS yang cocok.</td></tr>';
    return;
  }

  tbody.innerHTML = items.map(it => {
    const systemStock = it.stok || 0;
    return `
      <tr id="mk-op-row-${it.id}">
        <td class="font-mono font-bold">${escHtml(it.kode)}</td>
        <td class="font-semibold">${escHtml(it.nama)}</td>
        <td><span class="badge badge-xs badge-neutral font-semibold">${escHtml(it.category?.nama || it.kategori || 'MIKMS')}</span></td>
        <td class="text-center text-xs font-mono">${escHtml(it.satuan || 'pcs')}</td>
        <td class="text-center font-mono font-bold text-sm" id="mk-op-sys-${it.id}">${systemStock}</td>
        <td class="text-center">
          <input type="number" id="mk-op-phys-${it.id}" min="0" value="${systemStock}" 
            class="input input-bordered input-xs font-mono font-bold text-center w-20"
            oninput="calcOpnameDiff('${it.id}', ${systemStock})">
        </td>
        <td class="text-center">
          <span class="badge badge-xs font-mono font-bold badge-neutral" id="mk-op-diff-${it.id}">0</span>
        </td>
        <td class="text-center">
          <button class="btn btn-xs btn-primary font-bold" onclick="submitSingleOpname('${it.id}', ${systemStock})">
            Simpan
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function calcOpnameDiff(itemId, sysStock) {
  const phys = parseInt(document.getElementById('mk-op-phys-' + itemId)?.value || '0', 10);
  const diff = phys - sysStock;
  const badge = document.getElementById('mk-op-diff-' + itemId);
  if (!badge) return;

  if (diff === 0) {
    badge.className = 'badge badge-xs font-mono font-bold badge-neutral';
    badge.textContent = '0';
  } else if (diff > 0) {
    badge.className = 'badge badge-xs font-mono font-bold badge-success text-white';
    badge.textContent = '+' + diff;
  } else {
    badge.className = 'badge badge-xs font-mono font-bold badge-error text-white';
    badge.textContent = String(diff);
  }
}

async function submitSingleOpname(itemId, sysStock) {
  const physVal = document.getElementById('mk-op-phys-' + itemId)?.value;
  if (physVal === '' || isNaN(physVal)) {
    showToast('Masukkan jumlah fisik yang valid', true);
    return;
  }

  const payload = {
    item_id: itemId,
    physical_qty: parseInt(physVal, 10),
    notes: 'Penyesuaian stock opname lapangan MIKMS'
  };

  try {
    const r = await api('/api/mikms/stock-opnames', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message || 'Opname berhasil disimpan!');
      await loadStock();
      renderMikmsOpnameTable();
    } else {
      showToast(d.message || 'Gagal menyimpan opname', true);
    }
  } catch {
    showToast('Koneksi gagal', true);
  }
}

/* ── TAB 8: LOGS ── */
function renderMikmsLogsTable() {
  const filterType = document.getElementById('mk-logs-filter-type')?.value || '';
  const tbody = document.getElementById('mk-logs-tbody');
  if (!tbody) return;

  const filtered = mikmsLogs.filter(l => !filterType || l.action_type === filterType);
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-base-content/40">Belum ada riwayat aktivitas yang sesuai filter.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(l => {
    let typeBadge = 'badge-neutral';
    if (l.action_type === 'production') typeBadge = 'badge-primary';
    if (l.action_type === 'qc') typeBadge = 'badge-success text-white';
    if (l.action_type === 'shipment') typeBadge = 'badge-warning text-black';
    if (l.action_type === 'return') typeBadge = 'badge-info text-white';
    if (l.action_type === 'repair') typeBadge = 'badge-error text-white';

    return `
      <tr>
        <td class="font-mono text-[10px]">${formatDateTime(l.created_at)}</td>
        <td><span class="badge badge-xs font-bold uppercase ${typeBadge}">${escHtml(l.action_type)}</span></td>
        <td class="font-mono font-bold">${escHtml(l.reference_code || '—')}</td>
        <td class="text-xs max-w-xs truncate">${escHtml(l.details || '—')}</td>
        <td><span class="badge badge-xs badge-ghost font-bold">${escHtml(l.status || 'OK')}</span></td>
        <td class="text-xs font-medium">${escHtml(l.pic_name || '—')}</td>
      </tr>
    `;
  }).join('');
}

/* ── MODAL BOX REGISTER ── */
function openMikmsBoxModal() {
  document.getElementById('mk-box-reg-form').reset();
  document.getElementById('mikms-box-modal').classList.add('modal-open');
}

function closeMikmsBoxModal() {
  document.getElementById('mikms-box-modal').classList.remove('modal-open');
}

async function submitMikmsNewBox(e) {
  e.preventDefault();
  const payload = {
    box_code: document.getElementById('mk-reg-box-code').value.trim(),
    category: document.getElementById('mk-reg-box-category').value,
    program_code: document.getElementById('mk-reg-box-program').value,
    status: document.getElementById('mk-reg-box-status').value,
  };

  try {
    const r = await api('/api/mikms/boxes', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.success) {
      showToast(d.message || 'Boks kit berhasil didaftarkan!');
      closeMikmsBoxModal();
      await loadMikmsPage();
    } else {
      showToast(d.message || 'Gagal mendaftarkan boks', true);
    }
  } catch {
    showToast('Koneksi gagal', true);
  }
}

/* ── QUICK ACTIONS FROM BOX CARD ── */
function quickShipBox(boxId) {
  switchMikmsTab('shipment');
  setTimeout(() => {
    const cb = document.querySelector(`input[name="mk-ship-box-cb"][value="${boxId}"]`);
    if (cb) cb.checked = true;
    document.getElementById('mk-ship-number')?.focus();
  }, 100);
}

function quickQcBox(boxId) {
  switchMikmsTab('qc');
  setTimeout(() => {
    const select = document.getElementById('mk-qc-box-id');
    if (select) select.value = boxId;
  }, 100);
}

function quickReturnBox(boxId) {
  switchMikmsTab('return');
  setTimeout(() => {
    const select = document.getElementById('mk-ret-box-id');
    if (select) select.value = boxId;
  }, 100);
}

function quickRepairBox(boxId) {
  switchMikmsTab('repair');
  setTimeout(() => {
    const select = document.getElementById('mk-rep-box-id');
    if (select) select.value = boxId;
  }, 100);
}

/* ── EXCEL EXPORT ── */
function exportMikmsExcel() {
  if (!token) {
    showToast('Sesi tidak valid, silakan login kembali', true);
    return;
  }
  showToast('Menyiapkan file Excel MIKMS Lapangan...');
  const downloadUrl = `/api/mikms/export/excel?token=${encodeURIComponent(token)}`;
  window.open(downloadUrl, '_blank');
}

/* ── GUIDE / SOP PAGE ── */
function renderMikmsGuide() {
  document.getElementById('main-scroll').scrollTop = 0;
}

function formatDateTime(dtStr) {
  if (!dtStr) return '—';
  try {
    const d = new Date(dtStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
  } catch {
    return dtStr;
  }
}
</script>
</body>
</html>
