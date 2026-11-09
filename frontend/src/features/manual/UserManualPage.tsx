import { useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import {
  BookOpen,
  Boxes,
  CheckCircle2,
  ChevronRight,
  ClipboardCheck,
  Cpu,
  Factory,
  FileText,
  HelpCircle,
  Layers,
  PackageSearch,
  ScanBarcode,
  School,
  Shield,
  ShieldCheck,
  ShoppingCart,
  Sparkles,
  Truck,
  Users,
} from 'lucide-react';
import { Button } from '@/components/Button';
import { PageHeader } from '@/components/PageHeader';
import { StatCard } from '@/components/StatCard';
import { useAuth } from '@/features/auth/AuthContext';

type RoleKey = 'super_admin' | 'warehouse_pic' | 'production_lead' | 'staff';

interface RoleManualConfig {
  key: RoleKey;
  title: string;
  badge: string;
  badgeColor: string;
  badgeBg: string;
  description: string;
  responsibilities: string[];
  workflowSteps: {
    step: number;
    title: string;
    description: string;
    menuLink?: string;
    menuLabel?: string;
    icon: any;
  }[];
  keyFeatures: {
    title: string;
    points: string[];
  }[];
  tipsAndFaq: {
    question: string;
    answer: string;
  }[];
}

const ROLE_MANUALS: Record<RoleKey, RoleManualConfig> = {
  super_admin: {
    key: 'super_admin',
    title: 'Super Admin (Administrator Sistem)',
    badge: 'Super Admin',
    badgeColor: '#1e40af',
    badgeBg: '#dbeafe',
    description:
      'Super Admin memiliki otoritas tertinggi dan akses menyeluruh ke seluruh modul ERLASS PWMS, mencakup tata kelola pengguna, audit log, katalog produk, BOM, rencana produksi, inventaris gudang, persewaan MLK, hingga distribusi.',
    responsibilities: [
      'Mengelola akun pengguna, penetapan peran (role), dan pemeliharaan hak akses keamanan.',
      'Mengatur template surat resmi (Surat Tugas, Surat Jalan, Tanda Terima, Surat Bebas Masalah).',
      'Mengelola data customer, pemetaan Group Leader (GL) Marketing dan Sales representatif.',
      'Memantau kepatuhan operasional melalui rekam jejak Audit Log yang tidak dapat dimanipulasi.',
      'Melakukan supervisi menyeluruh atas sirkulasi inventaris, biaya material, dan laporan performa.',
    ],
    workflowSteps: [
      {
        step: 1,
        title: 'Manajemen Pengguna & Hak Akses Role',
        description:
          'Buka menu Administrasi > Pengguna untuk menambah staff baru, menonaktifkan akun yang tidak aktif, atau memperbarui role pengguna. Pastikan prinsip pemisahan wewenang (separation of duties) selalu dijaga.',
        menuLink: '/administration/users',
        menuLabel: 'Buka Menu Pengguna',
        icon: Users,
      },
      {
        step: 2,
        title: 'Pengaturan Customer & Penugasan Sales',
        description:
          'Buka menu Administrasi > Manajemen Customer untuk mendaftarkan instansi sekolah baru beserta kode sekolah dan nama sales penanggung jawab. Data ini menjadi rujukan kontrak sewa MLK dan peminjaman sales.',
        menuLink: '/administration/customers',
        menuLabel: 'Buka Manajemen Customer',
        icon: School,
      },
      {
        step: 3,
        title: 'Tata Kelola Template Surat Resmi',
        description:
          'Kelola format template dokumen operasional pada menu Administrasi > Template Surat. Template dapat ditambahkan variabel dinamis agar surat jalan dan berita acara seragam.',
        menuLink: '/administration/templates',
        menuLabel: 'Buka Template Surat',
        icon: FileText,
      },
      {
        step: 4,
        title: 'Monitoring Audit Log & Aktivitas Sistem',
        description:
          'Pantau setiap perubahan krusial, mulai dari pembuatan order sewa, penyesuaian stok, hingga pembaruan profil pengguna melalui menu Administrasi > Audit Log.',
        menuLink: '/administration/audit-logs',
        menuLabel: 'Buka Audit Log',
        icon: ShieldCheck,
      },
    ],
    keyFeatures: [
      {
        title: 'Keamanan & Kredensial Mandiri',
        points: [
          'Pengguna dapat memperbarui email dan password sendiri dari dropdown profil pojok kanan atas.',
          'Pembaruan password mewajibkan verifikasi password lama untuk mencegah pembajakan akun.',
          'Setiap sesi token Sanctum terisolasi dan langsung dicabut saat logout.',
        ],
      },
      {
        title: 'Sirkulasi Aset Netral Multi-Sekolah',
        points: [
          'Stiker barcode unit MLK (format: 1001_01) bersifat netral aset tanpa nama sekolah permanen di label.',
          'Satu unit fisik dapat disewakan berulang kali ke berbagai sekolah berbeda sepanjang masa pakai.',
          'Sistem menyimpan riwayat historis lengkap dari setiap sekolah yang pernah memegang unit.',
        ],
      },
    ],
    tipsAndFaq: [
      {
        question: 'Bagaimana jika ada pengguna yang lupa password atau ingin mengubah email?',
        answer:
          'Pengguna yang sedang login dapat mengklik inisial nama di pojok kanan atas (dropdown profile) lalu memilih "Ubah Email & Password". Jika pengguna terkunci/lupa sama sekali, Super Admin dapat mengedit password akun melalui menu Administrasi > Pengguna.',
      },
      {
        question: 'Apakah Super Admin bisa membatalkan order sewa yang keliru?',
        answer:
          'Super Admin dapat mengubah status sewa menjadi CLOSED atau memperbarui penugasan sekolah unit secara langsung melalui menu Pelacakan Unit Fisik MLK atau modal pemindai barcode.',
      },
    ],
  },

  warehouse_pic: {
    key: 'warehouse_pic',
    title: 'PIC Gudang (Penanggung Jawab Gudang & Logistik)',
    badge: 'PIC Gudang',
    badgeColor: '#15803d',
    badgeBg: '#dcfce7',
    description:
      'PIC Gudang bertanggung jawab atas akurasi fisik inventaris, penerimaan pengadaan komponen (PO/GRN), penyiapan bahan baku untuk rencana produksi, pencetakan barcode stiker aset, penerbitan Surat DO pengiriman, serta verifikasi fisik barang kembali.',
    responsibilities: [
      'Menerima pengadaan barang dari supplier dan memperbarui status Purchase Order.',
      'Mengelola lokasi penyimpanan komponen, modul, box, dan finished good.',
      'Mencetak stiker barcode Finished Good dan barcode serial unit MLK sewa sekolah.',
      'Menerbitkan Surat Delivery Order (DO) otomatis ke modul Distribusi untuk pengiriman ke sekolah.',
      'Mencetak kartu riwayat keluar masuk barang berdasarkan filter rentang tanggal tertentu.',
      'Melakukan inspeksi kelengkapan barang saat pengembalian sewa sekolah selesai.',
    ],
    workflowSteps: [
      {
        step: 1,
        title: 'Penerimaan Pengadaan Barang (PO & GRN)',
        description:
          'Buka menu Pengadaan untuk memeriksa status pesanan pembelian. Masukkan nama supplier, nomor WhatsApp, serta perbarui status pembelian (Dipesan, Dikirim, Selesai Diterima) saat barang fisik sampai di gudang.',
        menuLink: '/procurement',
        menuLabel: 'Buka Menu Pengadaan',
        icon: ShoppingCart,
      },
      {
        step: 2,
        title: 'Pemantauan Inventaris & Cetak Kartu Stok Periode',
        description:
          'Buka menu Inventaris untuk memantau stok fisik. Klik tombol "Cetak Kartu Keluar Masuk", pilih rentang tanggal awal dan akhir untuk mengunduh laporan mutasi barang.',
        menuLink: '/inventory',
        menuLabel: 'Buka Inventaris Gudang',
        icon: Boxes,
      },
      {
        step: 3,
        title: 'Pencetakan Barcode & Penyiapan Sewa MLK',
        description:
          'Saat order sewa sekolah dibuat, klik tombol "Cetak Barcode" untuk mencetak stiker barcode berurutan (misal 1001_01 s/d 1001_50). Label ini netral aset tanpa nama sekolah sehingga stiker awet dan dapat dipakai berulang kali.',
        menuLink: '/rentals/schools',
        menuLabel: 'Buka Sewa MLK Sekolah',
        icon: PackageSearch,
      },
      {
        step: 4,
        title: 'Penerbitan Surat DO & Pengiriman Distribusi',
        description:
          'Pada order sewa yang siap kirim, klik "Cetak Surat DO". Sistem otomatis menambahkan catatan pengiriman ke modul Distribusi dengan status SIAP DIKIRIM hingga SELESAI.',
        menuLink: '/distribution',
        menuLabel: 'Buka Menu Distribusi',
        icon: Truck,
      },
      {
        step: 5,
        title: 'Inspeksi Pengembalian Sewa & Klaim Kerugian',
        description:
          'Saat masa sewa selesai, klik "Inspeksi Kelengkapan QC". Masukkan kuantitas realisasi komponen, modul, dan box. Jika ada komponen hilang, sistem otomatis menghitung total kerugian yang dibebankan ke sekolah.',
        menuLink: '/quality-control',
        menuLabel: 'Buka Quality Control',
        icon: ClipboardCheck,
      },
    ],
    keyFeatures: [
      {
        title: 'Aturan Stok Barcode Fisik',
        points: [
          'Pencetakan batch barcode baru pada produk Finished Good fisik akan mengurangi stok fisik gudang.',
          'Pencetakan ulang (reprint) barcode tidak akan mengurangi stok yang sudah ada.',
          'Produk non-fisik (jasa/lisensi) tidak dapat mencetak barcode.',
        ],
      },
      {
        title: 'Klaim Ganti Rugi Otomatis',
        points: [
          'Setiap komponen dapat diatur apakah wajib ganti rugi atau tidak saat hilang.',
          'Kompensasi dihitung dari selisih qty realisasi terhadap qty standar dikalikan harga acuan.',
        ],
      },
    ],
    tipsAndFaq: [
      {
        question: 'Bagaimana cara mendeteksi unit MLK saat kembali ke gudang menggunakan HP?',
        answer:
          'Buka ERLASS PWMS di browser HP, klik tombol "Scan Barcode" di header atau dashboard, arahkan kamera ke stiker barcode unit (misal 1001_01). Sistem akan langsung menampilkan detail sekolah pemegang dan tombol untuk mengubah status menjadi "Selesai / Tersedia di Gudang".',
      },
      {
        question: 'Kapan nomor unit barcode MLK bertambah lanjut?',
        answer:
          'Saat sekolah pertama menyewa 50 unit, nomor unit otomatis 1001_01 - 1001_50. Sekolah berikutnya yang menyewa 10 unit akan melanjutkan dari 1001_51 - 1001_60, kecuali jika Anda mengisi nomor unit awal tertentu untuk menggunakan unit yang sudah kembali ke gudang.',
      },
    ],
  },

  production_lead: {
    key: 'production_lead',
    title: 'Kepala Produksi (Penanggung Jawab Manufaktur)',
    badge: 'Kepala Produksi',
    badgeColor: '#c2410c',
    badgeBg: '#ffedd5',
    description:
      'Kepala Produksi memegang kendali atas struktur Resep BOM bertingkat (Modul, Box, Finished Good), penyusunan Rencana Produksi, kalkulasi Material Requirements Planning (MRP Kebutuhan Kotor), serta pengelolaan tahapan produksi dinamis hingga 100%.',
    responsibilities: [
      'Menyusun dan mengelola Resep BOM bertahap: Produksi Modul -> Produksi Box -> Produksi Finished Good.',
      'Menyusun Rencana Produksi berdasarkan target kuantitas unit produk yang akan dihasilkan.',
      'Menghitung kebutuhan kotor material (MRP) untuk memastikan ketersediaan bahan baku di gudang.',
      'Membagi pengerjaan produksi menjadi beberapa tahapan dinamis (misal: Perakitan, Wiring, Pengujian) hingga mencapai 100%.',
      'Mengunci tahapan yang sudah selesai agar tidak dapat dihapus kembali.',
      'Memastikan hasil produksi siap diserahterimakan ke tim gudang untuk pemenuhan sewa sekolah.',
    ],
    workflowSteps: [
      {
        step: 1,
        title: 'Penyusunan Resep BOM Bertahap',
        description:
          'Buka menu BOM. Kelola resep produksi secara bertahap: buat resep Modul (dari komponen), lalu Box (dari modul & komponen pelengkap), hingga Finished Good MLK.',
        menuLink: '/bom',
        menuLabel: 'Buka Manajemen BOM',
        icon: Layers,
      },
      {
        step: 2,
        title: 'Pembuatan Rencana Produksi Baru',
        description:
          'Buka menu Rencana Produksi. Klik "Buat Rencana Produksi", tentukan kode rencana, tanggal jatuh tempo, produk target, dan kuantitas unit yang ingin diproduksi.',
        menuLink: '/production-planning',
        menuLabel: 'Buka Rencana Produksi',
        icon: Factory,
      },
      {
        step: 3,
        title: 'Kalkulasi MRP Kebutuhan Kotor Material',
        description:
          'Klik tombol "Hitung MRP Kebutuhan Kotor" pada rencana produksi untuk melihat rincian persis berapa banyak komponen, modul, dan box yang diperlukan sebelum pengerjaan dimulai.',
        menuLink: '/production-planning',
        menuLabel: 'Lihat Rencana & Hitung MRP',
        icon: Cpu,
      },
      {
        step: 4,
        title: 'Pengelolaan Tahapan Pengerjaan Dinamis',
        description:
          'Klik "Kelola Tahapan" pada baris rencana produksi. Tambah, edit bobot persentase progress tahapan (misal: Tahap 1 Perakitan 40%, Tahap 2 Wiring 30%, Tahap 3 QC Akhir 30%). Tahapan yang telah berstatus SELESAI otomatis dilindungi dan tidak dapat dihapus.',
        menuLink: '/production-planning',
        menuLabel: 'Kelola Tahapan Produksi',
        icon: CheckCircle2,
      },
    ],
    keyFeatures: [
      {
        title: 'Perhitungan MRP Kebutuhan Kotor',
        points: [
          'Cukup menghitung kebutuhan kotor (Gross Requirement) tanpa kompleksitas on-hand stok berlebih.',
          'Tabel rencana produksi menyajikan judul & kode rencana, target kuantitas, progress tahapan, dan jatuh tempo.',
        ],
      },
      {
        title: 'Integrasi dengan Kontrak Sewa Sekolah',
        points: [
          'Rencana produksi yang telah berstatus SELESAI akan otomatis muncul sebagai sumber unit pada menu Sewa MLK Sekolah.',
        ],
      },
    ],
    tipsAndFaq: [
      {
        question: 'Apakah resep BOM bisa diedit kapan saja?',
        answer:
          'Ya, resep BOM dapat ditambah, diedit, atau dihapus secara fleksibel melalui tombol aksi di tabel BOM.',
      },
      {
        question: 'Bagaimana jika total persentase tahapan produksi belum 100%?',
        answer:
          'Sistem akan menampilkan indikator progress saat ini. Anda dapat terus menambahkan tahapan pengerjaan secara dinamis hingga total akumulasi mencapai 100% dan rencana dinyatakan rampung.',
      },
    ],
  },

  staff: {
    key: 'staff',
    title: 'Staff Operasional (Pelaksana Lapangan & Sales)',
    badge: 'Staff Operasional',
    badgeColor: '#0369a1',
    badgeBg: '#e0f2fe',
    description:
      'Staff Operasional bertugas menangani operasional harian lapangan, mencakup peminjaman produk untuk projek sales, pelacakan unit sewa di sekolah mitra, pemindaian barcode unit menggunakan kamera smartphone/HP, dan pencatatan tanda terima pengembalian.',
    responsibilities: [
      'Mencatat peminjaman projek untuk sales dengan relasi GL Marketing, Sales, Sekolah, dan produk.',
      'Melakukan pengembalian barang pinjaman sales dan mencetak soft file PDF Bukti Tanda Terima.',
      'Memindai barcode unit MLK di lapangan menggunakan kamera smartphone untuk memeriksa status unit.',
      'Memeriksa riwayat kelengkapan dan sekolah pemegang unit saat ini.',
      'Memperbarui status unit (masih disewakan atau selesai kembali ke gudang) serta mengubah data sekolah.',
    ],
    workflowSteps: [
      {
        step: 1,
        title: 'Pencatatan Peminjaman Projek Sales',
        description:
          'Buka menu Peminjaman Sales. Klik "Buat Peminjaman Sales", pilih nama GL Marketing -> nama Sales yang bersangkutan -> nama Sekolah tujuan -> produk dan kuantitas unit.',
        menuLink: '/rentals/sales-loans',
        menuLabel: 'Buka Peminjaman Sales',
        icon: PackageSearch,
      },
      {
        step: 2,
        title: 'Pengembalian & Cetak Bukti Tanda Terima PDF',
        description:
          'Saat sales mengembalikan produk, klik tombol "Kembalikan" untuk mencatat tanggal kembali resmi. Setelah berstatus RETURNED, tombol "Cetak Tanda Terima" akan aktif untuk mengunduh dokumen bukti tanda terima resmi ERLASS Prokreatif Indonesia.',
        menuLink: '/rentals/sales-loans',
        menuLabel: 'Lihat Pinjaman & Cetak Bukti',
        icon: FileText,
      },
      {
        step: 3,
        title: 'Pemindaian Barcode Unit via Kamera Smartphone',
        description:
          'Klik tombol "Scan Barcode" di dashboard atau halaman sewa sekolah, izinkan akses kamera HP, lalu arahkan kamera ke stiker barcode unit MLK (1001_01). Data sekolah pemegang saat ini dan riwayat QC langsung muncul di layar HP.',
        menuLink: '/rentals/schools',
        menuLabel: 'Buka Scanner Barcode',
        icon: ScanBarcode,
      },
      {
        step: 4,
        title: 'Pengubahan Status & Data Sekolah Langsung dari HP',
        description:
          'Di dalam jendela modal scan kamera, Anda dapat langsung mengganti status unit menjadi "Selesai / Tersedia di Gudang" atau mengalihkan penugasan ke sekolah lain dengan menekan tombol "Simpan Pembaruan Unit".',
        menuLink: '/rentals/schools',
        menuLabel: 'Kelola Pelacakan Unit',
        icon: CheckCircle2,
      },
    ],
    keyFeatures: [
      {
        title: 'Akses Cepat di Smartphone / Mobile',
        points: [
          'Seluruh tampilan PWMS dioptimalkan agar nyaman digunakan di layar smartphone (HP).',
          'Pemindai barcode kamera bekerja responsif dengan kamera belakang dan lampu fokus otomatis browser.',
        ],
      },
      {
        title: 'Relasi Hierarki Bertingkat',
        points: [
          'Daftar Sales yang tampil disaring otomatis berdasarkan GL Marketing yang dipilih.',
          'Daftar Sekolah disaring otomatis berdasarkan Sales penanggung jawab dari master data customer.',
        ],
      },
    ],
    tipsAndFaq: [
      {
        question: 'Bagaimana jika kamera HP tidak bisa mendeteksi barcode?',
        answer:
          'Pastikan pencahayaan cukup dan barcode berada di dalam kotak bidik merah. Jika kamera fisik bermasalah, Anda dapat mengetik nomor barcode (misal: 1001_01) pada kolom input yang tersedia lalu klik "Deteksi".',
      },
      {
        question: 'Bagaimana cara mengganti password akun staff saya sendiri?',
        answer:
          'Klik nama/inisial Anda di pojok kanan atas layar, pilih "Ubah Email & Password", ketik password lama dan password baru minimal 8 karakter, lalu klik "Simpan Perubahan".',
      },
    ],
  },
};

export function UserManualPage() {
  const { user } = useAuth();

  // Tentukan role default berdasarkan user yang sedang login
  const defaultRoleKey: RoleKey = useMemo(() => {
    if (!user || !user.roles.length) return 'staff';
    const codes = user.roles.map((r) => r.code);
    if (codes.includes('super_admin')) return 'super_admin';
    if (codes.includes('warehouse_pic')) return 'warehouse_pic';
    if (codes.includes('production_lead')) return 'production_lead';
    return 'staff';
  }, [user]);

  const [activeRoleKey, setActiveRoleKey] = useState<RoleKey>(defaultRoleKey);
  const activeManual = ROLE_MANUALS[activeRoleKey] || ROLE_MANUALS.staff;

  const userPrimaryRoleName = user?.roles?.[0]?.name || 'Staff';

  return (
    <>
      <PageHeader
        eyebrow="Administrasi — Panduan & SOP"
        title="Manual Pengguna ERLASS PWMS"
        description="Panduan operasional standar dan tata cara alur kerja sistem yang disesuaikan secara dinamis berdasarkan peran (role) pengguna yang sedang login."
        actions={
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <span
              className="chip"
              style={{
                backgroundColor: '#dbeafe',
                color: '#1e40af',
                fontWeight: 700,
                fontSize: '0.8rem',
                padding: '6px 12px',
              }}
            >
              <Shield size={14} style={{ display: 'inline', marginRight: 4 }} />
              Login Sebagai: {userPrimaryRoleName}
            </span>
          </div>
        }
      />

      {/* Banner Peran Pengguna Aktif */}
      <section
        className="card mb-6"
        style={{
          background: 'linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%)',
          color: '#ffffff',
          borderRadius: 'var(--radius-lg, 16px)',
          padding: '24px 28px',
          boxShadow: '0 4px 14px rgba(30, 58, 138, 0.16)',
        }}
      >
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 20 }}>
          <div style={{ maxWidth: 740, flex: 1 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 10 }}>
              <span
                style={{
                  backgroundColor: 'rgba(255, 255, 255, 0.2)',
                  color: '#ffffff',
                  padding: '4px 10px',
                  borderRadius: 20,
                  fontSize: '0.725rem',
                  fontWeight: 700,
                  letterSpacing: '0.05em',
                  textTransform: 'uppercase',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: 4,
                }}
              >
                <Sparkles size={12} />
                Panduan Berdasarkan Role
              </span>
              <span style={{ fontSize: '0.8rem', opacity: 0.9 }}>
                ERLASS Prokreatif Indonesia
              </span>
            </div>
            <h2 style={{ fontSize: '1.45rem', fontWeight: 800, margin: '0 0 8px 0', color: '#ffffff', lineHeight: 1.3 }}>
              {activeManual.title}
            </h2>
            <p style={{ margin: 0, fontSize: '0.9rem', lineHeight: 1.6, opacity: 0.95 }}>
              {activeManual.description}
            </p>
          </div>

          <div
            style={{
              backgroundColor: 'rgba(255, 255, 255, 0.14)',
              backdropFilter: 'blur(8px)',
              padding: '16px 20px',
              borderRadius: 12,
              border: '1px solid rgba(255, 255, 255, 0.2)',
              minWidth: 230,
            }}
          >
            <div style={{ fontSize: '0.7rem', textTransform: 'uppercase', opacity: 0.85, fontWeight: 700, letterSpacing: '0.04em' }}>
              Pengguna Aktif:
            </div>
            <div style={{ fontSize: '1.05rem', fontWeight: 800, marginTop: 4 }}>
              {user?.name}
            </div>
            <div style={{ fontSize: '0.8rem', opacity: 0.85, marginTop: 2, fontFamily: 'monospace' }}>
              {user?.email}
            </div>
          </div>
        </div>
      </section>

      {/* Navigasi Pilihan Tab Role (Memungkinkan melihat SOP role lainnya) */}
      <div style={{ display: 'flex', gap: 10, marginBottom: 'var(--space-6, 24px)', overflowX: 'auto', paddingBottom: 4 }}>
        {(Object.keys(ROLE_MANUALS) as RoleKey[]).map((key) => {
          const m = ROLE_MANUALS[key];
          const isActive = activeRoleKey === key;
          const isUserRole = user?.roles.some((r) => r.code === key);

          return (
            <button
              key={key}
              onClick={() => setActiveRoleKey(key)}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 8,
                padding: '9px 16px',
                borderRadius: 'var(--radius-sm, 8px)',
                fontWeight: 600,
                fontSize: '0.85rem',
                border: isActive ? '2px solid var(--color-primary, #3859ac)' : '1px solid var(--color-border, #e2e8f0)',
                backgroundColor: isActive ? 'var(--color-primary-dark, #263f7d)' : '#ffffff',
                color: isActive ? '#ffffff' : 'var(--color-text-main, #0f172a)',
                cursor: 'pointer',
                transition: 'all 0.15s ease-in-out',
                whiteSpace: 'nowrap',
                boxShadow: isActive ? '0 2px 6px rgba(38, 63, 125, 0.2)' : 'none',
              }}
            >
              <BookOpen size={16} />
              <span>{m.badge}</span>
              {isUserRole && (
                <span
                  style={{
                    fontSize: '0.65rem',
                    backgroundColor: isActive ? 'rgba(255,255,255,0.25)' : '#dcfce7',
                    color: isActive ? '#ffffff' : '#15803d',
                    padding: '2px 6px',
                    borderRadius: 10,
                    fontWeight: 700,
                  }}
                >
                  Role Anda
                </span>
              )}
            </button>
          );
        })}
      </div>

      {/* Stat Card Ringkasan Panduan (Row di desktop, Col di mobile) */}
      <div className="summary-cards-row">
        <StatCard
          label="Peran Aktif Panduan"
          value={activeManual.badge}
          hint="Role panduan yang sedang dibuka"
          icon={<ShieldCheck size={18} />}
        />
        <StatCard
          label="Langkah Alur Kerja"
          value={activeManual.workflowSteps.length}
          hint="Tahapan SOP operasional standar"
          icon={<CheckCircle2 size={18} />}
          accent
        />
        <StatCard
          label="Fitur Kunci & Aturan"
          value={activeManual.keyFeatures.length}
          hint="Panduan teknis & aturan sistem"
          icon={<BookOpen size={18} />}
        />
      </div>

      {/* SEKSI 1: TANGGUNG JAWAB UTAMA */}
      <section className="card mb-6">
        <div className="card__header">
          <div>
            <h3 className="card__title" style={{ fontSize: '1.05rem', fontWeight: 700 }}>
              Tanggung Jawab & Wewenang Utama ({activeManual.badge})
            </h3>
            <p style={{ margin: '3px 0 0 0', fontSize: '0.8rem', color: 'var(--color-text-secondary, #64748b)' }}>
              Cakupan tugas inti dan wewenang yang diamanatkan pada peran operasional ini.
            </p>
          </div>
        </div>
        <div className="card__body" style={{ padding: '20px 24px' }}>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 14 }}>
            {activeManual.responsibilities.map((resp, idx) => (
              <div
                key={idx}
                style={{
                  display: 'flex',
                  alignItems: 'flex-start',
                  gap: 12,
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: 'var(--radius-sm, 8px)',
                  padding: '14px 16px',
                }}
              >
                <div
                  style={{
                    width: 26,
                    height: 26,
                    borderRadius: '50%',
                    backgroundColor: 'var(--color-primary-light, #e0e7ff)',
                    color: 'var(--color-primary, #3859ac)',
                    display: 'grid',
                    placeItems: 'center',
                    fontWeight: 700,
                    fontSize: '0.75rem',
                    flexShrink: 0,
                    marginTop: 1,
                  }}
                >
                  {idx + 1}
                </div>
                <div style={{ fontSize: '0.85rem', color: '#1e293b', lineHeight: 1.55 }}>
                  {resp}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* SEKSI 2: ALUR KERJA STEP-BY-STEP */}
      <section className="card mb-6">
        <div className="card__header">
          <div>
            <h3 className="card__title" style={{ fontSize: '1.05rem', fontWeight: 700 }}>
              Alur Kerja Operasional Langkah Demi Langkah (Step-by-Step SOP)
            </h3>
            <p style={{ margin: '3px 0 0 0', fontSize: '0.8rem', color: 'var(--color-text-secondary, #64748b)' }}>
              Ikuti urutan tahapan berikut secara runtut untuk menyelesaikan tugas dengan benar di dalam sistem.
            </p>
          </div>
        </div>

        <div className="card__body" style={{ padding: '20px 24px' }}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {activeManual.workflowSteps.map((wf) => {
              const Icon = wf.icon;
              return (
                <div
                  key={wf.step}
                  style={{
                    background: '#ffffff',
                    border: '1px solid var(--color-border, #e2e8f0)',
                    borderRadius: 'var(--radius-md, 12px)',
                    padding: '20px',
                    display: 'flex',
                    gap: 16,
                    alignItems: 'flex-start',
                    boxShadow: '0 1px 3px rgba(0, 0, 0, 0.04)',
                  }}
                >
                  <div
                    style={{
                      width: 44,
                      height: 44,
                      borderRadius: 10,
                      backgroundColor: 'var(--color-primary-dark, #263f7d)',
                      color: '#ffffff',
                      display: 'grid',
                      placeItems: 'center',
                      flexShrink: 0,
                    }}
                  >
                    <Icon size={22} />
                  </div>

                  <div style={{ flex: 1 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
                      <span
                        style={{
                          backgroundColor: '#f1f5f9',
                          color: 'var(--color-primary-dark, #263f7d)',
                          fontWeight: 800,
                          fontSize: '0.725rem',
                          padding: '3px 8px',
                          borderRadius: 6,
                          fontFamily: 'monospace',
                        }}
                      >
                        LANGKAH {wf.step}
                      </span>
                      <h4 style={{ margin: 0, fontSize: '0.975rem', fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)' }}>
                        {wf.title}
                      </h4>
                    </div>

                    <p style={{ margin: '4px 0 12px 0', fontSize: '0.85rem', color: '#475569', lineHeight: 1.6 }}>
                      {wf.description}
                    </p>

                    {wf.menuLink && (
                      <Link to={wf.menuLink}>
                        <Button size="sm" variant="secondary" icon={<ChevronRight size={14} />}>
                          {wf.menuLabel || 'Buka Halaman Terkait'}
                        </Button>
                      </Link>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* SEKSI 3: FITUR KUNCI & ATURAN SISTEM */}
      <section className="card mb-6">
        <div className="card__header">
          <div>
            <h3 className="card__title" style={{ fontSize: '1.05rem', fontWeight: 700 }}>
              Fitur Kunci & Aturan Teknis Sistem
            </h3>
            <p style={{ margin: '3px 0 0 0', fontSize: '0.8rem', color: 'var(--color-text-secondary, #64748b)' }}>
              Prinsip integritas data dan aturan bisnis wajib yang berlaku pada modul ini.
            </p>
          </div>
        </div>

        <div className="card__body" style={{ padding: '20px 24px' }}>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: 16 }}>
            {activeManual.keyFeatures.map((kf, idx) => (
              <div
                key={idx}
                style={{
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: 'var(--radius-md, 12px)',
                  padding: '18px 20px',
                }}
              >
                <h4 style={{ margin: '0 0 10px 0', fontSize: '0.925rem', fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)' }}>
                  {kf.title}
                </h4>
                <ul style={{ margin: 0, paddingLeft: 18, fontSize: '0.825rem', color: '#475569', lineHeight: 1.6 }}>
                  {kf.points.map((pt, pIdx) => (
                    <li key={pIdx} style={{ marginBottom: 6 }}>
                      {pt}
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* SEKSI 4: TANYA JAWAB (FAQ) & TIPS LAPANGAN */}
      <section className="card">
        <div className="card__header">
          <div>
            <h3 className="card__title" style={{ fontSize: '1.05rem', fontWeight: 700 }}>
              Tanya Jawab (FAQ) & Tips Penggunaan
            </h3>
            <p style={{ margin: '3px 0 0 0', fontSize: '0.8rem', color: 'var(--color-text-secondary, #64748b)' }}>
              Solusi cepat untuk kendala yang sering dijumpai saat pengoperasian di lapangan.
            </p>
          </div>
        </div>

        <div className="card__body" style={{ padding: '20px 24px' }}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            {activeManual.tipsAndFaq.map((faq, idx) => (
              <div
                key={idx}
                style={{
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: 'var(--radius-md, 12px)',
                  padding: '16px 20px',
                  boxShadow: '0 1px 2px rgba(0, 0, 0, 0.03)',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
                  <HelpCircle size={16} style={{ color: 'var(--color-primary, #3859ac)', flexShrink: 0 }} />
                  <div style={{ fontWeight: 700, fontSize: '0.875rem', color: 'var(--color-primary-dark, #263f7d)' }}>
                    {faq.question}
                  </div>
                </div>
                <div style={{ fontSize: '0.825rem', color: '#475569', lineHeight: 1.6, paddingLeft: 24 }}>
                  {faq.answer}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
