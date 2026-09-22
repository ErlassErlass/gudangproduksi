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
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
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

/* Print Area Screen Isolation: SELALU sembunyikan di layar biasa */
#print-area,
#print-area.thermal-mode,
#print-area.micro-grid-mode {
  display: none !important;
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
  #print-area.thermal-mode {
    display: block !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .thermal-unit-card {
    width: 54mm;
    max-width: 58mm;
    box-sizing: border-box;
    margin: 0 auto 5mm auto;
    border: 1.5px solid #000;
    background: #fff;
    page-break-after: always;
    break-after: page;
    color: #000 !important;
    font-family: 'JetBrains Mono', monospace;
  }

  /* STIKER MIKRO MICRO:BIT 1cm x 1cm (10mm x 10mm) GRID */
  #print-area.micro-grid-mode {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 1.5mm !important;
    padding: 3mm !important;
    margin: 0 !important;
    background: #fff !important;
    justify-content: flex-start !important;
    align-items: flex-start !important;
  }
  .micro-sticker-cell {
    width: 10mm !important;
    height: 10mm !important;
    max-width: 10mm !important;
    max-height: 10mm !important;
    box-sizing: border-box !important;
    padding: 0.4mm !important;
    border: 0.5px dashed #777 !important;
    background: #fff !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    overflow: hidden !important;
  }
  .micro-sticker-qr {
    width: 6.8mm !important;
    height: 6.8mm !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
  }
  .micro-sticker-qr canvas, .micro-sticker-qr img {
    width: 6.8mm !important;
    height: 6.8mm !important;
    image-rendering: pixelated !important;
  }
  .micro-sticker-text {
    font-size: 4.8px !important;
    font-weight: 900 !important;
    line-height: 1 !important;
    font-family: 'JetBrains Mono', monospace !important;
    text-align: center !important;
    margin-top: 0.2mm !important;
    letter-spacing: -0.2px !important;
    color: #000 !important;
    white-space: nowrap !important;
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
/* Utility: Hide Scrollbar */
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
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
    <nav class="flex-1 py-4 px-3 overflow-y-auto space-y-3" role="menu">
      <!-- ── SECTION 1: OPERASIONAL GUDANG ── -->
      <div>
        <div class="px-3 pb-1 text-[10px] font-extrabold uppercase tracking-wider text-base-content/40 select-none">
          Operasional Gudang
        </div>
        <div class="space-y-1">
          <!-- 1. DASHBOARD -->
          <div id="sb-link-dashboard" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold border border-transparent hover:bg-base-200/70 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50 active" onclick="goPage('dashboard')">
            <span class="text-base">📊</span> 
            <span>Dashboard</span>
          </div>

          <!-- 2. SCAN BARANG -->
          <div id="sb-link-scan" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold border border-transparent hover:bg-base-200/70 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50" onclick="goPage('scan')">
            <span class="text-base">📷</span> 
            <span>Scan Barang</span>
            <span class="text-[10px] font-bold text-base-content/40 ml-auto">In/Out</span>
          </div>

          <!-- 3. KATALOG STOK -->
          <div id="sb-link-stock" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold border border-transparent hover:bg-base-200/70 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50" onclick="goPage('stock')">
            <span class="text-base">📦</span> 
            <span>Katalog Stok</span>
            <span class="badge badge-error badge-sm font-extrabold ml-auto text-white" id="sb-pending" style="display:none">0</span>
          </div>

          <!-- 4. CETAK QR -->
          <div id="sb-link-print" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold border border-transparent hover:bg-base-200/70 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50" onclick="goPage('print')">
            <span class="text-base">🖨️</span> 
            <span>Cetak Label QR</span>
          </div>
        </div>
      </div>

      <!-- ── SECTION 2: PRODUKSI & FINISH GOODS ── -->
      <div>
        <div class="px-3 pb-1 text-[10px] font-extrabold uppercase tracking-wider text-base-content/40 select-none">
          Produksi & Aset
        </div>
        <div class="space-y-1">
          <!-- 5. MODUL MIKMS -->
          <div id="sb-link-mikms" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold border border-transparent hover:bg-base-200/70 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50" onclick="goPage('mikms')">
            <span class="text-base">🤖</span> 
            <span>Modul MIKMS</span>
            <span class="badge badge-primary badge-xs font-bold ml-auto text-primary-content">Kit Lab</span>
          </div>

          <!-- 6. SEWA & ASSET -->
          <div id="sb-link-sewadash" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold border border-transparent hover:bg-base-200/70 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50" onclick="goPage('sewadash')">
            <span class="text-base">🔄</span> 
            <span>Sewa & Asset</span>
            <span class="badge badge-neutral badge-xs font-bold ml-auto text-[9px]">Finish Goods</span>
          </div>
        </div>
      </div>

      <!-- ── SECTION 3: PANDUAN & BANTUAN ── -->
      <div>
        <div class="px-3 pb-1 text-[10px] font-extrabold uppercase tracking-wider text-base-content/40 select-none">
          Dokumentasi
        </div>
        <div class="space-y-1">
          <!-- 7. PANDUAN & SOP -->
          <div id="sb-link-mikmsguide" tabindex="0" role="menuitem" class="sb-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold border border-transparent hover:bg-base-200/70 transition duration-200 text-base-content/70 hover:text-base-content cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/50" onclick="goPage('mikmsguide')">
            <span class="text-base">📖</span> 
            <span>SOP & Panduan</span>
          </div>
        </div>
      </div>

      <!-- ── SECTION 4: MASTER DATA (ADMIN ONLY) ── -->
      <div class="admin-section pt-1" style="display:none">
        <div class="my-2 border-t border-base-200/60"></div>
        <details class="group" id="sb-master-details">
          <summary class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-base-content/60 hover:text-base-content hover:bg-base-200/60 cursor-pointer list-none transition select-none">
            <span class="flex items-center gap-2.5">
              <span class="text-base">🛠️</span>
              <span>Master Data</span>
            </span>
            <span class="text-xs transition-transform group-open:rotate-180">▾</span>
          </summary>
          <div class="pl-3 pr-1 py-1 space-y-0.5 border-l-2 border-base-300 ml-4 mt-1">
            <div id="sb-link-master" class="sb-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-base-200 text-base-content/70 hover:text-base-content cursor-pointer transition" onclick="goPage('master')"><span>📦</span> Master Barang</div>
            <div id="sb-link-categories" class="sb-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-base-200 text-base-content/70 hover:text-base-content cursor-pointer transition" onclick="goPage('categories')"><span>🏷️</span> Master Kategori</div>
            <div id="sb-link-programs" class="sb-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-base-200 text-base-content/70 hover:text-base-content cursor-pointer transition" onclick="goPage('programs')"><span>🤖</span> Master Program Kit</div>
            <div id="sb-link-locations" class="sb-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-base-200 text-base-content/70 hover:text-base-content cursor-pointer transition" onclick="goPage('locations')"><span>🏢</span> Master Lokasi</div>
            <div id="sb-link-vendors" class="sb-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-base-200 text-base-content/70 hover:text-base-content cursor-pointer transition" onclick="goPage('vendors')"><span>🤝</span> Master Vendor</div>
            <div id="sb-link-customers" class="sb-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-base-200 text-base-content/70 hover:text-base-content cursor-pointer transition" onclick="goPage('customers')"><span>🏫</span> Master Customer</div>
            <div id="sb-link-users" class="sb-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-base-200 text-base-content/70 hover:text-base-content cursor-pointer transition" onclick="goPage('users')"><span>👥</span> Master User & Akses</div>
          </div>
        </details>
      </div>
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
        <button id="tb-back-btn" class="btn btn-ghost btn-square lg:hidden" onclick="if(window.history.length > 1){window.history.back();}else{goPage('dashboard');}" style="display:none">
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

        <!-- MIKMS QUICK SUMMARY -->
        <div class="card bg-gradient-to-r from-primary/5 via-base-100 to-accent/5 border border-primary/15 shadow-sm p-4 mt-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-base-content/50">🤖 Ringkasan MIKMS</h3>
            <button class="btn btn-xs btn-ghost text-primary font-bold" onclick="goPage('mikms','unitregistry')">Lihat Semua →</button>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-base-100/80 rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3 cursor-pointer hover:bg-base-200/50 transition" onclick="goPage('mikms','unitregistry')">
              <div class="w-9 h-9 rounded-lg bg-info/10 text-info flex items-center justify-center text-lg font-bold">📦</div>
              <div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-base-content/50">Total Unit FG</div>
                <div class="text-base font-extrabold text-base-content" id="dash-mk-boxes">—</div>
              </div>
            </div>
            <div class="bg-base-100/80 rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3 cursor-pointer hover:bg-base-200/50 transition" onclick="goPage('mikms','modulestocks')">
              <div class="w-9 h-9 rounded-lg bg-success/10 text-success flex items-center justify-center text-lg font-bold">🏭</div>
              <div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-base-content/50">Modul Jadi (RW)</div>
                <div class="text-base font-extrabold text-success" id="dash-mk-modules">—</div>
              </div>
            </div>
            <div class="bg-base-100/80 rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3 cursor-pointer hover:bg-base-200/50 transition" onclick="goPage('mikms','packageorder')">
              <div class="w-9 h-9 rounded-lg bg-accent/10 text-accent flex items-center justify-center text-lg font-bold">⚙️</div>
              <div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-base-content/50">Perakitan Kit</div>
                <div class="text-base font-extrabold text-accent" id="dash-mk-orders">—</div>
              </div>
            </div>
            <div class="bg-base-100/80 rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3 cursor-pointer hover:bg-base-200/50 transition" onclick="goPage('mikms','dispatch')">
              <div class="w-9 h-9 rounded-lg bg-warning/10 text-warning flex items-center justify-center text-lg font-bold">📤</div>
              <div>
                <div class="text-[9px] font-bold uppercase tracking-wider text-base-content/50">Dispatched / Sewa</div>
                <div class="text-base font-extrabold text-warning" id="dash-mk-loan">—</div>
              </div>
            </div>
          </div>
        </div>
        <!-- MOBILE QUICK LINKS -->
        <div class="lg:hidden mt-6 grid grid-cols-2 gap-3">
          <button onclick="goPage('scan')" class="btn btn-primary w-full shadow-lg rounded-2xl py-4 h-auto text-sm font-extrabold flex flex-col items-center justify-center gap-1">
            <span class="text-2xl">📷</span> Scan Barang
          </button>
          <button onclick="goPage('mikms','packageorder')" class="btn btn-accent w-full shadow-lg rounded-2xl py-4 h-auto text-sm font-extrabold flex flex-col items-center justify-center gap-1">
            <span class="text-2xl">⚙️</span> Perakitan Kit MLK
          </button>
          <button onclick="goPage('stock')" class="btn btn-neutral btn-outline w-full rounded-2xl py-3 h-auto text-sm font-bold flex flex-col items-center justify-center gap-1">
            <span class="text-2xl">📦</span> Lihat Stok
          </button>
          <button onclick="goPage('mikms','production')" class="btn btn-neutral btn-outline w-full rounded-2xl py-3 h-auto text-sm font-bold flex flex-col items-center justify-center gap-1">
            <span class="text-2xl">🧩</span> Sub-Assembly RW
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

      <!-- ═══ UNIVERSAL SMART SCANNER HUB ═══ -->
      <div class="page" id="pg-scan">
        <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs">
          <div>
            <h3 class="font-black text-base flex items-center gap-2 text-base-content">
              <span>📷</span> Universal Smart Scanner Hub
            </h3>
            <p class="text-xs text-base-content/60 mt-0.5">
              Satu scanner cerdas untuk semua. Sistem otomatis mendeteksi apakah yang discan adalah <strong>Barang Inventaris Umum</strong> (komponen/sparepart/ATK) atau <strong>Unit Serial MIKMS</strong> (Finish Goods), dan langsung menyajikan status serta aksi yang tepat.
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <span class="badge badge-primary badge-outline text-xs font-bold font-mono">ALL-IN-ONE SCAN</span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Video Scanner Column (5 cols) -->
          <div class="lg:col-span-5 flex flex-col gap-4">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-black uppercase tracking-wider text-base-content/60 flex items-center gap-2">
                  <span class="inline-block w-2 h-2 rounded-full bg-success animate-pulse"></span> Kamera Scanner
                </span>
                <button class="btn btn-xs btn-outline btn-neutral" id="cam-toggle-btn" onclick="toggleCamera()">Nyalakan Kamera</button>
              </div>

              <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-black mx-auto border border-base-300 shadow-inner">
                <video id="scanner-video" class="w-full h-full object-cover" playsinline></video>
                <canvas id="scanner-canvas" class="hidden"></canvas>
                <!-- Scan lines and target frame overlay -->
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                  <div class="w-4/5 h-4/5 border-2 border-white/60 rounded-2xl relative shadow-2xl">
                    <div class="scan-line absolute left-3 right-3 h-[2px] bg-gradient-to-r from-transparent via-primary to-transparent shadow-lg shadow-primary"></div>
                  </div>
                </div>
                <!-- Camera activation cover -->
                <div class="absolute inset-0 bg-base-100/90 backdrop-blur-sm flex flex-col items-center justify-center gap-3 text-center p-6" id="cam-overlay">
                  <div class="text-5xl">📷</div>
                  <button class="btn btn-primary btn-sm rounded-xl font-bold shadow-md shadow-primary/20" onclick="startCamera()">Nyalakan Kamera</button>
                  <p class="text-[10px] text-base-content/50 max-w-[200px]">Atau gunakan barcode reader USB/Bluetooth & ketik manual di samping</p>
                </div>
              </div>

              <div class="alert alert-neutral mt-4 text-xs justify-center font-bold" id="scan-status">
                Siap memindai QR Code atau Barcode...
              </div>
            </div>

            <!-- Recent Scan Session History in Column 1 -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-base-content/60">Riwayat Sesi Scan Terakhir</span>
                <span class="badge badge-ghost badge-xs font-mono" id="scan-history-count">0 aktivitas</span>
              </div>
              <div class="overflow-x-auto max-h-[220px]">
                <table class="table table-xs w-full">
                  <tbody id="scan-session-tbody">
                    <tr><td class="text-center py-4 text-base-content/40 text-xs">Belum ada scan dalam sesi ini</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Dynamic Detection & Action Column (7 cols) -->
          <div class="lg:col-span-7 flex flex-col gap-4">
            <!-- Universal Search / Barcode Input Bar -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
              <div class="form-control w-full">
                <label class="label py-1">
                  <span class="label-text text-[10px] font-black uppercase tracking-wider text-base-content/60">Input Universal (Barcode Scanner / Serial / Kode)</span>
                  <span class="label-text-alt text-[10px] text-primary font-bold">Tekan Enter atau Scan Langsung</span>
                </label>
                <div class="join w-full">
                  <input type="text" id="scan-universal-input" class="input input-bordered input-sm join-item w-full font-mono uppercase font-bold text-sm tracking-wider" placeholder="Scan Barcode atau Ketik MLK-..., ROBO-..., KODE..." oninput="onUniversalScanInput(this.value)" onkeydown="if(event.key==='Enter'){processUniversalScan(this.value);}" autocomplete="off" list="scan-universal-dl">
                  <datalist id="scan-universal-dl"></datalist>
                  <button type="button" class="btn btn-sm btn-primary join-item font-bold" onclick="processUniversalScan(document.getElementById('scan-universal-input').value)">🔍 Cari</button>
                  <button type="button" class="btn btn-sm btn-ghost join-item border border-base-300" onclick="clearUniversalScan()" title="Bersihkan">✕</button>
                </div>
              </div>
            </div>

            <!-- RESULT CONTAINER: 3 STATES -->
            <!-- STATE 0: EMPTY STATE (BEFORE SCAN) -->
            <div id="sc-empty-state" class="card bg-base-100 border border-dashed border-base-300 p-8 text-center flex flex-col items-center justify-center min-h-[360px]">
              <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center text-3xl mb-3 shadow-inner">
                🎯
              </div>
              <h4 class="font-bold text-sm text-base-content">Siap Memindai Barcode / QR Code</h4>
              <p class="text-xs text-base-content/50 max-w-sm mt-1 mb-4 leading-relaxed">
                Arahkan kamera ke barcode/QR atau ketik kode di kolom pencarian. Sistem cerdas akan langsung mengenali identitas barang:
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 w-full max-w-md text-left text-xs">
                <div class="p-3 bg-base-200/60 rounded-xl border border-base-300">
                  <div class="font-bold text-primary flex items-center gap-1.5 mb-1">
                    <span>📦</span> Barang Inventaris Umum
                  </div>
                  <div class="text-[11px] text-base-content/60 leading-tight">
                    Komponen, modul mentah, kabel, sparepart. Langsung cek sisa stok & pilih <strong>Masuk (+)</strong> atau <strong>Keluar (-)</strong>.
                  </div>
                </div>
                <div class="p-3 bg-base-200/60 rounded-xl border border-base-300">
                  <div class="font-bold text-secondary flex items-center gap-1.5 mb-1">
                    <span>🤖</span> Unit Finish Goods MIKMS
                  </div>
                  <div class="text-[11px] text-base-content/60 leading-tight">
                    Serial MLK, ROBO, JIMU, ERBO, ALK. Langsung cek status QC, <strong>Dispatch</strong>, <strong>Retur</strong>, atau <strong>Cetak Stiker</strong>.
                  </div>
                </div>
              </div>
            </div>

            <!-- STATE 1: BARANG INVENTARIS UMUM FOUND (KUANTITATIF) -->
            <div id="sc-item-card" class="card bg-base-100 border border-base-300 shadow-sm p-6 hidden">
              <!-- Item Header Info & Realtime Stock Badge -->
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-4 border-b border-base-200">
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="badge badge-primary badge-sm font-bold">📦 INVENTARIS UMUM</span>
                    <span class="badge badge-secondary badge-sm font-bold hidden" id="sc-item-fg-badge">🤖 FINISH GOODS</span>
                    <span class="font-mono text-xs font-bold text-primary" id="sc-item-kode">ITEM-001</span>
                  </div>
                  <h3 class="font-black text-base text-base-content mt-1" id="sc-item-nama">Nama Barang</h3>
                  <div class="text-xs text-base-content/50 mt-0.5" id="sc-item-sub">Kategori: — | Satuan: pcs</div>
                </div>
                <div class="text-left sm:text-right shrink-0">
                  <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 mb-0.5">Sisa Stok Fisik:</div>
                  <div class="badge badge-lg font-black text-sm px-3 py-3" id="sc-item-stok-badge">
                    Stok: 0
                  </div>
                </div>
              </div>

              <!-- Unified Action Switcher (Masuk vs Keluar) -->
              <div class="mt-4">
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50 mb-2">Pilih Aksi Transaksi:</div>
                <div class="grid grid-cols-2 gap-2 bg-base-200 p-1 rounded-2xl">
                  <button type="button" id="sc-btn-mode-masuk" class="btn btn-sm font-extrabold rounded-xl btn-primary shadow-xs" onclick="setUniversalItemMode('masuk')">
                    ↓ Catat Barang Masuk (+)
                  </button>
                  <button type="button" id="sc-btn-mode-keluar" class="btn btn-sm font-extrabold rounded-xl btn-ghost text-error" onclick="setUniversalItemMode('keluar')">
                    ↑ Catat Barang Keluar (-)
                  </button>
                </div>
                <!-- Finish Good incoming educational hint -->
                <div id="sc-fg-incoming-hint" class="hidden text-xs text-primary font-bold bg-primary/10 border border-primary/20 p-2.5 rounded-xl mt-3 flex items-center gap-2">
                  <span>💡</span>
                  <div id="sc-fg-incoming-hint-text">Penerimaan barang vendor ini akan otomatis menerbitkan <strong>Nomor Seri MIKMS</strong> berstatus Menunggu QC.</div>
                </div>
              </div>

              <!-- Unified Transaction Form -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mt-4">
                <div class="form-control w-full">
                  <label class="label py-1"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Jumlah Unit *</span></label>
                  <div class="join w-full">
                    <button type="button" class="btn btn-sm join-item border border-base-300" onclick="adjustScQty(-1)">-</button>
                    <input type="number" id="sc-item-qty" class="input input-bordered input-sm join-item w-full text-center font-bold text-sm" value="1" min="1" oninput="validateScQty()">
                    <button type="button" class="btn btn-sm join-item border border-base-300" onclick="adjustScQty(1)">+</button>
                    <button type="button" class="btn btn-xs join-item border border-base-300 font-bold px-2" onclick="adjustScQty(5)">+5</button>
                    <button type="button" class="btn btn-xs join-item border border-base-300 font-bold px-2" onclick="adjustScQty(10)">+10</button>
                  </div>
                  <span id="sc-qty-warning" class="text-[10px] text-error font-bold mt-1 hidden">⚠️ Jumlah melebihi sisa stok!</span>
                </div>

                <div class="form-control w-full">
                  <label class="label py-1"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Gudang / Lokasi *</span></label>
                  <select id="sc-item-lokasi" class="select select-bordered select-sm w-full font-semibold">
                    <option>Gudang Utama</option>
                    <option>Gudang Raw Material</option>
                    <option>Gudang Work in Process</option>
                  </select>
                </div>

                <!-- Rekanan Dinamis (Vendor jika Masuk, Customer jika Keluar) -->
                <div class="form-control w-full md:col-span-2" id="sc-partner-wrap">
                  <label class="label py-1"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60" id="sc-partner-lbl">Sumber / Vendor *</span></label>
                  <input type="text" id="sc-item-partner" class="input input-bordered input-sm w-full font-semibold" placeholder="Pilih atau ketik vendor..." list="sc-partner-dl" autocomplete="off">
                  <datalist id="sc-partner-dl"></datalist>
                </div>

                <!-- Collapsible Dokumen Opsional -->
                <details class="md:col-span-2 group bg-base-200/50 rounded-xl p-3 border border-base-300/60">
                  <summary class="text-xs font-bold text-base-content/70 cursor-pointer flex items-center justify-between select-none">
                    <span>📄 Tambah Info Dokumen (PO, PRN, Job Number, Catatan)</span>
                    <span class="transition-transform group-open:rotate-180">▾</span>
                  </summary>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 pt-3 border-t border-base-300/40">
                    <div class="form-control">
                      <label class="label py-0"><span class="label-text text-[9px] font-bold uppercase text-base-content/60">No. PO</span></label>
                      <input type="text" id="sc-item-po" class="input input-bordered input-xs font-mono" placeholder="PO-xxx">
                    </div>
                    <div class="form-control">
                      <label class="label py-0"><span class="label-text text-[9px] font-bold uppercase text-base-content/60">No. PRN</span></label>
                      <input type="text" id="sc-item-prn" class="input input-bordered input-xs font-mono" placeholder="PRN-xxx">
                    </div>
                    <div class="form-control">
                      <label class="label py-0"><span class="label-text text-[9px] font-bold uppercase text-base-content/60">Job Number</span></label>
                      <input type="text" id="sc-item-job" class="input input-bordered input-xs font-mono" placeholder="JOB-xxx">
                    </div>
                    <div class="form-control">
                      <label class="label py-0"><span class="label-text text-[9px] font-bold uppercase text-base-content/60">Transfer Order</span></label>
                      <input type="text" id="sc-item-to" class="input input-bordered input-xs font-mono" placeholder="TO-xxx">
                    </div>
                    <div class="form-control md:col-span-2">
                      <label class="label py-0"><span class="label-text text-[9px] font-bold uppercase text-base-content/60">Catatan Tambahan</span></label>
                      <input type="text" id="sc-item-catatan" class="input input-bordered input-xs" placeholder="Keterangan opsional">
                    </div>
                  </div>
                </details>

                <div class="md:col-span-2 mt-2">
                  <button type="button" id="sc-item-submit-btn" class="btn btn-primary btn-sm w-full font-extrabold shadow-md" onclick="submitUniversalItemScan()">
                    ↓ Simpan Barang Masuk (+1)
                  </button>
                </div>

                <!-- Banner Hasil Generate Serial MIKMS -->
                <div id="sc-fg-generated-banner" class="hidden md:col-span-2 mt-3 p-4 bg-primary/10 border border-primary/30 rounded-2xl">
                  <div class="flex items-center gap-2">
                    <span class="text-xl">🎉</span>
                    <div class="font-extrabold text-sm text-primary" id="sc-fg-banner-title">Berhasil Menerbitkan Nomor Seri MIKMS!</div>
                  </div>
                  <p class="text-xs text-base-content/80 mt-1">
                    Unit fisik otomatis didaftarkan ke <strong>Unit Registry</strong> berstatus <span class="badge badge-warning badge-xs font-bold">Menunggu QC</span>.
                  </p>
                  <div id="sc-fg-banner-serials" class="font-mono text-xs font-bold bg-base-100 p-2 rounded-lg border border-base-200 mt-2 max-h-24 overflow-y-auto leading-relaxed"></div>
                  <div class="flex flex-wrap gap-2 mt-3" id="sc-fg-banner-actions"></div>
                </div>
              </div>
            </div>

            <!-- STATE 2: UNIT SERIAL MIKMS FOUND (FINISH GOODS / ASSET) -->
            <div id="sc-mikms-card" class="card bg-base-100 border border-secondary/40 shadow-sm p-6 hidden">
              <!-- MIKMS Header Info -->
              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-4 border-b border-base-200">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="badge badge-secondary badge-sm font-bold">🤖 FINISH GOODS MIKMS</span>
                    <span class="badge badge-outline text-[10px] font-bold" id="sc-mikms-type">ASSEMBLED</span>
                  </div>
                  <h3 class="font-black text-lg text-base-content mt-1" id="sc-mikms-prodname">Microbit Learning Kit</h3>
                  <div class="font-mono font-black text-xl text-secondary tracking-wider mt-0.5" id="sc-mikms-serial">MLK-00001</div>
                </div>
                <div class="text-left sm:text-right shrink-0 flex flex-col gap-1.5 items-start sm:items-end">
                  <div id="sc-mikms-status-badge" class="badge badge-success text-white font-bold text-xs px-3 py-2.5">
                    Stok Gudang
                  </div>
                  <div id="sc-mikms-qc-badge" class="text-xs font-bold text-success flex items-center gap-1">
                    ✓ QC Lolos
                  </div>
                </div>
              </div>

              <!-- Bundling Children Details (jika ada, misal ROBO) -->
              <div id="sc-mikms-children-wrap" class="mt-3 p-3 bg-base-200/70 border border-base-300 rounded-xl hidden">
                <div class="text-[10px] font-black uppercase text-base-content/60 mb-1">📦 Komponen Isi Kemasan (Bundling):</div>
                <div id="sc-mikms-children-list" class="text-xs space-y-0.5 font-mono"></div>
              </div>

              <!-- Contextual Action Buttons for MIKMS Unit -->
              <div class="mt-5">
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50 mb-2">Aksi Cepat untuk Unit Ini:</div>
                <div class="flex flex-wrap gap-2" id="sc-mikms-actions">
                  <!-- In Stock Actions -->
                  <button type="button" class="btn btn-sm btn-primary font-bold sc-act-instock" onclick="goToDispatchFromScan()">
                    📤 Kirim / Dispatch
                  </button>
                  <button type="button" class="btn btn-sm btn-neutral btn-outline font-bold sc-act-instock" onclick="goToSewaFromScan()">
                    🔄 Sewa Keluar
                  </button>
                  <!-- Dispatched Actions -->
                  <button type="button" class="btn btn-sm btn-warning font-bold sc-act-dispatched" onclick="quickReturnCurrentMikmsUnit()">
                    ↩️ Terima Retur (Wajib QC Ulang)
                  </button>
                  <!-- QC Actions -->
                  <button type="button" class="btn btn-sm btn-warning font-bold sc-act-qc" onclick="goToQcUnitFromScan()">
                    ✅ Buka QC Checklist
                  </button>
                  <!-- Print Labels -->
                  <button type="button" class="btn btn-sm btn-outline btn-info font-bold" onclick="printCurrentMikmsThermal()">
                    🖨️ Cetak Stiker Boks
                  </button>
                  <button type="button" id="sc-mikms-grid-btn" class="btn btn-sm btn-outline btn-secondary font-bold" onclick="openCurrentMikmsGrid()">
                    🏷️ Stiker Board 1x1cm
                  </button>
                </div>
              </div>
            </div>

            <!-- STATE 3: NOT FOUND ALERT -->
            <div id="sc-notfound-card" class="card bg-warning/10 border border-warning/30 p-5 hidden">
              <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div class="flex-1">
                  <h4 class="font-bold text-sm text-base-content">Kode / Serial Tidak Dikenali</h4>
                  <p class="text-xs text-base-content/70 mt-1" id="sc-notfound-msg">
                    Kode "<strong class="font-mono" id="sc-notfound-code">XYZ</strong>" tidak terdaftar di Master Barang ataupun Unit Registry MIKMS.
                  </p>
                  <div class="flex gap-2 mt-3 flex-wrap">
                    <button type="button" class="btn btn-xs btn-primary font-bold" onclick="goPage('master')">+ Buka Master Barang</button>
                    <button type="button" class="btn btn-xs btn-secondary font-bold" onclick="goPage('mikms', 'unitregistry')">🔢 Unit Registry MIKMS</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ STOCK PAGE ═══ -->
      <div class="page" id="pg-stock">
        <!-- SUB-NAV TABS (STOCK) -->
        <div class="flex items-center gap-1.5 mb-6 bg-base-100 p-1.5 rounded-2xl border border-base-200 shadow-xs w-fit">
          <button class="btn btn-sm btn-primary text-primary-content shadow-xs font-bold text-xs rounded-xl" onclick="goPage('stock')">📦 Katalog Stok</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('transactions')">📋 Riwayat Transaksi</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('stockcard')">📄 Kartu Stok</button>
        </div>

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
        <!-- SUB-NAV TABS (TRANSACTIONS) -->
        <div class="flex items-center gap-1.5 mb-6 bg-base-100 p-1.5 rounded-2xl border border-base-200 shadow-xs w-fit">
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('stock')">📦 Katalog Stok</button>
          <button class="btn btn-sm btn-primary text-primary-content shadow-xs font-bold text-xs rounded-xl" onclick="goPage('transactions')">📋 Riwayat Transaksi</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('stockcard')">📄 Kartu Stok</button>
        </div>

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
        <!-- SUB-NAV TABS (STOCK CARD) -->
        <div class="flex items-center gap-1.5 mb-6 bg-base-100 p-1.5 rounded-2xl border border-base-200 shadow-xs w-fit">
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('stock')">📦 Katalog Stok</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('transactions')">📋 Riwayat Transaksi</button>
          <button class="btn btn-sm btn-primary text-primary-content shadow-xs font-bold text-xs rounded-xl" onclick="goPage('stockcard')">📄 Kartu Stok</button>
        </div>
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
        <!-- SUB-NAV TABS (SEWA) -->
        <div class="flex items-center gap-1.5 mb-6 bg-base-100 p-1.5 rounded-2xl border border-base-200 shadow-xs w-fit">
          <button class="btn btn-sm btn-primary text-primary-content shadow-xs font-bold text-xs rounded-xl" onclick="goPage('sewadash')">📈 Dashboard Sewa</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewamutasi')">🔄 Transaksi Sewa</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewaunits')">🔍 Daftar Unit</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewacard')">📄 Riwayat Asset</button>
        </div>

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
        <!-- SUB-NAV TABS (SEWA MUTASI) -->
        <div class="flex items-center gap-1.5 mb-6 bg-base-100 p-1.5 rounded-2xl border border-base-200 shadow-xs w-fit">
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewadash')">📈 Dashboard Sewa</button>
          <button class="btn btn-sm btn-primary text-primary-content shadow-xs font-bold text-xs rounded-xl" onclick="goPage('sewamutasi')">🔄 Transaksi Sewa</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewaunits')">🔍 Daftar Unit</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewacard')">📄 Riwayat Asset</button>
        </div>

        <div class="tabs tabs-boxed mb-6 bg-base-200 p-1 flex justify-start gap-1">
          <button class="tab tab-active font-bold text-xs" id="tab-btn-sewain" onclick="switchSewaTab('sewain')">📥 Penerimaan Asset Baru</button>
          <button class="tab font-bold text-xs" id="tab-btn-sewaout" onclick="switchSewaTab('sewaout')">📤 Sewa Keluar</button>
          <button class="tab font-bold text-xs" id="tab-btn-sewaret" onclick="switchSewaTab('sewaret')">↩️ Sewa Kembali</button>
        </div>

        <!-- TAB: PENERIMAAN ASSET BARU -->
        <div id="sewa-tab-sewain" class="sewa-tab-content">
          <!-- Info Banner Alur Finish Goods Tergenerate -->
          <div class="alert alert-info border border-info/30 bg-info/10 text-xs rounded-2xl p-4 mb-6 shadow-xs flex items-start gap-3">
            <span class="text-xl">ℹ️</span>
            <div>
              <div class="font-extrabold text-sm text-base-content">Alur Penerimaan Aset Barang Sewa (Finish Goods)</div>
              <div class="text-base-content/80 mt-0.5 leading-relaxed">
                Unit barang sewa bersumber dari <strong>Finish Goods yang telah diproduksi/diinput dan lolos QC (in_stock)</strong> di modul MIKMS. Pilih unit dari panel kiri untuk mengisi data registrasi aset secara otomatis.
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-6xl">
            <!-- Panel Kiri: Pilihan Unit FG Tergenerate MIKMS -->
            <div class="lg:col-span-5 space-y-4">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                  <div class="font-extrabold text-sm flex items-center gap-2">
                    <span>🏷️</span> Unit Finish Goods Tersedia
                  </div>
                  <span id="sewain-fg-count-badge" class="badge badge-primary badge-sm font-bold">0 Unit</span>
                </div>

                <!-- Filter Kategori FG -->
                <div class="flex flex-wrap gap-1 mb-2.5" id="sewain-cat-pills">
                  <button type="button" class="btn btn-xs btn-primary font-bold rounded-lg sewain-cat-btn" data-cat="ALL" onclick="filterSewainCategory('ALL')">ALL</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewain-cat-btn" data-cat="MLK" onclick="filterSewainCategory('MLK')">MLK</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewain-cat-btn" data-cat="ROBO" onclick="filterSewainCategory('ROBO')">ROBO</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewain-cat-btn" data-cat="JIMU" onclick="filterSewainCategory('JIMU')">JIMU</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewain-cat-btn" data-cat="ERBO" onclick="filterSewainCategory('ERBO')">ERBO</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewain-cat-btn" data-cat="ALK" onclick="filterSewainCategory('ALK')">ALK</button>
                </div>

                <!-- Live Search Box -->
                <div class="relative mb-3">
                  <input type="text" id="sewain-search-input" oninput="handleSewainSearch(this.value)" placeholder="Cari serial / nama unit..." class="input input-bordered input-xs w-full pl-7 font-mono">
                  <span class="absolute left-2.5 top-1.5 text-xs opacity-50">🔍</span>
                </div>

                <!-- Scrollable List of Available Units -->
                <div id="sewain-available-units-list" class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                  <div class="text-center py-8 text-base-content/40 text-xs">Memuat unit Finish Goods...</div>
                </div>
              </div>
            </div>

            <!-- Panel Kanan: Form Registrasi Asset -->
            <div class="lg:col-span-7">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="font-extrabold text-base">Registrasi Aset Barang Sewa</h3>
                  <span class="badge badge-outline text-[10px] font-bold">Input Masuk Asset</span>
                </div>

                <!-- Indicator unit terpilih -->
                <div id="sewain-selected-banner" class="alert alert-success/15 border border-success/30 text-xs py-2 px-3 rounded-xl mb-4 hidden flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-success text-base">✓</span>
                    <div>Unit Terpilih: <strong id="sewain-selected-serial" class="font-mono text-primary"></strong> — <span id="sewain-selected-name"></span></div>
                  </div>
                  <button type="button" class="btn btn-ghost btn-xs text-error font-bold" onclick="clearSelectedFgUnit()">Batal</button>
                </div>

                <form onsubmit="handleNewAssetSubmit(event)" class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                      <label class="label py-1"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Master Barang (Finish Goods) *</span></label>
                      <select id="sewain-item-id" class="select select-bordered select-sm w-full font-semibold" onchange="onSewainItemChange(this.value)" required></select>
                      <span class="text-[10px] text-base-content/50 mt-1 block">Pilih produk untuk menyaring stok unit serial di bawah.</span>
                    </div>
                    <div class="form-control">
                      <label class="label py-1"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tipe Kepemilikan *</span></label>
                      <select id="sewain-tipe" class="select select-bordered select-sm w-full font-semibold" required>
                        <option value="sewa" selected>Sewa</option>
                        <option value="bekas">Bekas</option>
                      </select>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                      <div class="flex items-center justify-between py-1">
                        <label class="label p-0"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Serial Number Unit (Dari Stok) *</span></label>
                        <button type="button" class="btn btn-ghost btn-xs text-primary font-bold text-[10px] px-1.5 h-5 min-h-0" id="sewain-mode-toggle" onclick="toggleSewainInputMode()">
                          🔄 Ketik/Scan Manual
                        </button>
                      </div>

                      <!-- Mode 1: DROPDOWN PILIH DARI STOK KITA (Default) -->
                      <div id="sewain-dropdown-wrapper">
                        <select id="sewain-serial-select" class="select select-bordered select-sm w-full font-mono font-bold text-xs" onchange="onSewainSelectChange(this.value)">
                          <option value="">-- Pilih Serial Number dari Stok Kita --</option>
                        </select>
                        <div class="flex items-center justify-between text-[10px] text-base-content/50 mt-1">
                          <span id="sewain-stock-help">💡 Unit Finish Goods siap di stok gudang.</span>
                          <span id="sewain-stock-count-hint" class="font-bold text-primary"></span>
                        </div>
                      </div>

                      <!-- Mode 2: MANUAL INPUT / SCANNER -->
                      <div id="sewain-manual-wrapper" class="hidden">
                        <input type="text" id="sewain-serial" class="input input-bordered input-sm w-full font-mono font-bold" placeholder="Scan barcode atau ketik nomor seri..." list="sewain-stock-dl" oninput="onSewainManualInput(this.value)">
                        <datalist id="sewain-stock-dl"></datalist>
                        <span class="text-[10px] text-base-content/50 mt-1 block">Scan barcode scanner atau ketik nomor seri unit dari stok.</span>
                      </div>
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kondisi Awal *</span></label>
                      <select id="sewain-status" class="select select-bordered select-sm w-full font-semibold" required>
                        <option value="good" selected>Good</option>
                        <option value="lengkap">Lengkap</option>
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
                    <textarea id="sewain-catatan" class="textarea textarea-bordered textarea-sm w-full h-16" placeholder="Keterangan tambahan registrasi asset..."></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-xs">📥 Daftarkan Unit ke List Asset Barang Sewa</button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: SEWA KELUAR -->
        <div id="sewa-tab-sewaout" class="sewa-tab-content hidden">
          <!-- Info Banner Aturan Sewa Keluar -->
          <div class="alert alert-info border border-info/30 bg-info/10 text-xs rounded-2xl p-4 mb-6 shadow-xs flex items-start gap-3">
            <span class="text-xl">📤</span>
            <div>
              <div class="font-extrabold text-sm text-base-content">Aturan Distribusi Sewa Keluar</div>
              <div class="text-base-content/80 mt-0.5 leading-relaxed">
                Unit yang dapat disewakan keluar <strong>HANYALAH unit yang sudah terdaftar di list Aset Barang Sewa</strong> dan berstatus <strong>Ready (tidak sedang disewa & tidak berstatus Wajib QC Ulang)</strong>. Pilih dari panel kiri atau gunakan barcode scanner.
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-6xl">
            <!-- Panel Kiri: List Asset Barang Sewa Ready -->
            <div class="lg:col-span-5 space-y-4">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                  <div class="font-extrabold text-sm flex items-center gap-2">
                    <span>📦</span> Aset Sewa Ready
                  </div>
                  <span id="sewaout-ready-count-badge" class="badge badge-success badge-sm font-bold text-success-content">0 Ready</span>
                </div>

                <!-- Filter Kategori FG Sewa Ready -->
                <div class="flex flex-wrap gap-1 mb-2.5" id="sewaout-cat-pills">
                  <button type="button" class="btn btn-xs btn-primary font-bold rounded-lg sewaout-cat-btn" data-cat="ALL" onclick="filterSewaoutCategory('ALL')">ALL</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewaout-cat-btn" data-cat="MLK" onclick="filterSewaoutCategory('MLK')">MLK</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewaout-cat-btn" data-cat="ROBO" onclick="filterSewaoutCategory('ROBO')">ROBO</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewaout-cat-btn" data-cat="JIMU" onclick="filterSewaoutCategory('JIMU')">JIMU</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewaout-cat-btn" data-cat="ERBO" onclick="filterSewaoutCategory('ERBO')">ERBO</button>
                  <button type="button" class="btn btn-xs btn-ghost font-bold rounded-lg sewaout-cat-btn" data-cat="ALK" onclick="filterSewaoutCategory('ALK')">ALK</button>
                </div>

                <!-- Live Search Box -->
                <div class="relative mb-3">
                  <input type="text" id="sewaout-search-input" oninput="handleSewaoutSearch(this.value)" placeholder="Cari serial / nama produk..." class="input input-bordered input-xs w-full pl-7 font-mono">
                  <span class="absolute left-2.5 top-1.5 text-xs opacity-50">🔍</span>
                </div>

                <!-- Scrollable List of Ready Assets -->
                <div id="sewaout-ready-assets-list" class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                  <div class="text-center py-8 text-base-content/40 text-xs">Memuat daftar aset sewa ready...</div>
                </div>
              </div>
            </div>

            <!-- Panel Kanan: Form Sewa Keluar -->
            <div class="lg:col-span-7">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
                <h3 class="font-extrabold text-base mb-4">Transaksi Sewa Keluar</h3>
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

                  <!-- Scanner Barcode / Input Serial -->
                  <div class="bg-base-200/80 p-3.5 rounded-2xl border border-base-300 space-y-2">
                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/60 flex items-center justify-between">
                      <span>⚡ Scan / Masukkan Serial Number Asset</span>
                      <span class="text-[10px] text-base-content/40 font-normal">Tekan Enter untuk menambah</span>
                    </div>
                    <div class="flex gap-2">
                      <input type="text" id="sewaout-scan-input" class="input input-bordered input-sm flex-1 font-mono font-bold" placeholder="Scan QR atau ketik serial asset..." onkeydown="if(event.key==='Enter'){event.preventDefault();addAssetToSewaOutList();}">
                      <button type="button" class="btn btn-neutral btn-sm font-bold" onclick="addAssetToSewaOutList()">+ Tambah</button>
                    </div>
                  </div>

                  <!-- Daftar Unit yang akan Disewakan -->
                  <div class="border border-base-300 rounded-2xl p-4 bg-base-50 max-h-56 overflow-y-auto">
                    <div class="flex items-center justify-between mb-2">
                      <div class="text-xs font-bold text-base-content/70">📋 Unit yang akan Disewakan:</div>
                      <span id="sewaout-selected-count" class="badge badge-sm badge-neutral font-bold">0 Unit</span>
                    </div>
                    <table class="table table-sm table-zebra w-full text-xs">
                      <thead><tr>
                        <th>Serial Number</th><th>Nama Barang</th><th>Kondisi</th><th class="text-center">Aksi</th>
                      </tr></thead>
                      <tbody id="sewaout-list-tbody">
                        <tr><td colspan="4" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>
                      </tbody>
                    </table>
                  </div>

                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-xs">🚀 Proses Transaksi Sewa Keluar</button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB: SEWA KEMBALI -->
        <div id="sewa-tab-sewaret" class="sewa-tab-content hidden">
          <!-- Warning Banner: Wajib QC Ulang -->
          <div class="alert alert-warning border border-warning/40 bg-warning/10 text-xs rounded-2xl p-4 mb-6 shadow-xs flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
              <div class="font-black text-sm text-warning-content">ATURAN PENGEMBALIAN SEWA: WAJIB QC ULANG</div>
              <div class="text-warning-content/90 mt-1 leading-relaxed">
                Seluruh unit barang sewa yang kembali <strong>otomatis masuk ke status WAJIB QC ULANG (qc_pending)</strong>. Unit tersebut <strong>TIDAK DAPAT disewakan kembali</strong> sebelum dilakukan inspeksi QC pada menu <strong>QC Unit MIKMS</strong> dan dinyatakan <strong>LOLOS (PASS)</strong>.
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-6xl">
            <!-- Panel Kiri: Daftar Unit yang Sedang Disewa -->
            <div class="lg:col-span-5 space-y-4">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                  <div class="font-extrabold text-sm flex items-center gap-2">
                    <span>🔄</span> Unit Sedang Disewa
                  </div>
                  <span id="sewaret-rented-count-badge" class="badge badge-warning badge-sm font-bold text-warning-content">0 Disewa</span>
                </div>

                <!-- Search Box -->
                <div class="relative mb-3">
                  <input type="text" id="sewaret-search-input" oninput="handleSewaretSearch(this.value)" placeholder="Cari serial / nama barang..." class="input input-bordered input-xs w-full pl-7 font-mono">
                  <span class="absolute left-2.5 top-1.5 text-xs opacity-50">🔍</span>
                </div>

                <!-- Scrollable List of Rented Assets -->
                <div id="sewaret-rented-assets-list" class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                  <div class="text-center py-8 text-base-content/40 text-xs">Memuat unit sedang disewa...</div>
                </div>
              </div>
            </div>

            <!-- Panel Kanan: Form Pengembalian Unit -->
            <div class="lg:col-span-7">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
                <h3 class="font-extrabold text-base mb-4">Pengembalian Unit Sewa (Sewa Kembali)</h3>
                <form onsubmit="handleSewaReturnSubmit(event)" class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">No. Surat Jalan / Dokumen Kembali</span></label>
                      <input type="text" id="sewaret-dokumen" class="input input-bordered input-sm w-full" placeholder="SJK-2026-xxx">
                    </div>
                    <div class="form-control">
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan Pengembalian</span></label>
                      <input type="text" id="sewaret-catatan" class="input input-bordered input-sm w-full" placeholder="Keterangan kondisi fisik dll">
                    </div>
                  </div>

                  <!-- Scanner Box -->
                  <div class="bg-base-200/80 p-3.5 rounded-2xl border border-base-300 space-y-2">
                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/60 flex items-center justify-between">
                      <span>⚡ Scan / Cari Unit Asset yang Kembali</span>
                      <span class="text-[10px] text-base-content/40 font-normal">Tekan Enter untuk menambah</span>
                    </div>
                    <div class="flex gap-2">
                      <input type="text" id="sewaret-scan-input" class="input input-bordered input-sm flex-1 font-mono font-bold" placeholder="Masukkan / Scan QR Code Unit Asset..." onkeydown="if(event.key==='Enter'){event.preventDefault();addAssetToSewaReturnList();}">
                      <button type="button" class="btn btn-neutral btn-sm font-bold" onclick="addAssetToSewaReturnList()">+ Cari & Tambah</button>
                    </div>
                  </div>

                  <!-- List of returning assets -->
                  <div class="border border-base-300 rounded-2xl p-4 bg-base-50 max-h-56 overflow-y-auto">
                    <div class="flex items-center justify-between mb-2">
                      <div class="text-xs font-bold text-base-content/70">📋 Unit yang Akan Dikembalikan (Menunggu QC Ulang):</div>
                      <span id="sewaret-selected-count" class="badge badge-sm badge-warning font-bold">0 Unit</span>
                    </div>
                    <table class="table table-sm table-zebra w-full text-xs">
                      <thead><tr>
                        <th>Serial Number</th><th>Nama Barang</th><th>Status Pasca Kembali</th><th>Rak Penempatan Kembali</th><th class="text-center">Aksi</th>
                      </tr></thead>
                      <tbody id="sewaret-list-tbody">
                        <tr><td colspan="5" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>
                      </tbody>
                    </table>
                  </div>

                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-xs">↩️ Proses Pengembalian (Masuk & Kunci untuk QC Ulang)</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ DAFTAR UNIT ASSET PAGE ═══ -->
      <div class="page" id="pg-sewaunits">
        <!-- SUB-NAV TABS (SEWA UNITS) -->
        <div class="flex items-center gap-1.5 mb-6 bg-base-100 p-1.5 rounded-2xl border border-base-200 shadow-xs w-fit">
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewadash')">📈 Dashboard Sewa</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewamutasi')">🔄 Transaksi Sewa</button>
          <button class="btn btn-sm btn-primary text-primary-content shadow-xs font-bold text-xs rounded-xl" onclick="goPage('sewaunits')">🔍 Daftar Unit</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewacard')">📄 Riwayat Asset</button>
        </div>

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
        <!-- SUB-NAV TABS (SEWA CARD) -->
        <div class="flex items-center gap-1.5 mb-6 bg-base-100 p-1.5 rounded-2xl border border-base-200 shadow-xs w-fit">
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewadash')">📈 Dashboard Sewa</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewamutasi')">🔄 Transaksi Sewa</button>
          <button class="btn btn-sm btn-ghost text-base-content/70 hover:text-base-content font-bold text-xs rounded-xl" onclick="goPage('sewaunits')">🔍 Daftar Unit</button>
          <button class="btn btn-sm btn-primary text-primary-content shadow-xs font-bold text-xs rounded-xl" onclick="goPage('sewacard')">📄 Riwayat Asset</button>
        </div>

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

      <!-- ═══ MASTER PROGRAM KIT PAGE ═══ -->
      <div class="page" id="pg-programs">
        <div class="card bg-base-100 border border-base-300 shadow-sm p-5 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-2xl">🤖</span>
                <h2 class="text-lg font-extrabold text-base-content tracking-tight">Master Program Kit (BOM Packages)</h2>
                <span class="badge badge-primary badge-sm font-bold text-[10px]">CASCADING BOM</span>
              </div>
              <p class="text-xs text-base-content/60 mt-1">
                Definisikan program kit pembelajaran/pelatihan beserta modul-modul MIKMS yang menyusunnya. Paket ini otomatis tersinkron ke alur pesanan paket.
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button class="btn btn-sm btn-primary text-xs font-bold shadow-sm" onclick="openProgramModal()">
                + Tambah Program Kit
              </button>
            </div>
          </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden p-0">
          <div class="overflow-x-auto max-h-[calc(100vh-270px)]">
            <table class="table table-zebra table-pin-rows table-sm">
              <thead class="bg-base-200">
                <tr>
                  <th>Kode Program</th>
                  <th>Nama Program</th>
                  <th>Daftar Modul Terkait</th>
                  <th class="text-right">Est. Komponen (BOM)</th>
                  <th class="text-center">Status</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="programs-tbody">
                <tr><td colspan="6" class="text-center py-8 text-base-content/40">Memuat data program kit...</td></tr>
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
                  Sistem Produksi & Inventori Gudang: Perakitan modul BOM, QC kelayakan serial unit, packaging bundling, manajemen stok finish goods, serta dispatch & retur sewa.
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
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Total Unit Finish Goods</div>
                <div class="text-lg font-extrabold text-base-content" id="mk-stat-total-boxes">0</div>
              </div>
            </div>
            <div class="bg-base-100/80 backdrop-blur rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-success/10 text-success flex items-center justify-center text-xl font-bold">✅</div>
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">In Stock (Lolos QC)</div>
                <div class="text-lg font-extrabold text-success" id="mk-stat-ready-boxes">0</div>
              </div>
            </div>
            <div class="bg-base-100/80 backdrop-blur rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-warning/10 text-warning flex items-center justify-center text-xl font-bold">⚠️</div>
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Menunggu QC</div>
                <div class="text-lg font-extrabold text-warning" id="mk-stat-loan-boxes">0</div>
              </div>
            </div>
            <div class="bg-base-100/80 backdrop-blur rounded-xl p-3 border border-base-200 shadow-xs flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-xl font-bold">📤</div>
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Dispatched (Keluar)</div>
                <div class="text-lg font-extrabold text-primary" id="mk-stat-repair-boxes">0</div>
              </div>
            </div>
          </div>
        </div>

        <!-- WORKFLOW STEPPER BANNER (INTERACTIVE 5-STAGE PIPELINE) -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-5 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-base-200">
            <div class="flex items-center gap-2">
              <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></span>
              <h3 class="text-xs font-black uppercase tracking-wider text-base-content/80">Alur Operasional Finish Goods MIKMS</h3>
              <span class="badge badge-sm badge-neutral text-[10px] font-bold">5 Tahap Berurutan</span>
            </div>
            <span class="text-[11px] text-base-content/50 font-medium hidden sm:inline">Pilih tahap untuk langsung membuka modul kerja</span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5" id="mikms-stepper">
            <!-- Step 1 -->
            <div class="mikms-step-card p-3 rounded-2xl border border-base-200 bg-base-100 hover:border-primary/50 hover:bg-primary/5 cursor-pointer transition-all duration-200 flex flex-col justify-between group select-none shadow-xs" data-step="1" onclick="switchMikmsTab('packageorder')">
              <div class="flex items-center justify-between mb-2">
                <span class="mikms-step-num text-[10px] font-black px-2 py-0.5 rounded-md bg-base-200 text-base-content/70 group-hover:bg-primary group-hover:text-primary-content transition">01</span>
                <span class="text-xl">⚙️</span>
              </div>
              <div>
                <div class="text-xs font-extrabold text-base-content group-hover:text-primary transition">1. Perakitan Kit (MLK)</div>
                <div class="text-[10px] text-base-content/60 leading-tight mt-0.5">Potong BOM & Terbitkan Serial MLK</div>
              </div>
            </div>

            <!-- Step 2 -->
            <div class="mikms-step-card p-3 rounded-2xl border border-base-200 bg-base-100 hover:border-primary/50 hover:bg-primary/5 cursor-pointer transition-all duration-200 flex flex-col justify-between group select-none shadow-xs" data-step="2" onclick="switchMikmsTab('unitregistry')">
              <div class="flex items-center justify-between mb-2">
                <span class="mikms-step-num text-[10px] font-black px-2 py-0.5 rounded-md bg-base-200 text-base-content/70 group-hover:bg-primary group-hover:text-primary-content transition">02</span>
                <span class="text-xl">🔢</span>
              </div>
              <div>
                <div class="text-xs font-extrabold text-base-content group-hover:text-primary transition">2. Unit Registry & Stiker</div>
                <div class="text-[10px] text-base-content/60 leading-tight mt-0.5">Serial unik & cetak label thermal</div>
              </div>
            </div>

            <!-- Step 3 -->
            <div class="mikms-step-card p-3 rounded-2xl border border-base-200 bg-base-100 hover:border-primary/50 hover:bg-primary/5 cursor-pointer transition-all duration-200 flex flex-col justify-between group select-none shadow-xs" data-step="3" onclick="switchMikmsTab('qcunit')">
              <div class="flex items-center justify-between mb-2">
                <span class="mikms-step-num text-[10px] font-black px-2 py-0.5 rounded-md bg-base-200 text-base-content/70 group-hover:bg-primary group-hover:text-primary-content transition">03</span>
                <span class="text-xl">✅</span>
              </div>
              <div>
                <div class="text-xs font-extrabold text-base-content group-hover:text-primary transition">3. QC Unit Checklist</div>
                <div class="text-[10px] text-base-content/60 leading-tight mt-0.5">Checklist lolos/tidak per produk</div>
              </div>
            </div>

            <!-- Step 4 -->
            <div class="mikms-step-card p-3 rounded-2xl border border-base-200 bg-base-100 hover:border-primary/50 hover:bg-primary/5 cursor-pointer transition-all duration-200 flex flex-col justify-between group select-none shadow-xs" data-step="4" onclick="switchMikmsTab('bundling')">
              <div class="flex items-center justify-between mb-2">
                <span class="mikms-step-num text-[10px] font-black px-2 py-0.5 rounded-md bg-base-200 text-base-content/70 group-hover:bg-primary group-hover:text-primary-content transition">04</span>
                <span class="text-xl">🎁</span>
              </div>
              <div>
                <div class="text-xs font-extrabold text-base-content group-hover:text-primary transition">4. Packaging ROBO</div>
                <div class="text-[10px] text-base-content/60 leading-tight mt-0.5">Bundling MLK + Jimu (Opsi A)</div>
              </div>
            </div>

            <!-- Step 5 -->
            <div class="mikms-step-card p-3 rounded-2xl border border-base-200 bg-base-100 hover:border-primary/50 hover:bg-primary/5 cursor-pointer transition-all duration-200 flex flex-col justify-between group select-none shadow-xs col-span-2 sm:col-span-1" data-step="5" onclick="switchMikmsTab('dispatch')">
              <div class="flex items-center justify-between mb-2">
                <span class="mikms-step-num text-[10px] font-black px-2 py-0.5 rounded-md bg-base-200 text-base-content/70 group-hover:bg-primary group-hover:text-primary-content transition">05</span>
                <span class="text-xl">📤</span>
              </div>
              <div>
                <div class="text-xs font-extrabold text-base-content group-hover:text-primary transition">5. Dispatch & Retur</div>
                <div class="text-[10px] text-base-content/60 leading-tight mt-0.5">Kirim sewa/beli & catat retur</div>
              </div>
            </div>
          </div>
        </div>

        <!-- TABS BAR (GROUPED & LOGICALLY ORDERED) -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-3 mb-6">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- GROUP 1: FINISH GOODS & UNIT (UTAMA) -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 scrollbar-none">
              <span class="text-[10px] font-black uppercase tracking-wider text-base-content/40 px-2 shrink-0">Unit & Produk:</span>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="packageorder" onclick="switchMikmsTab('packageorder')">
                ⚙️ Perakitan Kit (MLK)
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn active bg-primary text-primary-content hover:bg-primary" data-tab="unitregistry" onclick="switchMikmsTab('unitregistry')">
                🔢 Unit Registry
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="qcunit" onclick="switchMikmsTab('qcunit')">
                ✅ QC Unit
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="bundling" onclick="switchMikmsTab('bundling')">
                🎁 Packaging ROBO
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="dispatch" onclick="switchMikmsTab('dispatch')">
                📤 Dispatch & Retur
              </button>
            </div>

            <div class="hidden lg:block w-px h-6 bg-base-300"></div>

            <!-- GROUP 2: MODUL & MATERIAL RW -->
            <div class="flex items-center gap-1.5 overflow-x-auto pt-2 lg:pt-0 border-t lg:border-t-0 border-base-200 scrollbar-none">
              <span class="text-[10px] font-black uppercase tracking-wider text-base-content/40 px-2 shrink-0">Modul & RW:</span>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="production" onclick="switchMikmsTab('production')">
                🧩 Sub-Assembly Modul (RW)
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="modulestocks" onclick="switchMikmsTab('modulestocks')">
                🏭 Stok Modul (RW)
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="bomlist" onclick="switchMikmsTab('bomlist')">
                📑 Master BOM
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="repair" onclick="switchMikmsTab('repair')">
                🔧 Repair
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="opname" onclick="switchMikmsTab('opname')">
                📋 Opname
              </button>
              <button class="btn btn-sm btn-ghost font-bold text-xs shrink-0 rounded-xl transition mikms-tab-btn" data-tab="logs" onclick="switchMikmsTab('logs')">
                📜 Audit Log
              </button>
            </div>
          </div>
        </div>


        <!-- ═══ TAB: UNIT REGISTRY ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-unitregistry">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
              <div>
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2">
                  <span>🔢</span> Unit Registry
                </h3>
                <p class="text-xs text-base-content/50 mt-0.5">Setiap unit fisik tercatat dengan nomor seri unik sepanjang lifecycle-nya.</p>
              </div>
              <div class="flex gap-2 flex-wrap">
                <input type="text" id="ur-search" class="input input-bordered input-sm w-36" placeholder="Cari serial..." oninput="loadUnitRegistry()">
                <select id="ur-filter-product" class="select select-bordered select-sm" onchange="loadUnitRegistry()">
                  <option value="">Semua Produk</option>
                </select>
                <select id="ur-filter-status" class="select select-bordered select-sm" onchange="loadUnitRegistry()">
                  <option value="">Semua Status</option>
                  <option value="qc_pending">⏳ Menunggu QC</option>
                  <option value="in_stock">✅ In Stock</option>
                  <option value="dispatched">📤 Terkirim</option>
                  <option value="returned">↩️ Dikembalikan</option>
                  <option value="repair">🔧 Perbaikan</option>
                  <option value="disposed">🗑️ Dilepas</option>
                </select>
                <button class="btn btn-sm btn-outline btn-secondary font-bold text-xs shrink-0" onclick="openMicrobitBulkModal()" title="Cetak Lembaran Stiker 1x1 cm untuk Board Micro:bit (Bulk)">
                  🏷️ Micro:bit Grid (1x1cm)
                </button>
                <button class="btn btn-sm btn-ghost" onclick="loadUnitRegistry()">🔄</button>
              </div>
            </div>

            <!-- Toolbar Seleksi Unit Registry -->
            <div id="ur-selection-toolbar" class="hidden flex flex-wrap items-center justify-between gap-2 bg-secondary/10 border border-secondary/30 rounded-xl p-2.5 mb-3">
              <div class="flex items-center gap-2 text-xs font-bold text-base-content">
                <span class="badge badge-secondary badge-sm text-white font-bold" id="ur-selected-count">0</span>
                <span>Unit fisik dipilih</span>
              </div>
              <div class="flex items-center gap-1.5 flex-wrap">
                <button class="btn btn-xs btn-primary font-bold text-white shadow-xs" onclick="printSelectedThermalLabels()">
                  🖨️ Cetak Stiker Boks Thermal (<span class="ur-selected-num">0</span>)
                </button>
                <button class="btn btn-xs btn-secondary font-bold text-white shadow-xs" onclick="openSelectedMicrobitModal()">
                  🏷️ Cetak Stiker Micro:bit Grid 1x1cm (<span class="ur-selected-num">0</span>)
                </button>
                <button class="btn btn-xs btn-ghost text-xs" onclick="clearUrSelection()">✕ Batal</button>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="table table-xs w-full">
                <thead>
                  <tr class="text-[10px] uppercase tracking-wider text-base-content/50">
                    <th class="w-8 text-center">
                      <input type="checkbox" id="ur-check-all" class="checkbox checkbox-xs" onchange="toggleUrCheckAll(this)" title="Pilih Semua Unit di Halaman Ini">
                    </th>
                    <th>Serial Number</th>
                    <th>Produk</th>
                    <th>Status</th>
                    <th>Sumber</th>
                    <th>QC Terakhir</th>
                    <th>Tgl Masuk</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody id="ur-table-body">
                  <tr><td colspan="8" class="text-center py-8 text-base-content/40">Memuat data unit...</td></tr>
                </tbody>
              </table>
            </div>
            <div id="ur-pagination" class="flex justify-center mt-3"></div>
          </div>
        </div>

        <!-- ═══ TAB: QC UNIT (Checklist) ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-qcunit">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2 mb-1">
              <span>✅</span> Quality Control Unit — Checklist
            </h3>
            <p class="text-xs text-base-content/50 mb-5 leading-relaxed">
              Scan atau ketik serial number unit. Sistem menampilkan checklist QC sesuai jenis produk.
              Semua poin harus <strong>LULUS</strong> agar unit masuk stok.
            </p>

            <!-- Input Serial -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
              <div class="sm:col-span-2">
                <label class="label text-xs font-bold py-1">Serial Number Unit *</label>
                <div class="flex gap-2">
                  <input type="text" id="qcu-serial" class="input input-bordered input-sm w-full font-mono font-bold uppercase" placeholder="MLK-00001" oninput="this.value=this.value.toUpperCase()">
                  <button class="btn btn-sm btn-primary" onclick="loadQcChecklist()">Muat Checklist →</button>
                </div>
              </div>
              <div>
                <label class="label text-xs font-bold py-1">Tanggal QC</label>
                <input type="date" id="qcu-date" class="input input-bordered input-sm w-full">
              </div>
              <div class="sm:col-span-2">
                <label class="label text-xs font-bold py-1">Diperiksa Oleh *</label>
                <input type="text" id="qcu-checker" class="input input-bordered input-sm w-full" placeholder="Nama teknisi QC">
              </div>
              <div>
                <label class="label text-xs font-bold py-1">Tipe QC</label>
                <select id="qcu-type" class="select select-bordered select-sm w-full">
                  <option value="production">Produksi Baru</option>
                  <option value="rental_return">QC Ulang (Setelah Sewa Kembali)</option>
                  <option value="return">Setelah Retur</option>
                </select>
              </div>
            </div>

            <!-- Info Unit -->
            <div id="qcu-unit-info" class="hidden alert alert-info text-xs mb-4 flex-col items-start gap-1">
              <span id="qcu-unit-info-text"></span>
            </div>

            <!-- Checklist Items -->
            <div id="qcu-checklist-wrap" class="hidden">
              <div class="font-bold text-sm mb-3">📋 Checklist Kondisi Unit</div>
              <div id="qcu-checklist-items" class="space-y-2 mb-4"></div>

              <div class="mb-4">
                <label class="label text-xs font-bold py-1">Catatan Defek / Masalah (jika ada)</label>
                <textarea id="qcu-defect" class="textarea textarea-bordered textarea-sm w-full" rows="2" placeholder="Contoh: LED tidak menyala, kabel USB longgar..."></textarea>
              </div>

              <!-- Status Preview -->
              <div id="qcu-status-preview" class="flex items-center gap-3 p-3 rounded-xl bg-base-200 mb-4 text-sm font-bold">
                <span id="qcu-preview-icon">—</span>
                <span id="qcu-preview-text" class="text-base-content/60">Isi checklist untuk melihat hasil prediksi</span>
              </div>

              <button class="btn btn-primary btn-sm font-bold" onclick="submitQcUnit()">
                💾 Simpan Hasil QC
              </button>
            </div>
          </div>
        </div>

        <!-- ═══ TAB: DISPATCH ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-dispatch">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2 mb-1">
              <span>📤</span> Dispatch Unit — Pengeluaran dari Gudang
            </h3>
            <p class="text-xs text-base-content/50 mb-5 leading-relaxed">
              Keluarkan unit yang sudah lolos QC dari gudang. Tentukan tipe (sewa/beli) dan tujuan secara opsional.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="label text-xs font-bold py-1">Tanggal Dispatch</label>
                <input type="date" id="dsp-date" class="input input-bordered input-sm w-full" required>
              </div>
              <div>
                <label class="label text-xs font-bold py-1">Tipe *</label>
                <select id="dsp-type" class="select select-bordered select-sm w-full font-semibold">
                  <option value="beli">🛒 Beli (Penjualan Permanen)</option>
                  <option value="sewa">🔄 Sewa (Khusus Finish Goods)</option>
                </select>
                <span class="text-[10px] text-base-content/50 mt-1 block">ℹ️ Sewa hanya berlaku untuk produk Finish Goods yang diizinkan (can_rent).</span>
              </div>
              <div class="sm:col-span-2">
                <label class="label text-xs font-bold py-1">Serial Number Unit (bisa lebih dari 1, pisahkan koma)</label>
                <input type="text" id="dsp-serials" class="input input-bordered input-sm w-full font-mono font-semibold uppercase" placeholder="MLK-00001, MLK-00002, ROBO-00005" oninput="this.value=this.value.toUpperCase()">
              </div>
              <div>
                <label class="label text-xs font-bold py-1">Tujuan (opsional)</label>
                <input type="text" id="dsp-destination" class="input input-bordered input-sm w-full" placeholder="Contoh: Customer B2B / Mitra / Kantor Pusat">
              </div>
              <div>
                <label class="label text-xs font-bold py-1">Dikirim Oleh *</label>
                <input type="text" id="dsp-by" class="input input-bordered input-sm w-full" placeholder="Nama petugas" required>
              </div>
              <div class="sm:col-span-2">
                <label class="label text-xs font-bold py-1">Catatan</label>
                <textarea id="dsp-notes" class="textarea textarea-bordered textarea-sm w-full" rows="2" placeholder="Catatan tambahan..."></textarea>
              </div>
            </div>

            <div class="flex gap-2">
              <button class="btn btn-primary btn-sm font-bold" onclick="submitDispatch()">
                📤 Proses Dispatch
              </button>
            </div>

            <!-- Retur Unit -->
            <div class="divider text-xs">atau — Proses Retur Unit Sewa</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="label text-xs font-bold py-1">Serial Unit yang Dikembalikan</label>
                <input type="text" id="ret-serial" class="input input-bordered input-sm w-full font-mono font-bold uppercase" placeholder="MLK-00001" oninput="this.value=this.value.toUpperCase()">
              </div>
              <div>
                <label class="label text-xs font-bold py-1">Diterima Oleh</label>
                <input type="text" id="ret-received" class="input input-bordered input-sm w-full" placeholder="Nama penerima">
              </div>
              <div class="sm:col-span-2">
                <label class="label text-xs font-bold py-1">Catatan Kondisi Retur</label>
                <textarea id="ret-notes" class="textarea textarea-bordered textarea-sm w-full" rows="2" placeholder="Contoh: Kabel USB hilang, boks lecet..."></textarea>
              </div>
            </div>
            <div class="mt-3">
              <button class="btn btn-warning btn-sm font-bold" onclick="submitReturn()">
                ↩️ Catat Retur (Unit → Wajib QC Ulang)
              </button>
            </div>
          </div>
        </div>

        <!-- ═══ TAB: BUNDLING & PACKAGING (Robotic Explorer = MLK + JIMU) ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-bundling">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2 mb-1">
              <span>🎁</span> Packaging & Bundling: Robotic Explorer
            </h3>
            <p class="text-xs text-base-content/50 mb-5 leading-relaxed">
              Robotic Explorer memiliki <strong>packaging luar tersendiri</strong>. Kemas <strong>1 Unit Microbit Learning Kit (MLK)</strong> + <strong>1 Unit Robotik Jimu (JIMU)</strong> yang telah lolos QC (<span class="badge badge-success badge-xs">In Stock</span>) ke dalam boks Robotic Explorer. Sistem akan men-generate nomor seri boks luar (contoh: <code>ROBO-00001</code>).
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <!-- Pilih MLK -->
              <div>
                <label class="label text-xs font-bold py-1">
                  <span>Unit Microbit Learning Kit (MLK) *</span>
                  <span id="bdl-mlk-count" class="text-[10px] text-base-content/50">Memuat stok...</span>
                </label>
                <select id="bdl-mlk-select" class="select select-bordered select-sm w-full font-mono font-semibold" onchange="syncBdlInputs()">
                  <option value="">— Pilih Unit MLK Ready —</option>
                </select>
                <div class="text-[10px] text-base-content/40 mt-1">atau scan/ketik manual:</div>
                <input type="text" id="bdl-mlk-manual" class="input input-bordered input-xs w-full font-mono uppercase mt-0.5" placeholder="MLK-00001" oninput="this.value=this.value.toUpperCase()">
              </div>

              <!-- Pilih JIMU -->
              <div>
                <label class="label text-xs font-bold py-1">
                  <span>Unit Robotik Jimu (JIMU) *</span>
                  <span id="bdl-jimu-count" class="text-[10px] text-base-content/50">Memuat stok...</span>
                </label>
                <select id="bdl-jimu-select" class="select select-bordered select-sm w-full font-mono font-semibold" onchange="syncBdlInputs()">
                  <option value="">— Pilih Unit JIMU Ready —</option>
                </select>
                <div class="text-[10px] text-base-content/40 mt-1">atau scan/ketik manual:</div>
                <input type="text" id="bdl-jimu-manual" class="input input-bordered input-xs w-full font-mono uppercase mt-0.5" placeholder="JIMU-00001" oninput="this.value=this.value.toUpperCase()">
              </div>

              <!-- Dikemas Oleh -->
              <div>
                <label class="label text-xs font-bold py-1">Dikemas Oleh *</label>
                <input type="text" id="bdl-packed-by" class="input input-bordered input-sm w-full font-semibold" placeholder="Nama staf packaging / gudang" required>
              </div>

              <!-- Catatan -->
              <div>
                <label class="label text-xs font-bold py-1">Catatan Packaging</label>
                <input type="text" id="bdl-notes" class="input input-bordered input-sm w-full" placeholder="Contoh: Dus luar mulus, busa pelindung lengkap">
              </div>
            </div>

            <div class="flex gap-2">
              <button class="btn btn-primary btn-sm font-bold" onclick="submitBundling()">
                🎁 Kemas Boks & Generate Serial ROBO
              </button>
              <button class="btn btn-sm btn-ghost" onclick="loadBundlingTab()">🔄 Refresh Unit Ready</button>
            </div>

            <!-- Hasil Bundling -->
            <div id="bdl-result" class="hidden mt-4 p-4 bg-primary/10 border border-primary/30 rounded-xl">
              <div class="font-bold text-primary text-sm mb-1">✅ Sukses! Boks Packaging Robotic Explorer Berhasil Didaftarkan.</div>
              <div id="bdl-result-details" class="text-xs text-base-content/70 leading-relaxed font-mono"></div>
              <div class="mt-3 flex gap-2">
                <button id="bdl-print-btn" class="btn btn-xs btn-primary font-bold">
                  🖨️ Cetak Stiker Boks Luar (ROBO)
                </button>
                <button id="bdl-qc-btn" class="btn btn-xs btn-warning font-bold">
                  ✅ Lanjut ke QC Boks Luar →
                </button>
              </div>
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

        <!-- ═══ TAB: SUB-ASSEMBLY MODUL (RW) ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-production">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form Perakitan Sub-Modul -->
            <div class="lg:col-span-6 space-y-4">
              <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
                <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 mb-2 flex items-center gap-2">
                  <span>🧩</span> Sub-Assembly Modul MIKMS (Buffer Stock RW)
                </h3>
                <p class="text-xs text-base-content/60 mb-4 leading-relaxed">
                  Gunakan form ini untuk merakit stok modul satuan (M01–M07) dari bahan mentah ke rak intermediate (RW). Modul ini berfungsi sebagai tabungan stok perakitan kit MLK atau suku cadang maintenance.
                </p>
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
                      <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Tujuan Penyimpanan</span></label>
                      <div class="input input-bordered input-sm bg-base-200/50 flex items-center text-xs font-semibold text-base-content/70">
                        Rak Modul Jadi (RW)
                      </div>
                      <input type="hidden" id="mk-prod-box-id" value="">
                    </div>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Teknisi / PIC Perakitan *</span></label>
                    <input type="text" id="mk-prod-pic" class="input input-bordered input-sm font-semibold w-full" placeholder="Nama Teknisi perakit" required>
                  </div>
                  <div class="form-control">
                    <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Catatan Produksi (Batch / Kondisi)</span></label>
                    <textarea id="mk-prod-notes" rows="2" class="textarea textarea-bordered textarea-sm w-full font-medium" placeholder="Contoh: Perakitan batch cadangan modul sensor..."></textarea>
                  </div>
                  <div class="p-3 rounded-xl bg-info/10 border border-info/20 text-xs text-info-content flex items-start gap-2">
                    <span class="text-sm">💡</span>
                    <span>Saat Anda menyimpan perakitan modul, sistem akan <strong>secara otomatis memotong saldo stok komponen (BOM)</strong> di gudang secara real-time.</span>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm w-full font-bold shadow-md" id="mk-prod-submit-btn">
                    🧩 Proses Sub-Assembly & Potong Bahan Mentah
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

        <!-- ═══ TAB: PESAN PAKET (PACKAGE ORDER + CASCADING BOM) ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-packageorder">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5 mb-4">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2 mb-4">
              <span>⚙️</span> Perakitan Kit MLK — Smart Cascading BOM & Auto-Serial Unit
            </h3>
            <p class="text-xs text-base-content/50 mb-4 leading-relaxed">
              Tentukan target jumlah perakitan kit MLK jadi. Sistem otomatis memeriksa: <strong>Tier 1</strong> — ambil stok modul jadi yang ready di rak (RW). <strong>Tier 2</strong> — otomatis potong bahan baku mentah (BOM) jika modul kurang. Setelah diproses, nomor seri unit <code>MLK-xxxxx</code> otomatis terbit berstatus <code>qc_pending</code> dan siap dicetak stiker thermal boks & stiker board 1x1cm.
            </p>

            <!-- FORM INPUT PESANAN -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <div class="flex items-center justify-between">
                  <label class="label text-xs font-bold py-1">Program Kit</label>
                  <a href="javascript:void(0)" class="text-[10px] font-bold text-primary hover:underline py-1 admin-section" style="display:none" onclick="goPage('programs')">⚙️ Kelola Master Program</a>
                </div>
                <select id="pkg-program" class="select select-bordered select-sm w-full font-semibold">
                  <option value="MLK">Microbit Learning Kit (MLK) — 95 item/paket</option>
                  <option value="ROBOTIC">Robotic Explorer — 81 item/paket</option>
                </select>
              </div>
              <div>
                <label class="label text-xs font-bold">Jumlah Paket *</label>
                <input type="number" id="pkg-qty" class="input input-bordered input-sm w-full font-semibold" placeholder="contoh: 5" min="1" max="100" value="1">
              </div>
              <div>
                <label class="label text-xs font-bold">Referensi / Batch Produksi (opsional)</label>
                <input type="text" id="pkg-customer" class="input input-bordered input-sm w-full" placeholder="Contoh: Batch produksi Sep 2026">
              </div>
              <div>
                <label class="label text-xs font-bold">Teknisi / Petugas *</label>
                <input type="text" id="pkg-orderedby" class="input input-bordered input-sm w-full" placeholder="Nama petugas perakitan">
              </div>
            </div>
            <div class="mb-4">
              <label class="label text-xs font-bold">Catatan Produksi (opsional)</label>
              <textarea id="pkg-notes" class="textarea textarea-bordered textarea-sm w-full" rows="2" placeholder="Catatan tambahan..."></textarea>
            </div>

            <div class="flex flex-wrap gap-2">
              <button class="btn btn-sm btn-info text-white font-bold" onclick="simulatePackageOrder()">
                🔍 Preview Kebutuhan Bahan (BOM)
              </button>
              <button class="btn btn-sm btn-success text-white font-bold" id="pkg-confirm-btn" style="display:none" onclick="executePackageOrder()">
                ⚙️ Konfirmasi & Proses Perakitan Kit MLK
              </button>
            </div>
          </div>

          <!-- SUCCESS NOTIFICATION BANNER -->
          <div id="pkg-order-success-banner" style="display:none" class="mb-4"></div>

          <!-- PREVIEW PANEL -->
          <div id="pkg-preview-panel" style="display:none">
            <!-- Summary Alert -->
            <div id="pkg-summary-alert" class="mb-4"></div>

            <!-- Modules Breakdown -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-5 mb-4">
              <h4 class="font-extrabold text-xs uppercase tracking-wider text-base-content/60 mb-3">📊 Breakdown per Modul</h4>
              <div class="overflow-x-auto border border-base-200 rounded-xl">
                <table class="table table-xs table-zebra w-full">
                  <thead class="bg-base-200">
                    <tr>
                      <th>Kode</th>
                      <th>Nama Modul</th>
                      <th class="text-center">Dibutuhkan</th>
                      <th class="text-center">Stok Jadi</th>
                      <th class="text-center">Dari Rak</th>
                      <th class="text-center">Perlu Dirakit</th>
                    </tr>
                  </thead>
                  <tbody id="pkg-modules-tbody"></tbody>
                </table>
              </div>
            </div>

            <!-- Raw Materials Needed -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-5 mb-4" id="pkg-raw-section" style="display:none">
              <h4 class="font-extrabold text-xs uppercase tracking-wider text-base-content/60 mb-3">🧱 Bahan Baku yang Perlu Dipotong (Tier 2)</h4>
              <div class="overflow-x-auto border border-base-200 rounded-xl">
                <table class="table table-xs table-zebra w-full">
                  <thead class="bg-base-200">
                    <tr>
                      <th>Kode</th>
                      <th>Nama Komponen</th>
                      <th class="text-center">Dibutuhkan</th>
                      <th class="text-center">Stok Tersedia</th>
                      <th class="text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody id="pkg-raw-tbody"></tbody>
                </table>
              </div>
            </div>

            <!-- Shortages -->
            <div id="pkg-shortages-section" style="display:none" class="mb-4">
              <div class="alert alert-error shadow-sm">
                <span class="font-bold">❌ Bahan Baku Kurang:</span>
                <ul id="pkg-shortages-list" class="list-disc list-inside text-sm mt-1"></ul>
              </div>
            </div>
          </div>

          <!-- RIWAYAT PESANAN PAKET -->
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2 mb-4">
              <span>📋</span> Riwayat Pesanan Paket
            </h3>
            <div class="overflow-x-auto border border-base-200 rounded-xl">
              <table class="table table-xs table-zebra table-pin-rows w-full">
                <thead class="bg-base-200">
                  <tr>
                    <th>Tanggal</th>
                    <th>Program</th>
                    <th class="text-center">Qty</th>
                    <th>Customer</th>
                    <th>Petugas</th>
                    <th>Status</th>
                    <th>Detail</th>
                  </tr>
                </thead>
                <tbody id="pkg-orders-tbody">
                  <tr><td colspan="7" class="text-center py-8 text-base-content/40">Memuat riwayat...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ═══ TAB: STOK MODUL JADI ═══ -->
        <div class="mikms-tab-content hidden" id="mk-panel-modulestocks">
          <div class="card bg-base-100 border border-base-300 shadow-sm p-5 mb-4">
            <h3 class="font-extrabold text-sm uppercase tracking-wider text-base-content/70 flex items-center gap-2 mb-4">
              <span>🏭</span> Inventori Modul Jadi di Rak
            </h3>
            <p class="text-xs text-base-content/50 mb-4 leading-relaxed">
              Stok modul yang sudah dirakit dan siap dipakai untuk package order. Adjust manual jika ada hasil rakitan baru masuk.
            </p>

            <div class="overflow-x-auto border border-base-200 rounded-xl mb-4">
              <table class="table table-xs table-zebra w-full">
                <thead class="bg-base-200">
                  <tr>
                    <th>Kode</th>
                    <th>Nama Modul</th>
                    <th class="text-center">Stok Ready</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody id="modstock-tbody">
                  <tr><td colspan="4" class="text-center py-8 text-base-content/40">Memuat stok modul...</td></tr>
                </tbody>
              </table>
            </div>

            <!-- Quick Adjust Form -->
            <div class="card bg-base-200/50 border border-base-300 p-4">
              <h4 class="font-bold text-xs uppercase tracking-wider text-base-content/60 mb-3">⚡ Penyesuaian Stok Modul Cepat</h4>
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                  <label class="label text-xs font-bold">Modul</label>
                  <select id="modadj-module" class="select select-bordered select-sm w-full font-semibold"></select>
                </div>
                <div>
                  <label class="label text-xs font-bold">Penyesuaian (+/-)</label>
                  <input type="number" id="modadj-qty" class="input input-bordered input-sm w-full font-semibold" placeholder="+10 atau -3">
                </div>
                <div>
                  <label class="label text-xs font-bold">Alasan</label>
                  <input type="text" id="modadj-reason" class="input input-bordered input-sm w-full" placeholder="Hasil rakitan hari ini">
                </div>
                <div class="flex items-end">
                  <button class="btn btn-sm btn-primary text-white font-bold w-full" onclick="adjustModuleStock()">
                    💾 Simpan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ═════════════════════════════════════════════
           MIKMS PANDUAN & SOP ALUR KERJA (HOW-TO)
           ═════════════════════════════════════════════ -->
      <div class="page" id="pg-mikmsguide">
        <!-- HEADER UTAMA SOP 2026 -->
        <div class="card bg-gradient-to-r from-accent/15 via-base-100 to-primary/15 border border-accent/25 shadow-sm p-6 mb-6">
          <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-accent text-accent-content flex items-center justify-center text-3xl font-extrabold shadow-md shadow-accent/30 shrink-0">
                📖
              </div>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h2 class="text-xl font-extrabold text-base-content tracking-tight">SOP &amp; Panduan Operasional Terpadu PT Erlass 2026</h2>
                  <span class="badge badge-accent badge-sm font-extrabold text-[10px] tracking-wider uppercase">SOP RESMI PT ERLASS 2026 V3.0</span>
                  <span class="badge badge-outline badge-primary badge-sm font-bold text-[10px]">AUDIT READY</span>
                </div>
                <p class="text-xs text-base-content/70 mt-1 max-w-4xl leading-relaxed">
                  Standar Sistem Operasional Perusahaan (SOP 2026) PT Erlass Proaktif Indonesia. Mengatur tata kelola pesanan &amp; MoU, pengiriman Surat Jalan (DO 3 Rangkap), penerimaan vendor 1 pintu via Universal Smart Scanner Hub, perakitan Bill of Materials (BOM), kontrol kualitas (QC Checklist Pass/Fail), label barcode thermal/QR, hingga prosedur sewa &amp; retur berstatus <em>Wajib QC Ulang (SOP-SEWA-01)</em>.
                </p>
              </div>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
              <button class="btn btn-sm btn-primary text-xs font-bold shadow-sm" onclick="goPage('scan')">
                ⚡ Universal Scanner Hub
              </button>
              <button class="btn btn-sm btn-outline border-base-300 text-xs font-bold" onclick="goPage('mikms')">
                🤖 Operasional MIKMS
              </button>
              <button class="btn btn-sm btn-outline border-base-300 text-xs font-bold" onclick="goPage('mikms', 'unitregistry')">
                🔢 Unit Registry
              </button>
              <button class="btn btn-sm btn-outline border-base-300 text-xs font-bold" onclick="goPage('sewamutasi')">
                🔄 Sewa &amp; Asset
              </button>
            </div>
          </div>
        </div>

        <!-- RINGKASAN EKSEKUTIF: 4 PILAR REFORMASI OPERASIONAL 2026 -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
              <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                <span>🏛️</span> 4 Pilar Transformasi Operasional Gudang &amp; Produksi 2026
              </h3>
              <p class="text-xs text-base-content/60 mt-0.5">Penyelarasan alur fisik lapangan dengan tata kelola administrasi dokumen resmi dan otomasi sistem digital.</p>
            </div>
            <span class="badge badge-sm badge-success font-bold text-[10px]">Standardization Matrix</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-2">
            <!-- Pilar 1 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-7 h-7 rounded-xl bg-primary text-primary-content text-xs font-black flex items-center justify-center">1</span>
                  <span class="font-extrabold text-xs text-base-content">Universal Scanner Hub</span>
                </div>
                <p class="text-[11px] text-base-content/70 leading-relaxed">
                  <strong>1 Pintu Masuk &amp; Keluar (<code>/#scan</code>):</strong> Penggabungan fungsi scan masuk/keluar ke satu layar terpadu. Input barang masuk vendor (Jimu, Erboblox, ALK) otomatis menerbitkan nomor seri MIKMS resmi berstatus <code>qc_pending</code> tanpa form manual terpisah.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/50 text-[10px] font-bold text-primary flex items-center gap-1">
                <span>✓</span> Eliminasi Direct Input Terpisah
              </div>
            </div>

            <!-- Pilar 2 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-7 h-7 rounded-xl bg-info text-info-content text-xs font-black flex items-center justify-center">2</span>
                  <span class="font-extrabold text-xs text-base-content">Dokumen Resmi 3 Rangkap</span>
                </div>
                <p class="text-[11px] text-base-content/70 leading-relaxed">
                  <strong>Legalitas SP, DO, dan Retur:</strong> Setiap pengeluaran barang wajib berdasar Surat Pesanan (SP) &amp; Form Permintaan Barang, disertai Surat Jalan / DO 3 rangkap (Akunting, Gudang, Pelanggan terstempel toko/sekolah). Penagihan hanya sah dengan DO terstempel.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/50 text-[10px] font-bold text-info flex items-center gap-1">
                <span>✓</span> Otorisasi Bertingkat &amp; Stempel
              </div>
            </div>

            <!-- Pilar 3 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-7 h-7 rounded-xl bg-success text-success-content text-xs font-black flex items-center justify-center">3</span>
                  <span class="font-extrabold text-xs text-base-content">Pemisahan Fungsi (SoD)</span>
                </div>
                <p class="text-[11px] text-base-content/70 leading-relaxed">
                  <strong>Separation of Duties:</strong> Marketing (SP &amp; MoU), Gudang (Fisik, DO &amp; Kartu Stok), Produksi (BOM Perakitan MLK), QC (Inspeksi Pass/Fail &amp; Mapping Retur Baik/Rusak), serta Akunting (Verifikasi DO terstempel &amp; Penagihan Invoice).
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/50 text-[10px] font-bold text-success flex items-center gap-1">
                <span>✓</span> Integritas &amp; Akuntabilitas Stok
              </div>
            </div>

            <!-- Pilar 4 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-7 h-7 rounded-xl bg-warning text-warning-content text-xs font-black flex items-center justify-center">4</span>
                  <span class="font-extrabold text-xs text-base-content">SOP Sewa Wajib QC Ulang</span>
                </div>
                <p class="text-[11px] text-base-content/70 leading-relaxed">
                  <strong>Restriksi Ketat (SOP-SEWA-01):</strong> Pendaftaran aset sewa wajib dari stok fisik unit Finish Goods yang lolos QC. Sewa kembali secara otomatis terkunci <em>Wajib QC Ulang</em> dan diblokir dari sewa keluar sampai lolos checklist QC MIKMS.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/50 text-[10px] font-bold text-warning-content flex items-center gap-1">
                <span>✓</span> Keamanan &amp; Kelayakan Aset Sewa
              </div>
            </div>
          </div>
        </div>

        <!-- BAB 1: UNIVERSAL SMART SCANNER HUB -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                  <span>⚡</span> Bab 1: Panduan Operasional Universal Smart Scanner Hub (<code>/#scan</code>)
                </h3>
                <span class="badge badge-primary badge-sm font-extrabold text-[10px]">1 PINTU OPERASIONAL</span>
              </div>
              <p class="text-xs text-base-content/60 mt-0.5">Antarmuka pemindaian barcode tunggal untuk pencatatan barang masuk (+), barang keluar (-), dan otomasi serial Finish Goods vendor.</p>
            </div>
            <button class="btn btn-xs btn-primary font-bold" onclick="goPage('scan')">
              Buka Scanner Hub ↗
            </button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-3">
            <!-- Sub-Card 1: Barang Masuk & Otomasi Serial FG -->
            <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="badge badge-sm badge-success font-bold text-[10px]">MODE MASUK (+)</span>
                  <span class="font-mono text-[10px] text-base-content/60">Inbound &amp; Vendor</span>
                </div>
                <h4 class="font-extrabold text-xs text-base-content">Penerimaan &amp; Otomasi Serial Vendor</h4>
                <p class="text-[11px] text-base-content/70 mt-1.5 leading-relaxed">
                  1. Pilih mode <strong>Barang Masuk (+)</strong> pada Universal Scanner Hub.<br>
                  2. Scan barcode vendor atau ketik kode/nama barang (misal: <code>RJ01</code>, <code>ERBO01</code>, <code>ALK01</code>).<br>
                  3. Sistem secara otomatis mengenali Finish Goods vendor MIKMS dan menampilkan badge pengenal.<br>
                  4. Masukkan kuantiti barang yang diterima. Saat diklik <strong>Catat Barang Masuk</strong>, sistem otomatis menambah mutasi stok gudang sekaligus menerbitkan nomor seri resmi berurutan (<code>JIMU-xxxxx</code>, <code>ERBO-xxxxx</code>, <code>ALK-xxxxx</code>) berstatus <code>qc_pending</code>.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/60 text-[10px] font-bold text-success">
                ✓ Banner instan: Cetak Label Thermal Batch &amp; Buka QC Checklist
              </div>
            </div>

            <!-- Sub-Card 2: Barang Keluar & DO Pengiriman -->
            <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="badge badge-sm badge-error font-bold text-[10px]">MODE KELUAR (-)</span>
                  <span class="font-mono text-[10px] text-base-content/60">Outbound &amp; DO</span>
                </div>
                <h4 class="font-extrabold text-xs text-base-content">Pengeluaran Barang &amp; Surat Jalan</h4>
                <p class="text-[11px] text-base-content/70 mt-1.5 leading-relaxed">
                  1. Pilih mode <strong>Barang Keluar (-)</strong> pada Universal Scanner Hub.<br>
                  2. Scan barcode item atau masukkan kode barang yang tercantum pada <em>Form Permintaan Barang</em> / Surat Pesanan.<br>
                  3. Input nomor referensi Surat Jalan / DO resmi dan pilih tujuan (Pelanggan/Sekolah/Instansi).<br>
                  4. Sistem memvalidasi ketersediaan stok fisik secara real-time dan memotong kartu stok seketika saat konfirmasi disimpan.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/60 text-[10px] font-bold text-error">
                ✓ Mengunci mutasi keluar berdasar ketersediaan stok riil
              </div>
            </div>

            <!-- Sub-Card 3: Shortcut Aksi Cepat & Cetak Batch -->
            <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="badge badge-sm badge-warning font-bold text-[10px]">SHORTCUT CEPAT</span>
                  <span class="font-mono text-[10px] text-base-content/60">1-Click Action</span>
                </div>
                <h4 class="font-extrabold text-xs text-base-content">Penerimaan Kilat &amp; Batch Print</h4>
                <p class="text-[11px] text-base-content/70 mt-1.5 leading-relaxed">
                  • <strong>Shortcut 1-Klik:</strong> Tersedia tombol cepat untuk menambahkan stok Finish Goods vendor (Jimu +1 / +5, Erboblox +1 / +5, ALK +1 / +5) tanpa input form berulang.<br>
                  • <strong>Batch Printing Thermal:</strong> Setelah serial digenerate, klik <em>"Cetak Semua Stiker Thermal"</em>. Dialog cetak otomatis membagi page-break presisi per unit boks.<br>
                  • <strong>Riwayat Sesi Terkini:</strong> Tabel di bawah scanner memantau seluruh riwayat scan dalam sesi kerja aktif operator.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/60 text-[10px] font-bold text-warning-content">
                ✓ Sangat efisien untuk penerimaan karton vendor dalam jumlah besar
              </div>
            </div>
          </div>
        </div>

        <!-- BAB 2: ALUR KERJA SIKLUS HIDUP FINISH GOODS (6 TAHAP TERPADU) -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
              <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                <span>🔄</span> Bab 2: Alur Kerja Siklus Hidup Finish Goods (Unit Lifecycle 6 Tahap)
              </h3>
              <p class="text-xs text-base-content/60 mt-0.5">Seluruh unit kit dan robotik melewati 6 tahapan proses terkontrol untuk menjamin kelayakan fungsi dan ketertiban pelacakan serial number.</p>
            </div>
            <span class="badge badge-sm badge-info font-bold text-[10px]">End-to-End Pipeline</span>
          </div>

          <!-- PIPELINE STEPS -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 mt-4">
            <!-- Step 1 -->
            <div class="p-3.5 rounded-2xl bg-base-200/50 border border-base-300 relative group hover:border-primary/50 transition flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">1</span>
                  <span class="badge badge-xs badge-neutral font-mono font-bold">SCAN / RAKIT</span>
                </div>
                <div class="font-bold text-xs text-base-content">Penerimaan &amp; Rakit</div>
                <p class="text-[10px] text-base-content/60 mt-1 leading-relaxed">
                  Produk vendor (Jimu, Erboblox, ALK) diinput via <strong>Universal Scanner Hub (<code>/#scan</code>)</strong>. Kit MLK dirakit dari modul M01-M07 di tab <strong>Perakitan Kit</strong>.
                </p>
              </div>
              <div class="mt-2 pt-2 border-t border-base-300/50 text-[9px] font-semibold text-warning">
                Status awal: qc_pending
              </div>
            </div>

            <!-- Step 2 -->
            <div class="p-3.5 rounded-2xl bg-base-200/50 border border-base-300 relative group hover:border-primary/50 transition flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">2</span>
                  <span class="badge badge-xs badge-info font-mono font-bold">REGISTRY</span>
                </div>
                <div class="font-bold text-xs text-base-content">Unit Registry &amp; Serial</div>
                <p class="text-[10px] text-base-content/60 mt-1 leading-relaxed">
                  Sistem otomatis menerbitkan Serial Number unik global 5-digit (contoh: <code>MLK-00001</code>, <code>JIMU-00001</code>). Unit terdaftar dan siap diperiksa QC.
                </p>
              </div>
              <div class="mt-2 pt-2 border-t border-base-300/50 text-[9px] font-semibold text-info">
                Pelacakan serial unik
              </div>
            </div>

            <!-- Step 3 -->
            <div class="p-3.5 rounded-2xl bg-base-200/50 border border-base-300 relative group hover:border-primary/50 transition flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">3</span>
                  <span class="badge badge-xs badge-success font-mono font-bold">QC CHECK</span>
                </div>
                <div class="font-bold text-xs text-base-content">QC Unit Checklist</div>
                <p class="text-[10px] text-base-content/60 mt-1 leading-relaxed">
                  Pemeriksaan fisik &amp; fungsi tanpa field kuantiti (centang Lolos / Tidak). Jika seluruh poin Lolos, status otomatis menjadi <code>in_stock</code> dan label thermal siap cetak.
                </p>
              </div>
              <div class="mt-2 pt-2 border-t border-base-300/50 text-[9px] font-semibold text-success">
                Auto-pass ke in_stock
              </div>
            </div>

            <!-- Step 4 -->
            <div class="p-3.5 rounded-2xl bg-base-200/50 border border-base-300 relative group hover:border-primary/50 transition flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">4</span>
                  <span class="badge badge-xs badge-secondary font-mono font-bold">OPSI A</span>
                </div>
                <div class="font-bold text-xs text-base-content">Packaging ROBO</div>
                <p class="text-[10px] text-base-content/60 mt-1 leading-relaxed">
                  Khusus Robotic Explorer: Pairing 1 unit MLK ready + 1 unit JIMU ready menjadi 1 boks luar <code>ROBO-xxxxx</code> berstiker identitas khusus (referensi isi anak).
                </p>
              </div>
              <div class="mt-2 pt-2 border-t border-base-300/50 text-[9px] font-semibold text-secondary">
                Bundling kemasan luar
              </div>
            </div>

            <!-- Step 5 -->
            <div class="p-3.5 rounded-2xl bg-base-200/50 border border-base-300 relative group hover:border-primary/50 transition flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">5</span>
                  <span class="badge badge-xs badge-success font-mono font-bold">STOK JADI</span>
                </div>
                <div class="font-bold text-xs text-base-content">Penyimpanan Stok Jadi</div>
                <p class="text-[10px] text-base-content/60 mt-1 leading-relaxed">
                  Unit yang lulus QC dan boks ROBO tersegel disimpan di rak barang jadi gudang. Siap digunakan untuk pengiriman penjualan atau dialokasikan ke unit aset sewa.
                </p>
              </div>
              <div class="mt-2 pt-2 border-t border-base-300/50 text-[9px] font-semibold text-success">
                Status: in_stock
              </div>
            </div>

            <!-- Step 6 -->
            <div class="p-3.5 rounded-2xl bg-base-200/50 border border-base-300 relative group hover:border-primary/50 transition flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">6</span>
                  <span class="badge badge-xs badge-error font-mono font-bold">REPAIR &amp; RE-QC</span>
                </div>
                <div class="font-bold text-xs text-base-content">Penanganan Defek / Retur</div>
                <p class="text-[10px] text-base-content/60 mt-1 leading-relaxed">
                  Unit yang gagal QC atau dikembalikan dari sewa (Wajib QC Ulang) diperiksa tim teknisi. Komponen rusak diganti dari bahan baku mentah (RW), lalu wajib QC ulang sampai lolos.
                </p>
              </div>
              <div class="mt-2 pt-2 border-t border-base-300/50 text-[9px] font-semibold text-error">
                Status: repair → qc_pending
              </div>
            </div>
          </div>
        </div>

        <!-- BAB 3: STANDAR 5 MASTER FINISH GOODS & KETENTUAN SERIAL NUMBER -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
              <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                <span>📦</span> Bab 3: Standar 5 Master Finish Goods &amp; Ketentuan Serial Number
              </h3>
              <p class="text-xs text-base-content/60 mt-0.5">Gudang mengelola 5 produk jadi berstandar resmi dengan format penomoran serial otomatis 5-digit angka.</p>
            </div>
            <span class="badge badge-sm badge-neutral font-mono font-bold">5-DIGIT SERIAL NUMBER</span>
          </div>

          <div class="overflow-x-auto border border-base-200 rounded-xl">
            <table class="table table-sm table-zebra w-full text-xs">
              <thead class="bg-base-200/70">
                <tr>
                  <th>Kode</th>
                  <th>Nama Finish Good</th>
                  <th>Metode Input Sistem</th>
                  <th>Format Serial</th>
                  <th>Penanda Fisik</th>
                  <th>Struktur Kemasan / Isi</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="font-mono font-bold text-primary">MLK</td>
                  <td class="font-bold">Microbit Learning Kit</td>
                  <td><span class="badge badge-xs badge-info font-bold">Assembled BOM (Perakitan Kit)</span></td>
                  <td class="font-mono font-bold">MLK-00001</td>
                  <td><span class="badge badge-xs badge-secondary font-bold">Stiker Boks + Stiker Board 1x1cm</span></td>
                  <td>Box Micro:bit (Modul M01 s/d M07, kabel, sensor, baterai holder)</td>
                </tr>
                <tr>
                  <td class="font-mono font-bold text-primary">JIMU</td>
                  <td class="font-bold">Jimu Trackbot</td>
                  <td><span class="badge badge-xs badge-primary font-bold">Universal Scanner Hub (Inbound)</span></td>
                  <td class="font-mono font-bold">JIMU-00001</td>
                  <td><span class="badge badge-xs badge-ghost font-bold">Stiker Boks Thermal</span></td>
                  <td>Kit Robotik Jimu Trackbot orisinil dari vendor</td>
                </tr>
                <tr>
                  <td class="font-mono font-bold text-primary">ERBO</td>
                  <td class="font-bold">Erboblox</td>
                  <td><span class="badge badge-xs badge-primary font-bold">Universal Scanner Hub (Inbound)</span></td>
                  <td class="font-mono font-bold">ERBO-00001</td>
                  <td><span class="badge badge-xs badge-ghost font-bold">Stiker Boks Thermal</span></td>
                  <td>Box Erboblox orisinil dari vendor</td>
                </tr>
                <tr>
                  <td class="font-mono font-bold text-primary">ALK</td>
                  <td class="font-bold">Arduino Learning Kit</td>
                  <td><span class="badge badge-xs badge-primary font-bold">Universal Scanner Hub (Inbound)</span></td>
                  <td class="font-mono font-bold">ALK-00001</td>
                  <td><span class="badge badge-xs badge-ghost font-bold">Stiker Boks Thermal</span></td>
                  <td>Box Arduino Learning Kit orisinil dari vendor</td>
                </tr>
                <tr>
                  <td class="font-mono font-bold text-secondary">ROBO</td>
                  <td class="font-bold">Robotic Explorer</td>
                  <td><span class="badge badge-xs badge-secondary font-bold">Packaging ROBO (Bundle Opsi A)</span></td>
                  <td class="font-mono font-bold">ROBO-00001</td>
                  <td><span class="badge badge-xs badge-primary font-bold">Stiker Boks Kemasan Luar</span></td>
                  <td><strong>Boks Kemasan Luar Sendiri</strong> memuat 1 Unit MLK + 1 Unit JIMU</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- BAB 4: MATRIKS DOKUMEN TRANSAKSI RESMI & OTORISASI PT ERLASS 2026 -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                  <span>📑</span> Bab 4: Matriks Dokumen Transaksi Resmi &amp; Otorisasi PT Erlass 2026
                </h3>
                <span class="badge badge-warning badge-sm font-extrabold text-[10px]">SOP EXCEL 2026 COMPLIANT</span>
              </div>
              <p class="text-xs text-base-content/60 mt-0.5">Berdasarkan dokumen resmi <em>SOP PT ERLASS 2026.xlsx</em> (Alur Pesanan, Pengiriman, Pereturan, &amp; Penagihan).</p>
            </div>
            <span class="badge badge-sm badge-info font-mono font-bold">3-Fold Copies Standard</span>
          </div>

          <div class="overflow-x-auto border border-base-200 rounded-xl mb-5">
            <table class="table table-sm table-zebra w-full text-xs">
              <thead class="bg-base-200/70">
                <tr>
                  <th>No</th>
                  <th>Dokumen Transaksi</th>
                  <th>PIC / Pembuat</th>
                  <th>Otorisasi Tanda Tangan Wajib</th>
                  <th>Rangkap &amp; Distribusi Fisik</th>
                  <th>Keterangan / Titik Koreksi</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="font-bold text-center">1</td>
                  <td class="font-bold text-primary">Surat Pesanan (SP) &amp; MoU/SPK</td>
                  <td>Salesman &amp; Admin Marketing</td>
                  <td>Salesman, Group Leader (GL), Manager Erlass</td>
                  <td><span class="badge badge-xs badge-neutral font-bold">1-2 Rangkap</span> (Admin Mkt &amp; Akunting Asli)</td>
                  <td>SP yang sudah ditandatangani Manager Erlass sah untuk diproses gudang. Wajib melampirkan MoU/SPK.</td>
                </tr>
                <tr>
                  <td class="font-bold text-center">2</td>
                  <td class="font-bold text-primary">Form Permintaan Barang</td>
                  <td>Admin Marketing / Salesman</td>
                  <td>Salesman, GL, Manager Marketing</td>
                  <td><span class="badge badge-xs badge-neutral font-bold">3 Rangkap</span> (Arsip Admin Gudang)</td>
                  <td>Dasar bagi Admin Gudang untuk menyiapkan fisik barang di rak penyimpanan.</td>
                </tr>
                <tr>
                  <td class="font-bold text-center">3</td>
                  <td class="font-bold text-secondary">Surat Jalan / Delivery Order (DO)</td>
                  <td>Admin Gudang</td>
                  <td>Admin Gudang, Petugas Pengirim, Satpam/Security, <strong>Pelanggan (Wajib Stempel)</strong></td>
                  <td><span class="badge badge-xs badge-info font-bold">3 Rangkap:</span> 1. Akunting, 2. Arsip Gudang, 3. Pelanggan</td>
                  <td><strong>Klausul Wajib:</strong> Saat barang diterima, pelanggan wajib ttd dan membubuhkan <u>STEMPEL RESMI</u> toko/sekolah. DO diserahkan ke Akunting setiap Kamis.</td>
                </tr>
                <tr>
                  <td class="font-bold text-center">4</td>
                  <td class="font-bold text-error">Form Retur Produk (Jual &amp; Sewa)</td>
                  <td>Admin Gudang &amp; Tim QC</td>
                  <td>Pelanggan, Sales, GL, Manager Erlass, Admin Gudang, &amp; Akunting</td>
                  <td><span class="badge badge-xs badge-warning font-bold">3 Rangkap:</span> 1. Akunting, 2. Adm Gudang, 3. Pertinggal Pelanggan</td>
                  <td>Admin Gudang memeriksa jumlah fisik dengan Form Retur. Jika sesuai -&gt; ttd Admin. Jika tidak sesuai -&gt; konfirmasi ke Sales.</td>
                </tr>
                <tr>
                  <td class="font-bold text-center">5</td>
                  <td class="font-bold text-success">Form Serah Terima &amp; Mapping QC Retur</td>
                  <td>Tim QC</td>
                  <td>Tim QC &amp; Admin Gudang</td>
                  <td><span class="badge badge-xs badge-success font-bold">1 Lembar + Form Serah Terima</span></td>
                  <td>Tim QC memilah <strong>Barang Baik</strong> (lolos QC kembali ke gudang) vs <strong>Barang Rusak/Cacat</strong> (klaim/repair). Wajib mencantumkan asal sekolah/mitra.</td>
                </tr>
                <tr>
                  <td class="font-bold text-center">6</td>
                  <td class="font-bold text-base-content">Invoice, Kwitansi, Faktur &amp; KTPB</td>
                  <td>Admin Akunting &amp; Keuangan</td>
                  <td>Admin Keuangan &amp; Akunting</td>
                  <td><span class="badge badge-xs badge-neutral font-bold">3 Rangkap:</span> 1. Pelanggan (Putih), 2. Admin Akt, 3. Arsip Akt</td>
                  <td>Penagihan produk ke pelanggan <strong>wajib melampirkan DO asli terstempel pelanggan</strong>, Kwitansi, dan Faktur.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Matriks Peran Operasional (4 Kotak) -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300">
              <div class="flex items-center gap-2 mb-2">
                <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">📥</span>
                <div>
                  <h5 class="font-extrabold text-xs text-base-content">Admin Gudang</h5>
                  <span class="text-[9px] text-base-content/50 uppercase font-bold">Penerimaan &amp; DO</span>
                </div>
              </div>
              <ul class="text-[11px] text-base-content/80 space-y-1 list-disc list-inside leading-relaxed">
                <li>Input barang vendor via Universal Scanner Hub.</li>
                <li>Verifikasi Form Permintaan Barang vs stok fisik.</li>
                <li>Terbitkan Surat Jalan/DO 3 rangkap berlampirkan SP.</li>
                <li>Arsipkan DO terstempel &amp; setor ke Akunting tiap Kamis.</li>
              </ul>
            </div>

            <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300">
              <div class="flex items-center gap-2 mb-2">
                <span class="w-8 h-8 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-sm">⚙️</span>
                <div>
                  <h5 class="font-extrabold text-xs text-base-content">Teknisi Perakitan</h5>
                  <span class="text-[9px] text-base-content/50 uppercase font-bold">Produksi MLK &amp; Modul</span>
                </div>
              </div>
              <ul class="text-[11px] text-base-content/80 space-y-1 list-disc list-inside leading-relaxed">
                <li>Rakit modul M01-M07 dengan pemotongan stok BOM.</li>
                <li>Proses perakitan kit paket MLK di tab Perakitan Kit.</li>
                <li>Tempelkan stiker mikro 1x1cm pada board Micro:bit V2.</li>
                <li>Serahkan unit terakit berstatus <code>qc_pending</code> ke tim QC.</li>
              </ul>
            </div>

            <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300">
              <div class="flex items-center gap-2 mb-2">
                <span class="w-8 h-8 rounded-xl bg-success/10 text-success flex items-center justify-center font-bold text-sm">✅</span>
                <div>
                  <h5 class="font-extrabold text-xs text-base-content">Tim Quality Control (QC)</h5>
                  <span class="text-[9px] text-base-content/50 uppercase font-bold">Inspeksi &amp; Mapping Retur</span>
                </div>
              </div>
              <ul class="text-[11px] text-base-content/80 space-y-1 list-disc list-inside leading-relaxed">
                <li>Checklist pengujian unit tanpa field kuantiti (Pass/Fail).</li>
                <li>Otorisasi kelulusan unit ke status <code>in_stock</code>.</li>
                <li>Mapping barang retur (Barang Baik vs Barang Rusak).</li>
                <li>Pemeriksaan QC ulang untuk unit sewa yang kembali.</li>
              </ul>
            </div>

            <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300">
              <div class="flex items-center gap-2 mb-2">
                <span class="w-8 h-8 rounded-xl bg-info/10 text-info flex items-center justify-center font-bold text-sm">💳</span>
                <div>
                  <h5 class="font-extrabold text-xs text-base-content">Admin Akunting &amp; Keu</h5>
                  <span class="text-[9px] text-base-content/50 uppercase font-bold">Verifikasi &amp; Penagihan</span>
                </div>
              </div>
              <ul class="text-[11px] text-base-content/80 space-y-1 list-disc list-inside leading-relaxed">
                <li>Menerima DO terstempel dari gudang setiap Kamis.</li>
                <li>Mencocokkan DO dengan Surat Pesanan (SP) &amp; MoU.</li>
                <li>Mencetak Invoice 3 rangkap, Kwitansi, dan Faktur.</li>
                <li>Otorisasi akhir pada Form Retur pereturan produk.</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- BAB 5: SOP ALUR SEWA FINISH GOODS & KEBIJAKAN WAJIB QC ULANG (SOP-SEWA-01) -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                  <span>🔄</span> Bab 5: SOP Alur Sewa Finish Goods &amp; Kebijakan "Wajib QC Ulang"
                </h3>
                <span class="badge badge-warning badge-sm font-extrabold text-[10px]">SOP-SEWA-01</span>
              </div>
              <p class="text-xs text-base-content/60 mt-0.5">Aturan operasional dan pembatasan ketat sistem untuk pendaftaran aset sewa, sewa keluar, dan penerimaan kembali barang sewa.</p>
            </div>
            <div class="flex gap-2">
              <button class="btn btn-xs btn-primary font-bold" onclick="switchSewaTab('sewain'); goPage('sewamutasi');">
                📥 Penerimaan Asset Baru
              </button>
              <button class="btn btn-xs btn-secondary font-bold" onclick="switchSewaTab('sewaout'); goPage('sewamutasi');">
                📤 Sewa Keluar
              </button>
            </div>
          </div>

          <!-- 4 PILAR UTAMA ALUR SEWA -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Pilar 1 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-primary text-primary-content font-extrabold text-xs flex items-center justify-center">1</span>
                  <span class="badge badge-xs badge-primary font-bold">SEWA MASUK</span>
                </div>
                <h5 class="font-extrabold text-xs text-base-content">Pilih dari Stok Unit Kita</h5>
                <p class="text-[11px] text-base-content/70 mt-1 leading-relaxed">
                  Input nomor seri <strong>wajib bersumber dari unit Finish Goods tergenerate</strong> yang berstatus <code>in_stock</code> dan telah lolos QC awal. Petugas dapat memilih langsung via dropdown stok atau scan barcode.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/50 text-[10px] font-semibold text-primary">
                Masuk ke List Asset Barang Sewa
              </div>
            </div>

            <!-- Pilar 2 -->
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-secondary text-secondary-content font-extrabold text-xs flex items-center justify-center">2</span>
                  <span class="badge badge-xs badge-secondary font-bold">SEWA KELUAR</span>
                </div>
                <h5 class="font-extrabold text-xs text-base-content">Hanya dari List Aset Sewa</h5>
                <p class="text-[11px] text-base-content/70 mt-1 leading-relaxed">
                  Unit yang keluar <strong>HANYALAH</strong> yang sudah tercatat di list masuk aset sewa dan berstatus <code>good</code> (lolos QC). Unit non-sewa atau yang sedang bermasalah diblokir oleh sistem.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-base-300/50 text-[10px] font-semibold text-secondary">
                Status unit: dispatched
              </div>
            </div>

            <!-- Pilar 3 -->
            <div class="p-4 rounded-2xl bg-warning/10 border border-warning/30 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-warning text-warning-content font-extrabold text-xs flex items-center justify-center">3</span>
                  <span class="badge badge-xs badge-warning text-black font-bold">SEWA KEMBALI</span>
                </div>
                <h5 class="font-extrabold text-xs text-base-content">Otomatis Wajib QC Ulang</h5>
                <p class="text-[11px] text-base-content/70 mt-1 leading-relaxed">
                  Setiap unit yang kembali dari sewa <strong>OTOMATIS BERSTATUS WAJIB QC ULANG</strong> (<code>qc_pending</code> &amp; <code>not_good</code>). Unit <strong>DIBLOKIR TOTAL</strong> dari transaksi sewa berikutnya sampai QC ulang selesai.
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-warning/30 text-[10px] font-bold text-warning-content">
                ⛔ DIBLOKIR DARI SEWA KELUAR
              </div>
            </div>

            <!-- Pilar 4 -->
            <div class="p-4 rounded-2xl bg-success/10 border border-success/30 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="w-6 h-6 rounded-full bg-success text-success-content font-extrabold text-xs flex items-center justify-center">4</span>
                  <span class="badge badge-xs badge-success text-success-content font-bold">QC ULANG MIKMS</span>
                </div>
                <h5 class="font-extrabold text-xs text-base-content">Inspeksi &amp; Buka Blokir</h5>
                <p class="text-[11px] text-base-content/70 mt-1 leading-relaxed">
                  Teknisi menguji unit di tab <em>QC Unit</em> dengan memilih tipe <code>rental_return</code>. Jika seluruh checklist <strong>Lolos (PASS)</strong>, blokir otomatis terbuka dan unit kembali berstatus <code>good</code> (Ready Sewa).
                </p>
              </div>
              <div class="mt-3 pt-2 border-t border-success/30 text-[10px] font-bold text-success">
                ✓ Kembali Ready Sewa Keluar
              </div>
            </div>
          </div>

          <!-- RINCIAN PROSEDUR -->
          <div class="p-4 rounded-2xl bg-base-200/50 border border-base-300">
            <h5 class="font-extrabold text-xs uppercase tracking-wider text-base-content/70 mb-3">
              📋 Prosedur Operasional Standar Alur Sewa (Langkah demi Langkah)
            </h5>
            <ol class="text-xs text-base-content/80 space-y-2.5 list-decimal list-inside">
              <li>
                <strong>Penerimaan Aset Baru ke Gudang Sewa:</strong> Buka menu <em>Sewa &amp; Asset</em> ➔ tab <em>Penerimaan Asset Baru</em>. Pilih Master Barang Finish Goods (MLK, ROBO, JIMU, ERBO, ALK), lalu pada kolom <em>Serial Number</em> pilih unit dari dropdown stok kita. Sistem otomatis memvalidasi keaslian serial number dan mencatat aset baru dengan kondisi <code>Good</code>.
              </li>
              <li>
                <strong>Pengeluaran Sewa ke Customer / Sekolah:</strong> Buka tab <em>Sewa Keluar</em>. Pilih unit dari katalog <em>Asset Barang Sewa (Ready)</em>. Jika unit yang dipilih belum lolos QC atau baru kembali dari sewa, sistem akan menolak transaksi dengan peringatan <em>"Unit dalam status WAJIB QC ULANG"</em>.
              </li>
              <li>
                <strong>Penerimaan Kembali (Retur Sewa):</strong> Saat barang sewa kembali ke gudang, petugas membuka tab <em>Sewa Kembali</em>, pilih unit yang sedang disewa, lalu klik <strong>Kembalikan</strong>. Sistem otomatis menandai unit sebagai <code>Wajib QC Ulang</code> dan mengunci unit dari sewa keluar.
              </li>
              <li>
                <strong>Pemeriksaan Kelayakan di QC Unit MIKMS:</strong> Petugas/Teknisi mengklik tombol <strong>[🔍 Lakukan QC Ulang]</strong> yang langsung mengarahkan ke tab <em>QC Unit MIKMS</em> dengan tipe <em>"QC Ulang Setelah Sewa Kembali"</em>.
                <ul class="list-disc list-inside pl-5 mt-1 text-[11px] text-base-content/70 space-y-1">
                  <li><strong>Jika LULUS (Semua butir PASS):</strong> Unit MIKMS kembali ke status <code>in_stock</code> dan Aset kembali ke status <code>good</code> (Buka Blokir). Unit siap disewakan kembali.</li>
                  <li><strong>Jika GAGAL (Ada butir FAIL):</strong> Unit MIKMS dialihkan ke status <code>repair</code> dan Aset tetap <code>not_good</code> untuk diperbaiki teknisi.</li>
                </ul>
              </li>
            </ol>
          </div>
        </div>

        <!-- BAB 6: SPESIFIKASI STIKER IDENTITAS PRODUKSI -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
              <h3 class="font-extrabold text-base text-base-content flex items-center gap-2">
                <span>🖨️</span> Bab 6: Standar Stiker Identitas Produksi (Boks Thermal &amp; Board 1x1cm)
              </h3>
              <p class="text-xs text-base-content/60 mt-0.5">Dua jenis label standar untuk identifikasi kemasan boks luar dan komponen Micro:bit.</p>
            </div>
            <span class="badge badge-sm badge-neutral font-mono font-bold">Boks 54-58mm + Board 1x1cm</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300">
              <div class="badge badge-primary font-bold text-[10px] mb-2">1. STIKER BOKS LUAR (THERMAL 54-58mm / 80mm)</div>
              <h5 class="font-extrabold text-sm text-base-content">Format Ringkas 2 Kolom</h5>
              <div class="p-3 bg-base-100 rounded-xl border border-base-300 font-mono text-[11px] mt-2 space-y-1">
                <div class="font-bold border-b border-base-300 pb-1">
                  <div>Robotic Explorer</div>
                  <div class="text-sm font-extrabold text-primary">ROBO-00001</div>
                </div>
                <div class="flex items-center justify-between pt-1">
                  <div class="w-12 h-12 bg-base-200 border border-base-300 flex items-center justify-center text-[9px] text-center font-bold">QR CODE</div>
                  <div class="text-[10px] space-y-0.5 pl-2 border-l border-base-300 leading-tight">
                    <div>QC &nbsp;: <strong>✓ PASSED</strong></div>
                    <div>TGL : 2026-09-22</div>
                    <div>BY &nbsp;: Tim QC Erlass</div>
                    <div>OPS : SEWA / BELI</div>
                  </div>
                </div>
              </div>
              <p class="text-xs text-base-content/70 mt-2 leading-relaxed">
                Dicetak pada printer thermal roll 54-58mm atau 80mm. Memuat nama produk, serial tebal, QR code 2D, dan detail QC. Cepat dipindai menggunakan barcode scanner gun.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-base-200/40 border border-base-300">
              <div class="badge badge-secondary font-bold text-[10px] mb-2">2. STIKER BOARD MICRO:BIT (1cm x 1cm GRID)</div>
              <h5 class="font-extrabold text-sm text-base-content">Micro QR Code + Teks Serial (Opsi A)</h5>
              <div class="p-3 bg-base-100 rounded-xl border border-base-300 font-mono text-[11px] mt-2 space-y-1">
                <div class="w-16 h-16 mx-auto bg-base-200 border border-dashed border-base-content/40 rounded flex flex-col items-center justify-center p-1">
                  <div class="w-8 h-8 bg-base-300 flex items-center justify-center text-[7px] font-bold">QR</div>
                  <div class="text-[7px] font-black mt-0.5 tracking-tighter">MLK-00001</div>
                </div>
              </div>
              <p class="text-xs text-base-content/70 mt-2 leading-relaxed">
                Ukuran presisi <strong>10mm × 10mm</strong> dicetak dalam <strong>format grid lembaran (sheet)</strong>. Ditempelkan pada bagian belakang PCB board Micro:bit V2 agar komponen modul tidak tertukar saat penggunaan di lapangan.
              </p>
            </div>
          </div>
        </div>

        <!-- BAB 7: FAQ & TROUBLESHOOTING OPERASIONAL PT ERLASS 2026 -->
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
          <h3 class="font-extrabold text-base text-base-content mb-3 flex items-center gap-2">
            <span>❓</span> Bab 7: Tanya Jawab (FAQ) &amp; Solusi Troubleshooting Operasional 2026
          </h3>
          <div class="space-y-3">
            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Mengapa tab "Direct Input" di MIKMS ditiadakan dan digabungkan ke Universal Smart Scanner Hub?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p>Penggabungan ini bertujuan menghilangkan duplikasi pekerjaan bagi staf gudang. Staf gudang cukup bekerja di satu layar yaitu <strong>Universal Smart Scanner Hub (<code>/#scan</code>)</strong> pada mode <strong>Barang Masuk (+)</strong>. Saat barcode produk vendor (Jimu, Erboblox, ALK) dipindai, sistem secara otomatis mencatat stok inventori dan sekaligus menerbitkan nomor seri resmi MIKMS berurutan berstatus <code>qc_pending</code> serta menyediakan tombol cetak label thermal batch secara instan.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Mengapa Surat Jalan / Delivery Order (DO) wajib ditandatangani dan dicap STEMPEL oleh pelanggan?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p>Sesuai <strong>SOP PESANAN-PENAGIHAN PT ERLASS 2026</strong>, Surat Jalan / DO merupakan bukti legalitas serah terima fisik barang. Bagian Akunting <strong>hanya dapat menerbitkan tagihan/invoice dan menagih ke pelanggan</strong> apabila lampiran DO rangkap 1 telah dibubuhi tanda tangan penerima dan <u>CAP STEMPEL RESMI</u> sekolah/pelanggan. DO yang sudah terstempel diserahkan staf gudang ke akunting setiap hari Kamis.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Bagaimana alur retur produk dan apa fungsi mapping oleh Tim QC?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p>Saat barang retur (jual atau sewa) tiba di gudang, Admin Gudang mencocokkan jumlah fisik dengan <em>Form Retur (3 rangkap)</em>. Selanjutnya, Tim QC melakukan <strong>mapping kelayakan barang</strong>: unit yang <strong>Baik</strong> diuji checklist QC dan jika lulus dikembalikan ke persediaan barang jadi (<code>in_stock</code>), sedangkan unit yang <strong>Rusak/Cacat</strong> dialihkan ke unit reparasi teknisi untuk pergantian komponen atau diproses klaim pembatalan nota.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Mengapa unit yang baru kembali dari sewa tidak bisa langsung disewakan keluar lagi?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p>Sesuai <strong>kebijakan operasional ketat (Wajib QC Ulang - SOP-SEWA-01)</strong>, setiap barang yang telah dipakai di lapangan berpotensi mengalami keausan, kerusakan komponen internal, atau kehilangan aksesori. Oleh karena itu, sistem secara otomatis mengunci unit tersebut dengan status <code>qc_pending</code> dan <code>not_good</code>. Unit wajib diinspeksi ulang di tab <em>QC Unit</em> terlebih dahulu untuk memastikan unit layak dan aman sebelum disewakan ke pelanggan berikutnya.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Bagaimana cara memilih nomor seri dari stok kita saat mendaftarkan aset sewa baru?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p>Pada tab <em>Penerimaan Asset Baru</em>, kolom <strong>Serial Number Unit (Dari Stok)</strong> berupa <strong>Dropdown Selector</strong>. Saat Anda memilih Master Barang (misal: <em>Robotic Explorer</em>), dropdown tersebut otomatis menyaring dan menampilkan nomor seri unit yang ada di stok kita (misal: <code>ROBO-00001</code>, <code>ROBO-00002</code>). Anda cukup mengklik nomor seri yang diinginkan. Anda juga dapat menggunakan tombol <em>🔄 Ketik/Scan Manual</em> jika ingin memindai barcode fisik menggunakan scanner gun.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Bagaimana alur untuk membuka blokir unit sewa yang berstatus Wajib QC Ulang?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p>Cukup klik tombol <strong>[🔍 Lakukan QC Ulang]</strong> yang ada di baris unit pada tabel aset atau riwayat sewa kembali. Anda akan langsung diarahkan ke tab <em>QC Unit MIKMS</em> dengan nomor serial terisi otomatis. Pilih jenis QC <em>"QC Ulang Setelah Sewa Kembali"</em>, lalu lakukan pemeriksaan checklist. Jika seluruh butir dinyatakan <strong>Lolos (PASS)</strong>, blokir seketika terbuka dan unit otomatis kembali berstatus <code>Ready Sewa</code>.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Apakah modul (M01-M07) atau bahan baku mentah (RW) boleh disewakan?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p><strong>Dilarang keras.</strong> Sesuai aturan sistem, <strong>sewa hanya terjadi di Finish Goods (Barang Jadi)</strong> yang telah memiliki serial number terdaftar dengan status <code>can_rent = true</code> dan telah lulus inspeksi QC (berstatus <code>in_stock</code>). Modul M01-M07 dan komponen raw material (RW) murni dialokasikan untuk kebutuhan perakitan dan suku cadang maintenance (repair).</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Apakah pada saat QC teknisi perlu memasukkan jumlah item part yang dicek?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p><strong>Tidak perlu.</strong> Checklist QC difokuskan pada kondisi kelayakan fisik &amp; fungsi (Pass/Fail) tanpa input angka kuantiti. Teknisi cukup mencentang <em>Lolos</em> atau <em>Tidak</em> untuk tiap butir pemeriksaan part.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Bagaimana jika ada satu butir checklist yang TIDAK LOLOS saat QC?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p>Jika ada minimal 1 butir checklist bernilai <em>Tidak</em>, sistem secara otomatis menandai hasil QC sebagai <strong>TIDAK LOLOS</strong>. Unit tidak akan masuk ke status <code>in_stock</code> (tetap berstatus <code>qc_pending</code> atau dialihkan ke antrean <code>repair</code>), dan teknisi <strong>wajib mengisi Catatan Defek</strong> agar bagian perbaikan dapat segera menangani masalah.</p>
              </div>
            </div>

            <div class="collapse collapse-arrow bg-base-200/50 border border-base-300 rounded-xl">
              <input type="checkbox" /> 
              <div class="collapse-title text-xs font-extrabold text-base-content">
                Apakah stiker thermal boks bisa dicetak sekaligus banyak (batch print)?
              </div>
              <div class="collapse-content text-xs text-base-content/70 leading-relaxed">
                <p><strong>Bisa.</strong> Setelah mencatat barang masuk vendor di Universal Scanner Hub, banner hasil input akan menampilkan tombol <em>"Cetak Semua Stiker Thermal (N Label)"</em>. Setiap label diformat otomatis memiliki page-break terpisah sehingga printer thermal akan memotong atau mencetak label unit secara presisi satu per satu.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════
           MODAL PROGRAM KIT (TAMBAH / EDIT)
           ═════════════════════════════════════════════ -->
      <div class="modal" id="program-modal">
        <div class="modal-box max-w-lg bg-base-100 border border-base-300 shadow-2xl p-6">
          <div class="flex justify-between items-center mb-5 pb-3 border-b border-base-200">
            <div>
              <h3 class="font-extrabold text-lg text-base-content" id="prog-modal-title">Tambah Program Kit Baru</h3>
              <p class="text-xs text-base-content/50">Tentukan kode, nama, dan checklist modul penyusun paket</p>
            </div>
            <button class="btn btn-sm btn-circle btn-ghost" onclick="closeProgramModal()">✕</button>
          </div>
          <form id="program-form" onsubmit="submitProgramForm(event)" class="space-y-4">
            <input type="hidden" id="prog-id" value="">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="form-control w-full">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Kode Program * (Unik)</span></label>
                <input type="text" id="prog-code" class="input input-bordered input-sm font-mono font-bold w-full uppercase" placeholder="Contoh: IOT, STEAM" required>
              </div>
              <div class="form-control w-full">
                <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Status Program</span></label>
                <select id="prog-active" class="select select-bordered select-sm font-bold w-full">
                  <option value="1">Aktif (Tersedia Dipilih)</option>
                  <option value="0">Nonaktif</option>
                </select>
              </div>
            </div>

            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Nama Program Kit *</span></label>
              <input type="text" id="prog-name" class="input input-bordered input-sm font-bold w-full" placeholder="Contoh: IoT Smart System Kit" required>
            </div>

            <div class="form-control w-full">
              <label class="label"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Deskripsi / Catatan Program</span></label>
              <textarea id="prog-desc" class="textarea textarea-bordered textarea-sm w-full" rows="2" placeholder="Catatan kegunaan atau kurikulum..."></textarea>
            </div>

            <div class="form-control w-full">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <label class="label p-0"><span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Pilih Modul MIKMS Penyusun *</span></label>
                  <button type="button" class="btn btn-ghost btn-xs text-[10px] text-primary underline p-0 h-auto min-h-0 font-bold" onclick="selectStandardModules()">M01–M07 (Std)</button>
                  <span class="text-base-content/20 text-xs">|</span>
                  <button type="button" class="btn btn-ghost btn-xs text-[10px] text-base-content/60 underline p-0 h-auto min-h-0" onclick="clearProgramModules()">Reset</button>
                </div>
                <span class="text-[11px] font-bold text-primary" id="prog-calc-pcs">Pilihan: 0 modul (~0 item)</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-base-200/50 rounded-xl border border-base-200 max-h-56 overflow-y-auto" id="prog-modules-checklist">
                <!-- Checkboxes populated dynamically -->
              </div>
              <span class="text-[10px] text-base-content/50 mt-1">Minimal pilih 1 modul. Estimasi item komponen dihitung otomatis dari BOM.</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-base-200">
              <button type="button" class="btn btn-sm btn-ghost" onclick="closeProgramModal()">Batal</button>
              <button type="submit" class="btn btn-sm btn-primary font-bold shadow-md" id="prog-submit-btn">
                Simpan Program
              </button>
            </div>
          </form>
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

<!-- ═══ MODAL CETAK BULK STIKER MICRO:BIT GRID 1x1 CM ═══ -->
<div class="modal" id="microbit-bulk-modal">
  <div class="modal-box max-w-lg bg-base-100 border border-base-300 shadow-2xl p-6">
    <div class="flex justify-between items-center mb-3">
      <div class="flex items-center gap-2">
        <span class="text-xl">🏷️</span>
        <h3 class="font-extrabold text-base text-base-content">Cetak Bulk Stiker Micro:bit (1x1 cm Grid)</h3>
      </div>
      <button class="btn btn-sm btn-circle btn-ghost" onclick="closeMicrobitBulkModal()">✕</button>
    </div>
    
    <p class="text-xs text-base-content/60 mb-4 leading-relaxed">
      Mencetak lembaran stiker mini <strong>1x1 cm (10x10 mm)</strong> dalam susunan Grid Sheet untuk ditempelkan langsung pada board fisik Micro:bit. Setiap stiker dilengkapi <strong>Micro QR Code 2D</strong> & nomor seri terbaca.
    </p>

    <!-- Quick Action Fillers -->
    <div class="flex flex-wrap items-center gap-1.5 mb-2.5">
      <span class="text-[10px] uppercase font-bold text-base-content/50 mr-1">Isi Cepat:</span>
      <button type="button" class="btn btn-xs btn-outline btn-secondary font-bold" onclick="fillMicrobitBulkWithChecked()">
        ☑️ Unit Dicentang (<span id="mb-fill-checked-count">0</span>)
      </button>
      <button type="button" class="btn btn-xs btn-outline btn-neutral font-bold" onclick="fillMicrobitBulkWithTableMlk()">
        📋 Semua MLK di Tabel
      </button>
      <button type="button" class="btn btn-xs btn-ghost text-error" onclick="clearMicrobitBulkText()">
        🗑️ Kosongkan
      </button>
    </div>

    <!-- Textarea Serial Numbers -->
    <div class="form-control w-full mb-3">
      <label class="label py-1">
        <span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Daftar Serial Number Unit *</span>
        <span class="label-text-alt text-[10px] text-base-content/40">Bisa paste dari Excel/CSV</span>
      </label>
      <textarea id="mb-bulk-serials" class="textarea textarea-bordered text-xs font-mono w-full h-32 leading-relaxed" placeholder="Contoh:&#10;MLK-00001&#10;MLK-00002&#10;MLK-00003&#10;&#10;(Pisahkan dengan baris baru, koma, atau spasi)" oninput="updateMicrobitBulkSummary()"></textarea>
    </div>

    <!-- Copies Selection -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
      <div class="form-control w-full">
        <label class="label py-1">
          <span class="label-text text-[10px] font-bold uppercase tracking-wider text-base-content/60">Jumlah Stiker per Unit *</span>
        </label>
        <select id="mb-bulk-copies" class="select select-bordered select-sm font-bold text-xs w-full" onchange="updateMicrobitBulkSummary()">
          <option value="1" selected>1 Stiker / Unit (Standar Fisik Board)</option>
          <option value="2">2 Stiker / Unit (Board + Boks/Kompartemen)</option>
          <option value="3">3 Stiker / Unit</option>
          <option value="4">4 Stiker / Unit</option>
          <option value="5">5 Stiker / Unit</option>
        </select>
      </div>

      <!-- Live Calculation Card -->
      <div class="bg-base-200 border border-base-300 rounded-xl p-2.5 flex flex-col justify-center">
        <div class="text-[10px] uppercase font-bold text-base-content/50">Total Stiker Dicetak:</div>
        <div class="flex items-baseline gap-2 mt-0.5">
          <span class="text-xl font-black text-secondary" id="mb-summary-total-stickers">0</span>
          <span class="text-xs text-base-content/70 font-semibold" id="mb-summary-detail">(0 unit × 1 copy)</span>
        </div>
      </div>
    </div>

    <!-- Modal Actions -->
    <div class="flex gap-2 pt-2 border-t border-base-300">
      <button type="button" class="btn btn-sm btn-ghost flex-1 font-bold" onclick="closeMicrobitBulkModal()">Batal</button>
      <button type="button" class="btn btn-sm btn-secondary flex-1 font-bold text-white shadow-md" onclick="submitMicrobitBulkPrint()">
        🖨️ Cetak Lembaran Grid Sekarang
      </button>
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
let currentUniversalItem = null;
let currentUniversalMikmsUnit = null;
let scanSessionHistory = [];
let isRouterNavigating = false;

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
   PRINT AREA ISOLATION & CLEANUP
   ═════════════════════════════════════════════ */
function cleanPrintArea() {
  try {
    const pa = document.getElementById('print-area');
    if (pa) {
      pa.innerHTML = '';
      pa.className = 'hidden';
      pa.removeAttribute('style');
      pa.style.cssText = 'display:none !important;';
    }
  } catch (e) {
    console.warn('cleanPrintArea error:', e);
  }
}

// Global afterprint listener to guarantee print-area is sanitized
window.addEventListener('afterprint', () => {
  cleanPrintArea();
});

/* ═════════════════════════════════════════════
   AUTH / NIK & PASSWORD
   ═════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  cleanPrintArea();
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
   INIT & ROUTER
   ═════════════════════════════════════════════ */
function getHashForPage(name, subTab) {
  if (name === 'mikms') {
    return '#/mikms/' + (subTab || currentMikmsTab || 'unitregistry');
  }
  if (name === 'sewadash') return '#/sewa';
  if (name === 'sewamutasi') return '#/sewa/mutasi';
  if (name === 'sewaunits') return '#/sewa/units';
  if (name === 'sewacard') return '#/sewa/card';
  if (name === 'mikmsguide') return '#/guide';
  return '#/' + name;
}

function handleHashRouting() {
  if (!token || !user) return; // Belum login

  let rawHash = window.location.hash.trim();
  if (!rawHash || rawHash === '#' || rawHash === '#/') {
    if (window.location.hash !== '#/dashboard') {
      window.location.hash = '#/dashboard';
    }
    return;
  }

  const clean = rawHash.replace(/^#\/?/, '');
  const parts = clean.split('/').map(p => p.trim()).filter(Boolean);
  const root = parts[0] ? parts[0].toLowerCase() : 'dashboard';
  const sub = parts[1] ? parts[1].toLowerCase() : null;

  isRouterNavigating = true;
  try {
    if (root === 'dashboard') {
      goPage('dashboard', null, false);
    } else if (root === 'scan') {
      goPage('scan', null, false);
    } else if (root === 'stock') {
      goPage('stock', null, false);
    } else if (root === 'transactions') {
      goPage('transactions', null, false);
    } else if (root === 'stockcard') {
      goPage('stockcard', null, false);
    } else if (root === 'print') {
      goPage('print', null, false);
    } else if (root === 'sewa') {
      if (sub === 'mutasi') goPage('sewamutasi', null, false);
      else if (sub === 'units') goPage('sewaunits', null, false);
      else if (sub === 'card') goPage('sewacard', null, false);
      else goPage('sewadash', null, false);
    } else if (['sewadash', 'sewamutasi', 'sewaunits', 'sewacard'].includes(root)) {
      goPage(root, null, false);
    } else if (root === 'mikms') {
      if (sub === 'directinput') {
        goPage('scan', null, true);
        showToast('Penerimaan vendor kini langsung dari Universal Smart Scanner (Barang Masuk +)');
        return;
      }
      const validMikmsTabs = ['packageorder', 'unitregistry', 'qcunit', 'bundling', 'dispatch', 'production', 'modulestocks', 'bomlist', 'repair', 'opname', 'logs'];
      const targetTab = (sub && validMikmsTabs.includes(sub)) ? sub : 'unitregistry';
      goPage('mikms', targetTab, false);
    } else if (root === 'guide' || root === 'mikmsguide') {
      goPage('mikmsguide', null, false);
    } else if (['master', 'locations', 'vendors', 'customers', 'categories', 'users', 'programs'].includes(root)) {
      goPage(root, null, false);
    } else {
      goPage('dashboard', null, false);
    }
  } finally {
    isRouterNavigating = false;
  }
}

window.addEventListener('hashchange', handleHashRouting);

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
  await Promise.all([loadStock(), loadTransactions(), loadMasterDataDependents(), loadMikmsProducts()]);
  renderDashboard();
  buildSelects();
  loadPackageProgramsDropdown();

  // URL Hash routing on startup/refresh
  handleHashRouting();
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
function goPage(name, mikmsTab, updateHash = true) {
  const targetEl = document.getElementById('pg-' + name);
  if (!targetEl) return;

  // Turn off camera if navigating away from scan
  if (name !== 'scan' && cameraActive) {
    stopCamera();
  }

  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  targetEl.classList.add('active');
  
  // Clear highlight on all items
  document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active', 'bg-primary/10', 'text-primary', 'border', 'border-primary/20'));
  
  // Highlight the active sidebar item
  let activeId = 'sb-link-' + name;
  if (['sewadash', 'sewamutasi', 'sewaunits', 'sewacard'].includes(name)) {
    activeId = 'sb-link-sewadash';
  } else if (['stock', 'stockcard', 'transactions'].includes(name)) {
    activeId = 'sb-link-stock';
  } else if (name === 'mikms') {
    activeId = 'sb-link-mikms';
  } else if (['master', 'locations', 'vendors', 'customers', 'categories', 'users', 'programs'].includes(name)) {
    const details = document.getElementById('sb-master-details');
    if (details) details.setAttribute('open', '');
    activeId = 'sb-link-' + name;
  }
  const activeLink = document.getElementById(activeId);
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

  // Title map — includes MIKMS tab-specific titles
  const mikmsTitles = {
    packageorder: 'Perakitan Kit MLK (BOM)',
    unitregistry: 'Unit Registry',
    qcunit:       'QC Unit — Checklist',
    bundling:     'Packaging Robotic Explorer',
    dispatch:     'Dispatch & Retur Unit',
    production:   'Sub-Assembly Modul (RW)',
    modulestocks: 'Stok Modul Jadi (RW)',
    bomlist:      'Master BOM Kit',
    repair:       'Maintenance & Repair',
    opname:       'Stock Opname MIKMS',
    logs:         'Log Aktivitas MIKMS',
  };
  const titles = { 
    dashboard: 'Dashboard Terpadu', 
    scan: 'Universal Smart Scanner Hub', 
    stock: 'Katalog Semua Stok', 
    transactions: 'Riwayat Transaksi', 
    stockcard: 'Kartu Stok Terpadu', 
    print: 'Cetak Label Barcode & QR', 
    master: 'Master Barang',
    sewadash: 'Dashboard Sewa & Asset (Finish Goods)',
    sewamutasi: 'Transaksi Sewa',
    sewaunits: 'Daftar Unit Asset',
    sewacard: 'Riwayat & Kartu Asset',
    locations: 'Manajemen Lokasi',
    vendors: 'Master Vendor',
    customers: 'Master Customer',
    categories: 'Master Kategori',
    users: 'Master User & Hak Akses',
    programs: 'Master Program Kit',
    mikms: 'Operasional MIKMS',
    mikmsguide: 'SOP & Panduan MIKMS'
  };
  // Use MIKMS tab-specific title if available
  const pageTitle = (name === 'mikms' && mikmsTab && mikmsTitles[mikmsTab]) ? mikmsTitles[mikmsTab] : (titles[name] || name);
  const titleEl = document.getElementById('tb-page-title');
  if (titleEl) titleEl.textContent = pageTitle;
  const mainScroll = document.getElementById('main-scroll');
  if (mainScroll) mainScroll.scrollTop = 0;
  closeSidebar();

  // Sync browser URL hash if requested and not handling hashchange
  if (updateHash && !isRouterNavigating) {
    const targetHash = getHashForPage(name, mikmsTab);
    if (window.location.hash !== targetHash) {
      window.location.hash = targetHash;
    }
  }

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
  if (name === 'programs') loadProgramsPage();
  if (name === 'mikms') {
    if (mikmsTab) {
      currentMikmsTab = mikmsTab;
      switchMikmsTab(mikmsTab, false);
    }
    loadMikmsPage();
  }
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

  const scLokasi = document.getElementById('sc-item-lokasi');
  if (scLokasi && locations.length > 0) {
    scLokasi.innerHTML = locations.map(l => `<option value="${escHtml(l.nama)}">${escHtml(l.nama)}</option>`).join('');
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

  if (typeof populateUniversalPartnerDl === 'function') {
    populateUniversalPartnerDl(currentMode || 'masuk');
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

  // MIKMS summary cards on dashboard
  fetchApi('/api/mikms/dashboard').then(res => {
    if (res && res.data) {
      const d = res.data;
      const totalUnits = d.total_units ?? (d.total_by_status || []).reduce((a, s) => a + s.count, 0);
      const dispatchedUnits = d.units_dispatched ?? (d.total_by_status || []).find(s => s.status === 'dipinjam')?.count ?? 0;
      const elBoxes = document.getElementById('dash-mk-boxes');
      const elLoan = document.getElementById('dash-mk-loan');
      const elOrders = document.getElementById('dash-mk-orders');
      if (elBoxes) elBoxes.textContent = totalUnits;
      if (elLoan) elLoan.textContent = dispatchedUnits;
      if (elOrders) elOrders.textContent = d.total_packages_ordered || 0;
    }
  }).catch(() => {});
  fetchApi('/api/mikms/module-stocks').then(res => {
    if (res && res.data) {
      const totalModules = res.data.reduce((a, m) => a + (m.stock_ready || 0), 0);
      const elMod = document.getElementById('dash-mk-modules');
      if (elMod) elMod.textContent = totalModules;
    }
  }).catch(() => {});
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

  const unDl = document.getElementById('scan-universal-dl');
  if (unDl) {
    let opts = master.map(m => `<option value="${m.kode}">${m.kode} — ${m.nama} (Stok: ${m.stok})</option>`);
    if (typeof mikmsProducts !== 'undefined' && mikmsProducts && mikmsProducts.length) {
      mikmsProducts.forEach(p => {
        opts.push(`<option value="${p.code}">${p.code} — ${p.name} (Finish Goods MIKMS)</option>`);
      });
    }
    unDl.innerHTML = opts.join('');
  }
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
   UNIVERSAL SMART SCANNER HUB (ALL-IN-ONE)
   ═════════════════════════════════════════════ */
let universalScanDebounce = null;

function onUniversalScanInput(val) {
  clearTimeout(universalScanDebounce);
  if (!val) return;
  const q = val.trim().toUpperCase();
  const isExactMaster = master.some(m => m.kode.toUpperCase() === q);
  const isMikmsPattern = /^(MLK|ROBO|JIMU|ERBO|ALK|AST)-[0-9A-Z]+$/i.test(q);
  if (isExactMaster || isMikmsPattern) {
    universalScanDebounce = setTimeout(() => {
      processUniversalScan(q);
    }, 250);
  }
}

function findMatchingMikmsProduct(item) {
  if (!item || typeof mikmsProducts === 'undefined' || !mikmsProducts || !mikmsProducts.length) return null;
  const kode = (item.kode || '').toUpperCase();
  const nama = (item.nama || '').toLowerCase();
  const produk = (item.produk || '').toLowerCase();
  
  return mikmsProducts.find(p => {
    const pCode = p.code.toUpperCase();
    const pName = p.name.toLowerCase();
    
    if (kode === pCode || kode.startsWith(pCode)) return true;
    if (pCode === 'JIMU' && (kode === 'RJ01' || nama.includes('jimu') || produk.includes('jimu'))) return true;
    if (pCode === 'ERBO' && (kode === 'ERBO01' || nama.includes('erbo') || produk.includes('erbo'))) return true;
    if (pCode === 'ALK' && (kode === 'ALK01' || nama.includes('arduino') || nama.includes('alk') || produk.includes('arduino'))) return true;
    if (pCode === 'MLK' && (kode === 'MLK01' || nama.includes('microbit') || nama.includes('mlk') || produk.includes('microbit'))) return true;
    if (pCode === 'ROBO' && (kode === 'RE01' || nama.includes('robotic explorer') || produk.includes('robotic explorer'))) return true;
    
    if (nama === pName || produk === pName) return true;
    return false;
  }) || null;
}

async function processUniversalScan(query) {
  if (!query) {
    showToast('Masukkan kode barang atau scan barcode / QR', true);
    return;
  }
  query = query.trim();
  if (query.includes('|')) {
    query = query.split('|')[0].trim();
  }
  const qUpper = query.toUpperCase();
  const qLower = query.toLowerCase();

  // Reset all card states
  document.getElementById('sc-empty-state').classList.add('hidden');
  document.getElementById('sc-item-card').classList.add('hidden');
  document.getElementById('sc-mikms-card').classList.add('hidden');
  document.getElementById('sc-notfound-card').classList.add('hidden');
  const banner = document.getElementById('sc-fg-generated-banner');
  if (banner) banner.classList.add('hidden');

  // STEP 1: Cek di Master Barang Inventaris Umum (Bahan Baku / Komponen / Sparepart / Finish Goods)
  let foundItem = master.find(m => m.kode.toUpperCase() === qUpper || m.nama.toLowerCase() === qLower);
  if (!foundItem && typeof mikmsProducts !== 'undefined' && mikmsProducts && mikmsProducts.length) {
    // If query matches a Finish Good code or name (e.g. JIMU, ERBO, ALK, ROBO, MLK)
    const fgMatch = mikmsProducts.find(p => p.code.toUpperCase() === qUpper || p.name.toLowerCase() === qLower);
    if (fgMatch) {
      foundItem = master.find(m => findMatchingMikmsProduct(m)?.code === fgMatch.code);
    }
  }

  if (foundItem) {
    currentUniversalItem = foundItem;
    currentUniversalMikmsUnit = null;

    document.getElementById('sc-item-kode').textContent = foundItem.kode;
    document.getElementById('sc-item-nama').textContent = foundItem.nama;
    document.getElementById('sc-item-sub').textContent = `Kategori: ${foundItem.category?.nama || foundItem.produk || '—'} | Satuan: ${foundItem.satuan || 'pcs'}`;
    
    // Check if item is a Finish Good in MIKMS
    const matchedFg = findMatchingMikmsProduct(foundItem);
    const fgBadge = document.getElementById('sc-item-fg-badge');
    if (fgBadge) {
      if (matchedFg) {
        fgBadge.textContent = `🤖 FINISH GOOD: ${matchedFg.name} (${matchedFg.code})`;
        fgBadge.classList.remove('hidden');
      } else {
        fgBadge.classList.add('hidden');
      }
    }

    // Badge stok fisik terkini
    const badge = document.getElementById('sc-item-stok-badge');
    const minStok = foundItem.min_stok || 5;
    let badgeClass = 'badge-success text-white';
    if (foundItem.stok <= 0) badgeClass = 'badge-error text-white';
    else if (foundItem.stok <= minStok) badgeClass = 'badge-warning text-white';
    badge.className = `badge badge-lg font-black text-sm px-3 py-3 ${badgeClass}`;
    badge.textContent = `Stok: ${foundItem.stok} ${foundItem.satuan || 'pcs'}`;

    // Reset qty & mode
    document.getElementById('sc-item-qty').value = 1;
    setUniversalItemMode(currentMode || 'masuk');
    validateScQty();

    document.getElementById('sc-item-card').classList.remove('hidden');

    const st = document.getElementById('scan-status');
    if (st) {
      st.textContent = `✓ Terdeteksi ${matchedFg ? 'Finish Good MIKMS: ' + matchedFg.name : 'Inventaris: ' + foundItem.kode + ' — ' + foundItem.nama}`;
      st.className = 'alert alert-success mt-4 text-xs justify-center font-bold text-white';
    }
    try { navigator.vibrate?.([80, 40, 80]); } catch(e) {}
    addScanSessionHistory(matchedFg ? 'FINISH_GOOD' : 'INV', `${foundItem.kode} — ${foundItem.nama}`, `Stok: ${foundItem.stok} ${foundItem.satuan || 'pcs'}`);
    return;
  }

  // STEP 2: Cek di MIKMS Finish Goods Unit Registry via API
  const st = document.getElementById('scan-status');
  if (st) {
    st.textContent = `🔍 Memeriksa Unit Registry MIKMS untuk "${query}"...`;
    st.className = 'alert alert-info mt-4 text-xs justify-center font-bold text-white';
  }

  try {
    const res = await api('/api/mikms/units/' + encodeURIComponent(query));
    if (res.ok) {
      const d = await res.json();
      if (d.success && d.data) {
        const unit = d.data;
        currentUniversalMikmsUnit = unit;
        currentUniversalItem = null;

        document.getElementById('sc-mikms-type').textContent = unit.source_type ? unit.source_type.toUpperCase() : 'FINISH GOODS';
        document.getElementById('sc-mikms-prodname').textContent = unit.product?.name || 'Unit Finish Goods';
        document.getElementById('sc-mikms-serial').textContent = unit.serial_number;

        // Status lifecycle badge
        const statusBadge = document.getElementById('sc-mikms-status-badge');
        const statusClasses = {
          in_stock: 'badge-success text-white',
          dispatched: 'badge-info text-white',
          rented: 'badge-primary text-white',
          qc_pending: 'badge-warning text-white',
          qc_failed: 'badge-error text-white',
          maintenance: 'badge-error text-white',
          retired: 'badge-neutral text-white'
        };
        statusBadge.className = `badge text-xs font-bold px-3 py-2.5 ${statusClasses[unit.status] || 'badge-ghost'}`;
        statusBadge.textContent = unit.status_label || unit.status;

        // Status QC badge
        const qcBadge = document.getElementById('sc-mikms-qc-badge');
        if (unit.status === 'qc_failed') {
          qcBadge.innerHTML = '❌ QC Terakhir Gagal';
          qcBadge.className = 'text-xs font-bold text-error flex items-center gap-1';
        } else if (unit.status === 'qc_pending') {
          qcBadge.innerHTML = '⚠️ Menunggu QC';
          qcBadge.className = 'text-xs font-bold text-warning flex items-center gap-1';
        } else {
          const qcCount = unit.qc_logs ? unit.qc_logs.length : 0;
          qcBadge.innerHTML = `✓ QC Lolos (${qcCount}x dicek)`;
          qcBadge.className = 'text-xs font-bold text-success flex items-center gap-1';
        }

        // Bundling children (misal Robotic Explorer yang berisi MLK + JIMU)
        const childWrap = document.getElementById('sc-mikms-children-wrap');
        const childList = document.getElementById('sc-mikms-children-list');
        if (unit.children && unit.children.length > 0) {
          childWrap.classList.remove('hidden');
          childList.innerHTML = unit.children.map(c => `
            <div class="flex items-center justify-between py-1 border-b border-base-300/40 last:border-none">
              <span>• ${escHtml(c.product?.name || 'Komponen')}</span>
              <span class="font-mono text-primary font-bold">${escHtml(c.serial_number)}</span>
            </div>
          `).join('');
        } else {
          childWrap.classList.add('hidden');
        }

        // Contextual buttons berdasarkan status
        const isInStock = unit.status === 'in_stock';
        const isOut = unit.status === 'dispatched' || unit.status === 'rented';
        const isQcNeeded = unit.status === 'qc_pending' || unit.status === 'qc_failed';

        document.querySelectorAll('.sc-act-instock').forEach(b => b.style.display = isInStock ? '' : 'none');
        document.querySelectorAll('.sc-act-dispatched').forEach(b => b.style.display = isOut ? '' : 'none');
        document.querySelectorAll('.sc-act-qc').forEach(b => b.style.display = isQcNeeded ? '' : 'none');

        const gridBtn = document.getElementById('sc-mikms-grid-btn');
        if (gridBtn) {
          gridBtn.style.display = (unit.serial_number.startsWith('MLK-') || unit.product?.code === 'MLK') ? '' : 'none';
        }

        document.getElementById('sc-mikms-card').classList.remove('hidden');

        if (st) {
          st.textContent = `✓ Terdeteksi Unit MIKMS: ${unit.serial_number} (${unit.product?.name || ''})`;
          st.className = 'alert alert-success mt-4 text-xs justify-center font-bold text-white';
        }
        try { navigator.vibrate?.([80, 40, 80]); } catch(e) {}
        addScanSessionHistory('MIKMS', `${unit.serial_number} — ${unit.product?.code || ''}`, `Status: ${unit.status_label || unit.status}`);
        return;
      }
    }
  } catch (err) {
    console.warn('Error querying MIKMS unit:', err);
  }

  // STEP 3: Tidak ditemukan di Master maupun Unit Registry
  currentUniversalItem = null;
  currentUniversalMikmsUnit = null;
  document.getElementById('sc-notfound-code').textContent = query;
  document.getElementById('sc-notfound-card').classList.remove('hidden');

  if (st) {
    st.textContent = `⚠️ Kode "${query}" tidak ditemukan di sistem`;
    st.className = 'alert alert-warning mt-4 text-xs justify-center font-bold text-white';
  }
  addScanSessionHistory('NOT_FOUND', query, 'Tidak terdaftar');
}

function clearUniversalScan() {
  const input = document.getElementById('scan-universal-input');
  if (input) {
    input.value = '';
    input.focus();
  }
  currentUniversalItem = null;
  currentUniversalMikmsUnit = null;
  document.getElementById('sc-empty-state').classList.remove('hidden');
  document.getElementById('sc-item-card').classList.add('hidden');
  document.getElementById('sc-mikms-card').classList.add('hidden');
  document.getElementById('sc-notfound-card').classList.add('hidden');
  const banner = document.getElementById('sc-fg-generated-banner');
  if (banner) banner.classList.add('hidden');
  const fgBadge = document.getElementById('sc-item-fg-badge');
  if (fgBadge) fgBadge.classList.add('hidden');
  const fgHint = document.getElementById('sc-fg-incoming-hint');
  if (fgHint) fgHint.classList.add('hidden');

  const st = document.getElementById('scan-status');
  if (st) {
    st.textContent = 'Siap memindai QR Code atau Barcode...';
    st.className = 'alert alert-neutral mt-4 text-xs justify-center font-bold';
  }
}

function setUniversalItemMode(m) {
  currentMode = m;
  const btnMasuk = document.getElementById('sc-btn-mode-masuk');
  const btnKeluar = document.getElementById('sc-btn-mode-keluar');
  const partnerLbl = document.getElementById('sc-partner-lbl');
  const partnerInput = document.getElementById('sc-item-partner');
  const submitBtn = document.getElementById('sc-item-submit-btn');
  const qty = parseInt(document.getElementById('sc-item-qty')?.value) || 1;
  const matchedFg = findMatchingMikmsProduct(currentUniversalItem);
  const hintEl = document.getElementById('sc-fg-incoming-hint');
  const hintText = document.getElementById('sc-fg-incoming-hint-text');

  if (btnMasuk && btnKeluar) {
    if (m === 'masuk') {
      btnMasuk.className = 'btn btn-sm font-extrabold rounded-xl btn-primary shadow-xs';
      btnKeluar.className = 'btn btn-sm font-extrabold rounded-xl btn-ghost text-error';
      if (partnerLbl) partnerLbl.textContent = 'Sumber / Vendor *';
      if (partnerInput) partnerInput.placeholder = 'Pilih atau ketik vendor / asal barang...';
      
      if (matchedFg && matchedFg.type === 'direct') {
        if (hintEl) {
          hintEl.classList.remove('hidden');
          if (hintText) hintText.innerHTML = `Penerimaan <strong>${escHtml(matchedFg.name)}</strong> akan otomatis menerbitkan <strong>Serial MIKMS (${matchedFg.code}-xxxxx)</strong> berstatus <span class="badge badge-warning badge-2xs font-bold">Menunggu QC</span>.`;
        }
        if (submitBtn) {
          submitBtn.className = 'btn btn-primary btn-sm w-full font-extrabold shadow-md';
          submitBtn.textContent = `↓ Catat Barang Masuk & Terbitkan Serial MIKMS (+${qty})`;
        }
      } else if (matchedFg && matchedFg.type === 'assembled') {
        if (hintEl) {
          hintEl.classList.remove('hidden');
          if (hintText) hintText.innerHTML = `💡 <strong>${escHtml(matchedFg.name)}</strong> adalah produk rakitan BOM. Untuk memotong bahan baku modul & terbitkan serial, gunakan menu <a href="javascript:void(0)" onclick="goPage('mikms', 'packageorder')" class="underline font-black text-primary">⚙️ Perakitan Kit MLK</a>.`;
        }
        if (submitBtn) {
          submitBtn.className = 'btn btn-primary btn-sm w-full font-extrabold shadow-md';
          submitBtn.textContent = `↓ Simpan Barang Masuk (+${qty})`;
        }
      } else if (matchedFg && matchedFg.type === 'bundle') {
        if (hintEl) {
          hintEl.classList.remove('hidden');
          if (hintText) hintText.innerHTML = `💡 <strong>${escHtml(matchedFg.name)}</strong> adalah produk kemasan bundle (MLK + JIMU). Gunakan menu <a href="javascript:void(0)" onclick="goPage('mikms', 'bundling')" class="underline font-black text-primary">🎁 Packaging ROBO</a>.`;
        }
        if (submitBtn) {
          submitBtn.className = 'btn btn-primary btn-sm w-full font-extrabold shadow-md';
          submitBtn.textContent = `↓ Simpan Barang Masuk (+${qty})`;
        }
      } else {
        if (hintEl) hintEl.classList.add('hidden');
        if (submitBtn) {
          submitBtn.className = 'btn btn-primary btn-sm w-full font-extrabold shadow-md';
          submitBtn.textContent = `↓ Simpan Barang Masuk (+${qty})`;
        }
      }
    } else {
      if (hintEl) hintEl.classList.add('hidden');
      btnMasuk.className = 'btn btn-sm font-extrabold rounded-xl btn-ghost text-primary';
      btnKeluar.className = 'btn btn-sm font-extrabold rounded-xl btn-error text-white shadow-xs';
      if (partnerLbl) partnerLbl.textContent = 'Tujuan / Penerima / Divisi *';
      if (partnerInput) partnerInput.placeholder = 'Pilih atau ketik penerima / customer / divisi...';
      if (submitBtn) {
        submitBtn.className = 'btn btn-error text-white btn-sm w-full font-extrabold shadow-md';
        submitBtn.textContent = `↑ Simpan Barang Keluar (-${qty})`;
      }
    }
  }
  populateUniversalPartnerDl(m);
  validateScQty();
}

function adjustScQty(delta) {
  const qtyInput = document.getElementById('sc-item-qty');
  let val = (parseInt(qtyInput?.value) || 1) + delta;
  if (val < 1) val = 1;
  if (qtyInput) qtyInput.value = val;
  validateScQty();
}

function validateScQty() {
  const qtyInput = document.getElementById('sc-item-qty');
  let qty = parseInt(qtyInput?.value) || 1;
  if (qty < 1) {
    qty = 1;
    if (qtyInput) qtyInput.value = 1;
  }
  const submitBtn = document.getElementById('sc-item-submit-btn');
  const matchedFg = findMatchingMikmsProduct(currentUniversalItem);
  if (submitBtn) {
    if (currentMode === 'masuk' && matchedFg && matchedFg.type === 'direct') {
      submitBtn.textContent = `↓ Catat Barang Masuk & Terbitkan Serial MIKMS (+${qty})`;
    } else {
      submitBtn.textContent = currentMode === 'masuk' ? `↓ Simpan Barang Masuk (+${qty})` : `↑ Simpan Barang Keluar (-${qty})`;
    }
  }
  const warning = document.getElementById('sc-qty-warning');
  if (warning) {
    if (currentMode === 'keluar' && currentUniversalItem && qty > currentUniversalItem.stok) {
      warning.classList.remove('hidden');
      warning.textContent = `⚠️ Qty keluar (${qty}) melebihi sisa stok (${currentUniversalItem.stok})!`;
    } else {
      warning.classList.add('hidden');
    }
  }
}

function populateUniversalPartnerDl(mode) {
  const dl = document.getElementById('sc-partner-dl');
  if (!dl) return;
  if (mode === 'masuk') {
    const defaultOptions = ['Transfer antar gudang', 'Retur dari karyawan', 'Produksi internal', 'Lainnya'];
    const vendorOptions = (vendors || []).map(v => v.nama);
    const combined = [...new Set([...vendorOptions, ...defaultOptions])];
    dl.innerHTML = combined.map(o => `<option value="${escHtml(o)}"></option>`).join('');
  } else {
    const defaultOptions = ['Divisi Perakitan Kit', 'Divisi Robotik', 'Customer Project', 'Divisi QC', 'Lainnya'];
    const custOptions = (customers || []).map(c => c.nama);
    const combined = [...new Set([...custOptions, ...defaultOptions])];
    dl.innerHTML = combined.map(o => `<option value="${escHtml(o)}"></option>`).join('');
  }
}

async function submitUniversalItemScan() {
  if (!currentUniversalItem) {
    showToast('Pilih atau scan barang terlebih dahulu', true);
    return;
  }
  const kode = currentUniversalItem.kode;
  const nama = currentUniversalItem.nama;
  const qty = parseInt(document.getElementById('sc-item-qty')?.value) || 0;
  const lokasi = document.getElementById('sc-item-lokasi')?.value || 'Gudang Utama';
  const satuan = currentUniversalItem.satuan || 'pcs';
  const partner = document.getElementById('sc-item-partner')?.value.trim();

  if (!kode || !nama || qty < 1) {
    showToast('Lengkapi jumlah unit (*)', true);
    return;
  }
  if (!partner) {
    showToast(currentMode === 'masuk' ? 'Pilih atau isi sumber / vendor' : 'Pilih atau isi penerima / customer', true);
    return;
  }
  if (currentMode === 'keluar' && qty > currentUniversalItem.stok) {
    if (!confirm(`Peringatan: Qty keluar (${qty}) melebihi sisa stok fisik (${currentUniversalItem.stok}). Lanjutkan pencatatan minus?`)) {
      return;
    }
  }

  const no_po = document.getElementById('sc-item-po')?.value.trim() || '';
  const no_prn = document.getElementById('sc-item-prn')?.value.trim() || '';
  const job_number = document.getElementById('sc-item-job')?.value.trim() || '';
  const transfer_order = document.getElementById('sc-item-to')?.value.trim() || '';
  const catatan = document.getElementById('sc-item-catatan')?.value.trim() || '';
  const petugas = (user && user.name) ? user.name : 'Petugas';

  const tx = {
    client_id: 'GS-' + Date.now(),
    kode,
    nama,
    tipe: currentMode,
    qty,
    satuan,
    lokasi,
    sumber: currentMode === 'masuk' ? partner : '',
    penerima: currentMode === 'keluar' ? partner : '',
    no_po,
    no_prn,
    job_number,
    transfer_order,
    catatan,
    petugas,
    transaction_date: new Date().toISOString().split('T')[0]
  };

  // Local log and master stock update
  txLog.unshift({
    id: tx.client_id,
    item: { kode: tx.kode, nama: tx.nama, satuan: tx.satuan, produk: currentUniversalItem.category?.nama || '' },
    tipe: tx.tipe,
    qty: tx.qty,
    transaction_date: tx.transaction_date,
    sumber: tx.sumber,
    penerima: tx.penerima,
    lokasi: tx.lokasi,
    petugas: tx.petugas,
    created_at: new Date().toISOString(),
    no_po: tx.no_po,
    no_prn: tx.no_prn
  });

  const idx = master.findIndex(m => m.kode === tx.kode);
  if (idx !== -1) {
    master[idx].stok += (tx.tipe === 'masuk' ? tx.qty : -tx.qty);
    currentUniversalItem.stok = master[idx].stok;
  }
  pending.push(tx);
  localStorage.setItem('gs_log', JSON.stringify(txLog));
  localStorage.setItem('gs_master', JSON.stringify(master));
  localStorage.setItem('gs_pending', JSON.stringify(pending));
  updatePendingBadge();

  // Update card display
  const badge = document.getElementById('sc-item-stok-badge');
  if (badge) {
    const minStok = currentUniversalItem.min_stok || 5;
    let badgeClass = 'badge-success text-white';
    if (currentUniversalItem.stok <= 0) badgeClass = 'badge-error text-white';
    else if (currentUniversalItem.stok <= minStok) badgeClass = 'badge-warning text-white';
    badge.className = `badge badge-lg font-black text-sm px-3 py-3 ${badgeClass}`;
    badge.textContent = `Stok: ${currentUniversalItem.stok} ${currentUniversalItem.satuan || 'pcs'}`;
  }

  showToast(`✓ ${tx.kode} ${tx.tipe === 'masuk' ? '+' : '-'}${tx.qty} ${tx.satuan} tersimpan`);
  addScanSessionHistory(tx.tipe === 'masuk' ? 'MASUK' : 'KELUAR', `${tx.kode} (${tx.tipe === 'masuk' ? '+' : '-'}${tx.qty} ${tx.satuan})`, `Sisa: ${currentUniversalItem.stok}`);

  // Check if item is a direct finish good and mode is 'masuk' -> Auto-generate MIKMS serial numbers!
  const matchedFg = findMatchingMikmsProduct(currentUniversalItem);
  if (tx.tipe === 'masuk' && matchedFg && matchedFg.type === 'direct') {
    try {
      showToast(`Menerbitkan ${tx.qty} nomor seri MIKMS (${matchedFg.code})...`);
      const r = await api('/api/mikms/direct-input', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          input_date: tx.transaction_date,
          product_id: matchedFg.id,
          quantity: tx.qty,
          supplier: tx.sumber,
          received_by: tx.petugas,
          notes: tx.catatan ? `${tx.catatan} (Universal Scanner Hub)` : 'Penerimaan barang vendor via Universal Scanner Hub'
        })
      });
      const d = await r.json();
      if (d.status === 'success' || d.success) {
        const serials = d.data?.serial_numbers || [];
        showToast(`✓ Berhasil menerbitkan ${serials.length} unit ${matchedFg.name}!`);
        
        // Render banner
        const banner = document.getElementById('sc-fg-generated-banner');
        const titleEl = document.getElementById('sc-fg-banner-title');
        const serialsEl = document.getElementById('sc-fg-banner-serials');
        const actionsEl = document.getElementById('sc-fg-banner-actions');
        
        if (banner && serialsEl && actionsEl) {
          if (titleEl) titleEl.textContent = `🎉 Berhasil Menerbitkan ${serials.length} Nomor Seri ${matchedFg.name}!`;
          serialsEl.innerHTML = serials.map(s => `<span class="inline-block bg-base-200 text-base-content px-2 py-0.5 rounded mr-1.5 mb-1">${escHtml(s)}</span>`).join('');
          
          actionsEl.innerHTML = `
            <button type="button" class="btn btn-sm btn-primary font-bold shadow-xs" onclick='printBatchUnitLabels(${JSON.stringify(serials)})'>
              🖨️ Cetak Semua Stiker Thermal (${serials.length} Label)
            </button>
            <button type="button" class="btn btn-sm btn-outline font-bold" onclick="goToQcUnit('${serials[0]}')">
              ✅ Buka QC Checklist Unit Pertama (${serials[0]})
            </button>
          `;
          banner.classList.remove('hidden');
          try { banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch(e) {}
        }
        
        addScanSessionHistory('FINISH_GOOD', `${matchedFg.code} (+${tx.qty})`, `Serial: ${serials[0]} s/d ${serials[serials.length - 1]}`);
        
        // Refresh products and registry in background
        loadMikmsProducts();
        if (typeof loadUnitRegistry === 'function') loadUnitRegistry();
      } else {
        showToast('Gagal auto-generate serial MIKMS: ' + (d.message || 'Error server'), true);
      }
    } catch (err) {
      console.error('Error direct input MIKMS:', err);
      showToast('Terjadi kesalahan saat registrasi serial MIKMS', true);
    }
  }

  // Reset inputs
  document.getElementById('sc-item-qty').value = 1;
  document.getElementById('sc-item-partner').value = '';
  ['sc-item-po', 'sc-item-prn', 'sc-item-job', 'sc-item-to', 'sc-item-catatan'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  validateScQty();
  syncPending();
}

// MIKMS Unit Contextual Actions
function goToDispatchFromScan() {
  if (!currentUniversalMikmsUnit) return;
  const serial = currentUniversalMikmsUnit.serial_number;
  goPage('mikms', 'dispatch');
  setTimeout(() => {
    const dspSerials = document.getElementById('dsp-serials');
    if (dspSerials) {
      dspSerials.value = serial;
      dspSerials.focus();
    }
  }, 100);
}

function goToSewaFromScan() {
  if (!currentUniversalMikmsUnit) return;
  const serial = currentUniversalMikmsUnit.serial_number;
  goPage('sewamutasi');
  setTimeout(() => {
    const swInput = document.getElementById('sw-serial') || document.getElementById('sw-unit-search');
    if (swInput) {
      swInput.value = serial;
      if (typeof filterSewaUnits === 'function') filterSewaUnits();
    }
  }, 100);
}

async function quickReturnCurrentMikmsUnit() {
  if (!currentUniversalMikmsUnit) return;
  const serial = currentUniversalMikmsUnit.serial_number;
  const penerima = (user && user.name) ? user.name : prompt('Nama penerima barang:') || 'Petugas Gudang';
  if (!confirm(`Konfirmasi penerimaan retur unit ${serial}?\nUnit akan kembali ke gudang dan berstatus Wajib QC Ulang.`)) {
    return;
  }
  showToast('Memproses retur unit...');
  try {
    const r = await api('/api/mikms/dispatch/return', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        serial_number: serial,
        received_by: penerima,
        notes: 'Retur cepat dari Universal Smart Scanner Hub'
      })
    });
    const d = await r.json();
    if (d.status === 'success' || d.success) {
      showToast(`✓ Retur ${serial} berhasil diproses. Status: Wajib QC!`);
      addScanSessionHistory('RETURN', serial, 'Retur diproses (Wajib QC)');
      processUniversalScan(serial); // refresh state
    } else {
      showToast(d.message || 'Gagal memproses retur', true);
    }
  } catch (err) {
    console.error('Error quick return:', err);
    showToast('Koneksi gagal', true);
  }
}

function goToQcUnitFromScan() {
  if (!currentUniversalMikmsUnit) return;
  goToQcUnit(currentUniversalMikmsUnit.serial_number);
}

function printCurrentMikmsThermal() {
  if (!currentUniversalMikmsUnit) return;
  printUnitThermalLabel(currentUniversalMikmsUnit.serial_number);
}

function openCurrentMikmsGrid() {
  if (!currentUniversalMikmsUnit) return;
  openMicrobitBulkModal([currentUniversalMikmsUnit.serial_number]);
}

// Session History
function addScanSessionHistory(type, title, detail) {
  const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  scanSessionHistory.unshift({ type, title, detail, time });
  renderScanSessionHistory();
}

function renderScanSessionHistory() {
  const tbody = document.getElementById('scan-session-tbody');
  const countBadge = document.getElementById('scan-history-count');
  if (countBadge) countBadge.textContent = scanSessionHistory.length + ' aktivitas';
  if (!tbody) return;
  if (scanSessionHistory.length === 0) {
    tbody.innerHTML = '<tr><td class="text-center py-4 text-base-content/40 text-xs">Belum ada scan dalam sesi ini</td></tr>';
    return;
  }
  tbody.innerHTML = scanSessionHistory.slice(0, 15).map(h => {
    let badgeClass = 'badge-ghost';
    if (h.type === 'MASUK') badgeClass = 'badge-success text-white';
    else if (h.type === 'KELUAR') badgeClass = 'badge-error text-white';
    else if (h.type === 'MIKMS') badgeClass = 'badge-secondary text-white';
    else if (h.type === 'RETURN') badgeClass = 'badge-warning text-white';
    else if (h.type === 'NOT_FOUND') badgeClass = 'badge-neutral text-white';

    return `<tr>
      <td class="whitespace-nowrap"><span class="badge ${badgeClass} text-[9px] font-black">${h.type}</span></td>
      <td class="text-xs font-bold truncate max-w-[140px]" title="${escHtml(h.title)}">${escHtml(h.title)}</td>
      <td class="text-[10px] text-base-content/60 truncate max-w-[100px]">${escHtml(h.detail)}</td>
      <td class="text-[9px] font-mono text-base-content/40 text-right whitespace-nowrap">${h.time}</td>
    </tr>`;
  }).join('');
}

// Camera Controls
function toggleCamera() {
  if (cameraActive) {
    stopCamera();
  } else {
    startCamera();
  }
}

function stopCamera() {
  const v = document.getElementById('scanner-video');
  if (v && v.srcObject) {
    try {
      v.srcObject.getTracks().forEach(t => t.stop());
    } catch(e) {}
    v.srcObject = null;
  }
  cameraActive = false;
  const overlay = document.getElementById('cam-overlay');
  if (overlay) overlay.style.display = 'flex';
  const toggleBtn = document.getElementById('cam-toggle-btn');
  if (toggleBtn) toggleBtn.textContent = 'Nyalakan Kamera';
  const st = document.getElementById('scan-status');
  if (st) {
    st.textContent = 'Kamera dinonaktifkan';
    st.className = 'alert alert-neutral mt-4 text-xs justify-center font-bold';
  }
}

async function startCamera() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 640 } }
    });
    const v = document.getElementById('scanner-video');
    v.srcObject = stream;
    v.play();
    cameraActive = true;
    const overlay = document.getElementById('cam-overlay');
    if (overlay) overlay.style.display = 'none';
    const toggleBtn = document.getElementById('cam-toggle-btn');
    if (toggleBtn) toggleBtn.textContent = 'Matikan Kamera';
    const st = document.getElementById('scan-status');
    if (st) {
      st.textContent = 'Kamera Aktif — Arahkan ke QR Code / Barcode';
      st.className = 'alert alert-success mt-4 text-xs justify-center font-bold text-white';
    }
    (function tick() {
      if (!cameraActive) return;
      const c = document.getElementById('scanner-canvas');
      if (c && v.readyState === v.HAVE_ENOUGH_DATA) {
        const ctx = c.getContext('2d');
        c.width = v.videoWidth;
        c.height = v.videoHeight;
        ctx.drawImage(v, 0, 0);
        const img = ctx.getImageData(0, 0, c.width, c.height);
        const code = (typeof jsQR !== 'undefined') ? jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' }) : null;
        if (code && code.data) {
          const now = Date.now();
          if (code.data !== lastScanned || now - lastScanTime > 3000) {
            lastScanned = code.data;
            lastScanTime = now;
            onQRDetected(code.data);
          }
        }
      }
      requestAnimationFrame(tick);
    })();
  } catch (err) {
    console.error('Camera error:', err);
    cameraActive = false;
    const st = document.getElementById('scan-status');
    if (st) {
      st.textContent = 'Izin kamera ditolak atau kamera tidak tersedia';
      st.className = 'alert alert-error mt-4 text-xs justify-center font-bold text-white';
    }
  }
}

function onQRDetected(raw) {
  try { navigator.vibrate?.([80, 40, 80]); } catch(e) {}
  const input = document.getElementById('scan-universal-input');
  if (input) input.value = raw;
  processUniversalScan(raw);
}

// Backward-compatible aliases
function setMode(m) { setUniversalItemMode(m); }
function autoFillScan() { /* legacy handled by datalist */ }
function submitScan() { submitUniversalItemScan(); }

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
      preview.innerHTML = '<div class="text-xs text-base-content/40 py-6 text-center w-full">Pilih barang atau tambah ke antrean untuk melihat pratinjau stiker.</div>';
      return;
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
  if (!area) return;
  area.innerHTML = ''; 
  area.className = '';
  area.removeAttribute('style');
  
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
  
  window.addEventListener('afterprint', cleanPrintArea, { once: true });
  setTimeout(() => {
    window.print();
    setTimeout(cleanPrintArea, 1000);
  }, 250);
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

// State untuk Pemilih Unit FG Tergenerate (Sewa Masuk)
let availableUnitsForSewa = [];
let sewainCategory = 'ALL';
let sewainSearch = '';

// State untuk Pemilih Unit Ready (Sewa Keluar)
let sewaoutCategory = 'ALL';
let sewaoutSearch = '';

// State untuk Unit Sedang Disewa (Sewa Kembali)
let sewaretSearch = '';

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

  if (tab === 'sewain') {
    loadAvailableUnitsForSewa();
  } else if (tab === 'sewaout') {
    renderReadyAssetsList();
  } else if (tab === 'sewaret') {
    renderRentedAssetsList();
  }
}

/* ── DASHBOARD SEWA ── */
async function loadSewaDashboard() {
  await loadSewaAssets();
  
  const total = sewaAssets.length;
  const ready = sewaAssets.filter(a => a.tipe_kepemilikan === 'sewa' && !a.is_rented && !a.needs_qc && (a.status === 'lengkap' || a.status === 'good')).length;
  const rented = sewaAssets.filter(a => a.is_rented).length;
  const wajibQc = sewaAssets.filter(a => a.needs_qc || a.status === 'not_good').length;
  
  document.getElementById('sewa-stats').innerHTML = [
    { icon: '📦', val: total, lbl: 'Total Unit Asset', cls: 'text-primary' },
    { icon: '✅', val: ready, lbl: 'Unit Ready Sewa', cls: 'text-success' },
    { icon: '🔄', val: rented, lbl: 'Sedang Disewa', cls: 'text-warning' },
    { icon: '⚠️', val: wajibQc, lbl: 'Wajib QC Ulang', cls: 'text-error' }
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
  await Promise.all([loadStock(), loadMasterDataDependents(), loadSewaAssets()]);
  
  // Populate Master Items select — Sewa HANYA terjadi di Finish Goods
  const itemSelect = document.getElementById('sewain-item-id');
  const finishGoodsItems = master.filter(m => {
    const isRaw = (m.produk === 'Umum' || m.produk === 'Modul' || m.komponen === 'Shared' || /^(CT|OP|SN|MT|PW|CN|MC|CS|TL|PC)-/i.test(m.kode));
    return !isRaw;
  });
  const itemsToRender = finishGoodsItems.length > 0 ? finishGoodsItems : master;
  itemSelect.innerHTML = '<option value="">-- Semua Produk Finish Goods --</option>' + itemsToRender.map(m => `
    <option value="${m.id}" data-kode="${m.kode}">${m.kode} — ${m.nama} (${m.produk || 'Finish Good'})</option>
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

  // Load sub-panel lists
  await loadAvailableUnitsForSewa();
  renderReadyAssetsList();
  renderRentedAssetsList();
}

/* ── UNIT FG TERGENERATE (PENERIMAAN ASSET BARU) ── */
async function loadAvailableUnitsForSewa() {
  const listEl = document.getElementById('sewain-available-units-list');
  const countBadge = document.getElementById('sewain-fg-count-badge');

  try {
    const r = await api('/api/units-for-sewa');
    const d = await r.json();
    if (d.success || d.status === 'success') {
      availableUnitsForSewa = d.data || [];
      if (countBadge) countBadge.textContent = `${availableUnitsForSewa.length} Unit`;
      renderAvailableUnitsList();
      renderSewainSerialSelect();
    }
  } catch (e) {
    if (listEl) listEl.innerHTML = '<div class="text-error text-center py-4 text-xs">Gagal memuat unit tergenerate.</div>';
  }
}

function filterSewainCategory(cat) {
  sewainCategory = cat;
  document.querySelectorAll('.sewain-cat-btn').forEach(b => {
    if (b.getAttribute('data-cat') === cat) {
      b.classList.add('btn-primary');
      b.classList.remove('btn-ghost');
    } else {
      b.classList.remove('btn-primary');
      b.classList.add('btn-ghost');
    }
  });
  renderAvailableUnitsList();
}

function handleSewainSearch(q) {
  sewainSearch = (q || '').trim().toLowerCase();
  renderAvailableUnitsList();
}

function renderAvailableUnitsList() {
  const listEl = document.getElementById('sewain-available-units-list');
  if (!listEl) return;

  let filtered = availableUnitsForSewa || [];
  if (sewainCategory !== 'ALL') {
    filtered = filtered.filter(u => (u.product_code || '').toUpperCase() === sewainCategory);
  }
  if (sewainSearch) {
    filtered = filtered.filter(u => 
      (u.serial_number || '').toLowerCase().includes(sewainSearch) ||
      (u.product_name || '').toLowerCase().includes(sewainSearch)
    );
  }

  if (filtered.length === 0) {
    listEl.innerHTML = `
      <div class="text-center py-8 text-base-content/40 text-xs flex flex-col items-center gap-2">
        <span class="text-2xl">📦</span>
        <span>Tidak ada unit Finish Goods yang siap didaftarkan.</span>
        <span class="text-[10px]">Semua unit tergenerate sudah masuk aset atau belum lolos QC.</span>
      </div>
    `;
    return;
  }

  listEl.innerHTML = filtered.map(u => `
    <div class="p-3 rounded-xl border border-base-200 bg-base-100 hover:border-primary/50 hover:bg-primary/5 transition flex items-center justify-between gap-2 shadow-2xs">
      <div class="min-w-0">
        <div class="flex items-center gap-1.5">
          <span class="font-mono font-black text-xs text-primary">${escHtml(u.serial_number)}</span>
          <span class="badge badge-xs badge-neutral font-bold">${escHtml(u.product_code || 'FG')}</span>
          <span class="badge badge-xs badge-success text-success-content font-bold">Lolos QC</span>
        </div>
        <div class="text-[11px] text-base-content/70 truncate mt-0.5">${escHtml(u.product_name || 'Finish Good')}</div>
      </div>
      <button type="button" class="btn btn-xs btn-primary font-bold shrink-0 shadow-2xs" onclick="selectFgUnitForSewa('${escHtml(u.serial_number)}', ${u.item_id || 'null'}, '${escHtml(u.product_name || '')}', '${escHtml(u.product_code || '')}')">
        Pilih Unit
      </button>
    </div>
  `).join('');
}

/* Render Dropdown Serial Number dari Stok Kita */
function renderSewainSerialSelect(selectedItemId = null) {
  const selectEl = document.getElementById('sewain-serial-select');
  const datalistEl = document.getElementById('sewain-stock-dl');
  const countHintEl = document.getElementById('sewain-stock-count-hint');
  if (!selectEl) return;

  let units = availableUnitsForSewa || [];
  if (selectedItemId) {
    units = units.filter(u => String(u.item_id) === String(selectedItemId));
  }

  if (countHintEl) {
    countHintEl.textContent = `${units.length} unit ready di stok`;
  }

  if (units.length === 0) {
    selectEl.innerHTML = '<option value="">-- Tidak ada stok unit tergenerate untuk produk ini --</option>';
  } else {
    // Group by product name
    const grouped = {};
    units.forEach(u => {
      const g = u.product_name || u.product_code || 'Finish Goods';
      if (!grouped[g]) grouped[g] = [];
      grouped[g].push(u);
    });

    let html = `<option value="">-- Pilih Serial Number dari Stok (${units.length} Unit Ready) --</option>`;
    for (const [prodName, uList] of Object.entries(grouped)) {
      html += `<optgroup label="${escHtml(prodName)} (${uList.length} unit)">`;
      uList.forEach(u => {
        html += `<option value="${escHtml(u.serial_number)}">
          ${escHtml(u.serial_number)} — ${escHtml(u.product_name || '')} (Lolos QC)
        </option>`;
      });
      html += `</optgroup>`;
    }
    selectEl.innerHTML = html;
  }

  // Populate datalist for manual mode autocomplete
  if (datalistEl) {
    datalistEl.innerHTML = (availableUnitsForSewa || []).map(u => `
      <option value="${escHtml(u.serial_number)}">${escHtml(u.product_name || '')} (Lolos QC)</option>
    `).join('');
  }
}

/* Event saat Master Barang dipilih */
function onSewainItemChange(itemId) {
  renderSewainSerialSelect(itemId);
  if (!itemId) {
    filterSewainCategory('ALL');
    return;
  }
  const u = (availableUnitsForSewa || []).find(x => String(x.item_id) === String(itemId));
  if (u && u.product_code) {
    filterSewainCategory(u.product_code);
  }
}

/* Event saat Serial Number dipilih dari dropdown stok */
function onSewainSelectChange(serial) {
  if (!serial) {
    clearSelectedFgUnit();
    return;
  }
  const u = (availableUnitsForSewa || []).find(x => x.serial_number === serial);
  if (u) {
    selectFgUnitForSewa(u.serial_number, u.item_id, u.product_name, u.product_code);
  } else {
    document.getElementById('sewain-serial').value = serial;
  }
}

/* Event saat mengetik manual / scanner scan serial */
function onSewainManualInput(val) {
  const serial = (val || '').trim();
  const u = (availableUnitsForSewa || []).find(x => x.serial_number.toLowerCase() === serial.toLowerCase());
  if (u) {
    if (u.item_id) {
      const sel = document.getElementById('sewain-item-id');
      if (sel) sel.value = u.item_id;
    }
    document.getElementById('sewain-status').value = 'good';
  }
}

/* Toggle antara Dropdown Stok vs Manual Input / Scanner */
function toggleSewainInputMode() {
  const dropWrapper = document.getElementById('sewain-dropdown-wrapper');
  const manWrapper = document.getElementById('sewain-manual-wrapper');
  const toggleBtn = document.getElementById('sewain-mode-toggle');
  if (!dropWrapper || !manWrapper) return;

  const isManual = !manWrapper.classList.contains('hidden');
  if (isManual) {
    // Switch to Dropdown Mode
    manWrapper.classList.add('hidden');
    dropWrapper.classList.remove('hidden');
    toggleBtn.textContent = '🔄 Ketik/Scan Manual';
    const selVal = document.getElementById('sewain-serial-select')?.value;
    if (selVal) document.getElementById('sewain-serial').value = selVal;
  } else {
    // Switch to Manual Mode
    dropWrapper.classList.add('hidden');
    manWrapper.classList.remove('hidden');
    toggleBtn.textContent = '📋 Pilih dari Dropdown Stok';
    const manualInput = document.getElementById('sewain-serial');
    if (manualInput) {
      manualInput.focus();
      manualInput.select();
    }
  }
}

function selectFgUnitForSewa(serial, itemId, name, code) {
  document.getElementById('sewain-serial').value = serial;

  // Sync the dropdown select
  const serialSelect = document.getElementById('sewain-serial-select');
  if (serialSelect) {
    let opt = Array.from(serialSelect.options).find(o => o.value === serial);
    if (!opt) {
      renderSewainSerialSelect(null);
      opt = Array.from(serialSelect.options).find(o => o.value === serial);
    }
    if (opt) serialSelect.value = serial;
  }

  if (itemId) {
    const sel = document.getElementById('sewain-item-id');
    if (sel) sel.value = itemId;
  }
  document.getElementById('sewain-tipe').value = 'sewa';
  document.getElementById('sewain-status').value = 'good';

  const banner = document.getElementById('sewain-selected-banner');
  const serialText = document.getElementById('sewain-selected-serial');
  const nameText = document.getElementById('sewain-selected-name');
  if (banner && serialText) {
    serialText.textContent = serial;
    if (nameText) nameText.textContent = name || code;
    banner.classList.remove('hidden');
  }

  showToast(`Unit ${serial} dipilih dari stok.`);
  const locSelect = document.getElementById('sewain-location-id');
  if (locSelect) locSelect.focus();
}

function clearSelectedFgUnit() {
  document.getElementById('sewain-serial').value = '';
  const sel = document.getElementById('sewain-serial-select');
  if (sel) sel.value = '';
  const banner = document.getElementById('sewain-selected-banner');
  if (banner) banner.classList.add('hidden');
}

/* Penerimaan Asset Baru Submit */
async function handleNewAssetSubmit(e) {
  e.preventDefault();
  const isManual = !document.getElementById('sewain-manual-wrapper').classList.contains('hidden');
  const serialNumber = (isManual 
    ? document.getElementById('sewain-serial').value 
    : (document.getElementById('sewain-serial-select').value || document.getElementById('sewain-serial').value)
  ).trim();

  if (!serialNumber) {
    showToast('Silakan pilih unit dari stok atau masukkan nomor seri.', true);
    return;
  }

  const body = {
    item_id: document.getElementById('sewain-item-id').value,
    tipe_kepemilikan: document.getElementById('sewain-tipe').value,
    serial_number: serialNumber,
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
      showToast(d.message || 'Unit Asset berhasil didaftarkan');
      document.getElementById('sewain-serial').value = '';
      const sel = document.getElementById('sewain-serial-select');
      if (sel) sel.value = '';
      document.getElementById('sewain-po').value = '';
      document.getElementById('sewain-dokumen').value = '';
      document.getElementById('sewain-catatan').value = '';
      clearSelectedFgUnit();
      await loadSewaAssets();
      await loadAvailableUnitsForSewa();
      renderReadyAssetsList();
    } else {
      showToast(d.message || 'Gagal menyimpan asset', true);
    }
  } catch (err) {
    showToast('Koneksi bermasalah atau nomor seri tidak valid', true);
  }
}

/* ── LIST ASSET SEWA READY (SEWA KELUAR) ── */
function filterSewaoutCategory(cat) {
  sewaoutCategory = cat;
  document.querySelectorAll('.sewaout-cat-btn').forEach(b => {
    if (b.getAttribute('data-cat') === cat) {
      b.classList.add('btn-primary');
      b.classList.remove('btn-ghost');
    } else {
      b.classList.remove('btn-primary');
      b.classList.add('btn-ghost');
    }
  });
  renderReadyAssetsList();
}

function handleSewaoutSearch(q) {
  sewaoutSearch = (q || '').trim().toLowerCase();
  renderReadyAssetsList();
}

function renderReadyAssetsList() {
  const listEl = document.getElementById('sewaout-ready-assets-list');
  const countBadge = document.getElementById('sewaout-ready-count-badge');
  if (!listEl) return;

  // Aturan Bisnis: Hanya unit yang ada di list asset barang sewa, TIDAK sedang disewa, dan TIDAK sedang butuh QC ulang
  const readyAssets = sewaAssets.filter(a => 
    a.tipe_kepemilikan === 'sewa' && 
    !a.is_rented && 
    !a.needs_qc && 
    (a.status === 'good' || a.status === 'lengkap')
  );

  if (countBadge) countBadge.textContent = `${readyAssets.length} Ready`;

  let filtered = readyAssets;
  if (sewaoutCategory !== 'ALL') {
    filtered = filtered.filter(a => {
      const code = a.mikms_unit?.product_code || a.item?.kode || '';
      return code.toUpperCase().includes(sewaoutCategory);
    });
  }
  if (sewaoutSearch) {
    filtered = filtered.filter(a => 
      (a.serial_number || '').toLowerCase().includes(sewaoutSearch) ||
      (a.item?.nama || '').toLowerCase().includes(sewaoutSearch) ||
      (a.item?.kode || '').toLowerCase().includes(sewaoutSearch)
    );
  }

  if (filtered.length === 0) {
    listEl.innerHTML = `
      <div class="text-center py-8 text-base-content/40 text-xs flex flex-col items-center gap-2">
        <span class="text-2xl">🔍</span>
        <span>Tidak ada aset sewa ready yang cocok.</span>
        <span class="text-[10px]">Pastikan unit sudah didaftarkan dan tidak berstatus Wajib QC Ulang.</span>
      </div>
    `;
    return;
  }

  listEl.innerHTML = filtered.map(a => {
    const isSelected = sewaOutList.some(item => item.id === a.id);
    return `
      <div class="p-3 rounded-xl border ${isSelected ? 'border-primary bg-primary/10' : 'border-base-200 bg-base-100'} hover:border-primary/50 transition flex items-center justify-between gap-2 shadow-2xs">
        <div class="min-w-0">
          <div class="flex items-center gap-1.5">
            <span class="font-mono font-black text-xs text-primary">${escHtml(a.serial_number)}</span>
            <span class="badge badge-xs badge-success text-success-content font-bold">Ready</span>
            <span class="text-[10px] text-base-content/60 font-medium">Rak: ${escHtml(a.location?.nama || '-')}</span>
          </div>
          <div class="text-[11px] text-base-content/70 truncate mt-0.5">${escHtml(a.item?.nama || 'Asset Sewa')}</div>
        </div>
        ${isSelected ? 
          '<span class="badge badge-xs badge-primary font-bold">Dipilih</span>' :
          `<button type="button" class="btn btn-xs btn-outline btn-primary font-bold shrink-0 shadow-2xs" onclick="addAssetObjToSewaOutList(${a.id})">+ Pilih</button>`
        }
      </div>
    `;
  }).join('');
}

function addAssetObjToSewaOutList(assetId) {
  const asset = sewaAssets.find(a => a.id === assetId);
  if (!asset) return;
  if (asset.tipe_kepemilikan !== 'sewa') {
    showToast('Hanya unit di list Asset Barang Sewa yang dapat disewakan!', true);
    return;
  }
  if (asset.is_rented) {
    showToast('Asset ini sedang berstatus disewa!', true);
    return;
  }
  if (asset.needs_qc) {
    showToast('⚠️ Unit ' + asset.serial_number + ' berstatus WAJIB QC ULANG!', true);
    return;
  }
  if (sewaOutList.some(a => a.id === asset.id)) {
    showToast('Asset sudah masuk dalam daftar sewa', true);
    return;
  }
  sewaOutList.push(asset);
  renderSewaOutListTable();
  renderReadyAssetsList();
}

/* Sewa Keluar Scan / Manual Input */
function addAssetToSewaOutList() {
  const serial = document.getElementById('sewaout-scan-input').value.trim();
  if (!serial) return;
  
  const asset = sewaAssets.find(a => a.serial_number.toLowerCase() === serial.toLowerCase());
  if (!asset) {
    showToast(`Unit ${serial} belum terdaftar di list Asset Barang Sewa! Daftarkan terlebih dahulu.`, true);
    return;
  }
  if (asset.tipe_kepemilikan !== 'sewa') {
    showToast(`Unit ${asset.serial_number} bukan bertipe aset sewa (tipe: ${asset.tipe_kepemilikan})!`, true);
    return;
  }
  if (asset.is_rented) {
    showToast(`Unit ${asset.serial_number} sedang dalam status disewa!`, true);
    return;
  }
  if (asset.needs_qc) {
    showToast(`⚠️ Unit ${asset.serial_number} berstatus WAJIB QC ULANG! Harus lulus QC terlebih dahulu sebelum dapat disewakan.`, true);
    return;
  }
  const isRaw = (asset.item && (asset.item.produk === 'Umum' || asset.item.produk === 'Modul' || asset.item.komponen === 'Shared' || /^(CT|OP|SN|MT|PW|CN|MC|CS|TL|PC)-/i.test(asset.item.kode)));
  if (isRaw) {
    showToast('Sewa hanya terjadi di Finish Goods! Item ini adalah komponen/bahan baku.', true);
    return;
  }
  if (sewaOutList.some(a => a.id === asset.id)) {
    showToast('Asset sudah masuk dalam daftar sewa', true);
    return;
  }

  sewaOutList.push(asset);
  document.getElementById('sewaout-scan-input').value = '';
  renderSewaOutListTable();
  renderReadyAssetsList();
}

function removeAssetFromSewaOutList(id) {
  sewaOutList = sewaOutList.filter(a => a.id !== id);
  renderSewaOutListTable();
  renderReadyAssetsList();
}

function renderSewaOutListTable() {
  const tbody = document.getElementById('sewaout-list-tbody');
  const countBadge = document.getElementById('sewaout-selected-count');
  if (countBadge) countBadge.textContent = `${sewaOutList.length} Unit`;

  if (sewaOutList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>';
    return;
  }
  tbody.innerHTML = sewaOutList.map(a => `
    <tr>
      <td class="font-mono font-bold text-primary">${escHtml(a.serial_number)}</td>
      <td class="font-medium">${escHtml(a.item?.nama || 'Asset')}</td>
      <td><span class="badge badge-xs badge-success text-success-content font-bold">Ready</span></td>
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
      showToast(d.message || 'Transaksi sewa keluar berhasil diproses');
      document.getElementById('sewaout-dokumen').value = '';
      document.getElementById('sewaout-keperluan').value = '';
      document.getElementById('sewaout-catatan').value = '';
      sewaOutList = [];
      await loadSewaAssets();
      renderSewaOutListTable();
      renderReadyAssetsList();
      renderRentedAssetsList();
    } else {
      showToast(d.message || 'Transaksi gagal diproses', true);
    }
  } catch (err) {
    showToast('Terjadi kesalahan pengiriman transaksi', true);
  }
}

/* ── UNIT SEDANG DISEWA & SEWA KEMBALI ── */
function handleSewaretSearch(q) {
  sewaretSearch = (q || '').trim().toLowerCase();
  renderRentedAssetsList();
}

function renderRentedAssetsList() {
  const listEl = document.getElementById('sewaret-rented-assets-list');
  const countBadge = document.getElementById('sewaret-rented-count-badge');
  if (!listEl) return;

  const rentedAssets = sewaAssets.filter(a => a.is_rented);
  if (countBadge) countBadge.textContent = `${rentedAssets.length} Disewa`;

  let filtered = rentedAssets;
  if (sewaretSearch) {
    filtered = filtered.filter(a => 
      (a.serial_number || '').toLowerCase().includes(sewaretSearch) ||
      (a.item?.nama || '').toLowerCase().includes(sewaretSearch) ||
      (a.item?.kode || '').toLowerCase().includes(sewaretSearch)
    );
  }

  if (filtered.length === 0) {
    listEl.innerHTML = `
      <div class="text-center py-8 text-base-content/40 text-xs flex flex-col items-center gap-2">
        <span class="text-2xl">✅</span>
        <span>Tidak ada unit aset yang sedang disewa.</span>
      </div>
    `;
    return;
  }

  listEl.innerHTML = filtered.map(a => {
    const isSelected = sewaReturnList.some(item => item.id === a.id);
    return `
      <div class="p-3 rounded-xl border ${isSelected ? 'border-warning bg-warning/10' : 'border-base-200 bg-base-100'} hover:border-warning/50 transition flex items-center justify-between gap-2 shadow-2xs">
        <div class="min-w-0">
          <div class="flex items-center gap-1.5">
            <span class="font-mono font-black text-xs text-warning">${escHtml(a.serial_number)}</span>
            <span class="badge badge-xs badge-warning text-warning-content font-bold">Sedang Disewa</span>
          </div>
          <div class="text-[11px] text-base-content/70 truncate mt-0.5">${escHtml(a.item?.nama || 'Asset Sewa')}</div>
        </div>
        ${isSelected ?
          '<span class="badge badge-xs badge-warning font-bold">Dipilih</span>' :
          `<button type="button" class="btn btn-xs btn-warning font-bold shrink-0 text-warning-content shadow-2xs" onclick="addAssetObjToSewaReturnList(${a.id})">↩️ Kembalikan</button>`
        }
      </div>
    `;
  }).join('');
}

function addAssetObjToSewaReturnList(assetId) {
  const asset = sewaAssets.find(a => a.id === assetId);
  if (!asset) return;
  if (!asset.is_rented) {
    showToast('Asset ini tidak sedang dalam status disewa!', true);
    return;
  }
  if (sewaReturnList.some(a => a.id === asset.id)) {
    showToast('Asset sudah ada di daftar pengembalian', true);
    return;
  }
  sewaReturnList.push(asset);
  renderSewaReturnListTable();
  renderRentedAssetsList();
}

function addAssetToSewaReturnList() {
  const serial = document.getElementById('sewaret-scan-input').value.trim();
  if (!serial) return;
  
  const asset = sewaAssets.find(a => a.serial_number.toLowerCase() === serial.toLowerCase());
  if (!asset) {
    showToast('Asset tidak terdaftar di sistem!', true);
    return;
  }
  if (!asset.is_rented) {
    showToast(`Unit ${asset.serial_number} tidak sedang dalam status disewa!`, true);
    return;
  }
  if (sewaReturnList.some(a => a.id === asset.id)) {
    showToast('Asset sudah ada di daftar pengembalian', true);
    return;
  }

  sewaReturnList.push(asset);
  document.getElementById('sewaret-scan-input').value = '';
  renderSewaReturnListTable();
  renderRentedAssetsList();
}

function removeAssetFromSewaReturnList(id) {
  sewaReturnList = sewaReturnList.filter(a => a.id !== id);
  renderSewaReturnListTable();
  renderRentedAssetsList();
}

function renderSewaReturnListTable() {
  const tbody = document.getElementById('sewaret-list-tbody');
  const countBadge = document.getElementById('sewaret-selected-count');
  if (countBadge) countBadge.textContent = `${sewaReturnList.length} Unit`;

  if (sewaReturnList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-base-content/30">Belum ada unit yang ditambahkan.</td></tr>';
    return;
  }
  
  const rakLocations = locations.filter(l => l.tipe === 'rak');
  const locOptions = rakLocations.map(l => `<option value="${l.id}">${l.nama} (${l.kode})</option>`).join('');

  tbody.innerHTML = sewaReturnList.map((a, idx) => `
    <tr>
      <td class="font-mono font-bold text-primary">${escHtml(a.serial_number)}</td>
      <td class="font-medium">${escHtml(a.item?.nama || 'Asset')}</td>
      <td>
        <span class="badge badge-xs badge-error font-bold gap-1">⚠️ Wajib QC Ulang</span>
        <input type="hidden" id="ret-status-${idx}" value="not_good">
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
    status: document.getElementById(`ret-status-${idx}`).value || 'not_good',
    location_id: document.getElementById(`ret-loc-${idx}`).value,
  }));

  const body = {
    returns: returns,
    no_dokumen: document.getElementById('sewaret-dokumen').value.trim() || null,
    catatan: document.getElementById('sewaret-catatan').value.trim() || null,
  };

  const returnedSerials = sewaReturnList.map(a => a.serial_number);

  try {
    const r = await api('/api/assets/rent-return', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if (d.success) {
      document.getElementById('sewaret-dokumen').value = '';
      document.getElementById('sewaret-catatan').value = '';
      sewaReturnList = [];
      await loadSewaAssets();
      renderSewaReturnListTable();
      renderRentedAssetsList();
      renderReadyAssetsList();

      // Notifikasi sukses & tawarkan langsung buka QC Unit MIKMS
      const firstSerial = returnedSerials[0] || '';
      if (confirm(`${d.message}\n\nIngin langsung membuka menu QC Unit MIKMS untuk melakukan QC Ulang unit ${firstSerial}?`)) {
        goToQcUnit(firstSerial);
      } else {
        showToast(d.message);
      }
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
    let isRentedLabel = '';
    if (a.is_rented) {
      isRentedLabel = '<span class="badge badge-warning font-bold text-[10px] text-warning-content">Disewakan</span>';
    } else if (a.needs_qc) {
      isRentedLabel = `<div class="flex items-center gap-1"><span class="badge badge-error font-bold text-[10px] text-white">⚠️ Wajib QC</span> <button type="button" class="btn btn-2xs btn-outline btn-warning font-bold" onclick="goToQcUnit('${a.serial_number}')">QC</button></div>`;
    } else {
      isRentedLabel = '<span class="badge badge-success font-bold text-[10px] text-success-content">Ready Sewa</span>';
    }
    
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
let currentMikmsTab = 'unitregistry';
let mikmsDashboardData = null;

function switchMikmsTab(tab, updateHash = true) {
  // Aliasing legacy / shortcut tabs
  if (tab === 'qc') tab = 'qcunit';
  if (tab === 'shipment' || tab === 'return') tab = 'dispatch';

  currentMikmsTab = tab;

  // Sync URL hash if MIKMS page is active
  if (updateHash && !isRouterNavigating) {
    const pgMikms = document.getElementById('pg-mikms');
    if (pgMikms && pgMikms.classList.contains('active')) {
      const targetHash = '#/mikms/' + tab;
      if (window.location.hash !== targetHash) {
        window.location.hash = targetHash;
      }
    }
  }
  document.querySelectorAll('.mikms-tab-btn').forEach(btn => {
    if (btn.dataset.tab === tab) {
      btn.classList.add('active', 'bg-primary', 'text-primary-content');
      btn.classList.remove('btn-ghost');
      try { btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' }); } catch(e){}
    } else {
      btn.classList.remove('active', 'bg-primary', 'text-primary-content');
      btn.classList.add('btn-ghost');
    }
  });

  // Highlight 5-stage workflow stepper cards
  document.querySelectorAll('.mikms-step-card').forEach(card => {
    const step = card.dataset.step;
    const isStepActive = (step === '1' && (tab === 'packageorder' || tab === 'production')) ||
                         (step === '2' && (tab === 'unitregistry' || tab === 'modulestocks')) ||
                         (step === '3' && tab === 'qcunit') ||
                         (step === '4' && tab === 'bundling') ||
                         (step === '5' && tab === 'dispatch');
    const numBadge = card.querySelector('.mikms-step-num');
    if (isStepActive) {
      card.classList.add('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary/20', 'shadow-sm');
      card.classList.remove('border-base-200', 'bg-base-100');
      if (numBadge) {
        numBadge.classList.add('bg-primary', 'text-primary-content');
        numBadge.classList.remove('bg-base-200', 'text-base-content/70');
      }
    } else {
      card.classList.remove('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary/20', 'shadow-sm');
      card.classList.add('border-base-200', 'bg-base-100');
      if (numBadge) {
        numBadge.classList.remove('bg-primary', 'text-primary-content');
        numBadge.classList.add('bg-base-200', 'text-base-content/70');
      }
    }
  });

  // Sync sidebar active highlight and topbar title
  const pgMikms = document.getElementById('pg-mikms');
  if (pgMikms && pgMikms.classList.contains('active')) {
    document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active', 'bg-primary/10', 'text-primary', 'border', 'border-primary/20'));
    const mkLink = document.getElementById('sb-link-mikms');
    if (mkLink) mkLink.classList.add('active', 'bg-primary/10', 'text-primary', 'border', 'border-primary/20');
    
    const mikmsTitles = {
      packageorder: 'Perakitan Kit MLK (BOM)',
      unitregistry: 'Unit Registry',
      qcunit:       'QC Unit — Checklist',
      bundling:     'Packaging Robotic Explorer',
      dispatch:     'Dispatch & Retur Unit',
      production:   'Sub-Assembly Modul (RW)',
      modulestocks: 'Stok Modul Jadi (RW)',
      bomlist:      'Master BOM Kit',
      repair:       'Maintenance & Repair',
      opname:       'Stock Opname MIKMS',
      logs:         'Log Aktivitas MIKMS',
    };
    if (mikmsTitles[tab]) {
      const titleEl = document.getElementById('tb-page-title');
      if (titleEl) titleEl.textContent = mikmsTitles[tab];
    }
  }

  document.querySelectorAll('.mikms-tab-content').forEach(p => p.classList.add('hidden'));
  const target = document.getElementById('mk-panel-' + tab);
  if (target) target.classList.remove('hidden');

  if (tab === 'packageorder') renderPackageOrderTab();
  if (tab === 'unitregistry') loadUnitRegistry();
  if (tab === 'qcunit') { 
    const d = document.getElementById('qcu-date');
    if (d && !d.value) d.value = new Date().toISOString().split('T')[0]; 
  }
  if (tab === 'bundling') loadBundlingTab();
  if (tab === 'dispatch') { 
    const d = document.getElementById('dsp-date');
    if (d && !d.value) d.value = new Date().toISOString().split('T')[0]; 
  }
  if (tab === 'production') renderMikmsProductionTab();
  if (tab === 'modulestocks') renderModuleStocksTab();
  if (tab === 'bomlist') renderBomListTable();
  if (tab === 'repair') renderMikmsRepairTab();
  if (tab === 'opname') renderMikmsOpnameTable();
  if (tab === 'logs') renderMikmsLogsTable();
}

/* ── STANDAR BOM & LIST KOMPONEN DATA (SHEET 2) ── */
const BOMS_DATA = {
  microbit: [
    { no: 1, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-001', name: 'Micro:bit V2', qty: 1, unit: 'Item' },
    { no: 2, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-003', name: 'Kabel Micro USB', qty: 1, unit: 'Item' },
    { no: 3, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-002', name: 'Kabel Micro Type-C', qty: 1, unit: 'Item' },
    { no: 4, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-001', name: 'LED Merah', qty: 4, unit: 'Item' },
    { no: 5, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-003', name: 'LED Hijau', qty: 4, unit: 'Item' },
    { no: 6, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-002', name: 'LED Kuning', qty: 4, unit: 'Item' },
    { no: 7, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'CN-006', name: 'Resistor', qty: 12, unit: 'Item' },
    { no: 8, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-001', name: 'Breadboard Mini (170 TP)', qty: 1, unit: 'Item' },
    { no: 9, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-007', name: 'Blok Konektor', qty: 2, unit: 'Item' },
    { no: 10, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-005', name: 'Kabel Alligator', qty: 10, unit: 'Item' },
    { no: 11, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-002', name: 'Kabel Jumper Male to Male', qty: 20, unit: 'Item' },
    { no: 12, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-004', name: 'Kabel Jumper Male to Female', qty: 10, unit: 'Item' },
    { no: 13, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-003', name: 'Kabel Jumper Female to Female', qty: 10, unit: 'Item' },
    { no: 14, box: 'BOX 2 – Supporting Equipment', mod: 'M03 - Motion Kit', code: 'MT-001', name: 'Servo 180°', qty: 2, unit: 'Item' },
    { no: 15, box: 'BOX 2 – Supporting Equipment', mod: 'M03 - Motion Kit', code: 'MT-002', name: 'Servo 360°', qty: 2, unit: 'Item' },
    { no: 16, box: 'BOX 2 – Supporting Equipment', mod: 'M03 - Motion Kit', code: 'MT-003', name: 'Servo MG996R', qty: 1, unit: 'Item' },
    { no: 17, box: 'BOX 2 – Supporting Equipment', mod: 'M04 - Sensor Kit', code: 'SN-001', name: 'Sensor Ultrasonik', qty: 1, unit: 'Item' },
    { no: 18, box: 'BOX 2 – Supporting Equipment', mod: 'M05 - Power Kit', code: 'PW-002', name: 'Battery Holder', qty: 1, unit: 'Item' },
    { no: 19, box: 'BOX 2 – Supporting Equipment', mod: 'M05 - Power Kit', code: 'PW-001', name: 'Baterai AAA', qty: 4, unit: 'Item' },
    { no: 20, box: 'BOX 3 – Bricks', mod: 'M06 - Mechanical Kit', code: 'MC-004', name: 'Lego (150 gr)', qty: 1, unit: 'Paket' },
    { no: 21, box: 'BOX 3 – Bricks', mod: 'M06 - Mechanical Kit', code: 'MC-003', name: 'Separator', qty: 1, unit: 'Item' },
    { no: 22, box: 'BOX 3 – Bricks', mod: 'M06 - Mechanical Kit', code: 'MC-005', name: 'Papan Lego / Base Plate', qty: 2, unit: 'Item' },
  ],
  robotic: [
    { no: 1, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-001', name: 'Micro:bit V2', qty: 1, unit: 'Item' },
    { no: 2, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-003', name: 'Kabel Micro USB', qty: 1, unit: 'Item' },
    { no: 3, box: 'BOX 1 – Beginner Kit', mod: 'M01 - Controller Kit', code: 'CT-002', name: 'Kabel Micro Type-C', qty: 1, unit: 'Item' },
    { no: 4, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-001', name: 'LED Merah', qty: 4, unit: 'Item' },
    { no: 5, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-003', name: 'LED Hijau', qty: 4, unit: 'Item' },
    { no: 6, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'OP-002', name: 'LED Kuning', qty: 4, unit: 'Item' },
    { no: 7, box: 'BOX 1 – Beginner Kit', mod: 'M02 - LED Kit', code: 'CN-006', name: 'Resistor', qty: 12, unit: 'Item' },
    { no: 8, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-001', name: 'Breadboard Mini (170 TP)', qty: 1, unit: 'Item' },
    { no: 9, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-007', name: 'Blok Konektor', qty: 2, unit: 'Item' },
    { no: 10, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-005', name: 'Kabel Alligator', qty: 10, unit: 'Item' },
    { no: 11, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-002', name: 'Kabel Jumper Male to Male', qty: 20, unit: 'Item' },
    { no: 12, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-004', name: 'Kabel Jumper Male to Female', qty: 10, unit: 'Item' },
    { no: 13, box: 'BOX 1 – Beginner Kit', mod: 'M07 - Connection Kit', code: 'CN-003', name: 'Kabel Jumper Female to Female', qty: 10, unit: 'Item' },
    { no: 14, box: 'Jimu Trackbot', mod: 'JM01 - Jimu', code: 'RJ01', name: 'Jimu Trackbot', qty: 1, unit: 'Item' },
  ],
  finishgood: [
    { no: 1, box: 'Finish Good Kit', mod: 'Finish Good', code: 'RJ01', name: 'Jimu Trackbot', qty: 1, unit: 'Unit' },
    { no: 2, box: 'Finish Good Kit', mod: 'Finish Good', code: 'ERB01', name: 'Erboblox', qty: 1, unit: 'Unit' },
    { no: 3, box: 'Finish Good Kit', mod: 'Finish Good', code: 'ALK01', name: 'Arduino Learning Kit', qty: 1, unit: 'Item' },
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
      if (d.success || d.status === 'success') {
        mikmsDashboardData = d.data;
        const s = d.data?.stats || d.data || {};
        document.getElementById('mk-stat-total-boxes').textContent = s.total_units ?? s.total_boxes ?? 0;
        document.getElementById('mk-stat-ready-boxes').textContent = s.units_in_stock ?? s.boxes_ready ?? 0;
        document.getElementById('mk-stat-loan-boxes').textContent = s.units_qc_pending ?? s.boxes_on_loan ?? 0;
        document.getElementById('mk-stat-repair-boxes').textContent = s.units_dispatched ?? s.boxes_repair ?? 0;
      }
    }

    if (modRes.ok) {
      const d = await modRes.json();
      if (d.status === 'success' || d.success) mikmsModules = d.data || [];
    }

    if (boxRes.ok) {
      const d = await boxRes.json();
      if (d.status === 'success' || d.success) mikmsBoxes = d.data || [];
    }

    if (logRes.ok) {
      const d = await logRes.json();
      if (d.status === 'success' || d.success) mikmsLogs = d.data || [];
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

    // Populate Finish Goods products
    loadMikmsProducts();

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

  if (repBox) {
    repBox.innerHTML = '<option value="">Pilih Boks...</option>' + mikmsBoxes.map(b => `
      <option value="${b.id}">${b.box_code} [${b.status}] — ${escHtml(b.category)}</option>
    `).join('');
  }
}

/* ── TAB: SUB-ASSEMBLY PRODUCTION (RW) ── */
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

/* ── TAB: REPAIRS ── */
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

/* ── QUICK REPAIR ACTION ── */

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

/* ═════════════════════════════════════════════
   PACKAGE ORDER — SIMULATE, EXECUTE, HISTORY
   ═════════════════════════════════════════════ */

let lastSimulationResult = null;

function renderPackageOrderTab() {
  loadPackageProgramsDropdown();
  loadPackageOrderHistory();
}

async function simulatePackageOrder() {
  const program = document.getElementById('pkg-program').value;
  const qty = document.getElementById('pkg-qty').value;

  if (!qty || qty < 1) {
    showToast('Masukkan jumlah paket yang valid', true);
    return;
  }

  showToast('Menghitung kebutuhan paket...');

  try {
    const res = await fetch(`/api/mikms/package-simulate?program_code=${program}&package_qty=${qty}`, {
      headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.status !== 'success') {
      showToast(json.message || 'Gagal simulate', true);
      return;
    }

    lastSimulationResult = json.data;
    renderSimulationPreview(json.data);
  } catch (e) {
    showToast('Error: ' + e.message, true);
  }
}

function renderSimulationPreview(data) {
  const panel = document.getElementById('pkg-preview-panel');
  panel.style.display = '';

  // Summary alert
  const alertDiv = document.getElementById('pkg-summary-alert');
  if (data.can_fulfill) {
    alertDiv.innerHTML = `<div class="alert alert-success shadow-sm"><span class="font-bold text-sm">${data.summary}</span></div>`;
    document.getElementById('pkg-confirm-btn').style.display = '';
  } else {
    alertDiv.innerHTML = `<div class="alert alert-warning shadow-sm"><span class="font-bold text-sm">${data.summary}</span></div>`;
    document.getElementById('pkg-confirm-btn').style.display = 'none';
  }

  // Modules breakdown table
  const modTbody = document.getElementById('pkg-modules-tbody');
  modTbody.innerHTML = data.modules_breakdown.map(m => {
    const assembleClass = m.to_assemble > 0 ? 'text-warning font-extrabold' : 'text-success font-bold';
    const stockClass = m.from_stock > 0 ? 'text-success font-extrabold' : '';
    return `<tr>
      <td class="font-mono font-bold">${m.code}</td>
      <td>${m.name}</td>
      <td class="text-center font-bold">${m.needed}</td>
      <td class="text-center">${m.ready_stock}</td>
      <td class="text-center ${stockClass}">${m.from_stock}</td>
      <td class="text-center ${assembleClass}">${m.to_assemble}</td>
    </tr>`;
  }).join('');

  // Raw materials table
  const rawSection = document.getElementById('pkg-raw-section');
  const rawTbody = document.getElementById('pkg-raw-tbody');
  if (data.raw_materials_needed && data.raw_materials_needed.length > 0) {
    rawSection.style.display = '';
    rawTbody.innerHTML = data.raw_materials_needed.map(r => {
      const statusBadge = r.sufficient
        ? '<span class="badge badge-success badge-xs text-white font-bold">Cukup</span>'
        : '<span class="badge badge-error badge-xs text-white font-bold">Kurang</span>';
      const rowClass = r.sufficient ? '' : 'bg-error/10';
      return `<tr class="${rowClass}">
        <td class="font-mono font-bold">${r.code}</td>
        <td>${r.name}</td>
        <td class="text-center font-bold">${r.needed} ${r.unit}</td>
        <td class="text-center">${r.available} ${r.unit}</td>
        <td class="text-center">${statusBadge}</td>
      </tr>`;
    }).join('');
  } else {
    rawSection.style.display = 'none';
  }

  // Shortages section
  const shortSection = document.getElementById('pkg-shortages-section');
  const shortList = document.getElementById('pkg-shortages-list');
  if (data.shortages && data.shortages.length > 0) {
    shortSection.style.display = '';
    shortList.innerHTML = data.shortages.map(s =>
      `<li><strong>${s.code}</strong> ${s.name}: kurang <strong>${s.shortage} ${s.unit}</strong> (butuh ${s.needed}, stok ${s.available})</li>`
    ).join('');
  } else {
    shortSection.style.display = 'none';
  }
}

async function executePackageOrder() {
  const program = document.getElementById('pkg-program').value;
  const qty = document.getElementById('pkg-qty').value;
  const customer = document.getElementById('pkg-customer').value.trim() || 'Gudang Internal';
  const orderedBy = document.getElementById('pkg-orderedby').value.trim();
  const notes = document.getElementById('pkg-notes').value.trim();

  if (!orderedBy) { showToast('Nama petugas wajib diisi', true); return; }

  if (!confirm(`Proses pesanan ${qty} paket ${program}?\n\nStok modul/komponen akan langsung dipotong dan diterbitkan nomor seri unit finish goods.`)) return;

  showToast('Memproses perakitan paket...');

  try {
    const res = await fetch('/api/mikms/package-orders', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        order_date: new Date().toISOString().slice(0, 10),
        program_code: program,
        package_qty: parseInt(qty),
        customer_name: customer,
        ordered_by: orderedBy,
        notes: notes || null,
      }),
    });
    const json = await res.json();
    if (json.status === 'success') {
      showToast(json.message);
      document.getElementById('pkg-confirm-btn').style.display = 'none';
      document.getElementById('pkg-preview-panel').style.display = 'none';
      lastSimulationResult = null;
      loadPackageOrderHistory();

      const serials = json.data?.serial_numbers || [];
      const successBanner = document.getElementById('pkg-order-success-banner');
      if (successBanner && serials.length > 0) {
        successBanner.style.display = 'block';
        successBanner.innerHTML = `
          <div class="alert alert-success shadow-sm border border-success/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
              <div class="font-bold flex items-center gap-2 text-sm text-success-content">
                <span>🎉</span> <span>Perakitan Berhasil: Diterbitkan ${serials.length} Unit MLK</span>
              </div>
              <div class="text-xs mt-1 font-mono font-bold text-success-content/90">${serials.join(', ')}</div>
              <div class="text-[11px] text-success-content/80 mt-1">Unit otomatis masuk ke <strong>Unit Registry</strong> dengan status <span class="badge badge-xs badge-warning font-semibold">Menunggu QC</span>.</div>
            </div>
            <div class="flex gap-2 shrink-0">
              <button class="btn btn-xs btn-neutral font-bold" onclick="switchMikmsTab('unitregistry')">
                📋 Buka Unit Registry
              </button>
              <button class="btn btn-xs btn-primary font-bold text-white shadow-xs" onclick='printBatchUnitLabels(${JSON.stringify(serials)})'>
                🖨️ Stiker Boks Thermal (${serials.length})
              </button>
              <button class="btn btn-xs btn-secondary font-bold text-white shadow-xs" onclick='printMicrobitGridLabels(${JSON.stringify(serials)}, 1)'>
                🏷️ Board 1x1cm Grid (${serials.length})
              </button>
            </div>
          </div>
        `;
      }

      // Reset form
      document.getElementById('pkg-customer').value = '';
      document.getElementById('pkg-orderedby').value = '';
      document.getElementById('pkg-notes').value = '';
      document.getElementById('pkg-qty').value = '1';
    } else {
      showToast(json.message || 'Gagal memproses pesanan', true);
      if (json.shortages) {
        alert('Detail kekurangan:\n' + json.shortages.join('\n'));
      }
    }
  } catch (e) {
    showToast('Error: ' + e.message, true);
  }
}

async function loadPackageOrderHistory() {
  try {
    const res = await fetch('/api/mikms/package-orders', {
      headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });
    const json = await res.json();
    const tbody = document.getElementById('pkg-orders-tbody');
    const orders = json.data?.data || json.data || [];

    if (!orders.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-base-content/40">Belum ada pesanan paket</td></tr>';
      return;
    }

    tbody.innerHTML = orders.map(o => {
      const statusBadge = o.status === 'COMPLETED'
        ? '<span class="badge badge-success badge-xs text-white font-bold">Selesai</span>'
        : `<span class="badge badge-warning badge-xs font-bold">${o.status}</span>`;
      const dateStr = o.order_date ? new Date(o.order_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
      return `<tr>
        <td class="font-mono text-xs">${dateStr}</td>
        <td class="font-bold">${o.program_name || o.program_code}</td>
        <td class="text-center font-extrabold text-primary">${o.package_qty}</td>
        <td>${o.customer_name || '—'}</td>
        <td>${o.ordered_by || '—'}</td>
        <td>${statusBadge}</td>
        <td><button class="btn btn-xs btn-ghost" onclick="showDeductionLog(${o.id})">📋</button></td>
      </tr>`;
    }).join('');
  } catch (e) {
    console.error('loadPackageOrderHistory error:', e);
  }
}

function showDeductionLog(orderId) {
  fetch('/api/mikms/package-orders', {
    headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
  }).then(r => r.json()).then(json => {
    const orders = json.data?.data || json.data || [];
    const order = orders.find(o => o.id === orderId);
    if (!order || !order.deduction_log) {
      alert('Log deduction tidak tersedia');
      return;
    }
    const log = typeof order.deduction_log === 'string' ? JSON.parse(order.deduction_log) : order.deduction_log;
    let msg = `📦 Pesanan #${orderId}: ${log.package_qty}x ${log.program_name}\n`;
    msg += `Waktu: ${log.timestamp}\n\n`;
    msg += '--- Modul ---\n';
    (log.modules || []).forEach(m => {
      msg += `${m.code} ${m.name}: butuh ${m.needed}, dari rw ${m.from_ready_stock}, dirakit ${m.assembled_from_raw}\n`;
    });
    if (log.raw_materials_deducted && log.raw_materials_deducted.length > 0) {
      msg += '\n--- Bahan Baku Dipotong ---\n';
      log.raw_materials_deducted.forEach(r => {
        msg += `${r.item_code} ${r.item_name}: ${r.qty_deducted} (untuk ${r.for_module})\n`;
      });
    }
    alert(msg);
  });
}

/* ═════════════════════════════════════════════
   MODULE STOCK MANAGEMENT
   ═════════════════════════════════════════════ */

let moduleStocksData = [];

function renderModuleStocksTab() {
  loadModuleStocks();
}

async function loadModuleStocks() {
  try {
    const res = await fetch('/api/mikms/module-stocks', {
      headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });
    const json = await res.json();
    moduleStocksData = json.data || [];

    // Render table
    const tbody = document.getElementById('modstock-tbody');
    if (!moduleStocksData.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-base-content/40">Tidak ada data modul</td></tr>';
      return;
    }

    tbody.innerHTML = moduleStocksData.map(s => {
      const stockBadge = s.stock_ready > 0
        ? `<span class="badge badge-success badge-sm text-white font-extrabold">${s.stock_ready}</span>`
        : `<span class="badge badge-ghost badge-sm font-bold">0</span>`;
      return `<tr>
        <td class="font-mono font-bold">${s.module_code}</td>
        <td>${s.module_name}</td>
        <td class="text-center">${stockBadge}</td>
        <td class="text-center">
          <button class="btn btn-xs btn-ghost" onclick="prefillModuleAdjust(${s.module_id}, '${s.module_code}')">⚡ Adjust</button>
        </td>
      </tr>`;
    }).join('');

    // Populate adjust dropdown
    const select = document.getElementById('modadj-module');
    select.innerHTML = moduleStocksData.map(s =>
      `<option value="${s.module_id}">${s.module_code} - ${s.module_name} (stok: ${s.stock_ready})</option>`
    ).join('');

  } catch (e) {
    console.error('loadModuleStocks error:', e);
  }
}

function prefillModuleAdjust(moduleId, moduleCode) {
  const select = document.getElementById('modadj-module');
  select.value = moduleId;
  document.getElementById('modadj-qty').focus();
}

async function adjustModuleStock() {
  const moduleId = document.getElementById('modadj-module').value;
  const qty = document.getElementById('modadj-qty').value;
  const reason = document.getElementById('modadj-reason').value.trim();

  if (!moduleId) { showToast('Pilih modul terlebih dahulu', true); return; }
  if (!qty || qty == 0) { showToast('Masukkan jumlah penyesuaian (+/-)', true); return; }
  if (!reason) { showToast('Alasan penyesuaian wajib diisi', true); return; }

  const currentUser = document.getElementById('sb-uname')?.textContent || 'System';

  try {
    const res = await fetch('/api/mikms/module-stocks/adjust', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        module_id: parseInt(moduleId),
        adjustment: parseInt(qty),
        reason: reason,
        adjusted_by: currentUser,
      }),
    });
    const json = await res.json();
    if (json.status === 'success') {
      showToast(json.message);
      document.getElementById('modadj-qty').value = '';
      document.getElementById('modadj-reason').value = '';
      loadModuleStocks();
    } else {
      showToast(json.message || 'Gagal menyesuaikan stok', true);
    }
  } catch (e) {
    showToast('Error: ' + e.message, true);
  }
}

/* ═════════════════════════════════════════════
   MASTER PROGRAM KIT (BOM PACKAGES)
   ═════════════════════════════════════════════ */
let programsList = [];

async function ensureMikmsModulesLoaded() {
  if (mikmsModules && mikmsModules.length > 0) return mikmsModules;
  try {
    const mr = await api('/api/mikms/modules');
    if (mr.ok) {
      const md = await mr.json();
      if (md.status === 'success' || md.success) {
        mikmsModules = md.data || [];
      }
    }
  } catch(e) {
    console.warn('Gagal memuat modul MIKMS:', e);
  }
  return mikmsModules;
}

async function loadProgramsPage() {
  const tbody = document.getElementById('programs-tbody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-base-content/40">Memuat data program kit...</td></tr>';

  // Ensure mikmsModules is populated
  await ensureMikmsModulesLoaded();

  try {
    const res = await api('/api/mikms/programs');
    const d = await res.json();
    if (d.status === 'success' || d.success) {
      programsList = d.data || [];
      renderProgramsTable();
    } else {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-error">${escHtml(d.message || 'Gagal memuat program')}</td></tr>`;
    }
  } catch (err) {
    console.error('Error loading programs:', err);
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-error">Koneksi bermasalah saat memuat program.</td></tr>';
  }
}

function renderProgramsTable() {
  const tbody = document.getElementById('programs-tbody');
  if (!tbody) return;
  if (!programsList.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-base-content/40">Belum ada Program Kit. Klik "+ Tambah Program Kit" untuk menambahkan.</td></tr>';
    return;
  }

  tbody.innerHTML = programsList.map(p => {
    const modules = Array.isArray(p.modules) ? p.modules : [];
    const moduleChips = modules.map(m => `<span class="badge badge-sm badge-neutral font-mono font-bold">${escHtml(m)}</span>`).join(' ');
    const statusBadge = p.is_active 
      ? '<span class="badge badge-success badge-sm text-white font-bold">Aktif</span>' 
      : '<span class="badge badge-ghost badge-sm font-semibold">Nonaktif</span>';

    return `
      <tr class="hover">
        <td class="font-mono font-extrabold text-primary">${escHtml(p.code)}</td>
        <td>
          <div class="font-bold text-base-content">${escHtml(p.name)}</div>
          ${p.description ? `<div class="text-[11px] text-base-content/50">${escHtml(p.description)}</div>` : ''}
        </td>
        <td>
          <div class="flex flex-wrap gap-1">${moduleChips || '<span class="text-xs text-base-content/40">-</span>'}</div>
        </td>
        <td class="text-right font-extrabold text-base-content">${p.total_pcs || 0} <span class="text-xs font-normal text-base-content/50">pcs</span></td>
        <td class="text-center">${statusBadge}</td>
        <td class="text-center">
          <div class="flex items-center justify-center gap-1">
            <button class="btn btn-xs btn-ghost text-info" onclick="editProgram(${p.id})" title="Edit Program">✏️</button>
            <button class="btn btn-xs btn-ghost text-error" onclick="deleteProgram(${p.id}, '${escHtml(p.code)}')" title="Hapus / Nonaktifkan">🗑️</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

async function openProgramModal(program = null) {
  document.getElementById('program-form').reset();
  const titleEl = document.getElementById('prog-modal-title');
  const codeEl = document.getElementById('prog-code');
  const idEl = document.getElementById('prog-id');
  const nameEl = document.getElementById('prog-name');
  const descEl = document.getElementById('prog-desc');
  const activeEl = document.getElementById('prog-active');

  const selectedCodes = program ? (Array.isArray(program.modules) ? program.modules : []) : [];

  if (program) {
    titleEl.textContent = `Edit Program: ${program.code}`;
    idEl.value = program.id;
    codeEl.value = program.code;
    codeEl.disabled = true;
    nameEl.value = program.name;
    descEl.value = program.description || '';
    activeEl.value = program.is_active ? '1' : '0';
  } else {
    titleEl.textContent = 'Tambah Program Kit Baru';
    idEl.value = '';
    codeEl.disabled = false;
    activeEl.value = '1';
  }

  // Buka modal segera agar interaksi responsif
  document.getElementById('program-modal').classList.add('modal-open');

  // Render checklist langsung dengan data yang ada / fallback
  renderProgramModulesChecklist(selectedCodes);
  updateProgramCalcPcs();

  // Jika modul belum dimuat dari API, muat dan render ulang
  if (!mikmsModules || !mikmsModules.length) {
    await ensureMikmsModulesLoaded();
    renderProgramModulesChecklist(selectedCodes);
    updateProgramCalcPcs();
  }
}

function closeProgramModal() {
  document.getElementById('program-modal').classList.remove('modal-open');
}

function renderProgramModulesChecklist(selectedCodes = []) {
  const container = document.getElementById('prog-modules-checklist');
  if (!container) return;

  const defaultFallbackModules = [
    { code: 'M01', name: 'Controller Kit', description: 'Micro:bit V2 dan kabel data' },
    { code: 'M02', name: 'LED Kit', description: 'LED Merah, Hijau, Kuning & Resistor' },
    { code: 'M03', name: 'Motion Kit', description: 'Servo SG90 180°, 360°, MG996R' },
    { code: 'M04', name: 'Sensor Kit', description: 'Sensor Ultrasonik HC-SR04' },
    { code: 'M05', name: 'Power Kit', description: 'Battery Holder & Baterai AAA' },
    { code: 'M06', name: 'Mechanical Kit', description: 'Lego, Separator, dan Base Plate' },
    { code: 'M07', name: 'Connection Kit', description: 'Breadboard Mini, Push Connector, Kabel' },
    { code: 'M08', name: 'Jimu Trackbot', description: 'Robotik Kit Jimu Trackbot (Finish Good)' },
    { code: 'M09', name: 'Erboblox', description: 'Erboblox Kit (Finish Good)' },
    { code: 'M10', name: 'Arduino Learning Kit', description: 'Arduino Kit Assembly (Finish Good)' },
    { code: 'M11', name: 'Sub-Assembly Kit', description: 'Sub-Assembly Kit Tambahan' }
  ];

  const list = (mikmsModules && mikmsModules.length > 0) ? mikmsModules : defaultFallbackModules;

  container.innerHTML = list.map(m => {
    const isChecked = selectedCodes.includes(m.code) ? 'checked' : '';
    const compCount = Array.isArray(m.components) && m.components.length > 0 ? `${m.components.length} komp` : '';
    return `
      <label class="flex items-start gap-2.5 p-2 rounded-lg bg-base-100 hover:bg-base-200/80 border border-base-200 hover:border-primary/40 transition-all cursor-pointer text-xs select-none shadow-xs">
        <input type="checkbox" name="prog_module_check" value="${escHtml(m.code)}" class="checkbox checkbox-xs checkbox-primary mt-0.5" ${isChecked} onchange="updateProgramCalcPcs()">
        <div class="flex flex-col min-w-0 flex-1">
          <div class="flex items-center justify-between gap-1">
            <div class="flex items-center gap-1.5 min-w-0">
              <span class="font-mono font-black text-primary text-[11px]">${escHtml(m.code)}</span>
              <span class="truncate text-base-content font-bold text-[11px]">${escHtml(m.name)}</span>
            </div>
            ${compCount ? `<span class="badge badge-ghost badge-xs text-[9px] font-mono shrink-0">${compCount}</span>` : ''}
          </div>
          ${m.description ? `<span class="text-[10px] text-base-content/50 truncate mt-0.5">${escHtml(m.description)}</span>` : ''}
        </div>
      </label>
    `;
  }).join('');
}

function selectStandardModules() {
  const std = ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07'];
  document.querySelectorAll('input[name="prog_module_check"]').forEach(cb => {
    cb.checked = std.includes(cb.value);
  });
  updateProgramCalcPcs();
}

function clearProgramModules() {
  document.querySelectorAll('input[name="prog_module_check"]').forEach(cb => {
    cb.checked = false;
  });
  updateProgramCalcPcs();
}

function updateProgramCalcPcs() {
  const checked = Array.from(document.querySelectorAll('input[name="prog_module_check"]:checked')).map(c => c.value);
  let total = 0;

  // 1. Hitung langsung dari komponen mikmsModules yang sudah dimuat
  if (mikmsModules && mikmsModules.length) {
    checked.forEach(modCode => {
      const m = mikmsModules.find(x => x.code === modCode);
      if (m && Array.isArray(m.components) && m.components.length > 0) {
        m.components.forEach(c => {
          total += (parseInt(c.quantity_per_module, 10) || 1);
        });
      }
    });
  }

  // 2. Fallback jika data komponen belum tersedia di mikmsModules
  if (total === 0 && typeof BOMS_DATA !== 'undefined') {
    if (BOMS_DATA.microbit) {
      BOMS_DATA.microbit.forEach(item => {
        checked.forEach(modCode => {
          if (item.mod && item.mod.includes(modCode)) {
            total += (item.qty || 1);
          }
        });
      });
    }
    if (checked.includes('M08') || checked.includes('RJ01')) total += 1;
    if (checked.includes('M09') || checked.includes('ERB01')) total += 1;
    if (checked.includes('M10') || checked.includes('ALK01')) total += 1;
    if (checked.includes('M11')) total += 1;
  }

  const calcEl = document.getElementById('prog-calc-pcs');
  if (calcEl) calcEl.textContent = `Pilihan: ${checked.length} modul (~${total} item/paket)`;
}

async function submitProgramForm(e) {
  e.preventDefault();
  const id = document.getElementById('prog-id').value;
  const isEdit = !!id;
  const code = document.getElementById('prog-code').value.trim().toUpperCase();
  const name = document.getElementById('prog-name').value.trim();
  const description = document.getElementById('prog-desc').value.trim();
  const is_active = document.getElementById('prog-active').value === '1';

  const selectedModules = Array.from(document.querySelectorAll('input[name="prog_module_check"]:checked')).map(c => c.value);
  if (!selectedModules.length) {
    showToast('Pilih minimal 1 modul untuk Program Kit ini', true);
    return;
  }

  const payload = {
    name,
    description,
    modules: selectedModules,
    is_active,
  };
  if (!isEdit) {
    payload.code = code;
  }

  const btn = document.getElementById('prog-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Menyimpan...';

  try {
    const url = isEdit ? `/api/mikms/programs/${id}` : '/api/mikms/programs';
    const method = isEdit ? 'PUT' : 'POST';

    const r = await api(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const d = await r.json();

    if (d.status === 'success' || d.success) {
      showToast(d.message || 'Program berhasil disimpan!');
      closeProgramModal();
      await loadProgramsPage();
      await loadPackageProgramsDropdown();
    } else {
      showToast(d.message || 'Gagal menyimpan program', true);
    }
  } catch (err) {
    console.error('Error saving program:', err);
    showToast('Terjadi kesalahan koneksi', true);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Simpan Program';
  }
}

function editProgram(id) {
  const p = programsList.find(x => x.id === id);
  if (p) openProgramModal(p);
}

async function deleteProgram(id, code) {
  if (!confirm(`Hapus / nonaktifkan Program Kit "${code}"?`)) return;

  try {
    const r = await api(`/api/mikms/programs/${id}`, { method: 'DELETE' });
    const d = await r.json();
    if (d.status === 'success' || d.success) {
      showToast(d.message || 'Program berhasil dihapus');
      await loadProgramsPage();
      await loadPackageProgramsDropdown();
    } else {
      showToast(d.message || 'Gagal menghapus program', true);
    }
  } catch (err) {
    showToast('Koneksi gagal', true);
  }
}

/* Dynamic dropdown in Pesan Paket form */
async function loadPackageProgramsDropdown() {
  const select = document.getElementById('pkg-program');
  if (!select) return;

  try {
    const r = await api('/api/mikms/programs?active_only=1');
    const d = await r.json();
    if (d.status === 'success' && d.data && d.data.length > 0) {
      const currentVal = select.value;
      select.innerHTML = d.data.map(p => `
        <option value="${escHtml(p.code)}">${escHtml(p.name)} (${escHtml(p.code)}) — ${p.total_pcs || 0} item/paket</option>
      `).join('');
      if (currentVal && d.data.some(p => p.code === currentVal)) {
        select.value = currentVal;
      }
    }
  } catch(e) {
    console.warn('Failed to load active programs for package order:', e);
  }
}

/* ═════════════════════════════════════════════
   MIKMS FINISH GOODS, DIRECT INPUT, QC, UNIT REGISTRY & THERMAL PRINT
   ═════════════════════════════════════════════ */
let mikmsProducts = [];
let currentQcTemplate = [];
let currentQcUnit = null;
let currentUrPage = 1;

// 1. Load Products (untuk dropdown & filter)
async function loadMikmsProducts() {
  try {
    const r = await api('/api/mikms/products');
    const d = await r.json();
    if (d.status === 'success' && d.data) {
      mikmsProducts = d.data;

      // Populate Unit Registry filter product select (semua produk)
      const urSelect = document.getElementById('ur-filter-product');
      if (urSelect) {
        const curVal = urSelect.value;
        urSelect.innerHTML = '<option value="">Semua Produk</option>' +
          mikmsProducts.map(p => `<option value="${p.id}">${escHtml(p.name)} (${escHtml(p.code)})</option>`).join('');
        if (curVal) urSelect.value = curVal;
      }
    }
  } catch (err) {
    console.warn('Failed to load MIKMS products:', err);
  }
}

// 2. Unit Registry Tab
async function loadUnitRegistry(page = 1) {
  currentUrPage = page;
  const tbody = document.getElementById('ur-table-body');
  if (!tbody) return;

  const search = document.getElementById('ur-search')?.value || '';
  const productId = document.getElementById('ur-filter-product')?.value || '';
  const status = document.getElementById('ur-filter-status')?.value || '';

  const params = new URLSearchParams();
  if (page) params.append('page', page);
  if (search) params.append('search', search);
  if (productId) params.append('product_id', productId);
  if (status) params.append('status', status);

  try {
    const r = await api(`/api/mikms/units?${params.toString()}`);
    const d = await r.json();
    if (d.status === 'success' && d.data) {
      const units = d.data.data || [];
      if (units.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-base-content/40">Belum ada unit yang terdaftar</td></tr>';
      } else {
        tbody.innerHTML = units.map(u => {
          const badgeClass = u.status === 'in_stock' ? 'badge-success' :
                             u.status === 'qc_pending' ? 'badge-warning' :
                             u.status === 'dispatched' ? 'badge-info' :
                             u.status === 'returned' ? 'badge-primary' :
                             u.status === 'repair' ? 'badge-error' : 'badge-ghost';
          return `
            <tr class="hover">
              <td class="text-center">
                <input type="checkbox" class="checkbox checkbox-xs ur-row-cb" value="${escHtml(u.serial_number)}" data-product="${escHtml(u.product_code || '')}" onchange="onUrRowCheckChange()">
              </td>
              <td class="font-mono font-bold text-xs">${escHtml(u.serial_number)}</td>
              <td><span class="font-semibold">${escHtml(u.product_name || '-')}</span> <span class="text-xs text-base-content/50">(${escHtml(u.product_code || '')})</span></td>
              <td><span class="badge ${badgeClass} badge-xs font-semibold">${escHtml(u.status_label)}</span></td>
              <td class="text-xs capitalize">${escHtml(u.source_type?.replace('_', ' ') || '-')}</td>
              <td class="text-xs">${u.qc_status === 'LOLOS' ? '<span class="text-success font-bold">✓ LOLOS</span>' : u.qc_status === 'TIDAK LOLOS' ? '<span class="text-error font-bold">✗ GAGAL</span>' : '<span class="text-base-content/40">Belum</span>'}</td>
              <td class="text-xs text-base-content/60">${escHtml(u.created_at || '-')}</td>
              <td>
                <div class="flex items-center gap-1">
                  <button class="btn btn-xs btn-outline btn-info" title="Cetak Stiker Boks Thermal" onclick="printUnitThermalLabel('${escHtml(u.serial_number)}')">🖨️</button>
                  ${(u.product_code === 'MLK' || u.serial_number.startsWith('MLK-')) ? `
                    <button class="btn btn-xs btn-outline btn-secondary" title="Cetak Stiker Board Micro:bit (1x1 cm Grid)" onclick="openMicrobitBulkModal(['${escHtml(u.serial_number)}'])">🏷️</button>
                  ` : ''}
                  ${u.status === 'qc_pending' || u.status === 'returned' ? `
                    <button class="btn btn-xs btn-outline btn-warning" onclick="goToQcUnit('${escHtml(u.serial_number)}')">QC</button>
                  ` : ''}
                  ${u.status === 'in_stock' ? `
                    <button class="btn btn-xs btn-outline btn-primary" onclick="goToDispatchUnit('${escHtml(u.serial_number)}')">Kirim</button>
                  ` : ''}
                </div>
              </td>
            </tr>
          `;
        }).join('');
      }
      const masterCb = document.getElementById('ur-check-all');
      if (masterCb) { masterCb.checked = false; masterCb.indeterminate = false; }
      updateUrSelectionToolbar();
      renderUrPagination(d.data);
    }
  } catch (err) {
    console.error('Failed to load unit registry:', err);
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-6 text-error">Gagal memuat data</td></tr>';
  }
}

function renderUrPagination(pagination) {
  const container = document.getElementById('ur-pagination');
  if (!container) return;
  if (!pagination || pagination.last_page <= 1) {
    container.innerHTML = '';
    return;
  }
  let html = '<div class="btn-group">';
  if (pagination.current_page > 1) {
    html += `<button class="btn btn-xs btn-ghost" onclick="loadUnitRegistry(${pagination.current_page - 1})">«</button>`;
  }
  html += `<button class="btn btn-xs btn-active">${pagination.current_page} / ${pagination.last_page}</button>`;
  if (pagination.current_page < pagination.last_page) {
    html += `<button class="btn btn-xs btn-ghost" onclick="loadUnitRegistry(${pagination.current_page + 1})">»</button>`;
  }
  html += '</div>';
  container.innerHTML = html;
}

function goToQcUnit(serial) {
  switchMikmsTab('qcunit');
  const serialInput = document.getElementById('qcu-serial');
  if (serialInput) {
    serialInput.value = serial;
    loadQcChecklist();
  }
}

function goToDispatchUnit(serial) {
  switchMikmsTab('dispatch');
  const dspInput = document.getElementById('dsp-serials');
  if (dspInput) {
    dspInput.value = serial;
  }
}

// 4. QC Unit Tab (Checklist)
async function loadQcChecklist() {
  const serial = document.getElementById('qcu-serial')?.value?.trim().toUpperCase();
  const infoEl = document.getElementById('qcu-unit-info');
  const infoText = document.getElementById('qcu-unit-info-text');
  const wrap = document.getElementById('qcu-checklist-wrap');
  const itemsContainer = document.getElementById('qcu-checklist-items');

  if (!serial) {
    showToast('Masukkan nomor seri unit terlebih dahulu', true);
    return;
  }

  try {
    const rUnit = await api(`/api/mikms/units/${encodeURIComponent(serial)}`);
    const dUnit = await rUnit.json();
    if (!dUnit.status || !dUnit.data) {
      showToast(dUnit.message || `Unit dengan serial "${serial}" tidak ditemukan`, true);
      infoEl?.classList.add('hidden');
      wrap?.classList.add('hidden');
      return;
    }

    currentQcUnit = dUnit.data;
    const prod = currentQcUnit.product;

    infoEl.classList.remove('hidden');
    infoText.innerHTML = `
      <div class="font-bold text-sm text-base-content">${escHtml(prod?.name || '-')} (${escHtml(prod?.code || '-')}) — Serial: <span class="font-mono text-primary font-extrabold">${escHtml(currentQcUnit.serial_number)}</span></div>
      <div class="text-xs text-base-content/70">Status saat ini: <span class="font-bold">${escHtml(currentQcUnit.status_label)}</span> | Sumber: ${escHtml(currentQcUnit.source_type)} | Riwayat QC: ${currentQcUnit.qc_logs?.length || 0} kali</div>
    `;

    const rTpl = await api(`/api/mikms/qc-templates/${prod.id}`);
    const dTpl = await rTpl.json();
    if (dTpl.status === 'success' && dTpl.data?.checklist) {
      currentQcTemplate = dTpl.data.checklist;

      if (currentQcTemplate.length === 0) {
        itemsContainer.innerHTML = '<div class="text-xs text-warning py-2">Belum ada checklist default untuk produk ini.</div>';
      } else {
        itemsContainer.innerHTML = currentQcTemplate.map((item, idx) => `
          <div class="flex items-center justify-between p-2.5 rounded-lg border border-base-200 bg-base-100 hover:bg-base-200/50 transition">
            <div class="flex items-center gap-2">
              <span class="badge badge-sm badge-ghost font-mono text-[10px]">${idx + 1}</span>
              <span class="text-xs font-semibold text-base-content/90">${escHtml(item.check_item)}</span>
            </div>
            <div class="flex items-center gap-3">
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="radio" name="qc_check_${idx}" value="1" class="radio radio-success radio-xs" checked onchange="updateQcPreview()">
                <span class="text-xs font-bold text-success">Lolos</span>
              </label>
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="radio" name="qc_check_${idx}" value="0" class="radio radio-error radio-xs" onchange="updateQcPreview()">
                <span class="text-xs font-bold text-error">Tidak</span>
              </label>
            </div>
          </div>
        `).join('');
      }

      wrap.classList.remove('hidden');
      updateQcPreview();
    } else {
      showToast('Gagal memuat template checklist', true);
    }
  } catch (err) {
    console.error('Error loading QC checklist:', err);
    showToast('Gagal mengambil data unit atau checklist', true);
  }
}

function updateQcPreview() {
  const previewIcon = document.getElementById('qcu-preview-icon');
  const previewText = document.getElementById('qcu-preview-text');
  const previewBox = document.getElementById('qcu-status-preview');
  if (!previewIcon || !previewText || !previewBox) return;

  if (currentQcTemplate.length === 0) {
    previewIcon.textContent = '—';
    previewText.textContent = 'Tidak ada checklist';
    previewBox.className = 'flex items-center gap-3 p-3 rounded-xl bg-base-200 mb-4 text-sm font-bold text-base-content/60';
    return;
  }

  let allPass = true;
  for (let idx = 0; idx < currentQcTemplate.length; idx++) {
    const checkedRadio = document.querySelector(`input[name="qc_check_${idx}"]:checked`);
    if (!checkedRadio || checkedRadio.value !== '1') {
      allPass = false;
      break;
    }
  }

  if (allPass) {
    previewIcon.textContent = '✅';
    previewText.textContent = 'HASIL: LULUS QC → Unit akan berstatus "IN STOCK" (siap digunakan/sewa)';
    previewBox.className = 'flex items-center gap-3 p-3 rounded-xl bg-success/15 text-success border border-success/30 mb-4 text-sm font-bold';
  } else {
    previewIcon.textContent = '⚠️';
    previewText.textContent = 'HASIL: TIDAK LOLOS → Ada poin gagal. Unit tetap pending / masuk repair.';
    previewBox.className = 'flex items-center gap-3 p-3 rounded-xl bg-error/15 text-error border border-error/30 mb-4 text-sm font-bold';
  }
}

async function submitQcUnit() {
  const serial = document.getElementById('qcu-serial')?.value?.trim().toUpperCase();
  const qc_date = document.getElementById('qcu-date')?.value;
  const checked_by = document.getElementById('qcu-checker')?.value?.trim();
  const qc_type = document.getElementById('qcu-type')?.value;
  const defect_notes = document.getElementById('qcu-defect')?.value?.trim();

  if (!serial || !qc_date || !checked_by) {
    showToast('Mohon lengkapi Serial Number, Tanggal QC, dan Nama Pemeriksa (*)', true);
    return;
  }

  if (currentQcTemplate.length === 0) {
    showToast('Checklist belum dimuat. Klik "Muat Checklist" dahulu.', true);
    return;
  }

  const checklist_results = [];
  let allPass = true;
  for (let idx = 0; idx < currentQcTemplate.length; idx++) {
    const itemText = currentQcTemplate[idx].check_item;
    const checkedRadio = document.querySelector(`input[name="qc_check_${idx}"]:checked`);
    const isPass = checkedRadio && checkedRadio.value === '1';
    if (!isPass) allPass = false;
    checklist_results.push({ item: itemText, pass: isPass });
  }

  if (!allPass && !defect_notes) {
    if (!confirm('Unit TIDAK LOLOS QC tetapi belum ada Catatan Defek. Lanjutkan simpan?')) {
      return;
    }
  }

  try {
    const r = await api('/api/mikms/qc-logs', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        serial_number: serial,
        qc_date,
        checked_by,
        qc_type,
        checklist_results,
        defect_notes
      })
    });
    const d = await r.json();
    if (d.status === 'success' || d.success) {
      showToast(d.message || 'Hasil QC berhasil disimpan');

      // Jika LULUS QC, langsung tawarkan cetak label thermal
      if (allPass) {
        if (confirm(`QC ${serial} LOLOS! Cetak stiker thermal untuk unit ini sekarang?`)) {
          printUnitThermalLabel(serial);
        }
      }

      document.getElementById('qcu-checklist-wrap')?.classList.add('hidden');
      document.getElementById('qcu-unit-info')?.classList.add('hidden');
      document.getElementById('qcu-serial').value = '';
      if (document.getElementById('qcu-defect')) document.getElementById('qcu-defect').value = '';
      currentQcUnit = null;
      currentQcTemplate = [];
      loadMikmsProducts();
    } else {
      showToast(d.message || 'Gagal menyimpan hasil QC', true);
    }
  } catch (err) {
    console.error('Error submitting QC unit:', err);
    showToast('Koneksi gagal', true);
  }
}

// 5. Dispatch Tab
async function submitDispatch() {
  const dispatch_date = document.getElementById('dsp-date')?.value;
  const type = document.getElementById('dsp-type')?.value;
  const serialsRaw = document.getElementById('dsp-serials')?.value?.trim();
  const destination = document.getElementById('dsp-destination')?.value?.trim();
  const dispatched_by = document.getElementById('dsp-by')?.value?.trim();
  const notes = document.getElementById('dsp-notes')?.value?.trim();

  if (!dispatch_date || !type || !serialsRaw || !dispatched_by) {
    showToast('Mohon lengkapi Tanggal, Tipe, Serial Numbers, dan Nama Petugas (*)', true);
    return;
  }

  const serial_numbers = serialsRaw.split(',').map(s => s.trim().toUpperCase()).filter(Boolean);
  if (serial_numbers.length === 0) {
    showToast('Masukkan minimal 1 serial number', true);
    return;
  }

  try {
    const r = await api('/api/mikms/dispatch', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        dispatch_date,
        type,
        serial_numbers,
        destination,
        dispatched_by,
        notes
      })
    });
    const d = await r.json();
    if (d.status === 'success' || d.success) {
      showToast(d.message || 'Dispatch berhasil diproses');
      document.getElementById('dsp-serials').value = '';
      document.getElementById('dsp-destination').value = '';
      document.getElementById('dsp-notes').value = '';
      loadMikmsProducts();
    } else {
      showToast(d.message || 'Gagal memproses dispatch', true);
    }
  } catch (err) {
    console.error('Error submitting dispatch:', err);
    showToast('Koneksi gagal', true);
  }
}

async function submitReturn() {
  const serial_number = document.getElementById('ret-serial')?.value?.trim().toUpperCase();
  const received_by = document.getElementById('ret-received')?.value?.trim();
  const notes = document.getElementById('ret-notes')?.value?.trim();

  if (!serial_number || !received_by) {
    showToast('Mohon isi Serial Unit dan Nama Penerima (*)', true);
    return;
  }

  try {
    const r = await api('/api/mikms/dispatch/return', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        serial_number,
        received_by,
        notes
      })
    });
    const d = await r.json();
    if (d.status === 'success' || d.success) {
      showToast(d.message || 'Retur berhasil dicatat. Unit wajib QC ulang!');
      document.getElementById('ret-serial').value = '';
      document.getElementById('ret-notes').value = '';
      loadMikmsProducts();
    } else {
      showToast(d.message || 'Gagal mencatat retur', true);
    }
  } catch (err) {
    console.error('Error submitting return:', err);
    showToast('Koneksi gagal', true);
  }
}

// 6. THERMAL PRINTER LABEL GENERATOR (BARCODE 1D + QR CODE + SERIAL)
async function printUnitThermalLabel(serialNumber) {
  showToast('Menyiapkan label thermal...');
  try {
    const r = await api(`/api/mikms/units/${encodeURIComponent(serialNumber)}`);
    const d = await r.json();
    if (!d.status || !d.data) {
      showToast(`Data unit ${serialNumber} tidak ditemukan`, true);
      return;
    }
    buildAndPrintThermalLabels([d.data]);
  } catch (err) {
    console.error('Error printing thermal label:', err);
    showToast('Gagal mencetak label thermal', true);
  }
}

async function printBatchUnitLabels(serialNumbers) {
  if (!serialNumbers || serialNumbers.length === 0) return;
  showToast(`Menyiapkan ${serialNumbers.length} label thermal...`);
  try {
    const units = [];
    for (const serial of serialNumbers) {
      const r = await api(`/api/mikms/units/${encodeURIComponent(serial)}`);
      const d = await r.json();
      if (d.status && d.data) {
        units.push(d.data);
      }
    }
    if (units.length === 0) {
      showToast('Tidak ada unit yang dapat dicetak', true);
      return;
    }
    buildAndPrintThermalLabels(units);
  } catch (err) {
    console.error('Error printing batch thermal labels:', err);
    showToast('Gagal mencetak label thermal batch', true);
  }
}

function buildAndPrintThermalLabels(units) {
  const printArea = document.getElementById('print-area');
  if (!printArea) return;

  printArea.innerHTML = '';
  printArea.className = 'thermal-mode';

  units.forEach((unit, idx) => {
    const prodName = unit.product?.name || 'Finish Good Unit';
    const serial = unit.serial_number;
    const latestQc = (unit.qc_logs && unit.qc_logs.length > 0) ? unit.qc_logs[0] : null;
    const qcStatus = latestQc ? latestQc.status_qc : (unit.status === 'in_stock' ? 'LOLOS' : 'PENDING');
    const qcDate = latestQc?.qc_date || (unit.created_at ? unit.created_at.split('T')[0] : new Date().toISOString().split('T')[0]);
    const qcChecker = latestQc?.checked_by || 'Budi Santoso';
    const canRent = unit.product?.can_rent ? 'SEWA / BELI' : 'BELI';

    const card = document.createElement('div');
    card.className = 'thermal-unit-card';
    card.style.cssText = 'width:54mm;max-width:58mm;box-sizing:border-box;margin:0 auto 4mm auto;border:1.5px solid #000;background:#fff;page-break-after:always;break-after:page;font-family:\'JetBrains Mono\',monospace;color:#000;';

    const uid = 'thm_' + idx + '_' + Math.random().toString(36).substr(2, 6);

    card.innerHTML = `
      <!-- Header: Nama Produk & Serial Boks Luar -->
      <div style="padding:2.5mm 3mm 2mm 3mm;border-bottom:1.5px solid #000;text-align:left;">
        <div style="font-size:10px;font-weight:700;line-height:1.2;color:#111;">${escHtml(prodName)}</div>
        <div style="font-size:15px;font-weight:900;letter-spacing:0.8px;line-height:1.2;margin-top:1.5mm;color:#000;">${escHtml(serial)}</div>
      </div>

      <!-- Content 2 Kolom: Kiri QR Code, Kanan Info QC -->
      <div style="display:flex;align-items:stretch;justify-content:space-between;padding:0;">
        <!-- Kiri: QR Code Serial -->
        <div style="width:48%;display:flex;align-items:center;justify-content:center;padding:2.5mm 1mm 2.5mm 2.5mm;">
          <div id="qr_${uid}" style="width:48px;height:48px;"></div>
        </div>

        <!-- Kanan: Detail QC -->
        <div style="width:52%;font-size:8px;line-height:1.45;text-align:left;border-left:1.5px solid #000;padding:2.5mm 2mm 2.5mm 2.5mm;display:flex;flex-direction:column;justify-content:center;">
          <div><span style="font-weight:700;">QC &nbsp;:</span> <strong style="font-weight:900;">${qcStatus === 'LOLOS' ? '✓ PASSED' : escHtml(qcStatus)}</strong></div>
          <div><span style="font-weight:700;">TGL :</span> ${escHtml(qcDate)}</div>
          <div><span style="font-weight:700;">BY &nbsp;:</span> ${escHtml(qcChecker)}</div>
          <div><span style="font-weight:700;">OPS :</span> ${escHtml(canRent)}</div>
        </div>
      </div>

      ${unit.children && unit.children.length > 0 ? `
        <!-- Bundle Contents jika ada (misal ROBO) -->
        <div style="font-size:6.5px;border-top:1px dashed #000;padding:2mm 2.5mm;text-align:left;line-height:1.25;">
          <div style="font-weight:900;text-transform:uppercase;">📦 ISI KEMASAN:</div>
          ${unit.children.map(c => `<div>• ${escHtml(c.product_code || '')}: <strong>${escHtml(c.serial_number)}</strong></div>`).join('')}
        </div>
      ` : ''}
    `;

    printArea.appendChild(card);

    // Render 2D QR Code
    setTimeout(() => {
      try {
        const qrContainer = document.getElementById(`qr_${uid}`);
        if (qrContainer && typeof QRCode !== 'undefined') {
          new QRCode(qrContainer, {
            text: serial,
            width: 48,
            height: 48,
            correctLevel: QRCode.CorrectLevel.M
          });
        }
      } catch (qrErr) {
        console.warn('QRCode render error:', qrErr);
      }
    }, 50);
  });

  // Trigger print dialog with reliable cleanup
  window.addEventListener('afterprint', cleanPrintArea, { once: true });
  setTimeout(() => {
    window.print();
    setTimeout(cleanPrintArea, 1500);
    setTimeout(cleanPrintArea, 3500);
  }, 350);
}

// ═════════════════════════════════════════════
// UNIT REGISTRY MULTI-SELECT & BULK ACTIONS
// ═════════════════════════════════════════════
function toggleUrCheckAll(master) {
  const cbs = document.querySelectorAll('.ur-row-cb');
  cbs.forEach(cb => cb.checked = master.checked);
  updateUrSelectionToolbar();
}

function onUrRowCheckChange() {
  const cbs = document.querySelectorAll('.ur-row-cb');
  const checked = document.querySelectorAll('.ur-row-cb:checked');
  const master = document.getElementById('ur-check-all');
  if (master) {
    master.checked = cbs.length > 0 && checked.length === cbs.length;
    master.indeterminate = checked.length > 0 && checked.length < cbs.length;
  }
  updateUrSelectionToolbar();
}

function getSelectedUrSerials() {
  const checked = document.querySelectorAll('.ur-row-cb:checked');
  return Array.from(checked).map(cb => cb.value.trim()).filter(Boolean);
}

function updateUrSelectionToolbar() {
  const selected = getSelectedUrSerials();
  const toolbar = document.getElementById('ur-selection-toolbar');
  const countEl = document.getElementById('ur-selected-count');
  const numEls = document.querySelectorAll('.ur-selected-num');
  const quickFillCount = document.getElementById('mb-fill-checked-count');
  
  if (quickFillCount) quickFillCount.textContent = selected.length;

  if (selected.length > 0) {
    if (toolbar) toolbar.classList.remove('hidden');
    if (countEl) countEl.textContent = selected.length;
    numEls.forEach(el => el.textContent = selected.length);
  } else {
    if (toolbar) toolbar.classList.add('hidden');
  }
}

function clearUrSelection() {
  const cbs = document.querySelectorAll('.ur-row-cb');
  cbs.forEach(cb => cb.checked = false);
  const master = document.getElementById('ur-check-all');
  if (master) {
    master.checked = false;
    master.indeterminate = false;
  }
  updateUrSelectionToolbar();
}

function printSelectedThermalLabels() {
  const serials = getSelectedUrSerials();
  if (serials.length === 0) {
    showToast('Pilih minimal 1 unit untuk dicetak stiker thermal', true);
    return;
  }
  printBatchUnitLabels(serials);
}

function openSelectedMicrobitModal() {
  const serials = getSelectedUrSerials();
  if (serials.length === 0) {
    showToast('Pilih minimal 1 unit MLK', true);
    return;
  }
  openMicrobitBulkModal(serials);
}

// ═════════════════════════════════════════════
// 6b. MODAL CETAK BULK STIKER MICRO:BIT GRID (1x1 cm)
// ═════════════════════════════════════════════
function openMicrobitBulkModal(initialSerials = null) {
  const modal = document.getElementById('microbit-bulk-modal');
  if (!modal) return;

  const textarea = document.getElementById('mb-bulk-serials');
  const copiesSelect = document.getElementById('mb-bulk-copies');
  if (copiesSelect) copiesSelect.value = '1';

  let list = [];
  if (Array.isArray(initialSerials) && initialSerials.length > 0) {
    list = initialSerials;
  } else {
    const checked = getSelectedUrSerials();
    if (checked.length > 0) {
      list = checked;
    } else {
      const rows = document.querySelectorAll('#ur-table-body tr');
      rows.forEach(tr => {
        const serialTd = tr.querySelector('td.font-mono');
        if (serialTd && serialTd.textContent.startsWith('MLK-')) {
          list.push(serialTd.textContent.trim());
        }
      });
    }
  }

  if (textarea) {
    textarea.value = list.join('\n');
  }

  updateMicrobitBulkSummary();
  modal.classList.add('modal-open');
}

function closeMicrobitBulkModal() {
  const modal = document.getElementById('microbit-bulk-modal');
  if (modal) modal.classList.remove('modal-open');
}

function parseMicrobitBulkSerials() {
  const raw = document.getElementById('mb-bulk-serials')?.value || '';
  const lines = raw.split(/[\r\n,;\s]+/);
  return Array.from(new Set(lines.map(s => s.trim().toUpperCase()).filter(Boolean)));
}

function updateMicrobitBulkSummary() {
  const serials = parseMicrobitBulkSerials();
  const copies = parseInt(document.getElementById('mb-bulk-copies')?.value) || 1;
  const total = serials.length * copies;

  const totalEl = document.getElementById('mb-summary-total-stickers');
  const detailEl = document.getElementById('mb-summary-detail');
  if (totalEl) totalEl.textContent = total;
  if (detailEl) detailEl.textContent = `(${serials.length} unit × ${copies} stiker)`;
}

function fillMicrobitBulkWithChecked() {
  const checked = getSelectedUrSerials();
  if (checked.length === 0) {
    showToast('Tidak ada unit yang sedang dicentang di tabel', true);
    return;
  }
  const textarea = document.getElementById('mb-bulk-serials');
  if (textarea) textarea.value = checked.join('\n');
  updateMicrobitBulkSummary();
  showToast(`${checked.length} unit dari seleksi dimasukkan`);
}

function fillMicrobitBulkWithTableMlk() {
  const rows = document.querySelectorAll('#ur-table-body tr');
  const list = [];
  rows.forEach(tr => {
    const serialTd = tr.querySelector('td.font-mono');
    if (serialTd && serialTd.textContent.startsWith('MLK-')) {
      list.push(serialTd.textContent.trim());
    }
  });
  if (list.length === 0) {
    showToast('Tidak ada serial unit MLK di tabel saat ini', true);
    return;
  }
  const textarea = document.getElementById('mb-bulk-serials');
  if (textarea) textarea.value = list.join('\n');
  updateMicrobitBulkSummary();
  showToast(`${list.length} unit MLK dari tabel dimasukkan`);
}

function clearMicrobitBulkText() {
  const textarea = document.getElementById('mb-bulk-serials');
  if (textarea) textarea.value = '';
  updateMicrobitBulkSummary();
}

function submitMicrobitBulkPrint() {
  const serials = parseMicrobitBulkSerials();
  if (serials.length === 0) {
    showToast('Masukkan minimal 1 Serial Number unit!', true);
    return;
  }
  const copies = parseInt(document.getElementById('mb-bulk-copies')?.value) || 1;
  closeMicrobitBulkModal();
  printMicrobitGridLabels(serials, copies);
}

function promptPrintMicrobitGrid(serial) {
  openMicrobitBulkModal([serial]);
}

function promptBatchMicrobitGrid() {
  openMicrobitBulkModal();
}

function printMicrobitGridLabels(serials, copiesPerSerial = 1) {
  if (!serials || serials.length === 0) return;
  const printArea = document.getElementById('print-area');
  if (!printArea) return;

  printArea.innerHTML = '';
  printArea.className = 'micro-grid-mode';

  showToast(`Menyiapkan lembar grid stiker 1x1 cm (${serials.length * copiesPerSerial} label)...`);

  const itemsToRender = [];
  serials.forEach(serial => {
    for (let c = 0; c < copiesPerSerial; c++) {
      itemsToRender.push(serial);
    }
  });

  itemsToRender.forEach((serial, idx) => {
    const uid = 'mqr_' + idx + '_' + Math.random().toString(36).substr(2, 6);
    const cell = document.createElement('div');
    cell.className = 'micro-sticker-cell';
    cell.innerHTML = `
      <div id="m_qr_${uid}" class="micro-sticker-qr"></div>
      <div class="micro-sticker-text">${escHtml(serial)}</div>
    `;
    printArea.appendChild(cell);

    setTimeout(() => {
      try {
        const qrContainer = document.getElementById(`m_qr_${uid}`);
        if (qrContainer && typeof QRCode !== 'undefined') {
          new QRCode(qrContainer, {
            text: serial,
            width: 32,
            height: 32,
            correctLevel: QRCode.CorrectLevel.L
          });
        }
      } catch (e) {
        console.warn('Micro QR error:', e);
      }
    }, 20);
  });

  // Delay adaptif berdasarkan jumlah stiker yang di-render (min 400ms, max 3000ms)
  const renderWait = Math.max(400, Math.min(itemsToRender.length * 25, 3000));

  window.addEventListener('afterprint', cleanPrintArea, { once: true });
  setTimeout(() => {
    window.print();
    setTimeout(cleanPrintArea, 1500);
    setTimeout(cleanPrintArea, 4000);
  }, renderWait);
}

// 7. BUNDLING & PACKAGING — Robotic Explorer (1 MLK + 1 JIMU)
let availableBundleUnits = { mlk: [], jimu: [] };

async function loadBundlingTab() {
  const mlkSelect = document.getElementById('bdl-mlk-select');
  const jimuSelect = document.getElementById('bdl-jimu-select');
  const mlkCount = document.getElementById('bdl-mlk-count');
  const jimuCount = document.getElementById('bdl-jimu-count');

  if (!mlkSelect || !jimuSelect) return;

  try {
    const r = await api('/api/mikms/bundle/available-units');
    const d = await r.json();
    if (d.status === 'success' && d.data) {
      availableBundleUnits = d.data;

      const mlkList = d.data.mlk || [];
      if (mlkCount) mlkCount.textContent = `(${mlkList.length} ready)`;
      mlkSelect.innerHTML = '<option value="">— Pilih Unit MLK Ready —</option>' +
        mlkList.map(u => `<option value="${escHtml(u.serial_number)}">${escHtml(u.serial_number)}</option>`).join('');

      const jimuList = d.data.jimu || [];
      if (jimuCount) jimuCount.textContent = `(${jimuList.length} ready)`;
      jimuSelect.innerHTML = '<option value="">— Pilih Unit JIMU Ready —</option>' +
        jimuList.map(u => `<option value="${escHtml(u.serial_number)}">${escHtml(u.serial_number)}</option>`).join('');
    }
  } catch (err) {
    console.error('Failed to load available bundle units:', err);
    showToast('Gagal memuat unit ready', true);
  }
}

function syncBdlInputs() {
  const mlkSel = document.getElementById('bdl-mlk-select')?.value;
  const jimuSel = document.getElementById('bdl-jimu-select')?.value;
  if (mlkSel) {
    const mlkManual = document.getElementById('bdl-mlk-manual');
    if (mlkManual) mlkManual.value = mlkSel;
  }
  if (jimuSel) {
    const jimuManual = document.getElementById('bdl-jimu-manual');
    if (jimuManual) jimuManual.value = jimuSel;
  }
}

async function submitBundling() {
  const mlkSerial = (document.getElementById('bdl-mlk-manual')?.value || document.getElementById('bdl-mlk-select')?.value || '').trim().toUpperCase();
  const jimuSerial = (document.getElementById('bdl-jimu-manual')?.value || document.getElementById('bdl-jimu-select')?.value || '').trim().toUpperCase();
  const packagedBy = document.getElementById('bdl-packed-by')?.value?.trim();
  const notes = document.getElementById('bdl-notes')?.value?.trim();

  if (!mlkSerial || !jimuSerial || !packagedBy) {
    showToast('Mohon pilih/isi Unit MLK, Unit Jimu, dan Nama Pengemas (*)', true);
    return;
  }

  try {
    const r = await api('/api/mikms/bundle-unit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        mlk_serial: mlkSerial,
        jimu_serial: jimuSerial,
        packaged_by: packagedBy,
        notes: notes
      })
    });
    const d = await r.json();
    if (d.status === 'success' || d.success) {
      showToast(d.message || 'Boks Robotic Explorer berhasil dikemas!');

      const resultBox = document.getElementById('bdl-result');
      const resultDetails = document.getElementById('bdl-result-details');
      const printBtn = document.getElementById('bdl-print-btn');
      const qcBtn = document.getElementById('bdl-qc-btn');

      if (resultBox && resultDetails) {
        resultDetails.innerHTML = `
          <div>Serial Boks Luar: <strong class="font-bold text-primary text-sm">${escHtml(d.data.serial_number)}</strong></div>
          <div class="mt-1">Isi Packaging: MLK: <strong>${escHtml(d.data.mlk_serial)}</strong> + JIMU: <strong>${escHtml(d.data.jimu_serial)}</strong></div>
          <div class="mt-1 text-warning font-semibold">⚠️ Boks Luar berstatus "qc_pending". Silakan lakukan inspeksi QC akhir boks luar.</div>
        `;
        resultBox.classList.remove('hidden');

        if (printBtn) {
          printBtn.onclick = () => printUnitThermalLabel(d.data.serial_number);
        }
        if (qcBtn) {
          qcBtn.onclick = () => goToQcUnit(d.data.serial_number);
        }
      }

      if (document.getElementById('bdl-mlk-manual')) document.getElementById('bdl-mlk-manual').value = '';
      if (document.getElementById('bdl-jimu-manual')) document.getElementById('bdl-jimu-manual').value = '';
      if (document.getElementById('bdl-notes')) document.getElementById('bdl-notes').value = '';
      loadBundlingTab();
      loadMikmsProducts();
    } else {
      showToast(d.message || 'Gagal mengemas boks Robotic Explorer', true);
    }
  } catch (err) {
    console.error('Error submitting bundling:', err);
    showToast('Koneksi gagal', true);
  }
}
</script>
</body>
</html>
